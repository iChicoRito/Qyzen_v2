{{-- Shared academic-term form fields. $term null on create. --}}
<div class="grid grid-cols-2 gap-5">
    <div class="flex flex-col gap-1"><label class="kt-form-label">Term Name</label>
        <input name="term_name" class="kt-input" value="{{ old('term_name', $term?->term_name) }}" placeholder="e.g. Prelim">
        @error('term_name')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror</div>
    <div class="flex flex-col gap-1"><label class="kt-form-label">Semester</label>
        <select name="semester" class="kt-select">
            @foreach (['1st Semester', '2nd Semester'] as $sem)
                <option value="{{ $sem }}" @selected(old('semester', $term?->semester)===$sem)>{{ $sem }}</option>
            @endforeach
        </select>
        @error('semester')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror</div>
    <div class="flex flex-col gap-1"><label class="kt-form-label">Academic Year</label>
        <select name="academic_year_id" class="kt-select">
            @foreach ($years as $year)
                <option value="{{ $year->id }}" @selected((int) old('academic_year_id', $term?->academic_year_id)===$year->id)>{{ $year->year }}</option>
            @endforeach
        </select>
        @error('academic_year_id')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror</div>
    <div class="flex flex-col gap-1"><label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="1" @selected(old('is_active', $term?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $term?->is_active ?? true)==0)>Inactive</option>
        </select>
        @error('is_active')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror</div>
</div>
