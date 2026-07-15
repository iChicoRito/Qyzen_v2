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
    <x-modal id="qz-fullscreen-gate" title="Fullscreen mode is required" width="560px" :persistent="true" :show-close="false" :scrollable="false">
        <div class="px-7 pt-6 pb-7 grid gap-5 text-center">
            <div class="mx-auto size-12 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                <i class="ki-filled ki-screen"></i>
            </div>
            <p class="text-sm text-secondary-foreground">
                This assessment uses fullscreen mode to help prevent accidental cursor exits and false cheating warnings.
            </p>
            <button type="button" class="kt-btn kt-btn-primary justify-center mt-1" id="qz-fullscreen-start">I Understand</button>
        </div>
    </x-modal>

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

    <div class="qz-quiz-grid" id="qz-session">
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

                {{-- Hints remaining --}}
                @if ($assessment->allow_hint && $hintsRemaining > 0)
                    <div class="rounded-lg border border-border p-3 text-sm flex items-center justify-between gap-2">
                        <span class="text-secondary-foreground">Hints remaining</span>
                        <span class="qz-hints-remaining text-mono font-medium">{{ $hintsRemaining }}</span>
                    </div>
                @endif

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
                                <div class="flex items-center justify-between w-full gap-3">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-primary">Question No. {{ $i + 1 }}</span>
                                    @if ($assessment->allow_hint && $hintsRemaining > 0)
                                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline qz-hint-btn" data-quiz-id="{{ $q->id }}">
                                            <i class="ki-filled ki-lamp"></i> Hint
                                        </button>
                                    @endif
                                </div>
                                <p class="text-base font-medium text-mono">{{ $q->question }}</p>
                                <p class="qz-hint-text text-xs text-primary hidden"></p>
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

    {{-- Task 02: hint mini-game modal. One shared instance, populated per-click. Persistent —
         no backdrop/ESC dismiss — so the only way out besides winning is the Skip button
         (also wired to skip, not KTUI's dismiss) — every hint interaction must cost one credit. --}}
    <div class="kt-modal kt-modal-center" data-kt-modal="true" data-kt-modal-persistent="true" id="qz-hint-modal">
        <div class="kt-modal-content" style="width: 100%; max-width: min(92vw, 560px);">
            <div class="kt-modal-header">
                <h3 class="kt-modal-title">Win a challenge to earn this hint</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" id="qz-hint-close"><i class="ki-filled ki-cross"></i></button>
            </div>
            <div class="kt-modal-body kt-scrollable-y flex flex-col gap-4">
                <div id="qz-hint-game" class="min-h-64 flex flex-col items-center justify-center gap-3"></div>
                <p id="qz-hint-result" class="text-sm font-medium text-center hidden"></p>
            </div>
            <div class="kt-modal-footer justify-end gap-2">
                <button type="button" class="kt-btn kt-btn-outline" id="qz-hint-skip">Skip (forfeit hint)</button>
            </div>
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
        let quizStarted = false;
        let authorizedFullscreenTransition = false;
        let fullscreenGuardTimer = null;
        let restoringFullscreen = false;

        const $ = (id) => document.getElementById(id);
        const $$ = (sel) => document.querySelectorAll(sel); // timer/progress/counter/warnings have desktop + mobile instances
        const form = $('qz-form');
        const fullscreenGate = $('qz-fullscreen-gate');
        const fullscreenStart = $('qz-fullscreen-start');
        const session = $('qz-session');
        let fullscreenModal = null;
        if (session && 'inert' in session) session.inert = true;

        // ---- Fullscreen gate ----
        function fullscreenElement() {
            return document.fullscreenElement
                || document.webkitFullscreenElement
                || document.mozFullScreenElement
                || document.msFullscreenElement
                || null;
        }

        function isFullscreenSupported() {
            const el = document.documentElement;
            return !!(el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen);
        }

        function markAuthorizedFullscreenTransition() {
            authorizedFullscreenTransition = true;
            clearTimeout(fullscreenGuardTimer);
            fullscreenGuardTimer = setTimeout(() => {
                authorizedFullscreenTransition = false;
                restoreFullscreen();
            }, 1500);
        }

        function requestQuizFullscreen() {
            if (!isFullscreenSupported()) return Promise.resolve(false);

            const el = document.documentElement;
            const request = el.requestFullscreen
                || el.webkitRequestFullscreen
                || el.mozRequestFullScreen
                || el.msRequestFullscreen;

            markAuthorizedFullscreenTransition();

            try {
                const result = request.call(el);
                return Promise.resolve(result).then(() => true).catch(() => false);
            } catch (e) {
                return Promise.resolve(false);
            }
        }

        function exitQuizFullscreen() {
            if (!fullscreenElement()) return Promise.resolve();

            const exit = document.exitFullscreen
                || document.webkitExitFullscreen
                || document.mozCancelFullScreen
                || document.msExitFullscreen;

            if (!exit) return Promise.resolve();
            markAuthorizedFullscreenTransition();

            try {
                return Promise.resolve(exit.call(document)).catch(() => {});
            } catch (e) {
                return Promise.resolve();
            }
        }

        function unlockQuiz() {
            quizStarted = true;
            if (session && 'inert' in session) session.inert = false;
            if (fullscreenModal) fullscreenModal.hide();
            else fullscreenGate?.classList.add('qz-hide');
        }

        function keepFullscreenGateOpen() {
            if (quizStarted || !fullscreenGate) return;
            if (fullscreenModal) fullscreenModal.show();
        }

        function restoreFullscreen() {
            if (!quizStarted || submitted || restoringFullscreen || !isFullscreenSupported() || fullscreenElement()) return;

            restoringFullscreen = true;
            Swal.fire({
                icon: 'warning',
                title: 'Return to fullscreen',
                text: 'Fullscreen mode is required until you submit this assessment.',
                confirmButtonText: 'Return to Fullscreen',
                allowOutsideClick: false,
                allowEscapeKey: false,
                confirmButtonColor: '#3475db',
            }).then(() => requestQuizFullscreen())
              .finally(() => { restoringFullscreen = false; });
        }

        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape' || quizStarted || !fullscreenGate) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            keepFullscreenGateOpen();
        }, true);

        fullscreenStart?.addEventListener('click', () => {
            fullscreenStart.disabled = true;
            requestQuizFullscreen().then(unlockQuiz);
        });

        ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(ev => {
            document.addEventListener(ev, () => {
                if (authorizedFullscreenTransition) return;
                restoreFullscreen();
            });
        });

        (function waitForFullscreenModal(attempts) {
            if (!fullscreenGate) return;
            if (typeof KTModal !== 'undefined') {
                fullscreenModal = KTModal.getOrCreateInstance(fullscreenGate);
                fullscreenModal.show();
                return;
            }
            if (attempts < 20) setTimeout(() => waitForFullscreenModal(attempts + 1), 100);
        })(0);

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
            }).then((r) => {
                if (!r.ok) {
                    // Silent by design (no visual indicator), but a failed save must still be
                    // logged and retried — otherwise answers stop persisting with zero feedback.
                    r.json().catch(() => ({})).then((data) => {
                        console.error('[quiz] autosave failed', r.status, data);
                    });
                    dirty = true;
                }
            }).catch(() => { dirty = true; });
        }

        // ---- Submit ----
        function doSubmit() {
            if (submitted) return;
            submitted = true;
            $('qz-warnings-input').value = warnings;
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while your assessment is graded.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: function () { Swal.showLoading(); }
            });
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form),
                credentials: 'same-origin'
            }).then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    if (!r.ok) throw data;
                    return data;
                });
            }).then(function (data) {
                exitQuizFullscreen().finally(function () {
                    window.location.href = data.redirect_url || @json(route('student.assessments.index'));
                });
            }).catch(function (data) {
                submitted = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Could not submit',
                    text: (data && data.message) || 'Please check your connection and try again.',
                    confirmButtonColor: '#3475db'
                });
            });
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

        // ---- Hints: modal mini-game gate (Task 02) ----
        // Win one randomly-picked challenge to reveal the answer; losing, timing out, or
        // skipping still consumes exactly one hint credit and reveals nothing.
        (function () {
            const hintUrl = @json(route('student.take-quiz.hint', $assessment));
            const modalEl = $('qz-hint-modal');
            const gameEl = $('qz-hint-game');
            const resultEl = $('qz-hint-result');
            const skipBtn = $('qz-hint-skip');
            const closeBtn = $('qz-hint-close');
            if (!modalEl) return; // hints not enabled for this session — nothing to wire

            let modal = null;
            let activeBtn = null;
            let cleanup = null; // current game's teardown (clears its timers/listeners)
            let settled = false; // guards against double-resolve (e.g. Skip after a game already finished)
            let introTimer = null;

            function clearGame() {
                if (cleanup) { cleanup(); cleanup = null; }
                if (introTimer) { clearTimeout(introTimer); introTimer = null; }
                gameEl.innerHTML = '';
                resultEl.classList.add('hidden');
            }

            function showIntro(title, description, renderGame) {
                const seconds = 8;
                gameEl.innerHTML = `<div class="text-center space-y-3 max-w-sm">
                    <h4 class="text-2xl font-bold">${title}</h4>
                    <p class="text-sm text-secondary-foreground">${description}</p>
                    <p class="text-sm font-medium">Starting in <span id="qz-intro-count">${seconds}</span>s</p>
                </div>`;
                const countEl = gameEl.querySelector('#qz-intro-count');
                let left = seconds;
                const tick = () => {
                    left--;
                    if (countEl) countEl.textContent = String(left);
                    if (left <= 0) {
                        if (introTimer) { clearTimeout(introTimer); introTimer = null; }
                        renderGame();
                    } else {
                        introTimer = setTimeout(tick, 1000);
                    }
                };
                introTimer = setTimeout(tick, 1000);
            }

            function finish(payload) {
                if (settled || !activeBtn) return;
                settled = true;
                if (cleanup) { cleanup(); cleanup = null; }
                const quizId = activeBtn.dataset.quizId;
                fetch(hintUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
                    body: JSON.stringify({quiz_id: quizId, ...payload})
                }).then(r => r.json().then(data => ({ok: r.ok, data})))
                  .then(({ok, data}) => {
                    if (!ok) { modal.hide(); Swal.fire({icon: 'info', title: 'No hint available', text: data.message || '', timer: 2000, showConfirmButton: false}); activeBtn.remove(); return; }
                    $$('.qz-hints-remaining').forEach(el => { el.textContent = data.remaining; });
                    if (data.hint) {
                        const text = activeBtn.closest('.qz-q').querySelector('.qz-hint-text');
                        text.textContent = 'Hint: ' + data.hint;
                        text.classList.remove('hidden');
                        activeBtn.remove();
                        modal.hide();
                    } else {
                        activeBtn.remove();
                        resultEl.textContent = 'No luck this time — the hint was consumed.';
                        resultEl.classList.remove('hidden');
                        setTimeout(() => modal.hide(), 1400);
                    }
                    if (data.remaining <= 0) $$('.qz-hint-btn').forEach(b => b.remove());
                  })
                  .catch(() => { settled = false; });
            }

            const GAMES = ['tictactoe', 'maze', 'math', 'simon', 'colormatch'];

            // KTUI's modal component must be loaded before we can create/show it. Under normal
            // conditions it's ready immediately (core.bundle.js + ktui.min.js load before this
            // script), but this guards against any production script-load race instead of
            // silently leaving every hint button dead with no diagnostics.
            function bindHintButtons() {
                modal = KTModal.getOrCreateInstance(modalEl);

                skipBtn.addEventListener('click', () => finish({outcome: 'skipped'}));
                closeBtn.addEventListener('click', () => finish({outcome: 'skipped'}));

                $$('.qz-hint-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        activeBtn = btn;
                        settled = false;
                        clearGame();
                        modal.show();
                        const pick = GAMES[Math.floor(Math.random() * GAMES.length)];
                        const { title, description } = GAME_INFO[pick];
                        showIntro(title, description, () => {
                            cleanup = RENDERERS[pick](gameEl, (outcome) => finish({outcome}));
                        });
                    });
                });
            }

            (function waitForKTModal(attempts) {
                if (typeof KTModal !== 'undefined') { bindHintButtons(); return; }
                if (attempts >= 20) {
                    console.error('[quiz] KTModal did not load — hint buttons disabled');
                    $$('.qz-hint-btn').forEach(b => {
                        b.disabled = true;
                        b.title = 'Hints are temporarily unavailable — please refresh the page.';
                    });
                    return;
                }
                setTimeout(() => waitForKTModal(attempts + 1), 100);
            })(0);

            const GAME_INFO = {
                tictactoe: {
                    title: 'Tic-Tac-Toe',
                    description: 'Beat the AI by making a line of three before it does.',
                },
                maze: {
                    title: 'Maze Escape',
                    description: 'Find your way from the start to the exit using the arrow controls.',
                },
                math: {
                    title: 'Math Challenge',
                    description: 'Solve the problem correctly to claim the hint.',
                },
                simon: {
                    title: 'Simon Sequence',
                    description: 'Watch the pattern and repeat it exactly.',
                },
                colormatch: {
                    title: 'Color Match',
                    description: 'Tap the button that matches the font color, not the word itself.',
                },
            };

            // ---- Game 1: Tic-tac-toe vs a medium-difficulty AI. Player = X, moves first. ----
            function renderTicTacToe(el, done) {
                const board = Array(9).fill(null);
                const lines = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
                const winner = (b) => lines.find(([a,b2,c]) => b[a] && b[a] === b[b2] && b[a] === b[c]);
                el.innerHTML = `<div class="grid grid-cols-3 gap-2" style="width:198px">${
                    board.map((_, i) => `<button type="button" class="kt-btn kt-btn-outline size-16 text-2xl font-bold" data-i="${i}"></button>`).join('')
                }</div><p class="text-xs text-secondary-foreground mt-2">You are X. Win the game to earn the hint.</p>`;
                const cells = el.querySelectorAll('button[data-i]');
                let over = false;
                function aiMove() {
                    const empty = board.map((v, i) => v ? null : i).filter(v => v !== null);
                    if (!empty.length) return;
                    // Medium AI: take a winning move, else block, else random (light center bias).
                    const tryWin = (mark) => empty.find(i => { const b = [...board]; b[i] = mark; return winner(b); });
                    let i = tryWin('O') ?? tryWin('X') ?? (board[4] === null && Math.random() < 0.4 ? 4 : empty[Math.floor(Math.random() * empty.length)]);
                    board[i] = 'O'; cells[i].textContent = 'O';
                }
                // Draw is a terminal state exactly like a win/loss: disabling every cell the
                // instant `over` flips guarantees no stray click can mutate the board afterward.
                function endGame(outcome) {
                    over = true;
                    cells.forEach(c => { c.disabled = true; });
                    done(outcome);
                }
                function checkEnd() {
                    const w = winner(board);
                    if (w) { endGame(board[w[0]] === 'X' ? 'won' : 'lost'); return true; }
                    if (board.every(v => v)) { endGame('lost'); return true; }
                    return false;
                }
                cells.forEach(btn => btn.addEventListener('click', () => {
                    const i = +btn.dataset.i;
                    if (over || board[i]) return;
                    board[i] = 'X'; btn.textContent = 'X';
                    if (checkEnd()) return;
                    aiMove();
                    checkEnd();
                }));
                return () => { over = true; };
            }

            // ---- Game 2: Maze escape. Randomized DFS maze; reach the exit to win. ----
            function renderMaze(el, done) {
                const N = 7;
                const cells = Array.from({length: N}, () => Array.from({length: N}, () => ({ n: true, s: true, e: true, w: true, seen: false })));
                (function carve(x, y) {
                    cells[y][x].seen = true;
                    const dirs = [['n', 0, -1, 's'], ['s', 0, 1, 'n'], ['e', 1, 0, 'w'], ['w', -1, 0, 'e']].sort(() => Math.random() - 0.5);
                    for (const [d, dx, dy, opp] of dirs) {
                        const nx = x + dx, ny = y + dy;
                        if (nx < 0 || ny < 0 || nx >= N || ny >= N || cells[ny][nx].seen) continue;
                        cells[y][x][d] = false; cells[ny][nx][opp] = false;
                        carve(nx, ny);
                    }
                })(0, 0);
                let px = 0, py = 0;
                const ex = N - 1, ey = N - 1;
                el.innerHTML = `<div class="grid gap-0" style="grid-template-columns:repeat(${N},28px)">${
                    cells.map((row, y) => row.map((c, x) => `<div data-x="${x}" data-y="${y}" style="width:28px;height:28px;box-sizing:border-box;border-top:${c.n?'2px solid #64748b':'2px solid transparent'};border-bottom:${c.s?'2px solid #64748b':'2px solid transparent'};border-left:${c.w?'2px solid #64748b':'2px solid transparent'};border-right:${c.e?'2px solid #64748b':'2px solid transparent'}" class="flex items-center justify-center text-xs"></div>`).join('')).join('')
                }</div>
                <div class="flex flex-col items-center gap-1 mt-3">
                    <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-move="n"><i class="ki-filled ki-up"></i></button>
                    <div class="flex gap-1">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-move="w"><i class="ki-filled ki-left"></i></button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-move="s"><i class="ki-filled ki-down"></i></button>
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon" data-move="e"><i class="ki-filled ki-right"></i></button>
                    </div>
                </div>
                <p class="text-xs text-secondary-foreground mt-2">Navigate from the top-left to the bottom-right.</p>`;
                function paint() {
                    el.querySelectorAll('[data-x]').forEach(d => {
                        const x = +d.dataset.x, y = +d.dataset.y;
                        d.textContent = (x === px && y === py) ? '●' : (x === ex && y === ey ? '★' : '');
                    });
                }
                function move(dir) {
                    const c = cells[py][px];
                    if (dir === 'n' && !c.n) py--;
                    else if (dir === 's' && !c.s) py++;
                    else if (dir === 'e' && !c.e) px++;
                    else if (dir === 'w' && !c.w) px--;
                    paint();
                    if (px === ex && py === ey) done('won');
                }
                const keyHandler = (e) => {
                    const map = {ArrowUp: 'n', ArrowDown: 's', ArrowLeft: 'w', ArrowRight: 'e'};
                    if (map[e.key]) { e.preventDefault(); move(map[e.key]); }
                };
                document.addEventListener('keydown', keyHandler);
                el.querySelectorAll('[data-move]').forEach(b => b.addEventListener('click', () => move(b.dataset.move)));
                paint();
                return () => document.removeEventListener('keydown', keyHandler);
            }

            // ---- Game 3: Math riddle. One attempt, plain arithmetic (not secret). ----
            function renderMath(el, done) {
                const roll = Math.random();
                const difficulty = roll < 0.34 ? 'easy' : roll < 0.67 ? 'medium' : 'hard';
                let a, b, op, answer;
                if (difficulty === 'easy') {
                    op = ['+', '-'][Math.floor(Math.random() * 2)];
                    a = 1 + Math.floor(Math.random() * 10);
                    b = 1 + Math.floor(Math.random() * 10);
                    if (op === '-' && b > a) { [a, b] = [b, a]; }
                    answer = op === '+' ? a + b : a - b;
                } else if (difficulty === 'medium') {
                    op = ['+', '-', '×'][Math.floor(Math.random() * 3)];
                    a = 2 + Math.floor(Math.random() * 18);
                    b = 2 + Math.floor(Math.random() * 18);
                    if (op === '-' && b > a) { [a, b] = [b, a]; }
                    answer = op === '+' ? a + b : op === '-' ? a - b : a * b;
                } else {
                    op = ['+', '-', '×'][Math.floor(Math.random() * 3)];
                    a = 10 + Math.floor(Math.random() * 90);
                    b = 3 + Math.floor(Math.random() * 12);
                    if (op === '-' && b > a) { [a, b] = [b, a]; }
                    if (op === '×') {
                        a = 6 + Math.floor(Math.random() * 14);
                        b = 6 + Math.floor(Math.random() * 14);
                    }
                    answer = op === '+' ? a + b : op === '-' ? a - b : a * b;
                }
                el.innerHTML = `<p class="text-lg font-semibold text-mono">${a} ${op} ${b} = ?</p>
                    <p class="text-xs text-secondary-foreground mb-1">Difficulty: ${difficulty}</p>
                    <input type="number" class="kt-input w-32 text-center" id="qz-math-input" autocomplete="off">
                    <button type="button" class="kt-btn kt-btn-primary" id="qz-math-submit">Submit</button>`;
                const input = el.querySelector('#qz-math-input');
                el.querySelector('#qz-math-submit').addEventListener('click', () => {
                    done(parseInt(input.value, 10) === answer ? 'won' : 'lost');
                });
                return null;
            }

            // ---- Game: Simon Says. 5 growing rounds; first wrong press fails instantly. ----
            function renderSimon(el, done) {
                const COLORS = [
                    {name: 'green', bg: '#16a34a'}, {name: 'red', bg: '#dc2626'},
                    {name: 'yellow', bg: '#ca8a04'}, {name: 'blue', bg: '#2563eb'},
                ];
                el.innerHTML = `<div class="grid grid-cols-2 gap-2" style="width:180px">${
                    COLORS.map(c => `<button type="button" class="rounded-lg" data-c="${c.name}" style="width:84px;height:84px;background:${c.bg};opacity:.55"></button>`).join('')
                }</div><p class="text-xs text-secondary-foreground mt-2" id="qz-simon-status">Watch the sequence…</p>`;
                const buttons = el.querySelectorAll('[data-c]');
                const byName = Object.fromEntries(Array.from(buttons).map(b => [b.dataset.c, b]));
                const status = el.querySelector('#qz-simon-status');
                const sequence = [];
                let round = 0, playerIdx = 0, accepting = false, timers = [];
                function flash(name, ms) {
                    return new Promise(res => {
                        timers.push(setTimeout(() => { byName[name].style.opacity = 1; }, ms));
                        timers.push(setTimeout(() => { byName[name].style.opacity = .55; res(); }, ms + 400));
                    });
                }
                async function playRound() {
                    accepting = false; playerIdx = 0;
                    round++;
                    sequence.push(COLORS[Math.floor(Math.random() * 4)].name);
                    status.textContent = `Round ${round} of 5 — watch…`;
                    for (let i = 0; i < sequence.length; i++) await flash(sequence[i], i * 550 + 300);
                    timers.push(setTimeout(() => { accepting = true; status.textContent = 'Your turn — repeat the sequence.'; }, sequence.length * 550 + 500));
                }
                buttons.forEach(btn => btn.addEventListener('click', () => {
                    if (!accepting) return;
                    // Pressed-state feedback: acknowledge every click (right or wrong) with the
                    // same brief opacity+scale flash the computer's own sequence uses.
                    btn.style.transform = 'scale(0.9)'; btn.style.opacity = 1;
                    setTimeout(() => { btn.style.transform = ''; btn.style.opacity = .55; }, 150);
                    if (btn.dataset.c !== sequence[playerIdx]) { accepting = false; done('lost'); return; }
                    playerIdx++;
                    if (playerIdx === sequence.length) {
                        if (round === 5) { accepting = false; done('won'); return; }
                        playRound();
                    }
                }));
                playRound();
                return () => timers.forEach(clearTimeout);
            }

            // ---- Game: Color match (Stroop test). 5 correct in a row; first miss fails. ----
            function renderColorMatch(el, done) {
                const COLORS = [{name: 'RED', hex: '#dc2626'}, {name: 'BLUE', hex: '#2563eb'}, {name: 'GREEN', hex: '#16a34a'}, {name: 'YELLOW', hex: '#ca8a04'}];
                let streak = 0;
                function round() {
                    const word = COLORS[Math.floor(Math.random() * 4)];
                    let font = COLORS[Math.floor(Math.random() * 4)];
                    if (font.name === word.name) font = COLORS[(COLORS.indexOf(font) + 1) % 4];
                    el.innerHTML = `<p class="text-3xl font-extrabold" style="color:${font.hex}">${word.name}</p>
                        <p class="text-xs text-secondary-foreground mb-1">Tap the FONT color (${streak}/5)</p>
                        <div class="grid grid-cols-2 gap-2">${
                            COLORS.map(c => `<button type="button" class="kt-btn kt-btn-outline transition-colors duration-150 data-[hovered=true]:bg-slate-900 data-[hovered=true]:text-white data-[hovered=true]:ring-2 data-[hovered=true]:ring-slate-400 data-[hovered=true]:shadow-md" data-c="${c.name}" aria-label="${c.name} color">${c.name}</button>`).join('')
                        }</div>`;
                    el.querySelectorAll('[data-c]').forEach(btn => {
                        btn.addEventListener('mouseenter', () => { btn.dataset.hovered = 'true'; });
                        btn.addEventListener('mouseleave', () => { delete btn.dataset.hovered; });
                        btn.addEventListener('click', () => {
                            if (btn.dataset.c !== font.name) { done('lost'); return; }
                            streak++;
                            if (streak >= 5) { done('won'); } else { round(); }
                        });
                    });
                }
                round();
                return null;
            }

            // ---- Game: Memory card flip. 8 pairs, 30s; timeout before all matched fails. ----
            function renderMemory(el, done) {
                const values = [1, 2, 3, 4, 5, 6, 7, 8];
                const deck = values.concat(values).sort(() => Math.random() - 0.5);
                el.innerHTML = `<div class="grid grid-cols-4 gap-2" style="width:216px">${
                    deck.map((_, i) => `<button type="button" class="kt-btn kt-btn-outline size-12 text-lg font-bold" data-i="${i}"></button>`).join('')
                }</div><p class="text-xs text-secondary-foreground mt-2">Matched: <span id="qz-mem-matched">0</span>/8 · Time: <span id="qz-mem-time">30</span>s</p>`;
                const cards = el.querySelectorAll('[data-i]');
                const matchedEl = el.querySelector('#qz-mem-matched'), timeEl = el.querySelector('#qz-mem-time');
                let timeLeft = 30, matched = 0, open = [], busy = false, over = false;
                const clock = setInterval(() => {
                    timeLeft--; timeEl.textContent = timeLeft;
                    if (timeLeft <= 0 && !over) { over = true; done('lost'); }
                }, 1000);
                cards.forEach(card => card.addEventListener('click', () => {
                    const i = +card.dataset.i;
                    if (over || busy || card.disabled || open.includes(i)) return;
                    card.textContent = deck[i];
                    open.push(i);
                    if (open.length < 2) return;
                    busy = true;
                    const [a, b] = open;
                    if (deck[a] === deck[b]) {
                        cards[a].disabled = true; cards[b].disabled = true;
                        matched++; matchedEl.textContent = matched;
                        open = []; busy = false;
                        if (matched >= 8) { over = true; clearInterval(clock); done('won'); }
                    } else {
                        setTimeout(() => {
                            cards[a].textContent = ''; cards[b].textContent = '';
                            open = []; busy = false;
                        }, 700);
                    }
                }));
                return () => clearInterval(clock);
            }

            // ---- Game: Slider puzzle (8-puzzle). Solved order 1-8 + blank; 45s. ----
            function renderSlider(el, done) {
                const SOLVED = [1, 2, 3, 4, 5, 6, 7, 8, null];
                let tiles = SOLVED.slice();
                const blankNeighbors = (pos) => {
                    const r = Math.floor(pos / 3), c = pos % 3, out = [];
                    if (r > 0) out.push(pos - 3);
                    if (r < 2) out.push(pos + 3);
                    if (c > 0) out.push(pos - 1);
                    if (c < 2) out.push(pos + 1);
                    return out;
                };
                // Shuffle via random valid slides from the solved state — guarantees solvability
                // (a random permutation of 1-8 is only solvable half the time; this avoids that).
                let blank = 8;
                for (let i = 0; i < 60; i++) {
                    const opts = blankNeighbors(blank);
                    const n = opts[Math.floor(Math.random() * opts.length)];
                    [tiles[blank], tiles[n]] = [tiles[n], tiles[blank]];
                    blank = n;
                }
                el.innerHTML = `<div class="grid grid-cols-3 gap-1" style="width:180px">${
                    tiles.map((_, i) => `<button type="button" class="kt-btn kt-btn-outline size-14 text-lg font-bold" data-i="${i}"></button>`).join('')
                }</div><p class="text-xs text-secondary-foreground mt-2">Arrange 1-8 in order · Time: <span id="qz-slider-time">45</span>s</p>`;
                const buttons = el.querySelectorAll('[data-i]');
                const timeEl = el.querySelector('#qz-slider-time');
                let timeLeft = 45, over = false;
                function paint() {
                    buttons.forEach((b, i) => { b.textContent = tiles[i] ?? ''; });
                }
                const clock = setInterval(() => {
                    timeLeft--; timeEl.textContent = timeLeft;
                    if (timeLeft <= 0 && !over) { over = true; done('lost'); }
                }, 1000);
                buttons.forEach((btn, i) => btn.addEventListener('click', () => {
                    if (over) return;
                    if (!blankNeighbors(blank).includes(i)) return;
                    [tiles[blank], tiles[i]] = [tiles[i], tiles[blank]];
                    blank = i;
                    paint();
                    if (tiles.every((v, idx) => v === SOLVED[idx])) { over = true; clearInterval(clock); done('won'); }
                }));
                paint();
                return () => clearInterval(clock);
            }

            // ---- Game: Bomb defusal. Pick 1 of 3 wires; wrong = BOOM, no timer. ----
            function renderBomb(el, done) {
                const correct = Math.floor(Math.random() * 3);
                el.innerHTML = `<p class="text-sm text-secondary-foreground mb-1">Cut the correct wire to defuse the bomb.</p>
                    <div class="flex gap-3">${
                        ['Wire A', 'Wire B', 'Wire C'].map((label, i) => `<button type="button" class="kt-btn kt-btn-outline" data-i="${i}">${label}</button>`).join('')
                    }</div><p class="text-lg font-bold mt-2" id="qz-bomb-result"></p>`;
                const resultEl = el.querySelector('#qz-bomb-result');
                el.querySelectorAll('[data-i]').forEach(btn => btn.addEventListener('click', () => {
                    el.querySelectorAll('[data-i]').forEach(b => b.disabled = true);
                    const won = +btn.dataset.i === correct;
                    resultEl.textContent = won ? 'Defused!' : 'BOOM!';
                    setTimeout(() => done(won ? 'won' : 'lost'), 500);
                }));
                return null;
            }

            // ---- Game: Coin flip streak. Single Heads/Tails call. ----
            function renderCoinFlip(el, done) {
                el.innerHTML = `<p class="text-sm text-secondary-foreground mb-1">Call the flip.</p>
                    <div class="flex gap-3">
                        <button type="button" class="kt-btn kt-btn-outline" data-c="heads">Heads</button>
                        <button type="button" class="kt-btn kt-btn-outline" data-c="tails">Tails</button>
                    </div><p class="text-lg font-bold mt-2" id="qz-coin-result"></p>`;
                const resultEl = el.querySelector('#qz-coin-result');
                el.querySelectorAll('[data-c]').forEach(btn => btn.addEventListener('click', () => {
                    el.querySelectorAll('[data-c]').forEach(b => b.disabled = true);
                    const flip = Math.random() < 0.5 ? 'heads' : 'tails';
                    const won = btn.dataset.c === flip;
                    resultEl.textContent = `${flip === 'heads' ? 'Heads' : 'Tails'} — you ${won ? 'win!' : 'lose.'}`;
                    setTimeout(() => done(won ? 'won' : 'lost'), 500);
                }));
                return null;
            }

            const RENDERERS = {
                tictactoe: renderTicTacToe, maze: renderMaze, math: renderMath, simon: renderSimon,
                colormatch: renderColorMatch, memory: renderMemory, slider: renderSlider,
                bomb: renderBomb, coinflip: renderCoinFlip,
            };
        })();

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

        // ---- Integrity ----
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

        // Shared detections — run on both desktop and mobile.
        let cooldown = false;
        function violation(reason) {
            if (!quizStarted || submitted || cooldown || authorizedFullscreenTransition) return;
            cooldown = true; setTimeout(() => { cooldown = false; }, 1000);
            bumpWarning(reason);
        }
        ['copy','cut','paste'].forEach(ev => document.addEventListener(ev, e => { e.preventDefault(); violation('You tried to copy, cut, or paste text. This is not allowed during the assessment.'); }));
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('visibilitychange', () => { if (document.hidden) violation('You switched to another tab or minimized the window.'); });

        // Split-screen / window-resize detection: fires when the OS window height drops below
        // 55 % of the available screen height (catches manual resize and OS split-screen snap).
        // Uses outerHeight (OS window frame) so a mobile soft keyboard, which only shrinks
        // innerHeight / visualViewport, does not produce a false positive.
        window.addEventListener('resize', () => {
            if (window.outerHeight < screen.availHeight * 0.55)
                violation('The browser window was resized to cover less than half the screen. Please maximise the window during the assessment.');
        });

        // App-switch / focus-loss detection — works on both desktop and mobile.
        window.addEventListener('blur', () => violation('You clicked away from the assessment window.'));

        // Desktop-only detections (pointer device assumed).
        if (!isTouch) {
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
