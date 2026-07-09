<?php

namespace App\Http\Controllers\Educator;

use App\Exports\QuizUploadTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Imports\QuizzesImport;
use App\Models\Assessment;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\TableQuery;
use Maatwebsite\Excel\Facades\Excel;

// Task 51: educator question bank. Questions are scoped to educator+subject, reusable across
// any number of assessments via each assessment's pool config (AssessmentQuestionPoolController).
// correct_answer is server-only ($hidden on the model); never sent to a student.
class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Quiz::class);

        $query = Quiz::visibleTo(Auth::user())
            ->with(['subject:id,subject_code,subject_name,sections_id', 'subject.section:id,section_name', 'eligibleAssessments:id,assessment_code']);
        TableQuery::search($query, $request->query('search'), [
            'question',
            fn (Builder $q, string $term) => $q->orWhereHas('subject', fn ($s) => $s
                ->where('subject_code', 'like', "%{$term}%")
                ->orWhere('subject_name', 'like', "%{$term}%")),
        ]);
        TableQuery::filters($query, $request, [
            'subject' => 'subject_id',
            'type' => 'quiz_type',
            'assessment' => fn (Builder $q, string $value) => $q->whereHas('eligibleAssessments', fn ($a) => $a->where('tbl_assessments.id', $value)),
            'batch' => 'batch_label',
        ]);
        TableQuery::sort($query, $request, ['question' => 'question', 'id' => 'id'], 'id', 'desc');

        $quizzes = $query->paginate(TableQuery::perPage($request))->withQueryString();
        $subjects = $this->subjectOptions();
        $assessments = $this->assessmentOptions();
        $batches = $this->batchOptions();

        return view('educator.quizzes.index', compact('quizzes', 'subjects', 'assessments', 'batches'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Quiz::class);

        return view('educator.quizzes.create', [
            'subjects' => $this->subjectOptions(),
            'selectedSubject' => $request->query('subject_id'),
            'assessments' => $this->assessmentOptions(),
        ]);
    }

    public function store(StoreQuizRequest $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $data = $request->validated();
        $subject = Subject::visibleTo(Auth::user())->findOrFail($data['subject_id']);
        $quiz = $this->makeQuiz($subject->id, $data);
        $this->syncAssessments($quiz, $data['assessment_ids'] ?? []);

        return redirect()->route('educator.quizzes.index')->with('status', 'Question added to the bank.');
    }

    public function edit(Quiz $quiz): View
    {
        $this->authorize('update', $quiz);

        return view('educator.quizzes.edit', [
            'quiz' => $quiz,
            'subjects' => $this->subjectOptions(),
            'assessments' => $this->assessmentOptions(),
            'selectedAssessmentIds' => $quiz->eligibleAssessments()->pluck('tbl_assessments.id')->all(),
        ]);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): RedirectResponse
    {
        $this->authorize('update', $quiz);

        $data = $request->validated();
        $subject = Subject::visibleTo(Auth::user())->findOrFail($data['subject_id']);
        $quiz->update([
            'subject_id' => $subject->id,
            'question' => $data['question'],
            'quiz_type' => $data['quiz_type'],
            'choices' => $data['quiz_type'] === 'multiple_choice' ? $data['choices'] : null,
            'correct_answer' => $this->correctAnswerFrom($data),
        ]);
        $this->syncAssessments($quiz, $data['assessment_ids'] ?? []);

        return redirect()->route('educator.quizzes.index')->with('status', 'Question updated.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorize('delete', $quiz);

        $quiz->delete();

        return redirect()->route('educator.quizzes.index')->with('status', 'Question deleted.');
    }

    // G6 bulk upload.
    public function uploadTemplate()
    {
        $this->authorize('create', Quiz::class);

        return Excel::download(new QuizUploadTemplateExport, 'quiz-upload-template.xlsx');
    }

    public function upload(Request $request): RedirectResponse
    {
        $this->authorize('create', Quiz::class);

        $request->validate([
            'subject_id' => ['required', 'integer'],
            'assessment_ids' => ['nullable', 'array'],
            'assessment_ids.*' => [Rule::exists('tbl_assessments', 'id')->where('educator_id', Auth::id())],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ], [
            'files.required' => 'Please choose at least one file to upload.',
            'files.*.mimes' => 'Wrong file format: :input is not an Excel/CSV file. Only .xlsx, .xls or .csv are accepted.',
            'files.*.file' => 'The upload was not a valid file.',
        ]);

        $subject = Subject::visibleTo(Auth::user())->findOrFail($request->input('subject_id'));
        $assessmentIds = array_filter((array) $request->input('assessment_ids', []));
        if ($assessmentIds !== [] && Assessment::whereKey($assessmentIds)->where('subject_id', '!=', $subject->id)->exists()) {
            throw ValidationException::withMessages(['assessment_ids' => 'Selected assessments must belong to the same subject as the upload.']);
        }
        $files = $request->file('files');
        $uploadedAt = now()->format('M j, Y g:i A');

        // All-or-nothing: validate every row of every file FIRST. If ANY row is bad (wrong
        // format, blank, missing/invalid columns), reject the whole upload — nothing is saved.
        $errors = [];
        $rows = [];
        foreach ($files as $file) {
            $batchLabel = "Upload: {$file->getClientOriginalName()} · {$uploadedAt}";
            $import = new QuizzesImport($subject, $file->getClientOriginalName(), $batchLabel);
            Excel::import($import, $file);
            $errors = array_merge($errors, $import->errors());
            $rows = array_merge($rows, $import->validRows());
        }

        if ($errors !== []) {
            // Surfaced as a validation-error toast; nothing was written.
            throw ValidationException::withMessages(['files' => $errors]);
        }

        $created = DB::transaction(function () use ($rows, $assessmentIds) {
            $quizIds = [];
            foreach ($rows as $row) {
                $quizIds[] = Quiz::create($row)->id; // create() applies the choices array cast + timestamps
            }
            foreach (Assessment::whereKey($assessmentIds)->get() as $assessment) {
                $assessment->eligibleQuizzes()->syncWithoutDetaching($quizIds);
            }

            return count($rows);
        });

        return redirect()->route('educator.quizzes.index')->with('status', "Uploaded {$created} question(s) to the bank.");
    }

    private function subjectOptions()
    {
        return Subject::visibleTo(Auth::user())->with('section:id,section_name')
            ->orderBy('subject_name')->get(['id', 'subject_code', 'subject_name', 'sections_id']);
    }

    // Assessments for the "also add to these pools" picker, richly labeled by subject.
    private function assessmentOptions()
    {
        return Assessment::visibleTo(Auth::user())
            ->with('subject:id,subject_code,subject_name')
            ->orderByDesc('id')->get();
    }

    private function syncAssessments(Quiz $quiz, array $assessmentIds): void
    {
        $quiz->eligibleAssessments()->sync(array_filter($assessmentIds));
    }

    // Distinct batch labels for this educator's questions, newest first, for the bank's filter dropdown.
    private function batchOptions()
    {
        return Quiz::visibleTo(Auth::user())
            ->whereNotNull('batch_label')
            ->selectRaw('batch_label, MAX(id) as max_id')
            ->groupBy('batch_label')
            ->orderByDesc('max_id')
            ->pluck('batch_label');
    }

    private function makeQuiz(int $subjectId, array $data): Quiz
    {
        return Quiz::create([
            'subject_id' => $subjectId,
            'educator_id' => Auth::id(),
            'question' => $data['question'],
            'quiz_type' => $data['quiz_type'],
            'choices' => $data['quiz_type'] === 'multiple_choice' ? $data['choices'] : null,
            'correct_answer' => $this->correctAnswerFrom($data),
            'batch_label' => 'Manual · '.now()->format('M j, Y g:i A'),
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
}
