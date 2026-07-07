{{-- Calendar event (assessment) detail. Renders as a bare fragment inside the shared #form_modal
     under ?modal=1, or as a full page otherwise. Layout mirrors admin/roles/show.blade.php. --}}
@php
    $isModal = request()->boolean('modal');
    $fmtTime = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('g:i A') : null;
    $opens = $assessment->start_date?->format('M j, Y').($fmtTime($assessment->start_time) ? ' · '.$fmtTime($assessment->start_time) : '');
    $closes = $assessment->end_date?->format('M j, Y').($fmtTime($assessment->end_time) ? ' · '.$fmtTime($assessment->end_time) : '');
@endphp
@extends($isModal ? 'layouts.fragment' : $layout)
@section('title', 'Assessment')
@section('heading', $assessment->assessment_code)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content grid gap-6 py-7.5">
            {{-- Identity --}}
            <div class="grid place-items-center gap-3">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-questionnaire-tablet text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ $assessment->assessment_code }}</span>
                    <span class="text-sm text-secondary-foreground text-center">{{ $assessment->subject?->subject_name ?? '—' }}</span>
                </div>
            </div>

            {{-- Detail rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Section</span>
                    <span class="text-sm text-mono">{{ $assessment->section?->section_name ?? '—' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 my-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Opens</span>
                    <span class="text-sm text-mono text-end">{{ $opens ?: '—' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 my-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Closes</span>
                    <span class="text-sm text-mono text-end">{{ $closes ?: '—' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 my-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Time limit</span>
                    <span class="text-sm text-mono">{{ $assessment->time_limit ? $assessment->time_limit.' min' : '—' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 my-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Term</span>
                    <span class="text-sm text-mono">{{ $assessment->academicTerm?->term_name ?? '—' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 mt-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Retakes</span>
                    <span class="text-sm text-mono">{{ $assessment->allow_retake ? $assessment->retake_count.' allowed' : 'Not allowed' }}</span>
                </div>
            </div>
        </div>
        <div class="kt-card-footer justify-end gap-2">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
            @else
                <a href="{{ $calendarRoute }}" class="kt-btn kt-btn-outline">Back to calendar</a>
            @endif
        </div>
    </div>
@endsection
