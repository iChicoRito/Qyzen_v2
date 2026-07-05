<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StudentImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Jobs\DispatchStudentImport;
use App\Models\Role;
use App\Models\User;
use App\Models\UserImport;
use App\Services\UserOnboardingService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

// F2/F3/F4: admin user management.
class UserController extends Controller
{
    public function __construct(private UserService $users, private UserOnboardingService $onboarding) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('roles:id,name');

        if ($status = $request->query('status')) {
            $query->where('is_active', $status === 'active');
        }
        if ($type = $request->query('user_type')) {
            $query->where('user_type', $type);
        }

        $users = $query->orderByDesc('id')->get();
        $roles = Role::orderBy('name')->get();

        $imports = UserImport::ownedBy($request->user())->latest()->take(6)->get();

        return view('admin.users.index', compact('users', 'roles', 'imports'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $user = $this->users->create($data, $data['role_names']);
        $this->onboarding->send($user);

        return redirect()->route('admin.users.index')->with('status', "User {$user->user_id} created.");
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('roles:id,name');

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles:id,name');

        return view('admin.users.edit', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $this->users->update($user, $data, $data['role_names']);

        return redirect()->route('admin.users.index')->with('status', "User {$user->user_id} updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Source did a hard delete of auth account + profile — match that (model uses SoftDeletes).
        $user->roles()->detach();
        $user->forceDelete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    public function resendVerification(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->email_verified_at === null) {
            $user->sendEmailVerificationNotification();

            return back()->with('status', 'Verification email resent.');
        }

        return back()->with('status', 'User is already verified.');
    }

    // F3: bulk student import.
    public function importTemplate()
    {
        $this->authorize('create', User::class);

        return Excel::download(new StudentImportTemplateExport, 'student-upload-template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate([
            'file' => ['required', 'array', 'min:1'],
            'file.*' => ['required', 'file', 'mimes:xlsx'],
        ]);

        foreach ($request->file('file') as $file) {
            $path = $file->storeAs('imports/uploads', uniqid('students-', true).'.'.$file->getClientOriginalExtension(), 'local');

            $import = UserImport::create([
                'initiated_by_user_id' => $request->user()->id,
                'original_filename' => $file->getClientOriginalName(),
                'upload_path' => $path,
                'status' => 'queued',
            ]);

            DispatchStudentImport::dispatch($import);
        }

        $count = count($request->file('file'));

        return redirect()->route('admin.users.index')
            ->with('status', $count === 1 ? 'Student import queued.' : "{$count} student imports queued.");
    }

    // Polled by the users-index timeline for live import status (no page refresh).
    public function importTimeline(Request $request)
    {
        $this->authorize('create', User::class);

        $imports = UserImport::ownedBy($request->user())->latest()->take(6)->get();

        return view('admin.users._import-timeline', compact('imports'));
    }

    // Detail fragment for the timeline: full status + per-row failure reasons (opened in the shared modal).
    public function showImport(UserImport $userImport): View
    {
        $this->authorize('view', $userImport);

        return view('admin.users.import-show', compact('userImport'));
    }

    public function downloadImportReport(UserImport $userImport)
    {
        $this->authorize('view', $userImport);

        abort_unless($userImport->failed_report_path, 404);
        abort_unless(Storage::disk('local')->exists($userImport->failed_report_path), 404);

        return Storage::disk('local')->download($userImport->failed_report_path, 'student-upload-failed.xlsx');
    }
}
