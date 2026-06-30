@extends('educator.layout')
@section('title', 'Add Question')
@section('heading', 'Add Question')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        @if ($assessments->isEmpty())
            <div class="alert alert-warning">Create an assessment first.</div>
        @else
            <form method="POST" action="{{ route('educator.quizzes.store') }}">@csrf
                @include('educator.quizzes._fields')
                <div class="mt-4"><button class="btn btn-primary">Create</button>
                    <a href="{{ route('educator.quizzes.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
