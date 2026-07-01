{{-- F2: view user. --}}
@extends('admin.layout')
@section('title', 'User')
@section('heading', $user->name)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm mb-0">
                <dt class="text-secondary-foreground">User ID</dt><dd class="text-mono">{{ $user->user_id }}</dd>
                <dt class="text-secondary-foreground">Name</dt><dd class="text-mono">{{ $user->name }}</dd>
                <dt class="text-secondary-foreground">Email</dt><dd class="text-mono">{{ $user->email }}</dd>
                <dt class="text-secondary-foreground">Type</dt><dd class="text-mono">{{ ucfirst($user->user_type) }}</dd>
                <dt class="text-secondary-foreground">Roles</dt><dd class="text-mono">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</dd>
                <dt class="text-secondary-foreground">Status</dt><dd class="text-mono">{{ $user->is_active ? 'Active' : 'Inactive' }}</dd>
                <dt class="text-secondary-foreground">Verified</dt><dd class="text-mono">{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd>
            </dl>
            <div class="flex gap-2 mt-5">
                <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-primary">Edit</a>
                <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">Back</a>
            </div>
        </div>
    </div>
@endsection
