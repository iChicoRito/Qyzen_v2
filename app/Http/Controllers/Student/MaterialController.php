<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrolled;
use App\Models\LearningMaterial;
use App\Models\Section;
use App\Models\Subject;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

// H9: student materials — enrollment-gated list + download. visibleTo restricts to active
// materials for subjects the student is actively enrolled in.
class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', LearningMaterial::class);

        $query = LearningMaterial::query()
            ->where('tbl_learning_materials.is_active', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('tbl_enrolled')
                ->whereColumn('tbl_enrolled.educator_id', 'tbl_learning_materials.educator_id')
                ->whereColumn('tbl_enrolled.subject_id', 'tbl_learning_materials.subject_id')
                ->where('tbl_enrolled.student_id', Auth::id())
                ->where('tbl_enrolled.is_active', true))
            ->select('tbl_learning_materials.*')
            ->leftJoin('tbl_subjects as sort_subjects', 'sort_subjects.id', '=', 'tbl_learning_materials.subject_id')
            ->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'tbl_learning_materials.section_id')
            ->with(['subject:id,subject_code,subject_name', 'section:id,section_name']);
        TableQuery::search($query, $request->query('search'), ['file_name', 'file_extension']);
        TableQuery::filters($query, $request, ['subject' => 'subject_id', 'section' => 'section_id']);
        TableQuery::sort($query, $request, [
            'file' => 'file_name',
            'subject' => function (Builder $q, string $direction): void {
                $q->orderBy('sort_subjects.subject_code', $direction)
                    ->orderBy('sort_subjects.subject_name', $direction)
                    ->orderBy('tbl_learning_materials.id', 'desc');
            },
            'section' => 'sort_sections.section_name',
            'type' => 'file_extension',
            'updated' => 'updated_at',
            'id' => 'id',
        ], 'id', 'desc');

        $materials = $query->paginate(TableQuery::perPage($request))->withQueryString();
        $groups = $materials->getCollection()->groupBy(fn ($m) => $m->subject_id.'::'.$m->section_id);

        $enrolledSubjectIds = Enrolled::where('student_id', Auth::id())->where('is_active', true)->pluck('subject_id')->unique();
        $filterSubjects = Subject::whereIn('id', $enrolledSubjectIds)->orderBy('subject_name')->get(['id', 'subject_code', 'subject_name']);
        $enrolledSectionIds = Subject::whereIn('id', $enrolledSubjectIds)->pluck('sections_id')->unique()->filter();
        $filterSections = Section::whereIn('id', $enrolledSectionIds)->orderBy('section_name')->get(['id', 'section_name']);

        return view('student.materials.index', compact('groups', 'materials', 'filterSubjects', 'filterSections'));
    }

    public function download(LearningMaterial $material)
    {
        $this->authorize('view', $material); // enrollment re-checked

        $disk = Storage::disk($material->storageDisk());
        abort_unless($disk->exists($material->storage_path), 404);

        return $disk->download($material->storage_path, $material->file_name);
    }
}
