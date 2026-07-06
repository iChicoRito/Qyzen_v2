{{-- Task 43: enrolled-student profile card. Modal-only fragment (same layout as
     admin.users.show), rendered inside the shared modal under ?modal=1. --}}
@extends('layouts.fragment')
@section('content')
    <div class="kt-card">
        @include('partials._user-card', ['user' => $user])
        <div class="kt-card-footer justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
        </div>
    </div>
@endsection
