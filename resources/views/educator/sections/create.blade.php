@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Add Section')
@section('heading', 'Add Section')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('educator.sections.store') }}">@csrf
            @include('educator.sections._fields')
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                <a href="{{ route('educator.sections.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
