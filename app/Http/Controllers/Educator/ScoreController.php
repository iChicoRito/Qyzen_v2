<?php

namespace App\Http\Controllers\Educator;

use App\Exports\ScoresExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\GrantRetakeRequest;
use App\Models\Assessment;
use App\Models\Score;
use App\Models\StudentAssessmentRetake;
use App\Services\NotificationService;
use App\Services\ScoreExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

// G7: scores are READ-ONLY for educators (they grade nothing manually — grading is server-side
// at submit, Stage H6). Educator may grant retakes and view per-attempt detail. G8: export.
class ScoreController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ScoreExportService $exporter,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Score::class);

        // Best + latest attempt per student per assessment.
        $scores = Score::visibleTo(Auth::user())
            ->with(['student:id,given_name,surname,user_id', 'assessment:id,assessment_code'])
            ->orderByDesc('submitted_at')->paginate(30);

        return view('educator.scores.index', compact('scores'));
    }

    public function show(Score $score): View
    {
        $this->authorize('view', $score);

        // Attempt detail: per-question correct answer + student answer + isCorrect.
        // correct_answer is loaded SERVER-SIDE here (educator view) — never serialized to a student.
        $score->load(['student:id,given_name,surname,user_id', 'assessment.quizzes']);

        return view('educator.scores.show', compact('score'));
    }

    public function grantRetake(GrantRetakeRequest $request): RedirectResponse
    {
        $this->authorize('create', \App\Models\Assessment::class);

        $data = $request->validated();
        StudentAssessmentRetake::updateOrCreate(
            ['educator_id' => Auth::id(), 'student_id' => $data['student_id'], 'assessment_id' => $data['assessment_id']],
            ['extra_retake_count' => $data['extra_retake_count'], 'is_active' => true],
        );

        $this->notifications->emit(Auth::user(), 'retake_updated', (int) $data['student_id'], [
            'assessment_id' => (int) $data['assessment_id'], 'title' => 'Retake granted',
            'subject_id' => Assessment::find($data['assessment_id'])?->subject_id,
        ]);

        return back()->with('status', 'Retake granted.');
    }

    // G8: single-assessment xlsx export.
    public function export(Assessment $assessment)
    {
        $this->authorize('view', $assessment);

        return Excel::download(
            new ScoresExport($assessment),
            "scores-{$assessment->assessment_code}.xlsx",
        );
    }

    // G8: bulk export → zip of TERM/SUBJECT/SECTION.xlsx. method = all|term|semester.
    public function exportBulk(\Illuminate\Http\Request $request)
    {
        $this->authorize('viewAny', Score::class);

        $method = $request->query('method', 'all');

        return $this->exporter->bulkZip(Auth::user(), $method);
    }
}
