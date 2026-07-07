{{-- Admin calendar page (sidebar). Full FullCalendar over institution-wide assessment windows. --}}
@extends('admin.layout')

@section('title', 'Calendar')
@section('heading', 'Calendar')

@section('content')
    @include('partials._full_calendar', ['events' => $events])
@endsection
