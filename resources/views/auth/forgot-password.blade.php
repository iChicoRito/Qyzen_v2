@extends('layouts.auth')

@section('title', 'Forgot password')

@section('card')
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-4">Forgot password</h2>
      <p class="text-secondary mb-4">Enter your email and we'll send a reset link.</p>

      @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
      @endif

      <form action="{{ route('password.email') }}" method="POST" novalidate>
        @csrf
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="your@email.com" required autofocus />
          @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100">Send reset link</button>
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    <a href="{{ route('login') }}">Back to sign in</a>
  </div>
@endsection
