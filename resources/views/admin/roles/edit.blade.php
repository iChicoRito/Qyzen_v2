{{-- F5: edit role + assign permissions (all-or-nothing replace). --}}
@extends('admin.layout')
@section('title', 'Edit Role')
@section('heading', 'Edit Role')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf @method('PUT')
            @include('admin.roles._fields', ['role' => $role])
            <div class="mt-4"><button class="btn btn-primary">Save</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a></div>
        </form>
    </div></div>
@endsection
