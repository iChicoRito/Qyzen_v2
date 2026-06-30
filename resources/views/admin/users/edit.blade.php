{{-- F4: edit user (resolves the source 🚧 stub). --}}
@extends('admin.layout')
@section('title', 'Edit User')
@section('heading', 'Edit User')
@section('content')
    @include('admin._status')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf @method('PUT')
                @include('admin.users._fields', ['user' => $user])
                <div class="mt-4">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
