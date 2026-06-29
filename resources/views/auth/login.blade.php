@extends('layouts.auth')

@section('title', 'Sign in')

@section('card')
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-4">Login to your account</h2>

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif
      @error('email')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror

      <form action="{{ route('login') }}" method="POST" autocomplete="off" novalidate>
        @csrf
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="your@email.com" required autofocus />
        </div>
        <div class="mb-2">
          <label class="form-label">
            Password
            <span class="form-label-description"><a href="{{ route('password.request') }}">I forgot password</a></span>
          </label>
          <input type="password" name="password" class="form-control" placeholder="Your password" required />
        </div>
        <div class="mb-2">
          <label class="form-check">
            <input type="checkbox" name="remember" class="form-check-input" />
            <span class="form-check-label">Remember me on this device</span>
          </label>
        </div>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </div>
      </form>
    </div>
    <div class="hr-text">or</div>
    <div class="card-body">
      <a href="{{ route('oauth.redirect', 'google') }}" class="btn btn-white w-100">Sign in with Google</a>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    Don't have an account yet? <a href="{{ route('register') }}">Sign up</a>
  </div>
@endsection
