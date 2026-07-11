@extends('student.layout')
@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('content')
    <div class="grid gap-5 lg:gap-7.5 lg:grid-cols-[280px_minmax(0,1fr)] items-stretch">
        @include('student.announcements._timeline', ['announcements' => $announcements->getCollection()->take(6)])
        <section class="min-w-0 grid gap-5 lg:gap-7.5">
            @forelse ($announcements as $announcement)
                @include('student.announcements._card', compact('announcement'))
            @empty
                <div class="kt-card"><div class="kt-card-content py-12 text-center text-secondary-foreground">No announcements yet.</div></div>
            @endforelse
        </section>
    </div>
    @if ($announcements->hasPages())<div class="mt-5">{{ $announcements->links() }}</div>@endif
@endsection
