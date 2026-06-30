@extends('educator.layout')
@section('title', 'Edit Section')
@section('heading', 'Edit Section')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('educator.sections.update', $section) }}">@csrf @method('PUT')
            @include('educator.sections._fields')
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('educator.sections.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
