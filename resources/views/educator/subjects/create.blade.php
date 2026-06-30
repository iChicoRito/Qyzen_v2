@extends('educator.layout')
@section('title', 'Add Subject')
@section('heading', 'Add Subject')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        @if ($sections->isEmpty())
            <div class="alert alert-warning">Create a section first.</div>
        @else
            <form method="POST" action="{{ route('educator.subjects.store') }}">@csrf
                @include('educator.subjects._fields')
                <div class="mt-4"><button class="btn btn-primary">Create</button>
                    <a href="{{ route('educator.subjects.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
