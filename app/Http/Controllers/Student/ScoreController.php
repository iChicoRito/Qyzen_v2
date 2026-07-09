<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Score;
use App\Models\Subject;
use App\Services\AssessmentAvailabilityService;
use App\Services\QuizGradingService;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// H7/H8: result/review + scores history. Own scores only (ScorePolicy + student_id guard).
// Review reveals a question's correct answer ONLY when allow_review=true OR the student got it
// right — the gated display rule from the source.
class ScoreController extends Controller
{
    // H8 / Task 23 + Task 46: scores history (own only), filtered and paginated server-side.
    // Counts are per ATTEMPT, not per unique assessment.
    public function index(Request $request): View
    {
        $query = Score::query()
            ->where('tbl_scores.student_id', Auth::id())
            ->whereIn('status', ['passed', 'failed', 'submitted'])
            ->select('tbl_scores.*')
            ->selectRaw('CASE WHEN tbl_scores.total_questions = 0 THEN 0 ELSE ROUND((tbl_scores.score * 100.0) / tbl_scores.total_questions) END as percentage')
            ->selectRaw("(select count(*) from tbl_scores as attempts where attempts.student_id = tbl_scores.student_id and attempts.assessment_id = tbl_scores.assessment_id and attempts.status in ('submitted', 'passed', 'failed')) as attempts_count")
            ->leftJoin('tbl_assessments as sort_assessments', 'sort_assessments.id', '=', 'tbl_scores.assessment_id')
            ->leftJoin('tbl_subjects as sort_subjects', 'sort_subjects.id', '=', 'sort_assessments.subject_id')
            ->leftJoin('tbl_sections as sort_sections', 'sort_sections.id', '=', 'sort_assessments.section_id')
            ->leftJoin('tbl_academic_term as sort_terms', 'sort_terms.id', '=', 'sort_assessments.term')
            ->with(['assessment:id,assessment_code,subject_id,section_id,term',
                'assessment.subject:id,subject_name',
                'assessment.section:id,section_name',
                'assessment.academicTerm:id,term_name']);

        TableQuery::search($query, $request->query('search'), [
            fn (Builder $q, string $term) => $q->orWhereHas('assessment', fn ($a) => $a->where('assessment_code', 'like', "%{$term}%")),
            fn (Builder $q, string $term) => $q->orWhereHas('assessment.subject', fn ($s) => $s->where('subject_name', 'like', "%{$term}%")),
        ]);
        TableQuery::filters($query, $request, [
            'assessment' => 'assessment_id',
            'result' => fn (Builder $q, string $value) => $q->where('is_passed', $value === 'passed'),
            'subject' => fn (Builder $q, string $value) => $q->whereHas('assessment', fn ($a) => $a->where('subject_id', $value)),
            'term' => fn (Builder $q, string $value) => $q->whereHas('assessment', fn ($a) => $a->where('term', $value)),
        ]);
        TableQuery::sort($query, $request, [
            'assessment' => 'sort_assessments.assessment_code',
            'subject' => function (Builder $q, string $direction): void {
                $q->orderBy('sort_subjects.subject_name', $direction)
                    ->orderBy('sort_subjects.subject_code', $direction)
                    ->orderBy('tbl_scores.id', 'desc');
            },
            'term' => 'sort_terms.term_name',
            'score' => 'score',
            'attempts' => 'attempts_count',
            'percentage' => 'percentage',
            'result' => 'is_passed',
            'submitted' => 'submitted_at',
        ], 'submitted', 'desc');

        $scores = $query->paginate(TableQuery::perPage($request))->withQueryString();

        // Per-assessment best score is still shown per row; attempts now come from the query
        // alias so the displayed count and the sort key stay in sync.
        $base = Score::where('student_id', Auth::id())->whereIn('status', ['passed', 'failed', 'submitted']);
        // Task 51: best by PERCENTAGE, not raw score — pool_size can change over an assessment's
        // life, so different attempts can have different total_questions; grouped in PHP (not SQL
        // MAX) since the "best" row also needs its own total_questions for the ratio, and integer
        // division behaves differently across MySQL/SQLite.
        $bestByAssessment = (clone $base)->get(['assessment_id', 'score', 'total_questions'])
            ->groupBy('assessment_id')
            ->map(fn ($rows) => $rows->sortByDesc(fn ($r) => $r->total_questions > 0 ? $r->score / $r->total_questions : 0)->first());

        $studentAssessmentIds = (clone $base)->pluck('assessment_id')->unique();
        $fAssessments = Assessment::whereIn('id', $studentAssessmentIds)->orderBy('assessment_code')->get(['id', 'assessment_code']);
        $fSubjects    = Subject::whereIn('id', Assessment::whereIn('id', $studentAssessmentIds)->pluck('subject_id')->unique())->orderBy('subject_name')->get(['id', 'subject_name']);
        $fTerms       = AcademicTerm::whereIn('id', Assessment::whereIn('id', $studentAssessmentIds)->pluck('term')->unique()->filter())->orderBy('term_name')->get(['id', 'term_name']);

        return view('student.scores.index', compact('scores', 'bestByAssessment', 'fAssessments', 'fSubjects', 'fTerms'));
    }

    // H7: result + per-question review (gated correct-answer display).
    public function show(Score $score, AssessmentAvailabilityService $availability, QuizGradingService $grading): View
    {
        // 404 (not 403) for non-owned OR unfinished attempts: same "Result not found" outcome, and no
        // row-existence leak. Unfinished attempts must never open on the results screen (spec Phase 1).
        abort_unless($score->student_id === Auth::id(), 404);
        abort_unless(in_array($score->status, ['submitted', 'passed', 'failed'], true), 404);

        $score->load(['assessment.subject:id,subject_code,subject_name', 'assessment.section:id,section_name',
            'assessment.educator:id,given_name,surname', 'assessment.academicTerm:id,term_name']);

        $allowReview = (bool) $score->assessment->allow_review;
        $studentAnswers = $score->student_answer ?? [];

        // Build review rows server-side. correct_answer is loaded here but only EXPOSED per the gate.
        // is_correct reuses the authoritative grader so review and grading can't disagree.
        $review = Quiz::whereIn('id', $score->drawn_quiz_ids ?? [])
            ->get(['id', 'question', 'quiz_type', 'choices', 'correct_answer'])
            ->map(function (Quiz $q) use ($studentAnswers, $allowReview, $grading) {
                $given = $studentAnswers[$q->id] ?? ($studentAnswers[(string) $q->id] ?? null);
                $isCorrect = $given !== null && $grading->isCorrect($q, (string) $given);

                return [
                    'question' => $q->question,
                    'quiz_type' => $q->quiz_type,
                    'choices' => $q->choices,
                    'given' => $given,
                    'is_correct' => $isCorrect,
                    // Gate: reveal the correct answer only if review is allowed OR they got it right.
                    'correct_answer' => ($allowReview || $isCorrect) ? $q->correct_answer : null,
                ];
            });

        // Attempt history (other attempts for the same assessment), newest-first for display.
        $attempts = Score::where('assessment_id', $score->assessment_id)
            ->where('student_id', Auth::id())
            ->whereIn('status', ['passed', 'failed', 'submitted'])
            ->orderByDesc('submitted_at')->get(['id', 'uuid', 'score', 'total_questions', 'is_passed', 'status', 'submitted_at']);

        // Single source of truth for "best": highest PERCENTAGE, ties → earliest attempt. The
        // Highest-Score badge, the best-score figure and the headline all key off this one attempt.
        // Task 51: percentage, not raw score — pool_size can change over an assessment's life, so
        // two attempts can have different total_questions and raw scores aren't comparable.
        // Stable sort (PHP 8.0+): submitted_at asc first, then percentage desc → highest %, earliest on tie.
        $bestAttempt = $attempts->sortBy('submitted_at')
            ->sortByDesc(fn (Score $a) => $a->total_questions > 0 ? $a->score / $a->total_questions : 0)
            ->first();
        $bestAttemptId = $bestAttempt?->id;
        $bestScore = (int) ($bestAttempt?->score ?? 0);
        // The best attempt's OWN total — pool_size can differ from the currently-viewed
        // attempt's total_questions, so $bestScore must never be divided by $score->total_questions.
        $bestTotal = (int) ($bestAttempt?->total_questions ?? $score->total_questions);

        // Stable "Attempt N" numbers by chronological order (attempt #1 = first taken).
        $attemptNumbers = $attempts->sortBy('submitted_at')->values()
            ->mapWithKeys(fn ($a, $i) => [$a->id => $i + 1]);

        // Retake vs Back-to-Assessments (recomputed from finished attempts, not a stale count).
        $summary = $availability->summarize($score->assessment, Auth::id());

        return view('student.scores.show', compact('score', 'review', 'attempts', 'bestScore', 'bestTotal', 'bestAttemptId', 'attemptNumbers', 'summary', 'allowReview'));
    }
}
