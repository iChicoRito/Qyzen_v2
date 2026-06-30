@extends('educator.layout')
@section('title', 'Edit Subject')
@section('heading', 'Edit Subject')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.subjects.update', $subject) }}">@csrf @method('PUT')
            @include('educator.subjects._fields')
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('educator.subjects.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
