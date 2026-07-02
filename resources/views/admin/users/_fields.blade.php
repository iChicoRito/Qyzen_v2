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
        @error('user_type')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">User ID</label>
        <input name="user_id" class="kt-input" value="{{ old('user_id', $user?->user_id) }}" placeholder="YYYY-NNNNN">
        @error('user_id')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Given Name</label>
        <input name="given_name" class="kt-input" value="{{ old('given_name', $user?->given_name) }}">
        @error('given_name')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Surname</label>
        <input name="surname" class="kt-input" value="{{ old('surname', $user?->surname) }}">
        @error('surname')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Email</label>
        <input name="email" type="email" class="kt-input" value="{{ old('email', $user?->email) }}">
        @error('email')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Status</label>
        <select name="is_active" class="kt-select">
            <option value="1" @selected(old('is_active', $user?->is_active ?? true)==1)>Active</option>
            <option value="0" @selected(old('is_active', $user?->is_active ?? true)==0)>Inactive</option>
        </select>
        @error('is_active')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
    <div class="flex flex-col gap-1.5 col-span-2">
        <label class="kt-form-label">Roles</label>
        <div class="grid grid-cols-1 gap-2">
            @foreach ($roles as $role)
                <x-checkbox-card
                    name="role_names[]"
                    :value="$role->name"
                    :title="ucfirst($role->name)"
                    :desc="$roleBlurbs[strtolower($role->name)] ?? ($role->description ?: 'Custom role.')"
                    :checked="in_array($role->name, $selectedRoles)" />
            @endforeach
        </div>
        @error('role_names')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>
</div>
