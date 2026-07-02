{{-- F2: create user. --}}
@extends(request()->boolean('modal') ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Add User')
@section('heading', 'Add User')
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                @include('admin.users._fields', ['user' => null])
                <div class="flex gap-2 mt-5">
                    <button class="kt-btn kt-btn-primary">Create</button>
                    <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
