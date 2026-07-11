@extends('student.layout')
@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('content')
    <div class="grid gap-5 lg:gap-7.5 items-start" id="announcement_layout">
        @include('student.announcements._timeline', ['announcements' => $announcements->getCollection(), 'newAnnouncementIds' => $newAnnouncementIds])
        <section class="min-w-0 grid gap-5 lg:gap-7.5 order-2 lg:order-1">
            @forelse ($announcements as $announcement)
                @include('student.announcements._card', compact('announcement'))
            @empty
                <div class="kt-card"><div class="kt-card-content py-12 text-center text-secondary-foreground">No announcements yet.</div></div>
            @endforelse
        </section>
    </div>
    @if ($announcements->hasPages())<div class="mt-5">{{ $announcements->links() }}</div>@endif
@endsection

@push('styles')
    <style nonce="{{ $cspNonce ?? '' }}">
        #announcement_layout { grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 768px) {
            #announcement_layout { grid-template-columns: minmax(0, 1fr) minmax(260px, 32%); }
        }
    </style>
@endpush
