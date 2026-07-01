{{-- H11: shared profile. Students: name read-only (email + media + password only).
     user_id / user_type / is_active are never editable (self-service lock). --}}
@php
    $role = $user->primaryRole();
    $navItems = [
        ['label' => 'Dashboard', 'url' => url('/'.$role.'/dashboard'), 'active' => false],
        ['label' => 'Profile', 'url' => route('profile.edit'), 'active' => true],
    ];
    $isStudent = $user->hasRole('student');
@endphp
@extends('layouts.app', ['role' => $role, 'navItems' => $navItems])

@section('title', 'Profile')
@section('heading', 'Profile')

@section('content')
    @include('admin._status')

    <div class="kt-card mb-5"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="grid md:grid-cols-2 gap-5">
                <div class="flex flex-col gap-1"><label class="kt-form-label">Given Name</label>
                    <input name="given_name" class="kt-input" value="{{ old('given_name', $user->given_name) }}" @disabled($isStudent)>
                    @if ($isStudent)<span class="text-xs text-secondary-foreground">Students can't change their name.</span>@endif</div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Surname</label>
                    <input name="surname" class="kt-input" value="{{ old('surname', $user->surname) }}" @disabled($isStudent)></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Email</label>
                    <input name="email" type="email" class="kt-input" value="{{ old('email', $user->email) }}"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Profile Picture</label>
                    <input name="profile_picture" type="file" class="kt-input" accept="image/png,image/jpeg,image/webp"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Cover Photo</label>
                    <input name="cover_photo" type="file" class="kt-input" accept="image/png,image/jpeg,image/webp"></div>
            </div>
            <div class="mt-5"><button class="kt-btn kt-btn-primary">Save</button></div>
        </form>
    </div></div>

    <div class="kt-card mb-5"><div class="kt-card-content p-5">
        <h4 class="text-sm font-semibold text-mono mb-3">Change Password</h4>
        <form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
            <div class="grid md:grid-cols-3 gap-5">
                <div class="flex flex-col gap-1"><label class="kt-form-label">Current Password</label>
                    <input name="current_password" type="password" class="kt-input"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">New Password</label>
                    <input name="password" type="password" class="kt-input"></div>
                <div class="flex flex-col gap-1"><label class="kt-form-label">Confirm</label>
                    <input name="password_confirmation" type="password" class="kt-input"></div>
            </div>
            <span class="text-xs text-secondary-foreground mt-2 block">≥8 chars, upper + lower + number + symbol.</span>
            <div class="mt-5"><button class="kt-btn kt-btn-primary">Change Password</button></div>
        </form>
    </div></div>

    <div class="kt-card"><div class="kt-card-content p-5">
        <h4 class="text-sm font-semibold text-mono mb-3">Linked Accounts</h4>
        <a href="{{ route('oauth.redirect', 'google') }}" class="kt-btn kt-btn-outline">Link Google</a>
    </div></div>
@endsection
