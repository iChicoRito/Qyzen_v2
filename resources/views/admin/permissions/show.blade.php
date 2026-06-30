{{-- F6: view permission. --}}
@extends('admin.layout')
@section('title', 'Permission')
@section('heading', $permission->permission_string)
@section('content')
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Permission</dt><dd class="col-sm-9">{{ $permission->permission_string }}</dd>
            <dt class="col-sm-3">Resource</dt><dd class="col-sm-9">{{ $permission->resource }}</dd>
            <dt class="col-sm-3">Action</dt><dd class="col-sm-9">{{ $permission->action }}</dd>
            <dt class="col-sm-3">Module</dt><dd class="col-sm-9">{{ $permission->module ?: '—' }}</dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $permission->description ?: '—' }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $permission->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <div class="mt-4">
            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Back</a>
        </div>
    </div></div>
@endsection
