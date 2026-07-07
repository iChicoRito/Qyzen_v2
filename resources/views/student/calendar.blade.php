{{-- Student calendar page (sidebar). Full FullCalendar over enrolled-subject assessment deadlines. --}}
@extends('student.layout')

@section('title', 'Calendar')
@section('heading', 'Calendar')

@section('content')
    @include('partials._full_calendar', ['events' => $events])
@endsection
