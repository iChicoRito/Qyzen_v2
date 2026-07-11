@extends('student.layout')
@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('content')
    <div class="grid gap-5 lg:gap-7.5 lg:grid-cols-2">
        @forelse ($announcements as $announcement)
            @include('student.announcements._card', compact('announcement'))
        @empty
            <div class="kt-card lg:col-span-2"><div class="kt-card-content py-12 text-center text-secondary-foreground">No announcements yet.</div></div>
        @endforelse
    </div>
    @if ($announcements->hasPages())<div class="mt-5">{{ $announcements->links() }}</div>@endif
@endsection
