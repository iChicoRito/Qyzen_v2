{{-- F8: create academic term. --}}
@extends('admin.layout')
@section('title', 'Add Academic Term')
@section('heading', 'Add Academic Term')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($years->isEmpty())
            <div class="kt-alert kt-alert-warning">Create an academic year first.</div>
        @else
            <form method="POST" action="{{ route('admin.academic-terms.store') }}">
                @csrf
                @include('admin.academic-terms._fields', ['term' => null])
                <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                    <a href="{{ route('admin.academic-terms.index') }}" class="kt-btn kt-btn-outline">Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
