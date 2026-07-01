<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Score;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// H7/H8: result/review + scores history. Own scores only (ScorePolicy + student_id guard).
// Review reveals a question's correct answer ONLY when allow_review=true OR the student got it
// right — the gated display rule from the source.
class ScoreController extends Controller
{
    // H8: scores history (own only) + filter/sort/paginate.
    public function index(Request $request): View
    {
        $query = Score::where('student_id', Auth::id())
            ->whereIn('status', ['passed', 'failed', 'submitted'])
            ->with(['assessment:id,assessment_code', 'assessment.subject:id,subject_code']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $scores = $query->orderByDesc('submitted_at')->get();

        $summary = [
            'total' => Score::where('student_id', Auth::id())->whereIn('status', ['passed', 'failed', 'submitted'])->count(),
            'passed' => Score::where('student_id', Auth::id())->where('status', 'passed')->count(),
            'failed' => Score::where('student_id', Auth::id())->where('status', 'failed')->count(),
        ];

        return view('student.scores.index', compact('scores', 'summary'));
    }

    // H7: result + per-question review (gated correct-answer display).
    public function show(Score $score): View
    {
        $this->authorize('view', $score); // ScorePolicy: student sees own only

        abort_unless($score->student_id === Auth::id(), 403);

        $allowReview = (bool) $score->assessment->allow_review;
        $studentAnswers = $score->student_answer ?? [];

        // Build review rows server-side. correct_answer is loaded here but only EXPOSED per the gate.
        $review = Quiz::where('assessment_id', $score->assessment_id)
            ->get(['id', 'question', 'quiz_type', 'choices', 'correct_answer'])
            ->map(function (Quiz $q) use ($studentAnswers, $allowReview) {
                $given = $studentAnswers[$q->id] ?? ($studentAnswers[(string) $q->id] ?? null);
                $isCorrect = $given !== null && strtolower(trim((string) $given)) === strtolower(trim((string) $q->correct_answer));

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

        // Attempt history (other attempts for the same assessment).
        $attempts = Score::where('assessment_id', $score->assessment_id)
            ->where('student_id', Auth::id())
            ->orderByDesc('submitted_at')->get(['id', 'score', 'total_questions', 'is_passed', 'submitted_at']);

        return view('student.scores.show', compact('score', 'review', 'attempts'));
    }
}
