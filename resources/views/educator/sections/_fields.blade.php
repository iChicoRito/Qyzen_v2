@php $selectedTerms = old('academic_term_ids', isset($section) ? $section->terms->pluck('id')->all() : []); @endphp
<div class="grid grid-cols-2 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Section Name</label>
        <input name="section_name" class="kt-input" value="{{ old('section_name', $section->section_name ?? '') }}" required>
        @error('section_name')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="1" @selected(old('is_active', $section->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $section->is_active ?? true)==0)>Inactive</option>
        </select>
    </div>
    <div class="flex flex-col gap-1.5 col-span-2">
        <label class="kt-form-label">Academic Terms</label>
        <div class="grid grid-cols-2 gap-2">
            @foreach ($terms as $term)
                <x-checkbox-card
                    name="academic_term_ids[]"
                    :value="$term->id"
                    :title="$term->term_name.' — '.$term->semester"
                    :desc="optional($term->year)->year"
                    :checked="in_array($term->id, $selectedTerms)" />
            @endforeach
        </div>
        @error('academic_term_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>
