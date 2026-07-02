@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Add Assessment')
@section('heading', 'Add Assessment')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($subjects->isEmpty() || $sections->isEmpty())
            <div class="kt-alert kt-alert-warning">Create a subject and section first.</div>
        @else
            <form method="POST" action="{{ route('educator.assessments.store') }}">@csrf
                @include('educator.assessments._fields')
                <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                    <a href="{{ route('educator.assessments.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
