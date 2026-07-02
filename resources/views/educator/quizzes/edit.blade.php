@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Edit Question')
@section('heading', 'Edit Question')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('educator.quizzes.update', $quiz) }}">@csrf @method('PUT')
            @include('educator.quizzes._fields')
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('educator.quizzes.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
