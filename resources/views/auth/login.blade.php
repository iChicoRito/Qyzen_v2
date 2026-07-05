@extends('layouts.auth')

@section('title', 'Sign in')

@section('card')
  <form action="{{ route('login') }}" method="POST" class="kt-card-content flex flex-col gap-5 p-10" autocomplete="off" novalidate>
    @csrf
    <div class="text-center mb-2.5">
      <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Sign in</h3>
      <div class="flex items-center justify-center font-medium">
        <span class="text-sm text-secondary-foreground">Use your institution account</span>
      </div>
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" class="kt-input" required autofocus />
      @error('email')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <div class="flex items-center justify-between gap-1">
        <label class="kt-form-label font-normal text-mono">Password</label>
        <a class="text-sm kt-link shrink-0" href="{{ route('password.request') }}">Forgot Password?</a>
      </div>
      <div class="kt-input" data-kt-toggle-password="true">
        <input type="password" name="password" placeholder="Enter Password" required />
        <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
          <span class="kt-toggle-password-active:hidden"><i class="ki-filled ki-eye text-muted-foreground"></i></span>
          <span class="hidden kt-toggle-password-active:block"><i class="ki-filled ki-eye-slash text-muted-foreground"></i></span>
        </button>
      </div>
    </div>

    <label class="kt-label">
      <input type="checkbox" name="remember" class="kt-checkbox kt-checkbox-sm" value="1" />
      <span class="kt-checkbox-label">Remember me</span>
    </label>

    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">Sign In</button>

    <div class="flex items-center gap-2">
      <span class="border-t border-border w-full"></span>
      <span class="text-xs text-muted-foreground font-medium uppercase">Or</span>
      <span class="border-t border-border w-full"></span>
    </div>

    <a href="{{ route('oauth.redirect', 'google') }}" class="kt-btn kt-btn-outline justify-center">
      <img alt="Google" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/brand-logos/google.svg') }}" class="size-3.5 shrink-0" />
      Sign in with Google
    </a>
  </form>
@endsection

@section('below_card')
  @include('auth._legal')
@endsection
