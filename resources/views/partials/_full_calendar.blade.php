{{-- Full calendar page (sidebar "Calendar"). Metronic-styled FullCalendar with the complete view
     switcher (month/week/day/list), colored block events themed from CSS vars, two-line eventContent,
     and a click-to-open detail modal — mirrors dist/html/demo1/plugins/fullcalendar.html.
     $events: array of ['title'=>, 'start'=>ISO, 'end'=>ISO|null, 'color'=>, 'subtitle'=>?, 'section'=>?,
       'timeLimit'=>?] (Assessment::calendarEvent()). --}}
@php $calId = $calId ?? 'full_calendar'; @endphp

@include('partials._fullcalendar_theme')

<div class="kt-card">
    <div class="kt-card-content p-5">
        <div id="{{ $calId }}"></div>
    </div>
</div>

{{-- Shared fetch-loaded modal (same reusable component/pattern as the rest of the app). The
     _modal-loader picks up the data-modal-url set on each event element and injects the fragment. --}}
<x-modal id="form_modal" title="Assessment details" width="460px" />

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/fullcalendar/index.global.min.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById(@json($calId));
        if (!el || typeof FullCalendar === 'undefined') return;

        var cssVar = function (name, fallback) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
        };
        var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
        // Detail-fragment URL template; each event fills its own uuid (opaque route key).
        var detailUrlTpl = @json(route('calendar.assessment', ['assessment' => '__UUID__']));

        new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            },
            buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List' },
            eventColor: cssVar('--primary', '#3b82f6'),
            eventTextColor: cssVar('--primary-foreground', '#ffffff'),
            eventDisplay: 'block',
            nowIndicator: true,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            views: {
                dayGridMonth: { titleFormat: { year: 'numeric', month: 'long' } },
                timeGridWeek: { titleFormat: { year: 'numeric', month: 'short', day: 'numeric' } },
                timeGridDay: { titleFormat: { year: 'numeric', month: 'short', day: 'numeric' } },
            },
            events: @json($events ?? []),
            eventContent: function (arg) {
                var sub = arg.event.extendedProps.subtitle || '';
                return { html: '<div class="flex flex-col gap-0.5 p-0.5">'
                    + '<span class="fc-event-title truncate">' + esc(arg.event.title) + '</span>'
                    + (sub ? '<span class="text-2xs opacity-90 truncate">' + esc(sub) + '</span>' : '')
                    + '</div>' };
            },
            // Tag each event element with the reusable modal-loader attributes (data-modal-url →
            // fetches the detail fragment into #form_modal). The delegated _modal-loader handles the
            // click, fetch, injection, and open — same path as every other detail modal in the app.
            eventDidMount: function (arg) {
                var uuid = arg.event.extendedProps.uuid;
                if (!uuid) return;
                arg.el.setAttribute('data-modal-url', detailUrlTpl.replace('__UUID__', uuid));
                arg.el.setAttribute('data-modal-target', '#form_modal');
                arg.el.setAttribute('data-modal-title', arg.event.title || 'Assessment details');
                arg.el.style.cursor = 'pointer';
            },
        }).render();
    });
</script>
@endpush
