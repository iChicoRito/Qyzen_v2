@extends('educator.layout')
@section('title', 'Add Section')
@section('heading', 'Add Section')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.sections.store') }}">@csrf
            @include('educator.sections._fields')
            <div class="mt-4"><button class="btn btn-primary">Create</button>
                <a href="{{ route('educator.sections.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
