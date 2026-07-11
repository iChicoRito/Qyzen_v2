@extends('student.layout')
@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('content')
    <div class="grid gap-5 lg:gap-7.5 lg:grid-cols-4 items-stretch">
        @include('student.announcements._timeline', ['announcements' => $announcements->getCollection()->take(6)])
        <div class="min-w-0 grid gap-5 lg:gap-7.5 lg:col-span-3">
            <header class="kt-card" id="announcement_feed_header">
                <div class="kt-card-content flex items-center justify-center min-h-[160px]">
                    <h1 class="text-4xl font-semibold uppercase tracking-wide text-mono">Announcements</h1>
                </div>
            </header>
            <section class="grid gap-5 lg:gap-7.5" id="announcement_feed_list">
                @forelse ($announcements as $announcement)
                    @include('student.announcements._card', compact('announcement'))
                @empty
                    <div class="kt-card"><div class="kt-card-content py-12 text-center text-secondary-foreground">No announcements yet.</div></div>
                @endforelse
            </section>
        </div>
    </div>
    @if ($announcements->hasPages())<div class="mt-5">{{ $announcements->links() }}</div>@endif
@endsection
