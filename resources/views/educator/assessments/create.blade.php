@extends('educator.layout')
@section('title', 'Add Assessment')
@section('heading', 'Add Assessment')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        @if ($subjects->isEmpty() || $sections->isEmpty())
            <div class="alert alert-warning">Create a subject and section first.</div>
        @else
            <form method="POST" action="{{ route('educator.assessments.store') }}">@csrf
                @include('educator.assessments._fields')
                <div class="mt-4"><button class="btn btn-primary">Create</button>
                    <a href="{{ route('educator.assessments.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
