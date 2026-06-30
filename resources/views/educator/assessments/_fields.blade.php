@php $a = $assessment ?? null; @endphp
<div class="row g-4">
    <div class="col-md-4"><label class="form-label required">Code</label>
        <input name="assessment_code" class="form-control" value="{{ old('assessment_code', $a?->assessment_code) }}"></div>
    <div class="col-md-4"><label class="form-label required">Subject</label>
        <select name="subject_id" class="form-select">
            @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id', $a?->subject_id)==$s->id)>{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label required">Section</label>
        <select name="section_id" class="form-select">
            @foreach ($sections as $s)<option value="{{ $s->id }}" @selected(old('section_id', $a?->section_id)==$s->id)>{{ $s->section_name }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label required">Term</label>
        <select name="term" class="form-select">
            @foreach ($terms as $t)<option value="{{ $t->id }}" @selected(old('term', $a?->term)==$t->id)>{{ $t->term_name }} — {{ $t->semester }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label required">Time Limit (minutes)</label>
        <input name="time_limit" class="form-control" value="{{ old('time_limit', $a?->time_limit) }}"></div>
    <div class="col-md-4"><label class="form-label">Cheating Attempts</label>
        <input type="number" name="cheating_attempts" class="form-control" value="{{ old('cheating_attempts', $a?->cheating_attempts ?? 0) }}"></div>

    <div class="col-md-3"><label class="form-label required">Start Date</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($a?->start_date)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label required">End Date</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($a?->end_date)->format('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label required">Start Time</label>
        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $a?->start_time) }}"></div>
    <div class="col-md-3"><label class="form-label required">End Time</label>
        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $a?->end_time) }}"></div>

    @php
        $toggles = [
            ['is_shuffle', 'Shuffle questions'], ['allow_review', 'Allow review'],
            ['allow_retake', 'Allow retake'], ['allow_hint', 'Allow hints'],
        ];
    @endphp
    @foreach ($toggles as [$field, $label])
        <div class="col-md-3"><label class="form-label">{{ $label }}</label>
            <select name="{{ $field }}" class="form-select">
                <option value="0" @selected(old($field, $a?->$field ?? false)==0)>No</option>
                <option value="1" @selected(old($field, $a?->$field ?? false)==1)>Yes</option>
            </select></div>
    @endforeach
    <div class="col-md-3"><label class="form-label">Retake Count</label>
        <input type="number" name="retake_count" class="form-control" value="{{ old('retake_count', $a?->retake_count ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Hint Count</label>
        <input type="number" name="hint_count" class="form-control" value="{{ old('hint_count', $a?->hint_count ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label required">Status (Active = published)</label>
        <select name="is_active" class="form-select">
            <option value="0" @selected(old('is_active', $a?->is_active ?? false)==0)>Inactive (draft)</option>
            <option value="1" @selected(old('is_active', $a?->is_active ?? false)==1)>Active (publish + notify)</option>
        </select></div>
</div>
