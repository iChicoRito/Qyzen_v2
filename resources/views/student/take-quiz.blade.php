{{-- Task 24: two-panel take-quiz (Result-page layout). Left panel = persistent details + relocated
     timer + progress + warnings + educator; right panel = questions. On mobile the left panel is a
     KTUI off-canvas drawer (copies the app-sidebar pattern), and a slim sticky bar keeps the timer +
     progress always visible. MC options are highlight-only (no radio circle). Questions carry NO
     correct_answer (server hides it); grading is server-side on submit. --}}
@extends('layouts.exam')
@section('title', 'Take Assessment')

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    /* Hide the native radio circle — selection is shown by the card highlight only. */
    .qz-option input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
    .qz-option { transition: border-color .15s, background-color .15s; }
    .qz-option:hover { border-color: var(--color-primary, #1b84ff); }
    .qz-option.selected { border-color: var(--color-primary, #1b84ff); background: color-mix(in srgb, var(--color-primary, #1b84ff) 6%, transparent); }
    /* Circular letter badge. */
    .qz-hex { border-radius: 50%; }
    .qz-option.selected .qz-hex { background: var(--color-primary, #1b84ff); color: #fff; }
    .qz-option.selected .qz-title { color: var(--color-primary, #1b84ff); }
    .qz-timer.warn { color: #ca8a04; }
    .qz-timer.danger { color: #dc2626; }
    .qz-timer.shake { animation: qz-shake .5s cubic-bezier(.36,.07,.19,.97) infinite; }
    @keyframes qz-shake { 10%,90%{transform:translateX(-1px)} 30%,70%{transform:translateX(2px)} 50%{transform:translateX(-2px)} }
    .qz-hide { display: none !important; }

    /* Two-panel grid (prebuilt bundle lacks responsive/arbitrary grid utilities). */
    .qz-quiz-grid { display: grid; gap: 1.25rem; align-items: start; grid-template-columns: minmax(0, 1fr); margin-top: 1.5rem; }
    @media (min-width: 1024px) {
        .qz-quiz-grid { grid-template-columns: 320px minmax(0, 1fr); }
        /* align-self:start keeps the panel top-aligned with the questions; sticky top matches the
           grid's own top margin so the panel doesn't ride higher than the quiz column when scrolled. */
        #qz-panel { position: sticky; top: 1.5rem; align-self: start; }
    }
    /* Off-canvas width when the panel is a drawer (mobile only). */
    @media (max-width: 1023.98px) { #qz-panel.kt-drawer { width: 300px; max-width: 85vw; } }
</style>
@endpush

@section('content')
    {{-- Mobile-only sticky bar: drawer toggle + compact timer + progress (always visible during a timed exam). --}}
    <div class="lg:hidden sticky top-0 z-30 bg-background border-b border-border -mx-4 px-4 py-3 mb-5 flex items-center gap-3">
        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline shrink-0" data-kt-drawer-toggle="#qz-panel">
            <i class="ki-filled ki-information-2"></i> Details
        </button>
        <span class="qz-timer text-xl font-bold text-mono tabular-nums leading-none shrink-0">--:--:--</span>
        <div class="flex-1 min-w-0">
            <div class="w-full rounded-full bg-accent overflow-hidden" style="height: 6px;">
                <div class="qz-progress bg-primary transition-all duration-300" style="width: 0%; height: 6px;"></div>
            </div>
        </div>
        <span class="qz-counter text-xs font-medium text-secondary-foreground shrink-0">0 / {{ $questions->count() }}</span>
    </div>

    <div class="qz-quiz-grid">
        {{-- Left panel — persistent on desktop, off-canvas drawer on mobile (app-sidebar pattern). --}}
        <div id="qz-panel"
             class="kt-card hidden lg:flex flex-col [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
             data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0">
            <div class="kt-card-content p-5 grid gap-5">
                {{-- Drawer close (mobile only) --}}
                <div class="flex items-center justify-between lg:hidden">
                    <span class="text-sm font-semibold text-mono">Assessment</span>
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-drawer-dismiss="true"><i class="ki-filled ki-cross"></i></button>
                </div>

                {{-- Timer (relocated from the old top bar) --}}
                <div class="flex flex-col items-center gap-1">
                    <span class="qz-timer text-4xl font-bold text-mono tabular-nums leading-none">--:--:--</span>
                    <span class="text-xs uppercase tracking-wide text-secondary-foreground">Time remaining</span>
                </div>

                {{-- Progress --}}
                <div class="grid gap-1.5">
                    <div class="w-full rounded-full bg-accent overflow-hidden" style="height: 8px;">
                        <div class="qz-progress bg-primary transition-all duration-300" style="width: 0%; height: 8px;"></div>
                    </div>
                    <span class="qz-counter text-xs font-medium text-secondary-foreground text-center">0 of {{ $questions->count() }} answered</span>
                </div>

                {{-- Warning triggers --}}
                <div class="rounded-lg border border-border p-3 text-sm grid gap-1.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-secondary-foreground">Warnings used</span>
                        <span class="qz-warnings text-mono font-medium">{{ $warnings }}{{ $assessment->cheating_attempts ? ' / '.$assessment->cheating_attempts : '' }}</span>
                    </div>
                    {{-- Last warning reason (persists after the toast fades so students know why). --}}
                    <span class="qz-warn-reason text-xs text-destructive hidden"></span>
                </div>

                {{-- Assessment + educator details --}}
                @php
                    $rows = [
                        ['Assessment', $assessment->assessment_code ?? '—'],
                        ['Subject', optional($assessment->subject)->subject_name ?? '—'],
                        ['Section', optional($assessment->section)->section_name ?? '—'],
                        ['Term', optional($assessment->academicTerm)->term_name ?? '—'],
                        ['Educator', optional($assessment->educator)->name ?? '—'],
                        ['Questions', (string) $questions->count()],
                        ['Time limit', ($assessment->time_limit ?? '—').' min'],
                        ['Shuffle', $assessment->is_shuffle ? 'On' : 'Off'],
                        ['Warning limit', $assessment->cheating_attempts ? (string) $assessment->cheating_attempts : 'None'],
                    ];
                @endphp
                <div class="grid gap-2 rounded-lg border border-border p-3.5 text-sm">
                    @foreach ($rows as $ri => [$label, $val])
                        @if ($ri > 0)<div class="border-t border-border border-dashed"></div>@endif
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-secondary-foreground shrink-0">{{ $label }}</span>
                            <span class="text-mono text-end">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right panel — questions. --}}
        <div class="min-w-0">
            <form id="qz-form" method="POST" action="{{ route('student.take-quiz.submit', $assessment) }}">
                @csrf
                <input type="hidden" name="warnings" id="qz-warnings-input" value="{{ $warnings }}">

                <div id="qz-questions" class="flex flex-col gap-6">
                    @foreach ($questions as $i => $q)
                        @php $given = $draftAnswers[$q->id] ?? ($draftAnswers[(string) $q->id] ?? null); @endphp
                        <div class="kt-card qz-q" data-idx="{{ $i }}">
                            <div class="kt-card-header flex-col items-start gap-1 py-4">
                                <span class="text-xs font-semibold uppercase tracking-wide text-primary">Question No. {{ $i + 1 }}</span>
                                <p class="text-base font-medium text-mono">{{ $q->question }}</p>
                            </div>
                            <div class="kt-card-content p-6">
                                @if ($q->quiz_type === 'multiple_choice')
                                    <div class="grid sm:grid-cols-2 gap-3">
                                        @foreach (($q->choices ?? []) as $key => $text)
                                            @php $letter = chr(65 + $loop->index); @endphp
                                            <label class="qz-option flex items-center gap-4 rounded-xl border border-border p-4 cursor-pointer {{ $given === (string) $key ? 'selected' : '' }}">
                                                <input type="radio" class="sr-only qz-answer" name="answers[{{ $q->id }}]" value="{{ $key }}" @checked($given === (string) $key)>
                                                <span class="qz-hex flex items-center justify-center shrink-0 size-12 bg-accent text-base font-semibold text-secondary-foreground">{{ $letter }}</span>
                                                <div class="flex flex-col min-w-0">
                                                    <span class="qz-title text-sm font-semibold text-mono">Choice {{ $letter }}</span>
                                                    <span class="text-sm text-secondary-foreground">{{ $text }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <input type="text" class="kt-input qz-answer" name="answers[{{ $q->id }}]" value="{{ $given ?? '' }}" placeholder="Type your answer" autocomplete="off">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Submit (full-width, centered, at the bottom of the question list) --}}
                <div class="mt-6 mb-10">
                    <button type="button" class="kt-btn kt-btn-primary w-full justify-center" id="qz-submit"><i class="ki-filled ki-check"></i> Submit</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        // If restored from bfcache (Back button after submit), refetch so the server gate runs.
        window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });

        const draftUrl = @json(route('student.take-quiz.draft', $assessment));
        const token = @json(csrf_token());
        const limit = {{ (int) ($assessment->cheating_attempts ?: 0) }};
        const total = {{ $questions->count() }};
        const isTouch = window.matchMedia('(pointer: coarse)').matches;
        let remaining = {{ (int) $remainingSeconds }};
        const totalSeconds = {{ (int) $assessment->time_limit * 60 }}; // original full time — color thresholds scale off this
        const hasTimer = {{ $assessment->time_limit > 0 ? 'true' : 'false' }};
        let warnings = {{ (int) $warnings }};
        let submitted = false, dirty = false;

        const $ = (id) => document.getElementById(id);
        const $$ = (sel) => document.querySelectorAll(sel); // timer/progress/counter/warnings have desktop + mobile instances
        const form = $('qz-form');

        // ---- Answers ----
        function collectAnswers() {
            const data = {};
            $$('.qz-answer').forEach(el => {
                const id = el.name.replace(/answers\[|\]/g, '');
                if (el.type === 'radio') { if (el.checked) data[id] = el.value; }
                else if (el.value.trim() !== '') data[id] = el.value;
            });
            return data;
        }
        function unansweredCount() { return total - Object.keys(collectAnswers()).length; }
        $$('.qz-answer').forEach(el => {
            const evt = el.type === 'radio' ? 'change' : 'input';
            el.addEventListener(evt, () => {
                dirty = true;
                if (el.type === 'radio') {
                    document.querySelectorAll(`input[name="${el.name}"]`).forEach(r =>
                        r.closest('.qz-option')?.classList.toggle('selected', r.checked));
                }
                updateProgress();
                scheduleSave();
            });
        });

        // ---- Autosave (debounced, dirty-only) ----
        let saveTimer = null;
        function scheduleSave() { clearTimeout(saveTimer); saveTimer = setTimeout(save, 800); }
        function save(manual) {
            if (!dirty && !manual) return;
            dirty = false;
            fetch(draftUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
                body: JSON.stringify({answers: collectAnswers(), warnings})
            }).then(() => {}) // silent background save — no visual indicator
              .catch(() => { dirty = true; });
        }

        // ---- Submit ----
        function doSubmit() {
            if (submitted) return;
            submitted = true;
            $('qz-warnings-input').value = warnings;
            form.submit();
        }
        function attemptSubmit() {
            const blank = unansweredCount();
            const opts = { showCancelButton: true, cancelButtonText: 'Keep working' };
            if (blank > 0) {
                Swal.fire({ icon: 'warning', title: 'Submit anyway?',
                    text: `You have ${blank} unanswered question(s). Once submitted, your score is final.`,
                    confirmButtonText: 'Submit Anyway', ...opts }).then(r => { if (r.isConfirmed) doSubmit(); });
            } else {
                Swal.fire({ icon: 'question', title: 'Submit assessment?', text: 'Once submitted, your score is final.',
                    confirmButtonText: 'Submit', ...opts }).then(r => { if (r.isConfirmed) doSubmit(); });
            }
        }

        // ---- Progress (answered / total) — updates every .qz-progress / .qz-counter instance ----
        function updateProgress() {
            const answered = Object.keys(collectAnswers()).length;
            const pct = (total ? (answered / total * 100) : 0) + '%';
            $$('.qz-counter').forEach(el => { el.textContent = `${answered} of ${total} answered`; });
            $$('.qz-progress').forEach(el => { el.style.width = pct; });
        }
        $('qz-submit').addEventListener('click', attemptSubmit);
        updateProgress();

        // ---- Timer (server-authoritative; resumes from real elapsed) — drives all .qz-timer instances ----
        function setTimer(text, state) {
            $$('.qz-timer').forEach(el => {
                el.textContent = text;
                if (state) {
                    el.classList.toggle('warn', state.warn);
                    el.classList.toggle('danger', state.danger);
                    el.classList.toggle('shake', state.shake);
                }
            });
        }
        if (hasTimer) {
            const tick = () => {
                if (submitted) return;
                if (remaining <= 0) {
                    Swal.fire({ icon: 'info', title: "Time's Up!", text: 'Your assessment is being submitted.',
                        timer: 1800, showConfirmButton: false, allowOutsideClick: false }).then(doSubmit);
                    return;
                }
                const h = Math.floor(remaining / 3600), m = Math.floor((remaining % 3600) / 60), s = remaining % 60;
                // Thresholds scale to the original time limit: green > 50%, yellow ≤ 50%, red ≤ 20%,
                // shake ≤ 10% — so a short quiz doesn't start out yellow.
                const t = totalSeconds || remaining;
                setTimer(`${h}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`, {
                    warn: remaining <= t * 0.5 && remaining > t * 0.2,
                    danger: remaining <= t * 0.2,
                    shake: remaining <= t * 0.1,
                });
                remaining--; setTimeout(tick, 1000);
            };
            tick();
        } else {
            setTimer('No limit');
        }

        // ---- Integrity (essentials) — skipped on touch devices ----
        function bumpWarning(reason) {
            warnings++;
            $('qz-warnings-input').value = warnings;
            $$('.qz-warnings').forEach(el => { el.textContent = warnings + (limit > 0 ? ' / ' + limit : ''); });
            // Surface the reason in the panel so it stays visible after the toast auto-dismisses.
            $$('.qz-warn-reason').forEach(el => { el.textContent = 'Last warning: ' + reason; el.classList.remove('hidden'); });
            dirty = true; save();
            if (limit > 0 && warnings >= limit) {
                Swal.fire({ icon: 'error', title: 'Warning limit reached',
                    text: `${reason} That was your last allowed warning — your assessment is being submitted.`,
                    timer: 2600, showConfirmButton: false, allowOutsideClick: false }).then(doSubmit);
            } else {
                const left = limit > 0 ? `${limit - warnings} warning(s) remaining before your attempt is auto-submitted.` : 'This has been recorded.';
                Swal.fire({ icon: 'warning', title: 'Assessment warning', html: `<b>${reason}</b><br><span style="font-size:.9em">${left}</span>`,
                    timer: 3200, showConfirmButton: false });
            }
        }
        if (!isTouch) {
            let cooldown = false;
            function violation(reason) {
                if (submitted || cooldown) return;
                cooldown = true; setTimeout(() => { cooldown = false; }, 1000);
                bumpWarning(reason);
            }
            ['copy','cut','paste'].forEach(ev => document.addEventListener(ev, e => { e.preventDefault(); violation('You tried to copy, cut, or paste text. This is not allowed during the assessment.'); }));
            document.addEventListener('contextmenu', e => e.preventDefault());
            document.addEventListener('visibilitychange', () => { if (document.hidden) violation('You switched to another tab or minimized the window.'); });
            window.addEventListener('blur', () => violation('You clicked away from the assessment window.'));
            document.addEventListener('mouseleave', () => violation('Your cursor left the assessment area.'));

            // Devtools / view-source shortcuts. NOTE: browsers open their own devtools above the
            // page, so preventDefault can't always stop F12 — but the attempt is caught + counted.
            document.addEventListener('keydown', e => {
                const k = (e.key || '').toUpperCase();
                const blocked = e.key === 'F12'
                    || ((e.ctrlKey || e.metaKey) && e.shiftKey && (k === 'I' || k === 'J' || k === 'C'))
                    || ((e.ctrlKey || e.metaKey) && k === 'U');
                if (blocked) { e.preventDefault(); violation('You used a developer-tools or view-source keyboard shortcut.'); }
            });

            // Heuristic devtools-open detector (docked panel changes the viewport delta). Fires
            // once per open; resets when closed. Can occasionally false-positive.
            const gapNow = () => Math.max(window.outerWidth - window.innerWidth, window.outerHeight - window.innerHeight);
            let devtoolsOpen = gapNow() > 170; // seed from current state so a small window doesn't false-fire
            setInterval(() => {
                const open = gapNow() > 170;
                if (open && !devtoolsOpen) violation('Developer tools appear to be open.');
                devtoolsOpen = open;
            }, 1000);
        }
    })();
    </script>
    @endpush
@endsection
