<?php

namespace App\Http\Controllers\Educator;

use App\Exports\QuizUploadTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Imports\QuizzesImport;
use App\Models\Assessment;
use App\Models\Enrolled;
use App\Models\Quiz;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

// G6: educator quizzes (questions). correct_answer is server-only ($hidden on the model);
// never sent to a student. quiz_* notifications fire to enrolled students.
class QuizController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(): View
    {
        $this->authorize('viewAny', Quiz::class);

        // Grouped by assessment with MC/identification counts.
        $assessments = Assessment::visibleTo(Auth::user())
            ->withCount([
                'quizzes',
                'quizzes as multiple_choice_count' => fn ($q) => $q->where('quiz_type', 'multiple_choice'),
                'quizzes as identification_count' => fn ($q) => $q->where('quiz_type', 'identification'),
            ])
            ->orderByDesc('id')->get();

        return view('educator.quizzes.index', compact('assessments'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Quiz::class);

        return view('educator.quizzes.create', [
            'assessments' => Assessment::visibleTo(Auth::user())->orderByDesc('id')->get(),
            'selectedAssessment' => $request->query('assessment_id'),
        ]);
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $assessment = Assessment::visibleTo(Auth::user())->findOrFail($request->input('assessment_id'));
        $quiz = $this->makeQuiz($assessment, $request->validated());

        $this->notifyEnrolled($assessment, 'quiz_created', 'New question added');

        return redirect()->route('educator.quizzes.index')->with('status', 'Question created.');
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        return view('educator.quizzes.edit', [
            'quiz' => $quiz,
            'assessments' => Assessment::visibleTo(Auth::user())->orderByDesc('id')->get(),
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $data = $request->validated();
        $quiz->update([
            'question' => $data['question'],
            'quiz_type' => $data['quiz_type'],
            'choices' => $data['quiz_type'] === 'multiple_choice' ? $data['choices'] : null,
            'correct_answer' => $data['correct_answer'],
        ]);

        $this->notifyEnrolled($quiz->assessment, 'quiz_updated', 'A question was updated');

        return redirect()->route('educator.quizzes.index')->with('status', 'Question updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $assessment = $quiz->assessment;
        $quiz->delete();
        $this->notifyEnrolled($assessment, 'quiz_deleted', 'A question was removed');

        return redirect()->route('educator.quizzes.index')->with('status', 'Question deleted.');
    }

    public function destroyForAssessment(Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment); // owns the assessment → may clear its questions

        Quiz::where('assessment_id', $assessment->id)->where('educator_id', Auth::id())->delete();

        return redirect()->route('educator.quizzes.index')->with('status', 'All questions deleted for the assessment.');
    }

    // G6 bulk upload.
    public function uploadTemplate()
    {
        $this->authorize('create', Quiz::class);

        return Excel::download(new QuizUploadTemplateExport(), 'quiz-upload-template.xlsx');
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'assessment_id' => ['required'],
        ]);

        $assessment = Assessment::visibleTo(Auth::user())->findOrFail($request->input('assessment_id'));
        $import = new QuizzesImport($assessment);
        Excel::import($import, $request->file('file'));

        if ($import->createdCount() > 0) {
            // single bundled quiz_uploaded notification per the source.
            $this->notifyEnrolled($assessment, 'quiz_uploaded', "{$import->createdCount()} new questions");
        }

        return redirect()->route('educator.quizzes.index')
            ->with('status', "Uploaded {$import->createdCount()} question(s); {$import->skippedCount()} skipped.");
    }

    private function makeQuiz(Assessment $assessment, array $data): Quiz
    {
        return Quiz::create([
            'assessment_id' => $assessment->id,
            'subject_id' => $assessment->subject_id,
            'section_id' => $assessment->section_id,
            'educator_id' => Auth::id(),
            'question' => $data['question'],
            'quiz_type' => $data['quiz_type'],
            'choices' => $data['quiz_type'] === 'multiple_choice' ? $data['choices'] : null,
            'correct_answer' => $data['correct_answer'],
        ]);
    }

    private function notifyEnrolled(Assessment $assessment, string $event, string $title): void
    {
        $studentIds = Enrolled::where('educator_id', Auth::id())
            ->where('subject_id', $assessment->subject_id)
            ->where('is_active', true)
            ->pluck('student_id')->all();

        $this->notifications->emitToMany(Auth::user(), $event, $studentIds, [
            'subject_id' => $assessment->subject_id, 'assessment_id' => $assessment->id,
            'section_id' => $assessment->section_id, 'title' => $title,
        ]);
    }
}
