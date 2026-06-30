@extends('educator.layout')
@section('title', 'Enroll Students')
@section('heading', 'Enroll Students')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        @if ($subjects->isEmpty())
            <div class="alert alert-warning">Create a subject first.</div>
        @else
            <form method="POST" action="{{ route('educator.enrollment.store') }}">@csrf
                <div class="row g-4">
                    <div class="col-md-6"><label class="form-label required">Students</label>
                        <select name="student_ids[]" class="form-select" multiple size="8">
                            @foreach ($students as $s)
                                <option value="{{ $s->id }}">{{ $s->user_id }} — {{ $s->name }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-6"><label class="form-label required">Subjects</label>
                        <select name="subject_ids[]" class="form-select" multiple size="8">
                            @foreach ($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->subject_code }} — {{ $sub->subject_name }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-6"><label class="form-label required">Status</label>
                        <select name="is_active" class="form-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                </div>
                <div class="mt-4"><button class="btn btn-primary">Enroll</button>
                    <a href="{{ route('educator.enrollment.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
