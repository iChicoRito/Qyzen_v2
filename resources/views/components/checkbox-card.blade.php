{{-- Standard toggleable option used in modal forms. Two variants:
       variant="card"   — bordered checkbox card (default; the /admin/users roles layout)
       variant="switch" — bordered row with a kt-switch toggle (demo permissions-toggle style)
     Props: name, value, title, desc (optional), checked (bool), class (extra input classes),
     icon (optional ki-filled name, switch variant only). --}}
@props([
    'name',
    'value',
    'title',
    'desc' => null,
    'checked' => false,
    'class' => '',
    'variant' => 'card',
    'icon' => 'shield-tick',
])
@if ($variant === 'switch')
    <label class="rounded-xl border border-border p-4 flex items-center justify-between gap-2.5 cursor-pointer w-full">
        <span class="flex items-center gap-3.5 min-w-0">
            <span class="flex items-center justify-center size-9 rounded-lg bg-muted/40 shrink-0">
                <i class="ki-filled ki-{{ $icon }} text-lg text-muted-foreground"></i>
            </span>
            <span class="flex flex-col gap-1 min-w-0">
                <span class="leading-none font-medium text-sm text-mono truncate">{{ $title }}</span>
                @if ($desc)<span class="text-xs text-secondary-foreground truncate">{{ $desc }}</span>@endif
            </span>
        </span>
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}"
               class="kt-switch kt-switch-sm shrink-0 {{ $class }}" @checked($checked)>
    </label>
@else
    <label class="flex items-start gap-2 border border-border rounded-lg p-3 cursor-pointer w-full">
        <input type="checkbox" name="{{ $name }}" value="{{ $value }}"
               class="kt-checkbox kt-checkbox-sm mt-1 {{ $class }}" @checked($checked)>
        <span class="flex flex-col gap-1">
            <span class="text-sm font-medium text-mono">{{ $title }}</span>
            @if ($desc)<span class="text-xs text-secondary-foreground">{{ $desc }}</span>@endif
        </span>
    </label>
@endif
