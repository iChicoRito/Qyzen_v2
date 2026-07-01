{{-- Shared user form fields (create + edit). $user is null on create. --}}
@php
    $selectedRoles = old('role_names', $user ? $user->roles->pluck('name')->all() : []);
    // ponytail: inline fallback blurbs for roles that have no DB description.
    $roleBlurbs = [
        'admin'    => 'Full access — manage users, roles, and settings.',
        'educator' => 'Create assessments, quizzes, and grade students.',
        'student'  => 'Enroll in sections and take assigned quizzes.',
    ];
@endphp
<div class="grid grid-cols-2 gap-5">
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">User Type</label>
        <select name="user_type" class="kt-select">
            @foreach (['student','educator','admin'] as $t)
                <option value="{{ $t }}" @selected(old('user_type', $user?->user_type)===$t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">User ID</label>
        <input name="user_id" class="kt-input" value="{{ old('user_id', $user?->user_id) }}" placeholder="YYYY-NNNNN">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Given Name</label>
        <input name="given_name" class="kt-input" value="{{ old('given_name', $user?->given_name) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Surname</label>
        <input name="surname" class="kt-input" value="{{ old('surname', $user?->surname) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Email</label>
        <input name="email" type="email" class="kt-input" value="{{ old('email', $user?->email) }}">
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="1" @selected(old('is_active', $user?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $user?->is_active ?? true)==0)>Inactive</option>
        </select>
    </div>
    <div class="flex flex-col gap-1.5 col-span-2">
        <label class="kt-form-label">Roles</label>
        <div class="grid grid-cols-1 gap-2">
            @foreach ($roles as $role)
                <label class="flex items-start gap-2 border border-border rounded-lg p-3 cursor-pointer">
                    <input class="kt-checkbox kt-checkbox-sm mt-1" type="checkbox" name="role_names[]" value="{{ $role->name }}"
                        @checked(in_array($role->name, $selectedRoles))>
                    <span class="flex flex-col gap-1">
                        <span class="text-sm font-medium text-mono">{{ ucfirst($role->name) }}</span>
                        <span class="text-xs text-secondary-foreground">{{ $roleBlurbs[strtolower($role->name)] ?? ($role->description ?: 'Custom role.') }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </div>
</div>
