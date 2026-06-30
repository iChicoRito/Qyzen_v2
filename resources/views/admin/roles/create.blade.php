{{-- F5: create role. --}}
@extends('admin.layout')
@section('title', 'Add Role')
@section('heading', 'Add Role')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            @include('admin.roles._fields', ['role' => null])
            <div class="mt-4"><button class="btn btn-primary">Create</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
