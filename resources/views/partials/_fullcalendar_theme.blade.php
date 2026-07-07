{{-- Map FullCalendar's own CSS variables onto Metronic theme tokens so every calendar surface
     (column headers, borders, today cell, list-view day rows) follows light/dark like the rest of
     the UI. FullCalendar's global bundle injects fixed light-gray defaults at runtime
     (--fc-neutral-bg-color: hsla(0,0%,82%,.3)) that don't adapt; overriding the variables makes its
     default rules resolve to themed colours. Inline <style> (not @push('styles')) because this
     partial renders inside @yield('content'), after the head @stack has already emitted. --}}
<style nonce="{{ $cspNonce ?? '' }}">
    .fc {
        --fc-border-color: var(--border);
        --fc-neutral-bg-color: var(--muted);
        --fc-page-bg-color: var(--background);
        --fc-today-bg-color: var(--accent);
        --fc-list-event-hover-bg-color: var(--accent);
    }
</style>
