@extends('layouts.auth')

@section('title', 'Reset password')

@section('card')
  <form action="{{ route('password.update') }}" method="POST" class="kt-card-content flex flex-col gap-5 p-10" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}" />
    <div class="text-center mb-2.5">
      <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Set New Password</h3>
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Email</label>
      <input type="email" name="email" value="{{ old('email', $request->email) }}" class="kt-input" required />
      @error('email')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">New password</label>
      <input type="password" name="password" class="kt-input" required autofocus />
      @error('password')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Confirm password</label>
      <input type="password" name="password_confirmation" class="kt-input" required />
    </div>

    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">Reset password</button>
  </form>
@endsection
