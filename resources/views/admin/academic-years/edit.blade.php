{{-- F7: edit academic year (resolves source 🚧 stub). --}}
@extends('admin.layout')
@section('title', 'Edit Academic Year')
@section('heading', 'Edit Academic Year')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('admin.academic-years.update', $year) }}">
            @csrf @method('PUT')
            <div class="grid md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-1"><label class="kt-form-label">Year</label>
                    <input name="year" class="kt-input" value="{{ old('year', $year->year) }}" placeholder="2026 - 2027"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Status</label>
                    <select name="is_active" class="kt-select">
                        <option value="1" @selected(old('is_active', $year->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $year->is_active)==0)>Inactive</option>
                    </select></div>
            </div>
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('admin.academic-years.index') }}" class="kt-btn kt-btn-outline">Cancel</a></div>
        </form>
    </div></div>
@endsection
