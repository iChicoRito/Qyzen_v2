@extends('admin.layout')

@section('title', 'Settings')
@section('heading', 'Settings')

@section('content')
    @include('admin._status')

    <form method="POST" action="{{ route('admin.settings.update') }}" class="kt-card">
        @csrf
        @method('PUT')
        <div class="kt-card-header">
            <h3 class="kt-card-title">Admin Settings</h3>
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

            <div class="rounded-xl border border-border p-4 flex items-center justify-between gap-2.5 w-full">
                <span class="flex items-center gap-3.5 min-w-0">
                    <span class="flex items-center justify-center size-9 rounded-lg bg-muted/40 shrink-0">
                        <i class="ki-filled ki-cloud-download text-lg text-muted-foreground"></i>
                    </span>
                    <span class="flex flex-col gap-1 min-w-0">
                        <span class="leading-none font-medium text-sm text-mono truncate">Download Database</span>
                        <span class="text-xs text-secondary-foreground truncate">Export a full schema and data snapshot of the database as a SQL file.</span>
                    </span>
                </span>
                <a href="{{ route('admin.settings.database.download') }}" class="kt-btn kt-btn-outline shrink-0">Download Database</a>
            </div>
        </div>
        <div class="kt-card-footer justify-end">
            <button type="submit" class="kt-btn kt-btn-primary">Save settings</button>
        </div>
    </form>
@endsection
