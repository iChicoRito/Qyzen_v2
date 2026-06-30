<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FailedStudentRowsExport;
use App\Exports\StudentImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Imports\StudentsImport;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

// F2/F3/F4: admin user management.
class UserController extends Controller
{
    public function __construct(private UserService $users) {}

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

        $users = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
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
        $user->sendEmailVerificationNotification();

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

        return Excel::download(new StudentImportTemplateExport(), 'student-import-template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new StudentsImport($this->users);
        Excel::import($import, $request->file('file'));

        $created = $import->createdCount();
        $failed = $import->failedRows();

        if (! empty($failed)) {
            session()->flash('status', "Imported {$created} students; ".count($failed).' rows failed.');
            // Hand back the failed rows as a downloadable file to retry.
            session()->flash('failed_import', true);

            return Excel::download(new FailedStudentRowsExport($failed), 'failed-student-rows.xlsx');
        }

        return redirect()->route('admin.users.index')->with('status', "Imported {$created} students.");
    }
}
