{{-- F5: edit role + assign permissions (all-or-nothing replace). --}}
@extends('admin.layout')
@section('title', 'Edit Role')
@section('heading', 'Edit Role')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('admin.roles.update', $role) }}">
            @csrf @method('PUT')
            @include('admin.roles._fields', ['role' => $role])
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">Cancel</a></div>
        </form>
    </div></div>
@endsection
