{{-- Task 22 / H7: result + per-question review. The server decides visibility — $r['correct_answer']
     is null unless the server permitted it (allow_review OR the student was correct). is_correct is
     computed server-side (QuizGradingService). Two-panel: summary (left) + review (right). Renders
     as a fragment inside the shared modal under ?modal=1. --}}
@php
    $isModal = request()->boolean('modal');
    $a = $score->assessment;
    $pct = $score->total_questions ? round($score->score / $score->total_questions * 100) : 0;
    $incorrect = max(0, $score->total_questions - $score->score);
    $bestPct = $bestTotal ? round($bestScore / $bestTotal * 100) : 0;
    $ring = $score->is_passed ? '#22c55e' : '#ef4444';
    $isBest = $score->id === $bestAttemptId;
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';
    $fmtStamp = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('M d, Y g:i A') : '—';
    $norm = fn ($v) => mb_strtolower(trim((string) $v));

    // Retake details (all from the server-recomputed availability summary).
    $used = $summary['submitted_attempts'];
    $allowed = 1 + $summary['effective_retakes']; // first attempt + retakes
    $remaining = $summary['remaining'];
@endphp
@extends($isModal ? 'layouts.fragment' : 'student.layout')
@section('title', 'Result')
@section('heading', 'Result')
@section('content')
    {{-- The app serves Metronic's prebuilt CSS (no Tailwind JIT over Blade), so responsive/arbitrary
         grid utilities like lg:grid-cols-[320px_1fr] don't exist in the bundle. Define the two-column
         split with a scoped media query instead. Modal (narrow) intentionally stays single-column. --}}
    @unless ($isModal)
        @push('styles')
        <style nonce="{{ $cspNonce ?? '' }}">
            @media (min-width: 1024px) { .qz-result-grid { grid-template-columns: 320px minmax(0, 1fr); } }
        </style>
        @endpush
    @endunless
    @include('admin._status')
    <div class="grid gap-5 items-start {{ $isModal ? '' : 'qz-result-grid' }}">
        {{-- ============ Summary panel ============ --}}
        <div class="kt-card">
            <div class="kt-card-content p-5 grid gap-5">
                {{-- Header: subject · section · code · term --}}
                <div class="text-center grid gap-1">
                    <span class="text-base font-semibold text-mono">{{ optional($a->subject)->subject_name ?? 'Assessment' }}</span>
                    <span class="text-xs text-secondary-foreground">
                        {{ optional($a->section)->section_name ?? '—' }}
                        · {{ $a->assessment_code ?? '—' }}
                        · {{ optional($a->academicTerm)->term_name ?? '—' }}
                    </span>
                </div>

                {{-- Score dial (CSS conic ring). Sizing/inset use inline styles: the prebuilt Metronic
                     bundle lacks size-40 / inset-[14px], and this must also render inside the modal
                     (which has no @stack('styles')). --}}
                <div class="flex flex-col items-center gap-2">
                    <div class="relative rounded-full" style="width:10rem;height:10rem;background: conic-gradient({{ $ring }} {{ $pct }}%, var(--color-accent, #e4e6ef) 0);">
                        <div class="absolute rounded-full bg-background flex flex-col items-center justify-center" style="inset:14px;">
                            <span class="text-3xl font-semibold text-mono">{{ $pct }}%</span>
                            <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-{{ $score->is_passed ? 'success' : 'destructive' }} mt-1">{{ $score->is_passed ? 'PASSED' : 'FAILED' }}</span>
                        </div>
                    </div>
                    <span class="text-xs font-medium uppercase tracking-wide text-secondary-foreground">Overall Score</span>
                </div>

                <div class="flex items-center justify-center gap-4 text-sm">
                    <span class="flex items-center gap-1.5"><span class="rounded-full" style="width:.625rem;height:.625rem;background:#22c55e"></span>{{ $score->score }} correct</span>
                    <span class="flex items-center gap-1.5"><span class="rounded-full" style="width:.625rem;height:.625rem;background:#ef4444"></span>{{ $incorrect }} incorrect</span>
                </div>

                {{-- Non-best note --}}
                @unless ($isBest)
                    <div class="rounded-lg border border-border border-dashed p-2.5 text-xs text-secondary-foreground text-center">
                        You are viewing a past attempt. Your best score is {{ $bestScore }}/{{ $bestTotal }} ({{ $bestPct }}%).
                    </div>
                @endunless

                {{-- Score snapshot --}}
                @php
                    $snapshot = [
                        ['Score', $score->score.'/'.$score->total_questions],
                        ['Percentage', $pct.'%'],
                        ['Best score', $bestScore.'/'.$bestTotal.' ('.$bestPct.'%)'],
                        ['Educator', optional($a->educator)->name ?? '—'],
                        ['Passing requirement', '≥ 70%'],
                    ];
                @endphp
                <div class="grid gap-2 rounded-lg border border-border p-3.5 text-sm">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Score snapshot</h4>
                    @foreach ($snapshot as [$label, $val])
                        <div class="border-t border-border border-dashed"></div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-secondary-foreground shrink-0">{{ $label }}</span>
                            <span class="text-mono text-end">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Retake details --}}
                @php
                    $retakeRows = [
                        ['Retakes allowed', $a->allow_retake ? 'Yes' : 'No'],
                        ['Attempts allowed', (string) $allowed],
                        ['Attempts used', (string) $used],
                        ['Attempts remaining', (string) $remaining],
                    ];
                @endphp
                <div class="grid gap-2 rounded-lg border border-border p-3.5 text-sm">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Retake details</h4>
                    @foreach ($retakeRows as [$label, $val])
                        <div class="border-t border-border border-dashed"></div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-secondary-foreground shrink-0">{{ $label }}</span>
                            <span class="text-mono text-end">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Assessment details + schedule --}}
                @php
                    $detailRows = [
                        ['Submitted', $fmtStamp($score->submitted_at)],
                        ['Time limit', ($a->time_limit ?? '—').' min'],
                        ['Shuffle', $a->is_shuffle ? 'On' : 'Off'],
                        ['Warnings used', (string) $score->warning_attempts],
                        ['Schedule', $a?->start_date?->format('M d, Y').' '.$fmtTime($a?->start_time).' → '.$a?->end_date?->format('M d, Y').' '.$fmtTime($a?->end_time)],
                    ];
                @endphp
                <div class="grid gap-2 rounded-lg border border-border p-3.5 text-sm">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Assessment details</h4>
                    @foreach ($detailRows as [$label, $val])
                        <div class="border-t border-border border-dashed"></div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-secondary-foreground shrink-0">{{ $label }}</span>
                            <span class="text-mono text-end">{{ $val }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <span class="text-secondary-foreground shrink-0">Status</span>
                        <span class="kt-badge kt-badge-sm kt-badge-{{ $score->is_passed ? 'success' : 'destructive' }}">{{ $score->is_passed ? 'PASSED' : 'FAILED' }}</span>
                    </div>
                </div>

                {{-- Plain-language result message --}}
                <div class="rounded-lg p-3.5 text-sm" style="{{ $score->is_passed ? 'background:rgba(34,197,94,.1);color:#15803d;' : 'background:rgba(239,68,68,.1);color:#b91c1c;' }}">
                    @if ($score->is_passed)
                        Nice work — you passed with {{ $pct }}%, meeting the ≥ 70% requirement.
                    @else
                        You scored {{ $pct }}%, which is below the ≥ 70% passing requirement.
                        {{ $remaining > 0 ? 'You still have attempts left — you can try again.' : 'No attempts remain for this assessment.' }}
                    @endif
                </div>
            </div>
        </div>

        {{-- ============ Details panel ============ --}}
        <div class="kt-card">
            <div class="kt-card-content p-5 grid gap-5">
                {{-- Attempt history --}}
                @if ($attempts->count() > 0)
                    <div class="grid gap-2">
                        <h4 class="text-sm font-semibold text-mono">Attempt history</h4>
                        <div class="grid gap-2">
                            @foreach ($attempts as $att)
                                @php
                                    $isCurrent = $att->id === $score->id;
                                    $attPct = $att->total_questions ? round($att->score / $att->total_questions * 100) : 0;
                                @endphp
                                <details class="rounded-lg border border-border {{ $isCurrent ? 'border-primary' : '' }}" {{ $isCurrent ? 'open' : '' }}>
                                    <summary class="flex items-center justify-between gap-3 p-3 cursor-pointer list-none">
                                        <span class="flex items-center gap-2 text-sm font-medium text-mono">
                                            Attempt {{ $attemptNumbers[$att->id] }}
                                            @if ($att->id === $bestAttemptId)
                                                <span class="kt-badge kt-badge-xs kt-badge-outline kt-badge-success">Highest Score</span>
                                            @endif
                                            @if ($isCurrent)
                                                <span class="kt-badge kt-badge-xs kt-badge-outline kt-badge-primary">Viewing</span>
                                            @endif
                                        </span>
                                        <span class="kt-badge kt-badge-sm kt-badge-{{ $att->is_passed ? 'success' : 'destructive' }}">{{ $att->is_passed ? 'PASSED' : 'FAILED' }}</span>
                                    </summary>
                                    <div class="border-t border-border p-3 grid gap-2 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-secondary-foreground">Score</span>
                                            <span class="text-mono">{{ $att->score }}/{{ $att->total_questions }} ({{ $attPct }}%)</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-secondary-foreground">Submitted</span>
                                            <span class="text-mono">{{ $fmtStamp($att->submitted_at) }}</span>
                                        </div>
                                        {{-- Task 23: the modal "View Score" window is look-back only —
                                             no attempt-switching (that belongs to the full-page results
                                             screen). History still lists all attempts; just no switch link. --}}
                                        @unless ($isCurrent || $isModal)
                                            <a href="{{ route('student.scores.show', $att) }}"
                                               class="kt-btn kt-btn-sm kt-btn-outline w-fit"><i class="ki-filled ki-eye"></i> View This Attempt</a>
                                        @endunless
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Question-by-question review — hidden entirely when the educator disabled review,
                     not just the correct_answer value (a disabled review must show nothing per-question). --}}
                <div class="grid gap-3">
                    <h4 class="text-sm font-semibold text-mono">Review</h4>
                    @unless ($allowReview)
                        <div class="text-sm text-secondary-foreground rounded-lg border border-border p-4">
                            Review is not enabled for this assessment.
                        </div>
                    @else
                    @foreach ($review as $i => $r)
                        <div class="grid gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-medium text-mono">{{ $i + 1 }}. {{ $r['question'] }}</p>
                                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-{{ $r['is_correct'] ? 'success' : 'destructive' }} shrink-0">{{ $r['is_correct'] ? 'Correct' : 'Incorrect' }}</span>
                            </div>

                            @if ($r['quiz_type'] === 'multiple_choice')
                                {{-- Every option, letter + text, colour-coded. The correct option is only
                                     revealed when the server exposed correct_answer (allow_review OR correct). --}}
                                <div class="grid gap-2">
                                    @foreach (($r['choices'] ?? []) as $key => $text)
                                        @php
                                            $letter = $key;
                                            $isGiven = $r['given'] !== null && ((string) $key === (string) $r['given'] || $norm($text) === $norm($r['given']));
                                            $isKey = ! is_null($r['correct_answer']) && ((string) $key === (string) $r['correct_answer'] || $norm($text) === $norm($r['correct_answer']));
                                            // Inline colours (bundle lacks border-success / bg-success/10 opacity variants).
                                            $green = 'border-color:#22c55e;background:rgba(34,197,94,.1);';
                                            $red = 'border-color:#ef4444;background:rgba(239,68,68,.1);';
                                            if ($isGiven && $r['is_correct']) {
                                                $ostyle = $green;
                                            } elseif ($isGiven) {
                                                $ostyle = $red;
                                            } elseif ($isKey) {
                                                $ostyle = $green;
                                            } else {
                                                $ostyle = 'border-color:var(--color-border, #e4e6ef);';
                                            }
                                        @endphp
                                        <div class="flex items-center gap-3 rounded-lg border p-3 text-sm" style="{{ $ostyle }}">
                                            <span class="flex items-center justify-center shrink-0 rounded-md bg-accent text-xs font-semibold text-secondary-foreground" style="width:1.75rem;height:1.75rem;">{{ $letter }}</span>
                                            <span class="text-mono">{{ $text }}</span>
                                            @if ($isGiven)<span class="kt-badge kt-badge-xs kt-badge-outline ms-auto">Your answer</span>@endif
                                        </div>
                                    @endforeach
                                    @if (is_null($r['given']))
                                        <span class="text-xs text-secondary-foreground">No answer submitted.</span>
                                    @endif
                                </div>
                            @else
                                {{-- Identification --}}
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="text-secondary-foreground">Your answer:</span>
                                    @if (is_null($r['given']) || trim((string) $r['given']) === '')
                                        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-secondary">No answer submitted</span>
                                    @else
                                        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-{{ $r['is_correct'] ? 'success' : 'destructive' }}">{{ $r['given'] }}</span>
                                    @endif
                                    @if (! is_null($r['correct_answer']))
                                        <span class="text-secondary-foreground">Correct:</span>
                                        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-success">{{ $r['correct_answer'] }}</span>
                                    @endif
                                </div>
                                @if (is_null($r['correct_answer']) && ! $r['is_correct'])
                                    <span class="text-xs text-secondary-foreground">Correct answer hidden (review not enabled).</span>
                                @endif
                            @endif
                        </div>
                    @endforeach
                    @endunless
                </div>
            </div>
            <div class="kt-card-footer justify-end gap-2">
                @if ($isModal)
                    <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
                @elseif ($summary['can_take'])
                    <a href="{{ route('student.assessments.index') }}" class="kt-btn kt-btn-outline">Back to Assessments</a>
                    <a href="{{ route('student.take-quiz', $a) }}" id="qz-retake" class="kt-btn kt-btn-primary"><i class="ki-filled ki-arrows-circle"></i> Retake Assessment</a>
                @else
                    <a href="{{ route('student.assessments.index') }}" class="kt-btn kt-btn-outline">Back to Assessments</a>
                @endif
            </div>
        </div>
    </div>

    @unless ($isModal)
        @push('scripts')
        <script nonce="{{ $cspNonce ?? '' }}">
            document.getElementById('qz-retake')?.addEventListener('click', function (e) {
                e.preventDefault();
                const url = this.href;
                Swal.fire({
                    icon: 'question', title: 'Retake assessment?',
                    text: 'A fresh attempt will start. Your previous attempts are kept.',
                    showCancelButton: true, confirmButtonText: 'Start fresh attempt', cancelButtonText: 'Cancel',
                }).then(r => { if (r.isConfirmed) window.location.href = url; });
            });
        </script>
        @endpush
    @endunless
@endsection
