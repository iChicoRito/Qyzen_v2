<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// H9: student materials — enrollment-gated list + download. visibleTo restricts to active
// materials for subjects the student is actively enrolled in.
class MaterialController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', LearningMaterial::class);

        $groups = LearningMaterial::visibleTo(Auth::user())
            ->with('subject:id,subject_code,subject_name')
            ->orderByDesc('id')->get()
            ->groupBy(fn ($m) => $m->subject_id.'::'.$m->section_id);

        return view('student.materials.index', compact('groups'));
    }

    public function download(LearningMaterial $material)
    {
        $this->authorize('view', $material); // enrollment re-checked

        $disk = Storage::disk($material->storage_bucket ?? 'local');
        abort_unless($disk->exists($material->storage_path), 404);

        return $disk->download($material->storage_path, $material->file_name);
    }
}
