{{-- F2: view user. Renders as a bare fragment inside the shared modal under ?modal=1.
     Layout mirrors demo1 public-profile/teams.html team card (centered avatar + dashed rows + footer). --}}
@php $isModal = request()->boolean('modal'); @endphp
@extends($isModal ? 'layouts.fragment' : 'admin.layout')
@section('title', 'User')
@section('heading', $user->name)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content grid gap-7 py-7.5">
            {{-- Centered identity --}}
            <div class="grid place-items-center gap-4">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-user text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ $user->name }}</span>
                    <a href="mailto:{{ $user->email }}" class="text-sm text-secondary-foreground text-center hover:text-primary">{{ $user->email }}</a>
                </div>
            </div>

            {{-- Detail rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">User ID</span>
                    <span class="text-sm text-mono">{{ $user->user_id }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Type</span>
                    <span class="kt-badge kt-badge-outline kt-badge-primary">{{ ucfirst($user->user_type) }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Roles</span>
                    <div class="flex flex-wrap gap-1.5 justify-end">
                        @forelse ($user->roles as $r)
                            <span class="kt-badge kt-badge-outline">{{ ucfirst($r->name) }}</span>
                        @empty
                            <span class="text-sm text-secondary-foreground">—</span>
                        @endforelse
                    </div>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Status</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap mt-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Verified</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $user->email_verified_at ? 'success' : 'warning' }}">{{ $user->email_verified_at ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>
        <div class="kt-card-footer justify-end gap-2">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
            @else
                <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">Back</a>
                <a href="{{ route('admin.users.edit', $user) }}" class="kt-btn kt-btn-primary">Edit</a>
            @endif
        </div>
    </div>
@endsection
