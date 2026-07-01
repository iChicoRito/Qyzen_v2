@extends('layouts.auth')

@section('title', 'Sign up')

@section('card')
  <form action="{{ route('register') }}" method="POST" class="kt-card-content flex flex-col gap-5 p-10" autocomplete="off" novalidate>
    @csrf
    <div class="text-center mb-2.5">
      <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Create Account</h3>
      <div class="flex items-center justify-center font-medium">
        <span class="text-sm text-secondary-foreground me-1.5">Already have an account?</span>
        <a class="text-sm kt-link" href="{{ route('login') }}">Sign in</a>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-2.5">
      <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">Given name</label>
        <input type="text" name="given_name" value="{{ old('given_name') }}" class="kt-input" required autofocus />
        @error('given_name')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
      </div>
      <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">Surname</label>
        <input type="text" name="surname" value="{{ old('surname') }}" class="kt-input" required />
        @error('surname')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
      </div>
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" class="kt-input" required />
      @error('email')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Password</label>
      <input type="password" name="password" class="kt-input" required />
      @error('password')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Confirm password</label>
      <input type="password" name="password_confirmation" class="kt-input" required />
    </div>

    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">Create account</button>
  </form>
@endsection
