<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Score;
use App\Services\AssessmentAvailabilityService;
use App\Services\QuizGradingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// H7/H8: result/review + scores history. Own scores only (ScorePolicy + student_id guard).
// Review reveals a question's correct answer ONLY when allow_review=true OR the student got it
// right — the gated display rule from the source.
class ScoreController extends Controller
{
    // H8 / Task 23: scores history (own only). All submitted attempts, newest first. Search/sort/
    // paginate/filter are client-side (KTDataTable); the server just returns the owned rows + the
    // aggregates the table/cards display. Counts are per ATTEMPT, not per unique assessment.
    public function index(): View
    {
        $scores = Score::where('student_id', Auth::id())
            ->whereIn('status', ['passed', 'failed', 'submitted'])
            ->with(['assessment:id,assessment_code,subject_id,section_id,term',
                'assessment.subject:id,subject_name',
                'assessment.section:id,section_name',
                'assessment.academicTerm:id,term_name'])
            ->orderByDesc('submitted_at')->get();

        // Per-assessment figures (best score + attempts used) — repeat across rows of the same
        // assessment by design (spec Stated Assumptions).
        $bestByAssessment = $scores->groupBy('assessment_id')->map->max('score');
        $attemptsByAssessment = $scores->groupBy('assessment_id')->map->count();

        return view('student.scores.index', compact('scores', 'bestByAssessment', 'attemptsByAssessment'));
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
        $review = Quiz::where('assessment_id', $score->assessment_id)
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

        // Single source of truth for "best": highest score, ties → earliest attempt. The Highest-Score
        // badge, the best-score figure and the headline all key off this one attempt.
        // Stable sort (PHP 8.0+): submitted_at asc first, then score desc → highest score, earliest on tie.
        $bestAttempt = $attempts->sortBy('submitted_at')->sortByDesc('score')->first();
        $bestAttemptId = $bestAttempt?->id;
        $bestScore = (int) ($bestAttempt?->score ?? 0);

        // Stable "Attempt N" numbers by chronological order (attempt #1 = first taken).
        $attemptNumbers = $attempts->sortBy('submitted_at')->values()
            ->mapWithKeys(fn ($a, $i) => [$a->id => $i + 1]);

        // Retake vs Back-to-Assessments (recomputed from finished attempts, not a stale count).
        $summary = $availability->summarize($score->assessment, Auth::id());

        return view('student.scores.show', compact('score', 'review', 'attempts', 'bestScore', 'bestAttemptId', 'attemptNumbers', 'summary'));
    }
}
