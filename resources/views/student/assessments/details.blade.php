{{-- H2: assessment details. --}}
@extends('student.layout')
@section('title', 'Assessment Details')
@section('heading', $assessment->assessment_code)
@section('content')
    @include('admin._status')
    <div class="kt-card mb-5"><div class="kt-card-content p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-2 gap-x-4 mb-4 text-sm">
            <dt class="text-secondary-foreground">Subject</dt><dd>{{ optional($assessment->subject)->subject_code }} — {{ optional($assessment->subject)->subject_name }}</dd>
            <dt class="text-secondary-foreground">Questions</dt><dd>{{ $questionCount }}</dd>
            <dt class="text-secondary-foreground">Time limit</dt><dd>{{ $assessment->time_limit }} min</dd>
            <dt class="text-secondary-foreground">Window</dt><dd>{{ $assessment->start_date?->format('Y-m-d') }} {{ $assessment->start_time }} → {{ $assessment->end_date?->format('Y-m-d') }} {{ $assessment->end_time }}</dd>
            <dt class="text-secondary-foreground">Availability</dt><dd>{{ $availability['badge'] }}</dd>
            <dt class="text-secondary-foreground">Attempts left</dt><dd>{{ $availability['remaining'] }}</dd>
            <dt class="text-secondary-foreground">Retakes</dt><dd>{{ $assessment->allow_retake ? 'Allowed' : 'No' }}</dd>
        </dl>
        <div class="flex gap-2">
            @if ($availability['can_take'] && $questionCount > 0)
                <a href="{{ route('student.take-quiz', $assessment) }}" class="kt-btn kt-btn-primary">Start quiz</a>
            @endif
            <a href="{{ route('student.assessments.index') }}" class="kt-btn kt-btn-outline">Back</a>
        </div>
    </div></div>

    <div class="kt-card"><div class="kt-card-content p-5">
        <h4 class="text-base font-medium text-mono mb-3">Attempt history</h4>
        <div class="kt-scrollable-x-auto">
            <table class="kt-table table-auto kt-table-border">
                <thead>
                    <tr>
                        <th class="min-w-[100px]">Score</th>
                        <th class="min-w-[110px]">Result</th>
                        <th class="min-w-[160px]">Submitted</th>
                        <th class="w-[100px]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $att)
                        <tr>
                            <td>{{ $att->score }}/{{ $att->total_questions }}</td>
                            <td>
                                <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $att->is_passed ? 'success' : 'destructive' }} gap-1 items-center">
                                    <span class="kt-badge-dot size-1.5"></span>{{ ucfirst($att->status) }}
                                </span>
                            </td>
                            <td class="text-secondary-foreground">{{ optional($att->submitted_at)->format('Y-m-d H:i') ?? 'In progress' }}</td>
                            <td class="text-end">@if (in_array($att->status, ['passed','failed','submitted']))<a href="{{ route('student.scores.show', $att) }}" class="kt-btn kt-btn-sm kt-btn-outline">View</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary-foreground py-5">No attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
@endsection
