@php $a = $assessment ?? null; @endphp
<div class="grid grid-cols-3 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Code</label>
        <input name="assessment_code" class="kt-input" value="{{ old('assessment_code', $a?->assessment_code) }}">
        @error('assessment_code')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Subject</label>
        <select name="subject_id" class="kt-select">
            @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id', $a?->subject_id)==$s->id)>{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Section</label>
        <select name="section_id" class="kt-select">
            @foreach ($sections as $s)<option value="{{ $s->id }}" @selected(old('section_id', $a?->section_id)==$s->id)>{{ $s->section_name }}</option>@endforeach
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Term</label>
        <select name="term" class="kt-select">
            @foreach ($terms as $t)<option value="{{ $t->id }}" @selected(old('term', $a?->term)==$t->id)>{{ $t->term_name }} — {{ $t->semester }}</option>@endforeach
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Time Limit (minutes)</label>
        <input name="time_limit" class="kt-input" value="{{ old('time_limit', $a?->time_limit) }}">
        @error('time_limit')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Cheating Attempts</label>
        <input type="number" name="cheating_attempts" class="kt-input" value="{{ old('cheating_attempts', $a?->cheating_attempts ?? 0) }}">
    </div>

    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Start Date</label>
        <input type="date" name="start_date" class="kt-input" value="{{ old('start_date', optional($a?->start_date)->format('Y-m-d')) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">End Date</label>
        <input type="date" name="end_date" class="kt-input" value="{{ old('end_date', optional($a?->end_date)->format('Y-m-d')) }}">
    </div>
    <div class="flex flex-col gap-1"><span class="hidden"></span></div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Start Time</label>
        <input type="time" name="start_time" class="kt-input" value="{{ old('start_time', $a?->start_time) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">End Time</label>
        <input type="time" name="end_time" class="kt-input" value="{{ old('end_time', $a?->end_time) }}">
    </div>
    <div class="flex flex-col gap-1"><span class="hidden"></span></div>

    @php
        $toggles = [
            ['is_shuffle', 'Shuffle questions'], ['allow_review', 'Allow review'],
            ['allow_retake', 'Allow retake'], ['allow_hint', 'Allow hints'],
        ];
    @endphp
    @foreach ($toggles as [$field, $label])
        <div class="flex flex-col gap-1">
            <label class="kt-form-label">{{ $label }}</label>
            <select name="{{ $field }}" class="kt-select">
                <option value="0" @selected(old($field, $a?->$field ?? false)==0)>No</option>
                <option value="1" @selected(old($field, $a?->$field ?? false)==1)>Yes</option>
            </select>
        </div>
    @endforeach
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Retake Count</label>
        <input type="number" name="retake_count" class="kt-input" value="{{ old('retake_count', $a?->retake_count ?? 0) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Hint Count</label>
        <input type="number" name="hint_count" class="kt-input" value="{{ old('hint_count', $a?->hint_count ?? 0) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="0" @selected(old('is_active', $a?->is_active ?? false)==0)>Inactive (draft)</option>
            <option value="1" @selected(old('is_active', $a?->is_active ?? false)==1)>Active (publish + notify)</option>
        </select>
    </div>
</div>
