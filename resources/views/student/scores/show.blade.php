{{-- H7 / Task 18: result + per-question review. correct_answer is shown only when the server
     permitted it (allow_review OR the student was correct) — $row['correct_answer'] is null
     otherwise. Two-panel: summary (left) + details (right). Fragment inside the shared modal
     under ?modal=1. --}}
@php
    $isModal = request()->boolean('modal');
    $a = $score->assessment;
    $pct = $score->total_questions ? round($score->score / $score->total_questions * 100) : 0;
    $incorrect = max(0, $score->total_questions - $score->score);
    $ring = $score->is_passed ? '#22c55e' : '#ef4444';
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';
@endphp
@extends($isModal ? 'layouts.fragment' : 'student.layout')
@section('title', 'Result')
@section('heading', 'Result')
@section('content')
    @include('admin._status')
    <div class="grid gap-5 lg:grid-cols-[320px_1fr] items-start">
        {{-- Summary panel --}}
        <div class="kt-card">
            <div class="kt-card-content p-5 grid gap-5">
                <div class="text-center">
                    <span class="text-base font-medium text-mono">{{ optional($a)->assessment_code ?? 'Attempt' }}</span>
                    <div class="text-xs text-secondary-foreground">Pass mark ≥ 75%</div>
                </div>

                {{-- Score dial (CSS conic ring) --}}
                <div class="flex justify-center">
                    <div class="relative size-40 rounded-full" style="background: conic-gradient({{ $ring }} {{ $pct }}%, var(--color-accent, #e4e6ef) 0);">
                        <div class="absolute inset-[14px] rounded-full bg-background flex flex-col items-center justify-center">
                            <span class="text-3xl font-semibold text-mono">{{ $pct }}%</span>
                            <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-{{ $score->is_passed ? 'success' : 'destructive' }} mt-1">{{ $score->is_passed ? 'Passed' : 'Failed' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-center gap-4 text-sm">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full" style="background:#22c55e"></span>{{ $score->score }} correct</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full" style="background:#ef4444"></span>{{ $incorrect }} incorrect</span>
                </div>

                {{-- Summary rows --}}
                <div class="grid gap-2 rounded-lg border border-border p-3.5 text-sm">
                    @php
                        $rows = [
                            ['Score', $score->score.'/'.$score->total_questions],
                            ['Best score', $bestScore.'/'.$score->total_questions],
                            ['Educator', optional(optional($a)->educator)->name ?? '—'],
                            ['Submitted', optional($score->submitted_at)->format('M d, Y g:i A') ?? '—'],
                            ['Time limit', ($a->time_limit ?? '—').' min'],
                            ['Shuffle', ($a && $a->is_shuffle) ? 'On' : 'Off'],
                            ['Warnings used', (string) $score->warning_attempts],
                            ['Schedule', ($a?->start_date?->format('M d, Y').' '.$fmtTime($a?->start_time).' → '.$a?->end_date?->format('M d, Y').' '.$fmtTime($a?->end_time))],
                        ];
                    @endphp
                    @foreach ($rows as $ri => [$label, $val])
                        @if ($ri > 0)<div class="border-t border-border border-dashed"></div>@endif
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-secondary-foreground shrink-0">{{ $label }}</span>
                            <span class="text-mono text-end">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Details panel --}}
        <div class="kt-card">
            <div class="kt-card-content p-5 grid gap-5">
                {{-- Attempt history --}}
                @if ($attempts->count() > 0)
                    <div class="grid gap-2">
                        <h4 class="text-sm font-semibold text-mono">Attempt history</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($attempts as $att)
                                <a href="{{ route('student.scores.show', $att) }}"
                                   @if ($isModal) data-modal-url="{{ route('student.scores.show', $att) }}" data-modal-target="#form_modal" data-modal-title="Result" @endif
                                   class="kt-btn kt-btn-sm {{ $att->id === $score->id ? 'kt-btn-primary' : 'kt-btn-outline' }}">
                                    {{ $att->score }}/{{ $att->total_questions }}
                                    <span class="kt-badge kt-badge-xs kt-badge-{{ $att->is_passed ? 'success' : 'destructive' }} ms-1">{{ $att->is_passed ? 'P' : 'F' }}</span>
                                    {{ $att->id === $score->id ? '(this)' : '' }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Question-by-question review --}}
                <div class="grid gap-3">
                    <h4 class="text-sm font-semibold text-mono">Review</h4>
                    @foreach ($review as $i => $r)
                        <div class="rounded-lg border border-border p-3">
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
            <div class="kt-card-footer justify-end gap-2">
                @if ($isModal)
                    <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
                @elseif ($summary['can_take'])
                    <a href="{{ route('student.assessments.index') }}" class="kt-btn kt-btn-outline">Back to Assessments</a>
                    <a href="{{ route('student.take-quiz', $a) }}" class="kt-btn kt-btn-primary"><i class="ki-filled ki-arrows-circle"></i> Retake Assessment</a>
                @else
                    <a href="{{ route('student.assessments.index') }}" class="kt-btn kt-btn-outline">Back to Assessments</a>
                @endif
            </div>
        </div>
    </div>
@endsection
