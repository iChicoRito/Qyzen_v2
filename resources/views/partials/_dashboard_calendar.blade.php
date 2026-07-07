{{-- Minimal dashboard calendar (right rail): a compact month grid. Each date with assessments shows
     a SINGLE indicator dot; hovering the date opens a styled popover (beside the calendar, never over
     the grid) listing that day's assessments. Collapsible to save space. The full, functional calendar
     (with an event-detail modal) lives on the sidebar "Calendar" page (partials/_full_calendar.blade.php).
     $events: array of ['title'=>, 'start'=>ISO, 'end'=>ISO|null, 'color'=>, 'subtitle'=>?]
       (Assessment::calendarEvent()).
     $calId: optional mount id (default dashboard_calendar). --}}
@php $calId = $calId ?? 'dashboard_calendar'; @endphp

@include('partials._fullcalendar_theme')

<div class="kt-card">
    <div class="kt-card-header cursor-pointer" id="{{ $calId }}_toggle" role="button" tabindex="0" aria-expanded="true">
        <h3 class="kt-card-title">Calendar</h3>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Collapse calendar" tabindex="-1">
            <i class="ki-filled ki-arrow-down transition-transform rotate-180" id="{{ $calId }}_chevron"></i>
        </button>
    </div>
    <div class="kt-card-content" id="{{ $calId }}_body">
        <div id="{{ $calId }}"></div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/fullcalendar/index.global.min.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById(@json($calId));
        if (!el || typeof FullCalendar === 'undefined') return;

        var events = @json($events ?? []);
        var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
        var ymd = function (d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        };

        // date → [{title, subtitle, color}] for the indicator dot + hover popover. Each event's window
        // spans start..end inclusive, so mark every day it covers (guarded against runaway ranges).
        var byDate = {};
        events.forEach(function (ev) {
            if (!ev.start) return;
            var s = new Date(ev.start), e = ev.end ? new Date(ev.end) : new Date(ev.start);
            var day = new Date(s.getFullYear(), s.getMonth(), s.getDate());
            var last = new Date(e.getFullYear(), e.getMonth(), e.getDate());
            for (var guard = 0; day <= last && guard < 400; guard++) {
                (byDate[ymd(day)] = byDate[ymd(day)] || []).push({ title: ev.title, subtitle: ev.subtitle, color: ev.color });
                day.setDate(day.getDate() + 1);
            }
        });

        // ONE shared popover, appended to <body> and positioned beside the calendar card (never over
        // the grid). kt-tooltip-light = themed popover surface (--popover / --popover-foreground +
        // border) so the foreground text tokens read correctly in light and dark. pointer-events:none
        // so it never steals the hover.
        var LIMIT = 5;
        var pop = document.createElement('div');
        pop.className = 'kt-tooltip kt-tooltip-light text-start';
        pop.style.cssText = 'position:fixed; max-width:240px; z-index:100; pointer-events:none;';
        document.body.appendChild(pop);
        var hideTimer;

        var buildContent = function (date, list) {
            var dateLabel = date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            // Each entry is ONE truncating line; min-w-0 lets the text shrink so truncate engages and
            // the card honours its max-width.
            var rows = list.slice(0, LIMIT).map(function (it) {
                var label = '<span class="text-mono">' + esc(it.title) + '</span>'
                    + (it.subtitle ? ' <span class="text-secondary-foreground">· ' + esc(it.subtitle) + '</span>' : '');
                return '<div class="flex items-center gap-2">'
                    + '<span class="inline-block size-2 rounded-full shrink-0" style="background-color: ' + esc(it.color || '#3b82f6') + ';"></span>'
                    + '<span class="text-xs truncate min-w-0">' + label + '</span>'
                    + '</div>';
            }).join('');
            if (list.length > LIMIT) rows += '<div class="text-xs text-secondary-foreground ps-4">…and ' + (list.length - LIMIT) + ' more</div>';
            return '<div class="font-semibold text-mono text-xs mb-2 pb-2 border-b border-border">' + esc(dateLabel) + '</div>'
                + '<div class="flex flex-col gap-1.5">' + rows + '</div>';
        };
        var hidePop = function () { pop.classList.remove('show'); };
        var showPop = function (cellEl, date, list) {
            pop.innerHTML = buildContent(date, list);
            pop.classList.add('show'); // measurable now
            var card = el.closest('.kt-card').getBoundingClientRect();
            var cell = cellEl.getBoundingClientRect();
            var pr = pop.getBoundingClientRect();
            var gap = 8;
            // Prefer the empty space left of the calendar card; flip to the right if it won't fit.
            var left = card.left - pr.width - gap;
            if (left < gap) left = Math.min(card.right + gap, window.innerWidth - pr.width - gap);
            var top = Math.max(gap, Math.min(cell.top, window.innerHeight - pr.height - gap));
            pop.style.left = left + 'px';
            pop.style.top = top + 'px';
        };

        var cal = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: { left: 'title', center: '', right: 'prev,next' },
            events: [], // no per-event rendering — one indicator dot per day is added below
            dayCellDidMount: function (arg) {
                var list = byDate[ymd(arg.date)];
                if (!list || !list.length) return;

                // Single indicator dot.
                var slot = arg.el.querySelector('.fc-daygrid-day-events');
                if (slot) {
                    var dot = document.createElement('div');
                    dot.className = 'flex justify-center pt-0.5';
                    dot.innerHTML = '<span class="inline-block size-1.5 rounded-full" style="background-color: var(--primary);"></span>';
                    slot.appendChild(dot);
                }

                arg.el.style.cursor = 'pointer';
                arg.el.addEventListener('mouseenter', function () { clearTimeout(hideTimer); showPop(arg.el, arg.date, list); });
                arg.el.addEventListener('mouseleave', function () { hideTimer = setTimeout(hidePop, 120); });
            },
            datesSet: hidePop, // dismiss when navigating months
        });
        cal.render();

        // Collapse / expand the calendar body (re-measure on expand — FullCalendar mis-sizes if it
        // was laid out while hidden).
        var toggle = document.getElementById(@json($calId . '_toggle'));
        var body = document.getElementById(@json($calId . '_body'));
        var chevron = document.getElementById(@json($calId . '_chevron'));
        var setCollapsed = function (collapsed) {
            body.classList.toggle('hidden', collapsed);
            chevron.classList.toggle('rotate-180', !collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            if (!collapsed) cal.updateSize();
        };
        toggle.addEventListener('click', function () { setCollapsed(!body.classList.contains('hidden')); });
        toggle.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); setCollapsed(!body.classList.contains('hidden')); }
        });
    });
</script>
@endpush
