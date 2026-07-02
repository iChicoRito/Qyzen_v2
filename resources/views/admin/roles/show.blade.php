{{-- F5: view role + assigned permissions. Fragment inside the shared modal under ?modal=1.
     Layout mirrors demo1 public-profile/teams.html team card. --}}
@php $isModal = request()->boolean('modal'); @endphp
@extends($isModal ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Role')
@section('heading', $role->name)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content grid gap-7 py-7.5">
            {{-- Centered identity --}}
            <div class="grid place-items-center gap-4">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-shield-tick text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ ucfirst($role->name) }}</span>
                    <span class="text-sm text-secondary-foreground text-center">{{ $role->description ?: 'No description.' }}</span>
                </div>
            </div>

            {{-- Detail rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Status</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $role->is_active ? 'success' : 'secondary' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">System role</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $role->is_system ? 'info' : 'secondary' }}">{{ $role->is_system ? 'Yes' : 'No' }}</span>
                </div>
                <div class="border-t border-input border-dashed mb-3.5"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 mb-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Permissions</span>
                    <span class="kt-badge kt-badge-outline">{{ $role->permissions->count() }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5 rounded-lg border border-input p-2.5 max-h-56 overflow-y-auto kt-scrollable-y">
                    @forelse ($role->permissions as $p)
                        <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">{{ $p->permission_string }}</span>
                    @empty
                        <span class="text-sm text-secondary-foreground">None assigned.</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="kt-card-footer justify-end gap-2">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
                <a href="#" class="kt-btn kt-btn-primary" data-modal-url="{{ route('admin.roles.edit', $role) }}" data-modal-target="#form_modal" data-modal-title="Edit role">Edit</a>
            @else
                <a href="{{ route('admin.roles.index') }}" class="kt-btn kt-btn-outline">Back</a>
                <a href="{{ route('admin.roles.edit', $role) }}" class="kt-btn kt-btn-primary">Edit</a>
            @endif
        </div>
    </div>
@endsection
