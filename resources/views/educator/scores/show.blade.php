{{-- G7: attempt detail. correct_answer rendered here is server-side (educator view) — this page
     is never served to a student. Scores are read-only; educator may grant a retake. --}}
@extends('educator.layout')
@section('title', 'Attempt Detail')
@section('heading', 'Attempt Detail')
@section('content')
    @include('admin._status')
    <div class="kt-card mb-5"><div class="kt-card-content p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-2 gap-x-4 text-sm">
            <dt class="text-secondary-foreground">Student</dt><dd>{{ optional($score->student)->name }} ({{ optional($score->student)->user_id }})</dd>
            <dt class="text-secondary-foreground">Assessment</dt><dd>{{ optional($score->assessment)->assessment_code }}</dd>
            <dt class="text-secondary-foreground">Score</dt><dd>{{ $score->score }}/{{ $score->total_questions }} — {{ $score->is_passed ? 'Passed' : 'Failed' }}</dd>
            <dt class="text-secondary-foreground">Warnings</dt><dd>{{ $score->warning_attempts }}</dd>
        </dl>
        <form method="POST" action="{{ route('educator.scores.grant-retake') }}" class="flex gap-2 mt-4 items-end">@csrf
            <input type="hidden" name="assessment_id" value="{{ $score->assessment_id }}">
            <input type="hidden" name="student_id" value="{{ $score->student_id }}">
            <div class="flex flex-col gap-1">
                <label class="kt-form-label">Grant retakes</label>
                <input type="number" name="extra_retake_count" class="kt-input w-40" min="1" value="1">
            </div>
            <button class="kt-btn kt-btn-primary">Grant retake</button>
        </form>
    </div></div>

    <div class="kt-card"><div class="kt-card-content p-5">
        <h4 class="text-base font-medium text-mono mb-3">Per-question review</h4>
        @php $answers = $score->student_answer ?? []; @endphp
        <div class="kt-scrollable-x-auto">
            <table class="kt-table table-auto kt-table-border">
                <thead>
                    <tr>
                        <th class="min-w-[260px]">Question</th>
                        <th class="min-w-[140px]">Student Answer</th>
                        <th class="min-w-[140px]">Correct Answer</th>
                        <th class="min-w-[100px]">Result</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td>
                                <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $isCorrect ? 'success' : 'destructive' }} gap-1 items-center">
                                    <span class="kt-badge-dot size-1.5"></span>{{ $isCorrect ? 'Correct' : 'Wrong' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <a href="{{ route('educator.scores.index') }}" class="kt-btn kt-btn-outline mt-4">Back</a>
    </div></div>
@endsection
