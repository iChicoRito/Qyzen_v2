{{-- Shared academic-term form fields. $term null on create. --}}
<div class="row g-4">
    <div class="col-md-6"><label class="form-label required">Term Name</label>
        <input name="term_name" class="form-control" value="{{ old('term_name', $term?->term_name) }}" placeholder="e.g. Prelim"></div>
    <div class="col-md-6"><label class="form-label required">Semester</label>
        <select name="semester" class="form-select">
            @foreach (['1st Semester', '2nd Semester'] as $sem)
                <option value="{{ $sem }}" @selected(old('semester', $term?->semester)===$sem)>{{ $sem }}</option>
            @endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label required">Academic Year</label>
        <select name="academic_year_id" class="form-select">
            @foreach ($years as $year)
                <option value="{{ $year->id }}" @selected((int) old('academic_year_id', $term?->academic_year_id)===$year->id)>{{ $year->year }}</option>
            @endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label required">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected(old('is_active', $term?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $term?->is_active ?? true)==0)>Inactive</option>
        </select></div>
</div>
