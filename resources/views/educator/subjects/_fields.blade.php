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
<div class="grid grid-cols-2 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Code</label>
        <input name="subject_code" class="kt-input" value="{{ $code }}">
        @error('subject_code')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Name</label>
        <input name="subject_name" class="kt-input" value="{{ $name }}">
        @error('subject_name')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="1" @selected($active==1)>Active</option>
            <option value="0" @selected($active==0)>Inactive</option>
        </select>
    </div>
    <div class="flex flex-col gap-1.5 col-span-2">
        <label class="kt-form-label">Sections <span class="text-secondary-foreground font-normal">(one subject row per section)</span></label>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($sections as $section)
                <x-checkbox-card
                    name="section_ids[]"
                    :value="$section->id"
                    :title="$section->section_name"
                    :checked="in_array($section->id, $selectedSections)" />
            @endforeach
        </div>
        @error('section_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>
