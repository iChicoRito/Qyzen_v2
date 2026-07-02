@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Add Question')
@section('heading', 'Add Question')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($assessments->isEmpty())
            <div class="kt-alert kt-alert-warning">Create an assessment first.</div>
        @else
            <form method="POST" action="{{ route('educator.quizzes.store') }}">@csrf
                @include('educator.quizzes._fields')
                <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Create</button>
                    <a href="{{ route('educator.quizzes.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
