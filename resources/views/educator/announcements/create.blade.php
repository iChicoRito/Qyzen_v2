@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Create Announcement')
@section('heading', 'Create Announcement')
@section('content')
    <div class="kt-card"><div class="kt-card-content p-5">
        @include('educator.announcements._form', ['announcement' => null, 'action' => route('educator.announcements.store'), 'method' => 'POST'])
    </div></div>
@endsection
