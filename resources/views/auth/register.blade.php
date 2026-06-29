@extends('layouts.auth')

@section('title', 'Sign up')

@section('card')
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-4">Create new account</h2>

      <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
        @csrf
        <div class="row">
          <div class="col mb-3">
            <label class="form-label">Given name</label>
            <input type="text" name="given_name" value="{{ old('given_name') }}" class="form-control" required autofocus />
            @error('given_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div class="col mb-3">
            <label class="form-label">Surname</label>
            <input type="text" name="surname" value="{{ old('surname') }}" class="form-control" required />
            @error('surname')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="your@email.com" required />
          @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required />
          @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm password</label>
          <input type="password" name="password_confirmation" class="form-control" required />
        </div>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100">Create account</button>
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
  </div>
@endsection
