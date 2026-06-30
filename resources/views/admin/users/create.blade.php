{{-- F2: create user. --}}
@extends('admin.layout')
@section('title', 'Add User')
@section('heading', 'Add User')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                @include('admin.users._fields', ['user' => null])
                <div class="mt-4">
                    <button class="btn btn-primary">Create</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
