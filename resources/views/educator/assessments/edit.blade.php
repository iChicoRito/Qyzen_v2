@extends('educator.layout')
@section('title', 'Edit Assessment')
@section('heading', 'Edit Assessment')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.assessments.update', $assessment) }}">@csrf @method('PUT')
            @include('educator.assessments._fields')
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('educator.assessments.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
