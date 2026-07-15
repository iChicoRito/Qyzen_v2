@extends('layouts.auth')

@section('title', 'Change temporary password')

@section('card')
  <form action="{{ route('password.force.update') }}" method="POST" class="kt-card-content flex flex-col gap-5 p-10" autocomplete="off" novalidate>
    @csrf
    @method('PUT')

    <div class="text-center mb-2.5">
      <div class="mx-auto mb-4 inline-flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
        <i class="ki-filled ki-lock-2 text-xl"></i>
      </div>
      <h3 class="text-lg font-medium text-mono leading-none mb-2.5">Change your temporary password</h3>
      <div class="flex items-center justify-center font-medium">
        <span class="text-sm text-secondary-foreground">Set a new password before continuing.</span>
      </div>
    </div>

    @if ($requiresCurrentPassword)
      <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">Temporary password</label>
        <input type="password" name="current_password" class="kt-input" required autofocus />
        @error('current_password')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
      </div>
    @endif

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">New password</label>
      <input type="password" name="password" class="kt-input" required @if (! $requiresCurrentPassword) autofocus @endif />
      @error('password')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="kt-form-label font-normal text-mono">Confirm new password</label>
      <input type="password" name="password_confirmation" class="kt-input" required />
    </div>

    <span class="text-xs text-secondary-foreground">Use at least 8 characters with uppercase, lowercase, number, and symbol.</span>

    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">Change Password</button>
  </form>
@endsection
