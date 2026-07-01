{{-- F6: view permission. --}}
@extends('admin.layout')
@section('title', 'Permission')
@section('heading', $permission->permission_string)
@section('content')
    <div class="kt-card"><div class="kt-card-content p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm mb-0">
            <dt class="text-secondary-foreground">Permission</dt><dd class="text-mono">{{ $permission->permission_string }}</dd>
            <dt class="text-secondary-foreground">Resource</dt><dd class="text-mono">{{ $permission->resource }}</dd>
            <dt class="text-secondary-foreground">Action</dt><dd class="text-mono">{{ $permission->action }}</dd>
            <dt class="text-secondary-foreground">Module</dt><dd class="text-mono">{{ $permission->module ?: '—' }}</dd>
            <dt class="text-secondary-foreground">Description</dt><dd class="text-mono">{{ $permission->description ?: '—' }}</dd>
            <dt class="text-secondary-foreground">Status</dt><dd class="text-mono">{{ $permission->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <div class="flex gap-2 mt-5">
            <a href="{{ route('admin.permissions.edit', $permission) }}" class="kt-btn kt-btn-primary">Edit</a>
            <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline">Back</a>
        </div>
    </div></div>
@endsection
