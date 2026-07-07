{{-- Shared profile card body (avatar + identity + detail rows). Layout from demo1
     network/user-cards/team-crew.html: rounded avatar, then name / student no. / email
     below it, followed by the User ID / Type / Roles / Status / Verified rows.
     Caller wraps this in a .kt-card and supplies its own footer. Needs $user with roles. --}}
@php $initial = strtoupper(mb_substr($user->given_name ?: $user->name, 0, 1)); @endphp
<div class="kt-card-content grid gap-7 py-7.5">
    {{-- Centered identity --}}
    <div class="grid place-items-center gap-4">
        <div class="size-20 shrink-0">
            @if ($user->profile_picture)
                <img class="rounded-full size-20 object-cover ring-1 ring-input" src="{{ asset($user->profile_picture) }}" alt="{{ $user->name }}" />
            @else
                <span class="flex items-center justify-center size-20 rounded-full bg-primary/10 text-primary text-2xl font-semibold">{{ $initial }}</span>
            @endif
        </div>
        <div class="grid place-items-center gap-0.5 text-center">
            <span class="text-base font-medium text-mono">{{ $user->name }}</span>
            <a href="mailto:{{ $user->email }}" class="text-sm text-secondary-foreground hover:text-primary">{{ $user->email }}</a>
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
