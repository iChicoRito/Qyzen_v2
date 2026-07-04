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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        // Grouped by assessment (accordion) with each assessment's questions nested + MC/identification counts.
        $assessments = Assessment::visibleTo(Auth::user())
            ->with([
                'subject:id,subject_code,subject_name',
                'section:id,section_name',
                'quizzes' => fn ($q) => $q->orderBy('id'),
            ])
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
            'assessments' => $this->assessmentOptions(),
            'selectedAssessment' => $request->query('assessment_id'),
        ]);
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $data = $request->validated();
        $assessments = Assessment::visibleTo(Auth::user())->findOrFail($data['assessment_ids']);

        // Add the same question to each selected assessment.
        foreach ($assessments as $assessment) {
            $this->makeQuiz($assessment, $data);
            $this->notifyEnrolled($assessment, 'quiz_created', 'New question added');
        }

        $n = $assessments->count();
        return redirect()->route('educator.quizzes.index')
            ->with('status', $n === 1 ? 'Question created.' : "Question added to {$n} assessments.");
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        return view('educator.quizzes.edit', [
            'quiz' => $quiz,
            'assessments' => $this->assessmentOptions(),
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $data = $request->validated();
        $assessment = Assessment::visibleTo(Auth::user())->findOrFail($data['assessment_id']);
        $quiz->update([
            'assessment_id' => $assessment->id,
            'subject_id' => $assessment->subject_id,
            'section_id' => $assessment->section_id,
            'question' => $data['question'],
            'quiz_type' => $data['quiz_type'],
            'choices' => $data['quiz_type'] === 'multiple_choice' ? $data['choices'] : null,
            'correct_answer' => $this->correctAnswerFrom($data),
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
            'assessment_ids' => ['required', 'array', 'min:1'],
            'assessment_ids.*' => ['required'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'files.required' => 'Please choose at least one file to upload.',
            'files.*.mimes' => 'Wrong file format: :input is not an Excel/CSV file. Only .xlsx, .xls or .csv are accepted.',
            'files.*.file' => 'The upload was not a valid file.',
        ]);

        $assessments = Assessment::visibleTo(Auth::user())->findOrFail($request->input('assessment_ids'));
        $files = $request->file('files');

        // All-or-nothing: validate every row of every (assessment × file) FIRST. If ANY row is
        // bad (wrong format, blank, missing/invalid columns), reject the whole upload — nothing is saved.
        $errors = [];
        $rowsByAssessment = []; // assessmentId => [quiz attribute rows]
        foreach ($assessments as $assessment) {
            $rowsByAssessment[$assessment->id] = [];
            foreach ($files as $file) {
                $import = new QuizzesImport($assessment, $file->getClientOriginalName());
                Excel::import($import, $file);
                $errors = array_merge($errors, $import->errors());
                $rowsByAssessment[$assessment->id] = array_merge($rowsByAssessment[$assessment->id], $import->validRows());
            }
        }

        if ($errors !== []) {
            // Surfaced as a validation-error toast; nothing was written.
            throw ValidationException::withMessages(['files' => $errors]);
        }

        // Clean → insert everything atomically.
        $created = DB::transaction(function () use ($assessments, $rowsByAssessment) {
            $total = 0;
            foreach ($assessments as $assessment) {
                $rows = $rowsByAssessment[$assessment->id];
                if ($rows === []) {
                    continue;
                }
                foreach ($rows as $row) {
                    Quiz::create($row); // create() applies the choices array cast + timestamps
                }
                $total += count($rows);
                // single bundled quiz_uploaded notification per assessment (per the source).
                $this->notifyEnrolled($assessment, 'quiz_uploaded', count($rows).' new questions');
            }

            return $total;
        });

        return redirect()->route('educator.quizzes.index')->with('status',
            "Uploaded {$created} question(s) across {$assessments->count()} assessment(s).");
    }

    // Assessments for the picker, with subject/section eager-loaded for the rich label.
    private function assessmentOptions()
    {
        return Assessment::visibleTo(Auth::user())
            ->with(['subject:id,subject_code,subject_name', 'section:id,section_name'])
            ->orderByDesc('id')->get();
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
            'correct_answer' => $this->correctAnswerFrom($data),
        ]);
    }

    // MC: the picked choice key. Identification: one accepted answer stored plain,
    // multiple stored as a JSON array (QuizGradingService accepts either form).
    private function correctAnswerFrom(array $data): string
    {
        if ($data['quiz_type'] === 'multiple_choice') {
            return $data['correct_answer'];
        }

        $answers = array_values(array_filter(
            array_map('trim', $data['answers'] ?? []),
            fn ($a) => $a !== ''
        ));

        return count($answers) === 1 ? $answers[0] : json_encode($answers);
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
            'link_path' => route('student.assessments.index'),
        ]);
    }
}
