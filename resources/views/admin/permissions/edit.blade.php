{{-- F6: edit permission (resolves source 🚧 stub). permission_string recomputed server-side. --}}
@extends(request()->boolean('modal') ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Edit Permission')
@section('heading', 'Edit Permission')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-5">
                <div class="flex flex-col gap-1"><label class="kt-form-label">Resource</label>
                    <input name="resource" class="kt-input" value="{{ old('resource', $permission->resource) }}"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Action</label>
                    <input name="action" class="kt-input" value="{{ old('action', $permission->action) }}"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Name</label>
                    <input name="name" class="kt-input" value="{{ old('name', $permission->name) }}"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Module</label>
                    <input name="module" class="kt-input" value="{{ old('module', $permission->module) }}"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Status</label>
                    <select name="is_active" class="kt-select">
                        <option value="1" @selected(old('is_active', $permission->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $permission->is_active)==0)>Inactive</option>
                    </select></div>
                <div class="flex flex-col gap-1 col-span-2"><label class="kt-form-label">Description</label>
                    <textarea name="description" class="kt-textarea" rows="2">{{ old('description', $permission->description) }}</textarea></div>
            </div>
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('admin.permissions.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
