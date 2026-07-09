@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Edit Enrollment')
@section('heading', 'Edit Enrollment')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('educator.enrollment.update', $enrolled) }}">@csrf @method('PUT')
            <div class="grid grid-cols-1 gap-5">
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Student</label>
                    <select name="student_id" class="kt-select"
                            data-kt-select="true"
                            data-kt-select-enable-search="true"
                            data-kt-select-search-placeholder="Search students…">
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_id', $enrolled->student_id)==$s->id)>{{ $s->user_id }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Subject</label>
                    <select name="subject_id" class="kt-select"
                            data-kt-select="true"
                            data-kt-select-enable-search="true"
                            data-kt-select-search-placeholder="Search subjects…">
                        @foreach ($subjects as $sub)
                            <option value="{{ $sub->id }}" @selected(old('subject_id', $enrolled->subject_id)==$sub->id)>{{ $sub->subject_code }} — {{ $sub->subject_name }} ({{ optional($sub->section)->section_name ?? 'No section' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Status</label>
                    <select name="is_active" class="kt-select">
                        <option value="1" @selected(old('is_active', $enrolled->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $enrolled->is_active)==0)>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('educator.enrollment.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
