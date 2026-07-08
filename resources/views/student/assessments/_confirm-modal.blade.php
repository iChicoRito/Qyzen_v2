{{-- Task 17: per-card confirmation modal. Static KTUI modal (enrollment-import pattern),
     toggled by the card's "Take Assessment" button. $a carries ->availability + ->pool_size.
     ponytail: one modal per card — fine for the handful a student sees. --}}
@php
    $av = $a->availability;
    $color = $a->status_color;
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '—';
@endphp
<div class="kt-modal kt-modal-center" data-kt-modal="true" id="kt_take_{{ $a->id }}">
    <div class="kt-modal-content" style="width: 100%; max-width: min(92vw, 600px); max-height: 86vh;">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title">Take Assessment</h3>
            <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true"><i class="ki-filled ki-cross"></i></button>
        </div>
        <div class="kt-modal-body kt-scrollable-y flex flex-col gap-5">
            {{-- 1. Identity / summary --}}
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-base font-medium text-mono">{{ $a->assessment_code }}</span>
                    <span class="kt-badge kt-badge-sm rounded-full kt-badge-outline kt-badge-{{ $color }} gap-1 items-center">
                        <span class="kt-badge-dot size-1.5"></span>{{ $a->status_label }}
                    </span>
                </div>
                <span class="text-sm text-secondary-foreground">
                    {{ optional($a->subject)->subject_code }} — {{ optional($a->subject)->subject_name }}
                    @if ($a->section) · {{ $a->section->section_name }} @endif
                </span>
                <span class="text-sm text-secondary-foreground">{{ $a->pool_size }} question(s) · {{ $a->time_limit }} min time limit</span>
            </div>

            {{-- 2. Schedule --}}
            <div class="grid gap-2 rounded-lg border border-border p-3.5">
                <div class="flex items-center justify-between gap-2 text-sm">
                    <span class="text-secondary-foreground">Opens</span>
                    <span class="text-mono">{{ $a->start_date?->format('M d, Y') }} · {{ $fmtTime($a->start_time) }}</span>
                </div>
                <div class="border-t border-border border-dashed"></div>
                <div class="flex items-center justify-between gap-2 text-sm">
                    <span class="text-secondary-foreground">Closes</span>
                    <span class="text-mono">{{ $a->end_date?->format('M d, Y') }} · {{ $fmtTime($a->end_time) }}</span>
                </div>
            </div>

            {{-- 3. Policies (receipt layout, matching the Schedule box) --}}
            <div class="grid gap-2">
                <h4 class="text-sm font-semibold text-mono">Policies</h4>
                <div class="grid gap-2 rounded-lg border border-border p-3.5 text-sm">
                    <div class="flex items-center justify-between gap-2"><span class="text-secondary-foreground">Attempts left</span><span class="text-mono">{{ $av['remaining'] }}</span></div>
                    <div class="border-t border-border border-dashed"></div>
                    <div class="flex items-center justify-between gap-2"><span class="text-secondary-foreground">Retakes</span><span class="text-mono">{{ $a->allow_retake ? $a->retake_count.' allowed' : 'Not allowed' }}</span></div>
                    <div class="border-t border-border border-dashed"></div>
                    <div class="flex items-center justify-between gap-2"><span class="text-secondary-foreground">Review answers</span><span class="text-mono">{{ $a->allow_review ? 'Yes, after submit' : 'No' }}</span></div>
                    <div class="border-t border-border border-dashed"></div>
                    <div class="flex items-center justify-between gap-2"><span class="text-secondary-foreground">Hints</span><span class="text-mono">{{ $a->allow_hint ? $a->hint_count.' per attempt' : 'None' }}</span></div>
                    <div class="border-t border-border border-dashed"></div>
                    <div class="flex items-center justify-between gap-2"><span class="text-secondary-foreground">Question order</span><span class="text-mono">{{ $a->is_shuffle ? 'Shuffled' : 'Fixed' }}</span></div>
                    <div class="border-t border-border border-dashed"></div>
                    <div class="flex items-center justify-between gap-2"><span class="text-secondary-foreground">Warning limit</span><span class="text-mono">{{ $a->cheating_attempts }}</span></div>
                </div>
            </div>

            {{-- 4. Rules / instructions --}}
            <div class="grid gap-2">
                <h4 class="text-sm font-semibold text-mono">Rules &amp; instructions</h4>
                <ul class="flex flex-col gap-1.5 text-sm text-secondary-foreground list-disc ps-5">
                    <li>The timer starts the moment you begin — you have <span class="text-mono">{{ $a->time_limit }}</span> minutes.</li>
                    <li>Your answers autosave as you go; you can resume an in-progress attempt.</li>
                    <li>Do not leave or switch tabs. Up to <span class="text-mono">{{ $a->cheating_attempts }}</span> warning(s) are allowed before your attempt is flagged.</li>
                    <li>Submit before the assessment window closes — a late attempt won't be accepted.</li>
                    @if ($a->is_shuffle)<li>Questions are shuffled, so your order may differ from classmates.</li>@endif
                </ul>
                <div class="rounded-lg border border-border bg-accent p-3 text-sm">
                    <span class="font-medium text-primary">Tip:</span>
                    <span class="text-secondary-foreground">You need ≥75% to pass. Read each question carefully and answer everything — blanks count as wrong.</span>
                </div>
                <div class="rounded-lg border border-border bg-accent p-3 text-sm">
                    <span class="font-medium text-destructive">Warning:</span>
                    <span class="text-secondary-foreground">Once started, this counts as an attempt. Make sure you have a stable connection and enough time before you begin.</span>
                </div>
            </div>
        </div>
        <div class="kt-modal-footer justify-end gap-2">
            <div class="flex items-center gap-2">
                <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
                @if ($a->startable)
                    <a href="{{ route('student.take-quiz', $a) }}" class="kt-btn kt-btn-primary"><i class="ki-filled ki-rocket"></i> Start Assessment</a>
                @else
                    @php
                        $reason = $a->pool_size === 0 ? 'Not ready yet — no questions'
                            : ($av['badge'] === 'Upcoming' ? 'Not yet open'
                            : ($av['badge'] === 'Expired' ? 'No longer available'
                            : ($av['remaining'] <= 0 ? 'No attempts remaining' : 'Not available right now')));
                    @endphp
                    <span class="text-sm text-destructive font-medium">{{ $reason }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
