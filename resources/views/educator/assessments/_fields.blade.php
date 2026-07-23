@php $a = $assessment ?? null; @endphp
@php $singleSubject = $a !== null; @endphp
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
                @foreach ($terms as $t)<option value="{{ $t->id }}" @selected(old('term', $a?->term)==$t->id)>{{ $t->term_name }} - {{ $t->semester }}</option>@endforeach
            </select>
        </div>
    </div>

    {{-- Create can pick multiple subjects. Edit and duplicate accept one subject only. --}}
    @php
        $currentSubjectId = $singleSubject ? $a->subject_id : null;
        $selectedSubjects = old('subject_ids', $singleSubject ? [$currentSubjectId] : []);
        $selectedCount = count($selectedSubjects);
        $selectedSubjectId = $selectedSubjects[0] ?? $currentSubjectId;
    @endphp
    <div class="flex flex-col gap-1.5">
        <label class="kt-form-label">Select Subject Designated in the Section</label>
        <details class="rounded-lg border border-border" @if($errors->has('subject_ids')) open @endif>
            <summary class="flex items-center justify-between gap-2 px-4 py-3 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden">
                <span class="text-sm text-mono" data-subject-summary data-subject-summary-default="Select subject and section options">
                    {{ $selectedCount ? $selectedCount . ' selected' : 'Select subject and section options' }}
                </span>
                <i class="ki-filled ki-down text-sm text-muted-foreground"></i>
            </summary>
            <div class="grid grid-cols-1 gap-2.5 p-3 pt-0 max-h-72 overflow-y-auto kt-scrollable-y">
                @foreach ($subjects as $s)
                    @php $isCurrent = $singleSubject && $s->id === $currentSubjectId; @endphp
                    @if ($singleSubject)
                        <label class="flex items-start gap-2 border border-border rounded-lg p-3 cursor-pointer w-full">
                            <input type="radio" name="subject_ids[]" value="{{ $s->id }}"
                                   class="kt-radio kt-radio-sm mt-1" @checked((int) $selectedSubjectId === $s->id) data-subject-option required>
                            <span class="flex flex-col gap-1">
                                <span class="text-sm font-medium text-mono">{{ $s->subject_name }}{{ $isCurrent ? ' (current)' : '' }}</span>
                                <span class="text-xs text-secondary-foreground">{{ $s->subject_code }} | {{ $s->section?->section_name ?? '-' }} - {{ $terms->count() }} academic terms available</span>
                            </span>
                        </label>
                    @else
                        <x-checkbox-card
                            name="subject_ids[]"
                            :value="$s->id"
                            :title="$s->subject_name"
                            :desc="$s->subject_code . ' | ' . ($s->section?->section_name ?? '-') . ' - ' . $terms->count() . ' academic terms available'"
                            :checked="in_array($s->id, $selectedSubjects)"
                            data-subject-option />
                    @endif
                @endforeach
            </div>
        </details>
        @error('subject_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
        @error('subject_ids.0')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
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

    {{-- Allow retake reveals Retake Count. --}}
    <input type="hidden" name="allow_retake" value="0">
    <x-checkbox-card variant="switch" :icon="null" name="allow_retake" value="1"
        title="Allow retake" desc="Permit retaking the assessment"
        :checked="$retakeOn" data-reveal="#asm_retake_count" />
    <div id="asm_retake_count" class="flex flex-col gap-1 ps-4 @unless($retakeOn) hidden @endunless">
        <label class="kt-form-label">Retake Count</label>
        <input type="number" name="retake_count" class="kt-input" value="{{ old('retake_count', $a?->retake_count ?? 0) }}">
    </div>

    {{-- Allow hints reveals Hint Count. --}}
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
        <select name="publish_mode" class="kt-select">
            @php
                $defaultPublishMode = old('publish_mode');
                if ($defaultPublishMode === null) {
                    $defaultPublishMode = old('is_active', $a?->is_active ?? false) ? 'active_notify' : 'inactive';
                }
            @endphp
            <option value="inactive" @selected($defaultPublishMode === 'inactive')>Inactive (draft)</option>
            <option value="active_notify" @selected($defaultPublishMode === 'active_notify')>Active (publish + notify)</option>
            <option value="active_silent" @selected($defaultPublishMode === 'active_silent')>Active (publish only - no notification)</option>
        </select>
    </div>
</div>
</div>
