{{-- H7: result + per-question review. Correct answer is shown only when the server permitted it
     (allow_review OR the student was correct) — $row['correct_answer'] is null otherwise.
     Fragment inside the shared modal under ?modal=1. Layout mirrors demo1 teams team card. --}}
@php
    $isModal = request()->boolean('modal');
    $pct = $score->total_questions ? round($score->score / $score->total_questions * 100) : 0;
@endphp
@extends($isModal ? 'layouts.fragment' : 'student.layout')
@section('title', 'Result')
@section('heading', 'Result')
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content grid gap-7 py-7.5">
            {{-- Centered identity --}}
            <div class="grid place-items-center gap-4">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-award text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ optional($score->assessment)->assessment_code ?? 'Attempt' }}</span>
                    <span class="text-sm text-secondary-foreground text-center">Pass mark ≥ 75%</span>
                </div>
            </div>

            {{-- Summary rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Score</span>
                    <span class="text-sm text-mono font-semibold">{{ $score->score }}/{{ $score->total_questions }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Percentage</span>
                    <span class="text-sm text-mono font-semibold">{{ $pct }}%</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Result</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $score->is_passed ? 'success' : 'destructive' }}">{{ $score->is_passed ? 'Passed' : 'Failed' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap mt-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Warnings</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $score->warning_attempts > 0 ? 'destructive' : 'secondary' }}">{{ $score->warning_attempts }}</span>
                </div>
            </div>

            @if ($attempts->count() > 1)
                <div class="flex items-center gap-2 flex-wrap justify-center">
                    <span class="text-xs text-secondary-foreground uppercase">Attempts</span>
                    @foreach ($attempts as $att)
                        <a href="{{ route('student.scores.show', $att) }}"
                           @if ($isModal) data-modal-url="{{ route('student.scores.show', $att) }}" data-modal-target="#form_modal" data-modal-title="Result" @endif
                           class="kt-btn kt-btn-sm {{ $att->id === $score->id ? 'kt-btn-primary' : 'kt-btn-outline' }}">
                            {{ $att->score }}/{{ $att->total_questions }}{{ $att->id === $score->id ? ' (this)' : '' }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Review --}}
            <div class="grid gap-3">
                <h4 class="text-sm font-semibold text-mono">Review</h4>
                @foreach ($review as $i => $r)
                    <div class="rounded-lg border border-input p-3">
                        <p class="text-sm font-medium text-mono mb-2">{{ $i + 1 }}. {{ $r['question'] }}</p>
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="text-secondary-foreground">Your answer:</span>
                            <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-{{ $r['is_correct'] ? 'success' : 'destructive' }}">{{ $r['given'] ?? '—' }}</span>
                            @if (! is_null($r['correct_answer']))
                                <span class="text-secondary-foreground">Correct:</span>
                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-success">{{ $r['correct_answer'] }}</span>
                            @elseif (! $r['is_correct'])
                                <span class="text-xs text-secondary-foreground">Correct answer hidden (review not enabled).</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="kt-card-footer justify-end">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
            @else
                <a href="{{ route('student.scores.index') }}" class="kt-btn kt-btn-outline">Back to scores</a>
            @endif
        </div>
    </div>
@endsection
