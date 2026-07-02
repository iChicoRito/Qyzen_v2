{{-- F7: create academic year. --}}
@extends(request()->boolean('modal') ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Add Academic Year')
@section('heading', 'Add Academic Year')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('admin.academic-years.store') }}">
            @csrf
            <div class="grid grid-cols-2 gap-5">
                <div class="flex flex-col gap-1"><label class="kt-form-label">Year</label>
                    <input name="year" class="kt-input" value="{{ old('year') }}" placeholder="2026 - 2027" required pattern="\d{4} - \d{4}" title="Format: YYYY - YYYY"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Status</label>
                    <select name="is_active" class="kt-select">
                        <option value="1" @selected(old('is_active', true)==1)>Active</option>
                        <option value="0" @selected(old('is_active')==='0')>Inactive</option>
                    </select></div>
            </div>
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                <a href="{{ route('admin.academic-years.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
