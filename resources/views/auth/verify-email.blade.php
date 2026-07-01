@extends('layouts.auth')

@section('title', 'Verify email')

@section('card')
  <div class="kt-card-content flex flex-col gap-5 p-10 text-center">
    <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Verify Your Email</h3>
    <div class="text-sm text-secondary-foreground font-medium">
      We've sent a verification link to your email. Click it to activate your account.
    </div>

    @if (session('status') == 'verification-link-sent')
      <div class="text-sm font-medium text-green-600">A new verification link has been sent.</div>
    @endif

    <div class="flex flex-center justify-center gap-2.5">
      <form action="{{ route('verification.send') }}" method="POST">
        @csrf
        <button type="submit" class="kt-btn kt-btn-primary">Resend link</button>
      </form>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="kt-btn kt-btn-outline">Log out</button>
      </form>
    </div>
  </div>
@endsection
