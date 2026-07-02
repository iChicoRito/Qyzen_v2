{{-- F4: edit user (resolves the source 🚧 stub). --}}
@extends(request()->boolean('modal') ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Edit User')
@section('heading', 'Edit User')
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content p-5">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf @method('PUT')
                @include('admin.users._fields', ['user' => $user])
                <div class="flex gap-2 mt-5">
                    <button class="kt-btn kt-btn-primary">Save</button>
                    <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
