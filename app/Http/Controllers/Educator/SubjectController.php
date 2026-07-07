<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Models\Section;
use App\Models\Subject;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// G3: educator subjects. Stored one row per section; the UI groups by code+name.
class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Subject::class);

        $query = Subject::query()
            ->where('tbl_subjects.educator_id', Auth::id())
            ->with('section:id,section_name');
        TableQuery::search($query, $request->query('search'), ['subject_code', 'subject_name']);
        TableQuery::filters($query, $request, ['status' => 'is_active']);
        TableQuery::sort($query, $request, [
            'code' => 'subject_code',
            'name' => 'subject_name',
            'section' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'tbl_subjects.sections_id')
                    ->select('tbl_subjects.*')
                    ->orderBy('sort_sections.section_name', $direction)
                    ->orderBy('tbl_subjects.id', 'desc');
            },
            'status' => 'is_active',
            'id' => 'id',
        ], 'id', 'desc');

        $subjects = $query->paginate(TableQuery::perPage($request))->withQueryString();
        // Group the current server page only; full datasets are never loaded into the browser.
        $groups = $subjects->getCollection()
            ->groupBy(fn ($s) => $s->subject_code.'::'.$s->subject_name.'::'.(int) $s->is_active);

        return view('educator.subjects.index', compact('groups', 'subjects'));
    }

    public function create(): View
    {
        $this->authorize('create', Subject::class);

        return view('educator.subjects.create', ['sections' => $this->ownedSections()]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Subject::class);

        $data = $request->validated();
        DB::transaction(function () use ($data) {
            foreach ($data['section_ids'] as $sectionId) {
                Subject::create([
                    'educator_id' => Auth::id(),
                    'sections_id' => $sectionId,
                    'subject_code' => $data['subject_code'],
                    'subject_name' => $data['subject_name'],
                    'is_active' => $data['is_active'],
                ]);
            }
        });

        return redirect()->route('educator.subjects.index')->with('status', 'Subject created.');
    }

    public function edit(Subject $subject): View
    {
        $this->authorize('update', $subject);

        // Load the whole group (same code+name for this educator) so edit replaces all of it.
        $group = Subject::where('educator_id', Auth::id())
            ->where('subject_code', $subject->subject_code)
            ->where('subject_name', $subject->subject_name)
            ->get();

        return view('educator.subjects.edit', [
            'subject' => $subject,
            'group' => $group,
            'sections' => $this->ownedSections(),
        ]);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);

        $data = $request->validated();
        DB::transaction(function () use ($data) {
            // Replace the group: delete its rows (ownership-checked), recreate with new sections.
            Subject::where('educator_id', Auth::id())->whereIn('id', $data['row_ids'])->delete();
            foreach ($data['section_ids'] as $sectionId) {
                Subject::create([
                    'educator_id' => Auth::id(),
                    'sections_id' => $sectionId,
                    'subject_code' => $data['subject_code'],
                    'subject_name' => $data['subject_name'],
                    'is_active' => $data['is_active'],
                ]);
            }
        });

        return redirect()->route('educator.subjects.index')->with('status', 'Subject updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $this->authorize('delete', $subject);

        // Delete the whole code+name group, ownership-checked.
        Subject::where('educator_id', Auth::id())
            ->where('subject_code', $subject->subject_code)
            ->where('subject_name', $subject->subject_name)
            ->delete();

        return redirect()->route('educator.subjects.index')->with('status', 'Subject deleted.');
    }

    private function ownedSections()
    {
        return Section::visibleTo(Auth::user())->orderBy('section_name')->get();
    }
}
