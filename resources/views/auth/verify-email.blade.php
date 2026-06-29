@extends('layouts.auth')

@section('title', 'Verify email')

@section('card')
  <div class="card card-md">
    <div class="card-body text-center">
      <h2 class="h2 mb-3">Verify your email</h2>
      <p class="text-secondary">
        We've sent a verification link to your email. Click it to activate your account.
      </p>

      @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">A new verification link has been sent.</div>
      @endif

      <div class="d-flex justify-content-between mt-4">
        <form action="{{ route('verification.send') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-primary">Resend link</button>
        </form>
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-link text-secondary">Log out</button>
        </form>
      </div>
    </div>
  </div>
@endsection
