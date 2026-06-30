@php $selectedTerms = old('academic_term_ids', isset($section) ? $section->terms->pluck('id')->all() : []); @endphp
<div class="row g-4">
    <div class="col-md-6"><label class="form-label required">Section Name</label>
        <input name="section_name" class="form-control" value="{{ old('section_name', $section->section_name ?? '') }}"></div>
    <div class="col-md-6"><label class="form-label required">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected(old('is_active', $section->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $section->is_active ?? true)==0)>Inactive</option>
        </select></div>
    <div class="col-12"><label class="form-label required">Academic Terms</label>
        <div class="d-flex flex-wrap gap-4">
            @foreach ($terms as $term)
                <label class="form-check form-check-custom">
                    <input class="form-check-input" type="checkbox" name="academic_term_ids[]" value="{{ $term->id }}" @checked(in_array($term->id, $selectedTerms))>
                    <span class="form-check-label">{{ $term->term_name }} — {{ $term->semester }} ({{ optional($term->year)->year }})</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
