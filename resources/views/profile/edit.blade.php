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

    <div class="card mb-5"><div class="card-body">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="row g-4">
                <div class="col-md-6"><label class="form-label">Given Name</label>
                    <input name="given_name" class="form-control" value="{{ old('given_name', $user->given_name) }}" @disabled($isStudent)>
                    @if ($isStudent)<div class="form-text">Students can't change their name.</div>@endif</div>
                <div class="col-md-6"><label class="form-label">Surname</label>
                    <input name="surname" class="form-control" value="{{ old('surname', $user->surname) }}" @disabled($isStudent)></div>
                <div class="col-md-6"><label class="form-label required">Email</label>
                    <input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Profile Picture</label>
                    <input name="profile_picture" type="file" class="form-control" accept="image/png,image/jpeg,image/webp"></div>
                <div class="col-md-6"><label class="form-label">Cover Photo</label>
                    <input name="cover_photo" type="file" class="form-control" accept="image/png,image/jpeg,image/webp"></div>
            </div>
            <div class="mt-4"><button class="btn btn-primary">Save</button></div>
        </form>
    </div></div>

    <div class="card mb-5"><div class="card-body">
        <h4 class="mb-3">Change Password</h4>
        <form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
            <div class="row g-4">
                <div class="col-md-4"><label class="form-label required">Current Password</label>
                    <input name="current_password" type="password" class="form-control"></div>
                <div class="col-md-4"><label class="form-label required">New Password</label>
                    <input name="password" type="password" class="form-control"></div>
                <div class="col-md-4"><label class="form-label required">Confirm</label>
                    <input name="password_confirmation" type="password" class="form-control"></div>
            </div>
            <div class="form-text mt-2">≥8 chars, upper + lower + number + symbol.</div>
            <div class="mt-4"><button class="btn btn-primary">Change Password</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <h4 class="mb-3">Linked Accounts</h4>
        <a href="{{ route('oauth.redirect', 'google') }}" class="btn btn-light">Link Google</a>
    </div></div>
@endsection
