@extends('admin.layout')

@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    @include('admin._status')

    <form method="POST" action="{{ route('admin.settings.update') }}" class="kt-card max-w-2xl">
        @csrf
        @method('PUT')
        <div class="kt-card-header">
            <h3 class="kt-card-title">Offline registration</h3>
        </div>
        <div class="kt-card-content flex flex-col gap-4">
            <x-checkbox-card
                variant="switch"
                name="offline_registration_enabled"
                value="1"
                title="Enable offline student registration"
                desc="Student accounts are verified, active, and shown with local credentials instead of sending email."
                icon="wifi-square"
                :checked="$offlineRegistrationEnabled" />
            @error('offline_registration_enabled')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
        </div>
        <div class="kt-card-footer justify-end">
            <button type="submit" class="kt-btn kt-btn-primary">Save settings</button>
        </div>
    </form>
@endsection
