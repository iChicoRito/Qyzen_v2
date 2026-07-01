{{-- F5: create role. --}}
@extends('admin.layout')
@section('title', 'Add Role')
@section('heading', 'Add Role')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf
            @include('admin.roles._fields', ['role' => null])
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">Cancel</a></div>
        </form>
    </div></div>
@endsection
