{{-- Shared role form fields. $role null on create. --}}
@php $selectedPerms = old('permission_ids', $role ? $role->permissions->pluck('id')->all() : []); @endphp
<div class="grid md:grid-cols-2 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Name</label>
        <input name="name" class="kt-input" value="{{ old('name', $role?->name) }}" placeholder="lower_snake_case">
        @error('name')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="1" @selected(old('is_active', $role?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $role?->is_active ?? true)==0)>Inactive</option>
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">System role</label>
        <select name="is_system" class="kt-select">
            <option value="0" @selected(old('is_system', $role?->is_system ?? false)==0)>No</option>
            <option value="1" @selected(old('is_system', $role?->is_system ?? false)==1)>Yes</option>
        </select>
    </div>
    <div class="flex flex-col gap-1 md:col-span-2">
        <label class="kt-form-label">Description</label>
        <textarea name="description" class="kt-textarea" rows="2">{{ old('description', $role?->description) }}</textarea>
        @error('description')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1 md:col-span-2">
        <label class="kt-form-label">Permissions</label>
        <div class="flex flex-wrap gap-4">
            @foreach ($permissions as $perm)
                <label class="kt-label">
                    <input class="kt-checkbox kt-checkbox-sm" type="checkbox" name="permission_ids[]" value="{{ $perm->id }}"
                        @checked(in_array($perm->id, $selectedPerms))>
                    <span class="kt-checkbox-label">{{ $perm->permission_string }}</span>
                </label>
            @endforeach
        </div>
        @error('permission_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>
