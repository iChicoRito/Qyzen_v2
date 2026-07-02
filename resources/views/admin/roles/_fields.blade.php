{{-- Shared role form fields. $role null on create. --}}
@php $selectedPerms = old('permission_ids', $role ? $role->permissions->pluck('id')->all() : []); @endphp
<div class="flex flex-col gap-5">
    <div class="grid grid-cols-3 gap-5">
        <div class="flex flex-col gap-1">
            <label class="kt-form-label">Name</label>
            <input name="name" class="kt-input" value="{{ old('name', $role?->name) }}" placeholder="lower_snake_case" required pattern="[a-z]+(_[a-z]+)*" title="Lowercase words separated by underscores">
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
    </div>
    <div class="flex flex-col gap-1 w-full">
        <label class="kt-form-label">Description</label>
        <textarea name="description" class="kt-textarea" rows="2">{{ old('description', $role?->description) }}</textarea>
        @error('description')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1.5 w-full">
        <label class="kt-form-label">Permissions</label>
        <div class="grid grid-cols-2 gap-2.5 w-full max-h-80 overflow-y-auto kt-scrollable-y pe-1">
            @foreach ($permissions as $perm)
                <x-checkbox-card
                    variant="switch"
                    name="permission_ids[]"
                    :value="$perm->id"
                    :title="$perm->permission_string"
                    :desc="$perm->description ?: $perm->module"
                    :checked="in_array($perm->id, $selectedPerms)" />
            @endforeach
        </div>
        @error('permission_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>
