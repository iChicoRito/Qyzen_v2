@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Edit Announcement')
@section('heading', 'Edit Announcement')
@section('content')
    <div class="kt-card"><div class="kt-card-content p-5">
        @include('educator.announcements._form', ['action' => route('educator.announcements.update', $announcement), 'method' => 'PUT'])
    </div></div>
@endsection
