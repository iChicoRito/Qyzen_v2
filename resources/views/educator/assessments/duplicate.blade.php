@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Duplicate Assessment')
@section('heading', 'Duplicate Assessment')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('educator.assessments.duplicate.store', $sourceAssessment) }}">@csrf
            @include('educator.assessments._fields')
            <div class="flex gap-2 mt-5">
                <button class="kt-btn kt-btn-primary">Duplicate</button>
                <a href="{{ route('educator.assessments.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a>
            </div>
        </form>
    </div></div>
@endsection
