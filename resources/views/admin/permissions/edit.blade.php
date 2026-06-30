{{-- F6: edit permission (resolves source 🚧 stub). permission_string recomputed server-side. --}}
@extends('admin.layout')
@section('title', 'Edit Permission')
@section('heading', 'Edit Permission')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-md-6"><label class="form-label required">Resource</label>
                    <input name="resource" class="form-control" value="{{ old('resource', $permission->resource) }}"></div>
                <div class="col-md-6"><label class="form-label required">Action</label>
                    <input name="action" class="form-control" value="{{ old('action', $permission->action) }}"></div>
                <div class="col-md-6"><label class="form-label">Name</label>
                    <input name="name" class="form-control" value="{{ old('name', $permission->name) }}"></div>
                <div class="col-md-6"><label class="form-label">Module</label>
                    <input name="module" class="form-control" value="{{ old('module', $permission->module) }}"></div>
                <div class="col-md-6"><label class="form-label required">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', $permission->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $permission->is_active)==0)>Inactive</option>
                    </select></div>
                <div class="col-12"><label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $permission->description) }}</textarea></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
