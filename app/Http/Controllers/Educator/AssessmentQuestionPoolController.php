<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateQuestionPoolRequest;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Quiz;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Task 51: an assessment's pool config — which bank questions are eligible to be drawn, and how
// many (N) to draw per attempt. A sub-action on Assessment, gated by AssessmentPolicy::update.
class AssessmentQuestionPoolController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function edit(Assessment $assessment): View
    {
        $this->authorize('update', $assessment);

        $bankQuestions = Quiz::visibleTo(Auth::user())
            ->where('subject_id', $assessment->subject_id)
            ->with('eligibleAssessments:id,assessment_code')
            ->orderBy('id')->get();
        $eligibleIds = $assessment->eligibleQuizzes()->pluck('tbl_quizzes.id')->all();
        $batches = $bankQuestions->pluck('batch_label')->filter()->unique()->values();

        return view('educator.assessments.pool', compact('assessment', 'bankQuestions', 'eligibleIds', 'batches'));
    }

    public function update(UpdateQuestionPoolRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $data = $request->validated();
        $assessment->eligibleQuizzes()->sync($data['eligible_quiz_ids'] ?? []);
        $assessment->update(['pool_size' => $data['pool_size']]);

        $this->notifyEnrolled($assessment);

        return redirect()->route('educator.assessments.pool.edit', $assessment)
            ->with('status', 'Question pool updated.');
    }

    private function notifyEnrolled(Assessment $assessment): void
    {
        $studentIds = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->where('is_active', true)
            ->pluck('student_id')->all();

        $this->notifications->emitToMany(Auth::user(), 'quiz_updated', $studentIds, [
            'subject_id' => $assessment->subject_id, 'assessment_id' => $assessment->id,
            'section_id' => $assessment->section_id, 'title' => 'Assessment questions were updated',
            'link_path' => route('student.assessments.index'),
        ]);
    }
}
