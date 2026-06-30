@extends('educator.layout')
@section('title', 'Edit Question')
@section('heading', 'Edit Question')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.quizzes.update', $quiz) }}">@csrf @method('PUT')
            @include('educator.quizzes._fields')
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('educator.quizzes.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
