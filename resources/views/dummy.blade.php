@extends('layouts.app')

@section('title', 'Layout check')
@section('heading', 'Dashboard')

@section('content')
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm text-gray-600">
            Base layout renders: sidebar nav, header, and content slot are wired.
        </p>
    </div>
@endsection
