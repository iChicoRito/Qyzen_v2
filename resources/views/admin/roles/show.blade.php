{{-- F5: view role + assigned permissions. --}}
@extends('admin.layout')
@section('title', 'Role')
@section('heading', $role->name)
@section('content')
    <div class="card"><div class="card-body">
        <dl class="row mb-4">
            <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $role->name }}</dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $role->description ?: '—' }}</dd>
            <dt class="col-sm-3">System</dt><dd class="col-sm-9">{{ $role->is_system ? 'Yes' : 'No' }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $role->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <h4>Permissions ({{ $role->permissions->count() }})</h4>
        <div class="d-flex flex-wrap gap-2">
            @forelse ($role->permissions as $p)
                <span class="badge badge-light-primary">{{ $p->permission_string }}</span>
            @empty
                <span class="text-muted">None assigned.</span>
            @endforelse
        </div>
        <div class="mt-4">
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Back</a>
        </div>
    </div></div>
@endsection
