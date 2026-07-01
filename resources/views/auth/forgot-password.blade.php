@extends('layouts.auth')

@section('title', 'Forgot password')

@section('card')
  <form action="{{ route('password.email') }}" method="POST" class="kt-card-content flex flex-col gap-5 p-10" novalidate>
    @csrf
    <div class="text-center mb-2.5">
      <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Forgot Password?</h3>
      <div class="text-sm text-secondary-foreground font-medium">Enter your email to reset it.</div>
    </div>

    @if (session('status'))
      <div class="kt-alert kt-alert-success">{{ session('status') }}</div>
    @endif

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" class="kt-input" required autofocus />
      @error('email')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-wrap justify-center gap-2.5">
      <button type="submit" class="kt-btn kt-btn-primary grow justify-center">Send reset link</button>
      <a href="{{ route('login') }}" class="kt-btn kt-btn-outline">Back to sign in</a>
    </div>
  </form>
@endsection
