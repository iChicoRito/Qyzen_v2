@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Add Subject')
@section('heading', 'Add Subject')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($sections->isEmpty())
            <div class="kt-alert kt-alert-warning">Create a section first.</div>
        @else
            <form method="POST" action="{{ route('educator.subjects.store') }}">@csrf
                @include('educator.subjects._fields')
                <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                    <a href="{{ route('educator.subjects.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
