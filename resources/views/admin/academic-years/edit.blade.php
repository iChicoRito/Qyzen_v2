{{-- F7: edit academic year (resolves source 🚧 stub). --}}
@extends('admin.layout')
@section('title', 'Edit Academic Year')
@section('heading', 'Edit Academic Year')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.academic-years.update', $year) }}">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-md-6"><label class="form-label required">Year</label>
                    <input name="year" class="form-control" value="{{ old('year', $year->year) }}" placeholder="2026 - 2027"></div>
                <div class="col-md-6"><label class="form-label required">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', $year->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $year->is_active)==0)>Inactive</option>
                    </select></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.academic-years.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
