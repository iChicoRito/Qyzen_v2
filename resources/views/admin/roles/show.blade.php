{{-- F5: view role + assigned permissions. --}}
@extends('admin.layout')
@section('title', 'Role')
@section('heading', $role->name)
@section('content')
    <div class="kt-card"><div class="kt-card-content p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm mb-5">
            <dt class="text-secondary-foreground">Name</dt><dd class="text-mono">{{ $role->name }}</dd>
            <dt class="text-secondary-foreground">Description</dt><dd class="text-mono">{{ $role->description ?: '—' }}</dd>
            <dt class="text-secondary-foreground">System</dt><dd class="text-mono">{{ $role->is_system ? 'Yes' : 'No' }}</dd>
            <dt class="text-secondary-foreground">Status</dt><dd class="text-mono">{{ $role->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <h4 class="text-sm font-semibold text-mono mb-2.5">Permissions ({{ $role->permissions->count() }})</h4>
        <div class="flex flex-wrap gap-2">
            @forelse ($role->permissions as $p)
                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">{{ $p->permission_string }}</span>
            @empty
                <span class="text-secondary-foreground">None assigned.</span>
            @endforelse
        </div>
        <div class="flex gap-2 mt-5">
            <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-primary">Edit</a>
            <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">Back</a>
        </div>
    </div></div>
@endsection
