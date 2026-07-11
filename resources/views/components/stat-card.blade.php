@props(['label', 'value', 'icon' => 'chart-simple', 'hint' => null])

{{-- Dashboard metric card (kt-card). Reused across the three role dashboards. --}}
<div class="kt-card">
    <div class="kt-card-content flex items-center justify-between gap-3 p-5">
        <div class="flex flex-col gap-1 min-w-0">
            <span class="text-2xl font-semibold text-mono leading-none">{{ $value }}</span>
            <span class="text-sm font-medium text-secondary-foreground break-words">{{ $label }}</span>
            @if ($hint)
                <span class="text-xs text-muted-foreground break-words">{{ $hint }}</span>
            @endif
        </div>
        <span class="inline-flex items-center justify-center size-11 rounded-lg bg-primary/10 text-primary shrink-0">
            <i class="ki-filled ki-{{ $icon }} text-xl"></i>
        </span>
    </div>
</div>
