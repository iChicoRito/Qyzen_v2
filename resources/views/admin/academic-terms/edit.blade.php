{{-- F8: edit academic term (resolves source 🚧 stub). --}}
@extends('admin.layout')
@section('title', 'Edit Academic Term')
@section('heading', 'Edit Academic Term')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.academic-terms.update', $term) }}">
            @csrf @method('PUT')
            @include('admin.academic-terms._fields', ['term' => $term])
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.academic-terms.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
