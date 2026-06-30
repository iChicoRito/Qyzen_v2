{{-- F8: create academic term. --}}
@extends('admin.layout')
@section('title', 'Add Academic Term')
@section('heading', 'Add Academic Term')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        @if ($years->isEmpty())
            <div class="alert alert-warning">Create an academic year first.</div>
        @else
            <form method="POST" action="{{ route('admin.academic-terms.store') }}">
                @csrf
                @include('admin.academic-terms._fields', ['term' => null])
                <div class="mt-4"><button class="btn btn-primary">Create</button>
                    <a href="{{ route('admin.academic-terms.index') }}" class="btn btn-light">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
