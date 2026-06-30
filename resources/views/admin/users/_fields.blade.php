{{-- Shared user form fields (create + edit). $user is null on create. --}}
@php $selectedRoles = old('role_names', $user ? $user->roles->pluck('name')->all() : []); @endphp
<div class="row g-4">
    <div class="col-md-6">
        <label class="form-label required">User Type</label>
        <select name="user_type" class="form-select">
            @foreach (['student','educator','admin'] as $t)
                <option value="{{ $t }}" @selected(old('user_type', $user?->user_type)===$t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label required">User ID</label>
        <input name="user_id" class="form-control" value="{{ old('user_id', $user?->user_id) }}" placeholder="YYYY-NNNNN">
    </div>
    <div class="col-md-6">
        <label class="form-label required">Given Name</label>
        <input name="given_name" class="form-control" value="{{ old('given_name', $user?->given_name) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label required">Surname</label>
        <input name="surname" class="form-control" value="{{ old('surname', $user?->surname) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label required">Email</label>
        <input name="email" type="email" class="form-control" value="{{ old('email', $user?->email) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label required">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" @selected(old('is_active', $user?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $user?->is_active ?? true)==0)>Inactive</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label required">Roles</label>
        <div class="d-flex flex-wrap gap-4">
            @foreach ($roles as $role)
                <label class="form-check form-check-custom">
                    <input class="form-check-input" type="checkbox" name="role_names[]" value="{{ $role->name }}"
                        @checked(in_array($role->name, $selectedRoles))>
                    <span class="form-check-label">{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
