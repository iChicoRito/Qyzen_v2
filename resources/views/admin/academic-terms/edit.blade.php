{{-- F8: edit academic term (resolves source 🚧 stub). --}}
@extends(request()->boolean('modal') ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Edit Academic Term')
@section('heading', 'Edit Academic Term')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('admin.academic-terms.update', $term) }}">
            @csrf @method('PUT')
            @include('admin.academic-terms._fields', ['term' => $term])
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('admin.academic-terms.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
