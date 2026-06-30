{{-- H2: assessment details. --}}
@extends('student.layout')
@section('title', 'Assessment Details')
@section('heading', $assessment->assessment_code)
@section('content')
    @include('admin._status')
    <div class="card mb-5"><div class="card-body">
        <dl class="row mb-3">
            <dt class="col-sm-3">Subject</dt><dd class="col-sm-9">{{ optional($assessment->subject)->subject_code }} — {{ optional($assessment->subject)->subject_name }}</dd>
            <dt class="col-sm-3">Questions</dt><dd class="col-sm-9">{{ $questionCount }}</dd>
            <dt class="col-sm-3">Time limit</dt><dd class="col-sm-9">{{ $assessment->time_limit }} min</dd>
            <dt class="col-sm-3">Window</dt><dd class="col-sm-9">{{ $assessment->start_date?->format('Y-m-d') }} {{ $assessment->start_time }} → {{ $assessment->end_date?->format('Y-m-d') }} {{ $assessment->end_time }}</dd>
            <dt class="col-sm-3">Availability</dt><dd class="col-sm-9">{{ $availability['badge'] }}</dd>
            <dt class="col-sm-3">Attempts left</dt><dd class="col-sm-9">{{ $availability['remaining'] }}</dd>
            <dt class="col-sm-3">Retakes</dt><dd class="col-sm-9">{{ $assessment->allow_retake ? 'Allowed' : 'No' }}</dd>
        </dl>
        @if ($availability['can_take'] && $questionCount > 0)
            <a href="{{ route('student.take-quiz', $assessment) }}" class="btn btn-primary">Start quiz</a>
        @endif
        <a href="{{ route('student.assessments.index') }}" class="btn btn-light">Back</a>
    </div></div>

    <div class="card"><div class="card-body">
        <h4 class="mb-3">Attempt history</h4>
        <table class="table table-row-dashed fs-6">
            <thead><tr class="text-gray-500 fw-bold text-uppercase fs-7"><th>Score</th><th>Result</th><th>Submitted</th><th></th></tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($attempts as $att)
                    <tr>
                        <td>{{ $att->score }}/{{ $att->total_questions }}</td>
                        <td><span class="badge badge-light-{{ $att->is_passed ? 'success' : 'danger' }}">{{ ucfirst($att->status) }}</span></td>
                        <td>{{ optional($att->submitted_at)->format('Y-m-d H:i') ?? 'In progress' }}</td>
                        <td>@if (in_array($att->status, ['passed','failed','submitted']))<a href="{{ route('student.scores.show', $att) }}" class="btn btn-sm btn-light">View</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No attempts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
