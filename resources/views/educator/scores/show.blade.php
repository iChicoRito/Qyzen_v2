{{-- G7: attempt detail. correct_answer rendered here is server-side (educator view) — this page
     is never served to a student. Scores are read-only; educator may grant a retake. --}}
@extends('educator.layout')
@section('title', 'Attempt Detail')
@section('heading', 'Attempt Detail')
@section('content')
    @include('admin._status')
    <div class="card mb-5"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Student</dt><dd class="col-sm-9">{{ optional($score->student)->name }} ({{ optional($score->student)->user_id }})</dd>
            <dt class="col-sm-3">Assessment</dt><dd class="col-sm-9">{{ optional($score->assessment)->assessment_code }}</dd>
            <dt class="col-sm-3">Score</dt><dd class="col-sm-9">{{ $score->score }}/{{ $score->total_questions }} — {{ $score->is_passed ? 'Passed' : 'Failed' }}</dd>
            <dt class="col-sm-3">Warnings</dt><dd class="col-sm-9">{{ $score->warning_attempts }}</dd>
        </dl>
        <form method="POST" action="{{ route('educator.scores.grant-retake') }}" class="d-flex gap-2 mt-3 align-items-end">@csrf
            <input type="hidden" name="assessment_id" value="{{ $score->assessment_id }}">
            <input type="hidden" name="student_id" value="{{ $score->student_id }}">
            <div><label class="form-label">Grant retakes</label>
                <input type="number" name="extra_retake_count" class="form-control w-150px" min="1" value="1"></div>
            <button class="btn btn-primary">Grant retake</button>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <h4 class="mb-3">Per-question review</h4>
        @php $answers = $score->student_answer ?? []; @endphp
        <table class="table table-row-dashed fs-6">
            <thead><tr class="text-gray-500 fw-bold text-uppercase fs-7"><th>Question</th><th>Student Answer</th><th>Correct Answer</th><th>Result</th></tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @foreach ($score->assessment->quizzes as $quiz)
                    @php
                        $given = $answers[$quiz->id] ?? ($answers[(string) $quiz->id] ?? null);
                        $correct = $quiz->correct_answer; // server-side only
                        $isCorrect = $given !== null && (string) $given === (string) $correct;
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($quiz->question, 60) }}</td>
                        <td>{{ $given ?? '—' }}</td>
                        <td>{{ $correct }}</td>
                        <td><span class="badge badge-light-{{ $isCorrect ? 'success' : 'danger' }}">{{ $isCorrect ? 'Correct' : 'Wrong' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <a href="{{ route('educator.scores.index') }}" class="btn btn-light">Back</a>
    </div></div>
@endsection
