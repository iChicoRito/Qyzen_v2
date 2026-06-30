@php
    $editing = isset($group);
    $selectedSections = old('section_ids', $editing ? $group->pluck('sections_id')->all() : []);
    $code = old('subject_code', $editing ? $group->first()->subject_code : '');
    $name = old('subject_name', $editing ? $group->first()->subject_name : '');
    $active = old('is_active', $editing ? $group->first()->is_active : true);
@endphp
@if ($editing)
    @foreach ($group as $row)
        <input type="hidden" name="row_ids[]" value="{{ $row->id }}">
    @endforeach
@endif
<div class="row g-4">
    <div class="col-md-6"><label class="form-label required">Code</label>
        <input name="subject_code" class="form-control" value="{{ $code }}"></div>
    <div class="col-md-6"><label class="form-label required">Name</label>
        <input name="subject_name" class="form-control" value="{{ $name }}"></div>
    <div class="col-md-6"><label class="form-label required">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected($active==1)>Active</option>
            <option value="0" @selected($active==0)>Inactive</option>
        </select></div>
    <div class="col-12"><label class="form-label required">Sections (one subject row created per section)</label>
        <div class="d-flex flex-wrap gap-4">
            @foreach ($sections as $section)
                <label class="form-check form-check-custom">
                    <input class="form-check-input" type="checkbox" name="section_ids[]" value="{{ $section->id }}" @checked(in_array($section->id, $selectedSections))>
                    <span class="form-check-label">{{ $section->section_name }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
