{{-- H7: result + per-question review. Correct answer is shown only when the server permitted it
     (allow_review OR the student was correct) — $row['correct_answer'] is null otherwise. --}}
@extends('student.layout')
@section('title', 'Result')
@section('heading', 'Result')
@section('content')
    @include('admin._status')
    <div class="card mb-5"><div class="card-body">
        <div class="d-flex gap-6 mb-3">
            <div><div class="fs-2hx fw-bold {{ $score->is_passed ? 'text-success' : 'text-danger' }}">{{ $score->score }}/{{ $score->total_questions }}</div><div class="text-gray-500">Score</div></div>
            <div><div class="fs-2hx fw-bold">{{ $score->total_questions ? round($score->score / $score->total_questions * 100) : 0 }}%</div><div class="text-gray-500">Percentage</div></div>
            <div><div class="fs-2hx fw-bold">{{ $score->is_passed ? 'PASS' : 'FAIL' }}</div><div class="text-gray-500">Result (pass ≥ 75%)</div></div>
            <div><div class="fs-2hx fw-bold">{{ $score->warning_attempts }}</div><div class="text-gray-500">Warnings</div></div>
        </div>
        @if ($attempts->count() > 1)
            <div class="mb-2">Attempts:
                @foreach ($attempts as $att)
                    <a href="{{ route('student.scores.show', $att) }}" class="btn btn-sm {{ $att->id === $score->id ? 'btn-primary' : 'btn-light' }}">
                        {{ $att->score }}/{{ $att->total_questions }}{{ $att->id === $score->id ? ' (this)' : '' }}
                    </a>
                @endforeach
            </div>
        @endif
    </div></div>

    <div class="card"><div class="card-body">
        <h4 class="mb-3">Review</h4>
        @foreach ($review as $i => $r)
            <div class="mb-4">
                <p class="fw-bold">{{ $i + 1 }}. {{ $r['question'] }}</p>
                <div>Your answer: <span class="badge badge-light-{{ $r['is_correct'] ? 'success' : 'danger' }}">{{ $r['given'] ?? '—' }}</span></div>
                @if (! is_null($r['correct_answer']))
                    <div>Correct answer: <span class="badge badge-light-success">{{ $r['correct_answer'] }}</span></div>
                @elseif (! $r['is_correct'])
                    <div class="text-muted fs-7">Correct answer hidden (review not enabled for this assessment).</div>
                @endif
            </div>
        @endforeach
        <a href="{{ route('student.scores.index') }}" class="btn btn-light">Back to scores</a>
    </div></div>
@endsection
