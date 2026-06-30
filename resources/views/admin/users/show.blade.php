{{-- F2: view user. --}}
@extends('admin.layout')
@section('title', 'User')
@section('heading', $user->name)
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">User ID</dt><dd class="col-sm-9">{{ $user->user_id }}</dd>
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $user->name }}</dd>
                <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $user->email }}</dd>
                <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ ucfirst($user->user_type) }}</dd>
                <dt class="col-sm-3">Roles</dt><dd class="col-sm-9">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $user->is_active ? 'Active' : 'Inactive' }}</dd>
                <dt class="col-sm-3">Verified</dt><dd class="col-sm-9">{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd>
            </dl>
            <div class="mt-4">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit</a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>
    </div>
@endsection
