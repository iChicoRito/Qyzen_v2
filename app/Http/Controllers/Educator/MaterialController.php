<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Enrolled;
use App\Models\LearningMaterial;
use App\Models\Section;
use App\Models\Subject;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

// G9: learning materials. Files stored on the 'local' disk; download via a signed temporary
// URL after an access check (ports the source's Supabase 60s signed URLs).
class MaterialController extends Controller
{
    private const DISK = 'local';

    public function __construct(private NotificationService $notifications) {}

    public function index(): View
    {
        $this->authorize('viewAny', LearningMaterial::class);

        $groups = LearningMaterial::visibleTo(Auth::user())
            ->with(['subject:id,subject_code,subject_name'])
            ->orderByDesc('id')->get()
            ->groupBy(fn ($m) => $m->subject_id.'::'.$m->section_id);

        return view('educator.materials.index', compact('groups'));
    }

    public function create(): View
    {
        $this->authorize('create', LearningMaterial::class);

        return view('educator.materials.create', [
            'subjects' => Subject::visibleTo(Auth::user())->orderBy('subject_code')->get(),
            'sections' => Section::visibleTo(Auth::user())->orderBy('section_name')->get(),
        ]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $this->authorize('create', LearningMaterial::class);

        $data = $request->validated();
        $studentIds = $this->enrolledStudentIds($data['subject_id']);

        foreach ($request->file('files') as $file) {
            $path = $file->store("learning-materials/".Auth::id(), self::DISK);
            $material = LearningMaterial::create([
                'educator_id' => Auth::id(),
                'subject_id' => $data['subject_id'],
                'section_id' => $data['section_id'],
                'storage_bucket' => self::DISK,
                'storage_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'is_active' => true,
            ]);

            $this->notifications->emitToMany(Auth::user(), 'learning_material_uploaded', $studentIds, [
                'subject_id' => $material->subject_id, 'section_id' => $material->section_id,
                'title' => 'New learning material',
            ]);
        }

        return redirect()->route('educator.materials.index')->with('status', 'Material(s) uploaded.');
    }

    public function edit(LearningMaterial $material): View
    {
        $this->authorize('update', $material);

        return view('educator.materials.edit', compact('material'));
    }

    public function update(UpdateMaterialRequest $request, LearningMaterial $material): RedirectResponse
    {
        $this->authorize('update', $material);

        $material->update($request->validated()); // metadata only; storage object untouched

        return redirect()->route('educator.materials.index')->with('status', 'Material updated.');
    }

    public function destroy(LearningMaterial $material): RedirectResponse
    {
        $this->authorize('delete', $material);

        Storage::disk($material->storage_bucket ?? self::DISK)->delete($material->storage_path);
        $material->delete();

        return redirect()->route('educator.materials.index')->with('status', 'Material deleted.');
    }

    // Signed temporary URL target — access re-checked here even though the URL is signed.
    public function download(LearningMaterial $material)
    {
        $this->authorize('view', $material);

        $disk = Storage::disk($material->storage_bucket ?? self::DISK);
        abort_unless($disk->exists($material->storage_path), 404);

        return $disk->download($material->storage_path, $material->file_name);
    }

    /** Build a 60s signed URL for a material (used by views/students later). */
    public static function temporaryUrl(LearningMaterial $material): string
    {
        return URL::temporarySignedRoute('educator.materials.download', now()->addSeconds(60), ['material' => $material->id]);
    }

    private function enrolledStudentIds(int $subjectId): array
    {
        return Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->pluck('student_id')->all();
    }
}
