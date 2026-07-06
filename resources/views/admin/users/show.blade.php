{{-- F2: view user. Renders as a bare fragment inside the shared modal under ?modal=1.
     Layout mirrors demo1 public-profile/teams.html team card (centered avatar + dashed rows + footer). --}}
@php $isModal = request()->boolean('modal'); @endphp
@extends($isModal ? 'layouts.fragment' : 'admin.layout')
@section('title', 'User')
@section('heading', $user->name)
@section('content')
    <div class="kt-card">
        @include('partials._user-card', ['user' => $user])
        <div class="kt-card-footer justify-end gap-2">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
            @else
                <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">Back</a>
                <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-primary">Edit</a>
            @endif
        </div>
    </div>
@endsection
