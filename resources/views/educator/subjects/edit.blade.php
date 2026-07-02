@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Edit Subject')
@section('heading', 'Edit Subject')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('educator.subjects.update', $subject) }}">@csrf @method('PUT')
            @include('educator.subjects._fields')
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('educator.subjects.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
