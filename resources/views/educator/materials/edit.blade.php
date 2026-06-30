@extends('educator.layout')
@section('title', 'Edit Material')
@section('heading', 'Edit Material')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.materials.update', $material) }}">@csrf @method('PUT')
            <div class="row g-4">
                <div class="col-md-8"><label class="form-label required">File Name</label>
                    <input name="file_name" class="form-control" value="{{ old('file_name', $material->file_name) }}"></div>
                <div class="col-md-4"><label class="form-label required">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', $material->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $material->is_active)==0)>Inactive</option>
                    </select></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('educator.materials.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
