@php $a = $assessment ?? null; @endphp
@php $editing = $a !== null; @endphp
<div class="flex flex-col gap-6">
<div class="flex flex-col gap-5">
    <div class="grid grid-cols-2 gap-5">
        <div class="flex flex-col gap-1">
            <label class="kt-form-label">Assessment Name</label>
            <input name="assessment_code" class="kt-input" value="{{ old('assessment_code', $a?->assessment_code) }}" required>
            @error('assessment_code')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
        </div>
        <div class="flex flex-col gap-1">
            <label class="kt-form-label">Term</label>
            <select name="term" class="kt-select">
                @foreach ($terms as $t)<option value="{{ $t->id }}" @selected(old('term', $a?->term)==$t->id)>{{ $t->term_name }} — {{ $t->semester }}</option>@endforeach
            </select>
        </div>
    </div>

    {{-- Pick one or more subjects; each carries its own section. On edit, the current subject
         is locked (kept) and any extra checked subjects become NEW assessments.
         Native <details> keeps the long list collapsed so it doesn't stretch the modal
         (no KTUI reinit needed — works as-is inside the innerHTML-injected fragment). --}}
    @php
        $currentSubjectId = $editing ? $a->subject_id : null;
        $selectedSubjects = old('subject_ids', $editing ? [$currentSubjectId] : []);
        $selectedCount = count($selectedSubjects);
    @endphp
    <div class="flex flex-col gap-1.5">
        <label class="kt-form-label">Select Subject Designated in the Section</label>
        @if ($editing)
            {{-- Guarantees the current subject always posts even though its checkbox is disabled. --}}
            <input type="hidden" name="subject_ids[]" value="{{ $currentSubjectId }}">
        @endif
        <details class="rounded-lg border border-border" @if($errors->has('subject_ids')) open @endif>
            <summary class="flex items-center justify-between gap-2 px-4 py-3 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden">
                <span class="text-sm text-mono" data-subject-summary data-subject-summary-default="Select subject and section options">
                    {{ $selectedCount ? $selectedCount . ' selected' : 'Select subject and section options' }}
                </span>
                <i class="ki-filled ki-down text-sm text-muted-foreground"></i>
            </summary>
            <div class="grid grid-cols-1 gap-2.5 p-3 pt-0 max-h-72 overflow-y-auto kt-scrollable-y">
                @foreach ($subjects as $s)
                    @php $isCurrent = $editing && $s->id === $currentSubjectId; @endphp
                    <x-checkbox-card
                        name="subject_ids[]"
                        :value="$s->id"
                        :title="$s->subject_name . ($isCurrent ? ' (current)' : '')"
                        :desc="$s->subject_code . ' | ' . ($s->section?->section_name ?? '—') . ' · ' . $terms->count() . ' academic terms available'"
                        :checked="in_array($s->id, $selectedSubjects)"
                        data-subject-option
                        :disabled="$isCurrent ?: null" />
                @endforeach
            </div>
        </details>
        @error('subject_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>

<h4 class="kt-form-label text-mono border-b border-border pb-2">Timing</h4>
<div class="grid grid-cols-2 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Start Date</label>
        <input type="text" name="start_date" class="kt-input cursor-pointer" value="{{ old('start_date', optional($a?->start_date)->format('Y-m-d')) }}" required data-flatpickr-date>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">End Date</label>
        <input type="text" name="end_date" class="kt-input cursor-pointer" value="{{ old('end_date', optional($a?->end_date)->format('Y-m-d')) }}" required data-flatpickr-date>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Start Time</label>
        <input type="text" name="start_time" class="kt-input cursor-pointer" value="{{ old('start_time', $a?->start_time) }}" required data-flatpickr-time>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">End Time</label>
        <input type="text" name="end_time" class="kt-input cursor-pointer" value="{{ old('end_time', $a?->end_time) }}" required data-flatpickr-time>
    </div>
</div>

@php
    $retakeOn = (bool) old('allow_retake', $a?->allow_retake ?? false);
    $hintOn   = (bool) old('allow_hint', $a?->allow_hint ?? false);
@endphp
<h4 class="kt-form-label text-mono border-b border-border pb-2">Rules</h4>
<div class="flex flex-col gap-2.5">
    <div class="grid grid-cols-2 gap-5 mb-2.5">
        <div class="flex flex-col gap-1">
            <label class="kt-form-label">Time Limit (minutes)</label>
            <input name="time_limit" class="kt-input" value="{{ old('time_limit', $a?->time_limit) }}" required>
            @error('time_limit')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
        </div>
        <div class="flex flex-col gap-1">
            <label class="kt-form-label">Cheating Attempts</label>
            <input type="number" name="cheating_attempts" class="kt-input" value="{{ old('cheating_attempts', $a?->cheating_attempts ?? 0) }}">
        </div>
    </div>

    {{-- Simple toggles (no dependent field). --}}
    <input type="hidden" name="is_shuffle" value="0">
    <x-checkbox-card variant="switch" :icon="null" name="is_shuffle" value="1"
        title="Shuffle questions" desc="Randomize question order per student"
        :checked="(bool) old('is_shuffle', $a?->is_shuffle ?? false)" />

    <input type="hidden" name="allow_review" value="0">
    <x-checkbox-card variant="switch" :icon="null" name="allow_review" value="1"
        title="Allow review" desc="Let students review answers after submitting"
        :checked="(bool) old('allow_review', $a?->allow_review ?? false)" />

    {{-- Allow retake → reveals Retake Count. --}}
    <input type="hidden" name="allow_retake" value="0">
    <x-checkbox-card variant="switch" :icon="null" name="allow_retake" value="1"
        title="Allow retake" desc="Permit retaking the assessment"
        :checked="$retakeOn" data-reveal="#asm_retake_count" />
    <div id="asm_retake_count" class="flex flex-col gap-1 ps-4 @unless($retakeOn) hidden @endunless">
        <label class="kt-form-label">Retake Count</label>
        <input type="number" name="retake_count" class="kt-input" value="{{ old('retake_count', $a?->retake_count ?? 0) }}">
    </div>

    {{-- Allow hints → reveals Hint Count. --}}
    <input type="hidden" name="allow_hint" value="0">
    <x-checkbox-card variant="switch" :icon="null" name="allow_hint" value="1"
        title="Allow hints" desc="Show hints during the assessment"
        :checked="$hintOn" data-reveal="#asm_hint_count" />
    <div id="asm_hint_count" class="flex flex-col gap-1 ps-4 @unless($hintOn) hidden @endunless">
        <label class="kt-form-label">Hint Count</label>
        <input type="number" name="hint_count" class="kt-input" value="{{ old('hint_count', $a?->hint_count ?? 0) }}">
    </div>
</div>

<h4 class="kt-form-label text-mono border-b border-border pb-2">Publish</h4>
<div class="grid grid-cols-1 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="0" @selected(old('is_active', $a?->is_active ?? false)==0)>Inactive (draft)</option>
            <option value="1" @selected(old('is_active', $a?->is_active ?? false)==1)>Active (publish + notify)</option>
        </select>
    </div>
</div>
</div>
