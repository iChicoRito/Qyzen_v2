{{-- Educator calendar page (sidebar). Full FullCalendar over own assessment windows. --}}
@extends('educator.layout')

@section('title', 'Calendar')
@section('heading', 'Calendar')

@section('content')
    @include('partials._full_calendar', ['events' => $events])
@endsection
