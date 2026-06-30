@extends('educator.layout')
@section('title', 'Edit Enrollment')
@section('heading', 'Edit Enrollment')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.enrollment.update', $enrolled) }}">@csrf @method('PUT')
            <div class="row g-4">
                <div class="col-md-6"><label class="form-label required">Student</label>
                    <select name="student_id" class="form-select">
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_id', $enrolled->student_id)==$s->id)>{{ $s->user_id }} — {{ $s->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="form-label required">Subject</label>
                    <select name="subject_id" class="form-select">
                        @foreach ($subjects as $sub)
                            <option value="{{ $sub->id }}" @selected(old('subject_id', $enrolled->subject_id)==$sub->id)>{{ $sub->subject_code }} — {{ $sub->subject_name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="form-label required">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', $enrolled->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $enrolled->is_active)==0)>Inactive</option>
                    </select></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('educator.enrollment.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
