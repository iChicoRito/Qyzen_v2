{{-- Shared role form fields. $role null on create. --}}
@php $selectedPerms = old('permission_ids', $role ? $role->permissions->pluck('id')->all() : []); @endphp
<div class="row g-4">
    <div class="col-md-6">
        <label class="form-label required">Name</label>
        <input name="name" class="form-control" value="{{ old('name', $role?->name) }}" placeholder="lower_snake_case">
    </div>
    <div class="col-md-6">
        <label class="form-label required">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected(old('is_active', $role?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $role?->is_active ?? true)==0)>Inactive</option>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">System role</label>
        <select name="is_system" class="form-select">
            <option value="0" @selected(old('is_system', $role?->is_system ?? false)==0)>No</option>
            <option value="1" @selected(old('is_system', $role?->is_system ?? false)==1)>Yes</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $role?->description) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Permissions</label>
        <div class="d-flex flex-wrap gap-4">
            @foreach ($permissions as $perm)
                <label class="form-check form-check-custom">
                    <input class="form-check-input" type="checkbox" name="permission_ids[]" value="{{ $perm->id }}"
                        @checked(in_array($perm->id, $selectedPerms))>
                    <span class="form-check-label">{{ $perm->permission_string }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
