{{-- F7: create academic year. --}}
@extends('admin.layout')
@section('title', 'Add Academic Year')
@section('heading', 'Add Academic Year')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.academic-years.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-md-6"><label class="form-label required">Year</label>
                    <input name="year" class="form-control" value="{{ old('year') }}" placeholder="2026 - 2027"></div>
                <div class="col-md-6"><label class="form-label required">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', true)==1)>Active</option>
                        <option value="0" @selected(old('is_active')==='0')>Inactive</option>
                    </select></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary">Create</button>
                <a href="{{ route('admin.academic-years.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
