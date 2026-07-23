<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrolled;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $query = Enrolled::visibleTo($request->user())
            ->where('tbl_enrolled.student_id', $request->user()->id)
            ->where('tbl_enrolled.is_active', true)
            ->with([
                'educator:id,given_name,surname,profile_picture',
                'subject:id,subject_code,subject_name,sections_id',
                'subject.section:id,section_name',
            ])
            ->join('tbl_subjects as sort_subjects', 'sort_subjects.id', '=', 'tbl_enrolled.subject_id')
            ->join('tbl_users as sort_educators', 'sort_educators.id', '=', 'tbl_enrolled.educator_id')
            ->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'sort_subjects.sections_id')
            ->select('tbl_enrolled.*');

        TableQuery::search($query, $request->query('search'), [
            'sort_subjects.subject_code', 'sort_subjects.subject_name', 'sort_educators.given_name',
            'sort_educators.surname', 'sort_sections.section_name',
        ]);
        TableQuery::sort($query, $request, [
            'educator' => fn (Builder $q, string $direction) => $q->orderBy('sort_educators.surname', $direction)->orderBy('sort_educators.given_name', $direction),
            'subject' => fn (Builder $q, string $direction) => $q->orderBy('sort_subjects.subject_name', $direction)->orderBy('sort_subjects.subject_code', $direction),
            'section' => 'sort_sections.section_name',
        ], 'educator');

        $enrollments = $query->paginate(TableQuery::perPage($request))->withQueryString();

        return view('student.subjects.index', compact('enrollments'));
    }
}
