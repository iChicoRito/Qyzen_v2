@extends('educator.layout')
@section('title', 'Upload Materials')
@section('heading', 'Upload Materials')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        @if ($subjects->isEmpty() || $sections->isEmpty())
            <div class="alert alert-warning">Create a subject and section first.</div>
        @else
            <form method="POST" action="{{ route('educator.materials.store') }}" enctype="multipart/form-data">@csrf
                <div class="row g-4">
                    <div class="col-md-6"><label class="form-label required">Subject</label>
                        <select name="subject_id" class="form-select">
                            @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-6"><label class="form-label required">Section</label>
                        <select name="section_id" class="form-select">
                            @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->section_name }}</option>@endforeach
                        </select></div>
                    <div class="col-12"><label class="form-label required">Files</label>
                        <input type="file" name="files[]" class="form-control" multiple required></div>
                </div>
                <div class="mt-4"><button class="btn btn-primary">Upload</button>
                    <a href="{{ route('educator.materials.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
