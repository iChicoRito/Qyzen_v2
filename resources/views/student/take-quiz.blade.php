{{-- Task 19: slideshow-only take-quiz. Minimal UI — no metadata header; a fixed sticky top bar
     holds the timer + progress bar; MC options are highlight-only (no radio circle). Questions
     carry NO correct_answer (server hides it). Grading is server-side on submit. --}}
@extends('layouts.exam')
@section('title', 'Take Assessment')

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    /* Hide the native radio circle — selection is shown by the card highlight only. */
    .qz-option input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }
    .qz-option { transition: border-color .15s, background-color .15s; }
    .qz-option:hover { border-color: var(--color-primary, #1b84ff); }
    .qz-option.selected { border-color: var(--color-primary, #1b84ff); background: color-mix(in srgb, var(--color-primary, #1b84ff) 6%, transparent); }
    /* Hexagon letter badge (permissions-toggle demo motif). */
    .qz-hex { clip-path: polygon(25% 0, 75% 0, 100% 50%, 75% 100%, 25% 100%, 0 50%); }
    .qz-option.selected .qz-hex { background: var(--color-primary, #1b84ff); color: #fff; }
    .qz-option.selected .qz-title { color: var(--color-primary, #1b84ff); }
    .qz-timer.warn { color: #ca8a04; }
    .qz-timer.danger { color: #dc2626; }
    .qz-timer.shake { animation: qz-shake .5s cubic-bezier(.36,.07,.19,.97) infinite; }
    @keyframes qz-shake { 10%,90%{transform:translateX(-1px)} 30%,70%{transform:translateX(2px)} 50%{transform:translateX(-2px)} }
    .qz-hide { display: none !important; }
</style>
@endpush

@section('content')
    {{-- Sticky top bar: timer + progress (nav-style). No quiz metadata. --}}
    <div id="qz-bar" class="fixed top-0 bg-background border-b border-border shadow-sm" style="left: 0; right: 0; z-index: 1000;">
        <div class="w-full px-4 lg:px-8 py-4 flex flex-col items-center justify-center gap-2.5">
            {{-- Timer (prominent) --}}
            <span class="qz-timer text-3xl font-bold text-mono tabular-nums leading-none" id="qz-timer">--:--:--</span>
            {{-- Progress --}}
            <div class="w-full flex flex-col items-center gap-1">
                <div class="w-full rounded-full bg-accent overflow-hidden" style="height: 6px;">
                    <div id="qz-progress" class="bg-primary transition-all duration-300" style="width: 0%; height: 6px;"></div>
                </div>
                <span id="qz-counter" class="text-xs font-medium text-secondary-foreground">0 of {{ $questions->count() }} answered</span>
            </div>
        </div>
    </div>
    <div id="qz-spacer" style="height: 100px;"></div>

    <form id="qz-form" method="POST" action="{{ route('student.take-quiz.submit', $assessment) }}">
        @csrf
        <input type="hidden" name="warnings" id="qz-warnings-input" value="{{ $warnings }}">

        <div id="qz-questions" class="flex flex-col gap-6">
            @foreach ($questions as $i => $q)
                @php $given = $draftAnswers[$q->id] ?? ($draftAnswers[(string) $q->id] ?? null); @endphp
                <div class="kt-card qz-q" data-idx="{{ $i }}">
                    <div class="kt-card-content p-6 flex flex-col gap-5">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-secondary-foreground">Question No. {{ $i + 1 }}</span>
                            <p class="text-base font-medium text-mono">{{ $q->question }}</p>
                        </div>
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

        {{-- Submit (list view: at the bottom) --}}
        <div class="flex justify-end mt-6">
            <button type="button" class="kt-btn kt-btn-primary" id="qz-submit"><i class="ki-filled ki-check"></i> Submit</button>
        </div>
    </form>

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
        const hasTimer = {{ $assessment->time_limit > 0 ? 'true' : 'false' }};
        let warnings = {{ (int) $warnings }};
        let submitted = false, dirty = false;

        const $ = (id) => document.getElementById(id);
        const form = $('qz-form');

        // Keep the spacer exactly as tall as the fixed bar so the first question never tucks under it.
        function syncSpacer() { $('qz-spacer').style.height = $('qz-bar').offsetHeight + 'px'; }
        syncSpacer();
        window.addEventListener('resize', syncSpacer);

        // ---- Answers ----
        function collectAnswers() {
            const data = {};
            document.querySelectorAll('.qz-answer').forEach(el => {
                const id = el.name.replace(/answers\[|\]/g, '');
                if (el.type === 'radio') { if (el.checked) data[id] = el.value; }
                else if (el.value.trim() !== '') data[id] = el.value;
            });
            return data;
        }
        function unansweredCount() { return total - Object.keys(collectAnswers()).length; }
        document.querySelectorAll('.qz-answer').forEach(el => {
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

        // ---- Progress (answered / total) ----
        function updateProgress() {
            const answered = Object.keys(collectAnswers()).length;
            $('qz-counter').textContent = `${answered} of ${total} answered`;
            $('qz-progress').style.width = (total ? (answered / total * 100) : 0) + '%';
        }
        $('qz-submit').addEventListener('click', attemptSubmit);
        updateProgress();

        // ---- Timer (server-authoritative; resumes from real elapsed) ----
        if (hasTimer) {
            const tEl = $('qz-timer');
            const tick = () => {
                if (submitted) return;
                if (remaining <= 0) {
                    Swal.fire({ icon: 'info', title: "Time's Up!", text: 'Your assessment is being submitted.',
                        timer: 1800, showConfirmButton: false, allowOutsideClick: false }).then(doSubmit);
                    return;
                }
                const h = Math.floor(remaining / 3600), m = Math.floor((remaining % 3600) / 60), s = remaining % 60;
                tEl.textContent = `${h}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
                tEl.classList.toggle('warn', remaining <= 300 && remaining > 60);
                tEl.classList.toggle('danger', remaining <= 60);
                tEl.classList.toggle('shake', remaining <= 30);
                remaining--; setTimeout(tick, 1000);
            };
            tick();
        } else {
            $('qz-timer').textContent = 'No limit';
        }

        // ---- Integrity (essentials) — skipped on touch devices ----
        function bumpWarning(reason) {
            warnings++;
            $('qz-warnings-input').value = warnings;
            dirty = true; save();
            if (limit > 0 && warnings >= limit) {
                Swal.fire({ icon: 'error', title: 'Warning limit reached', text: 'Your assessment is being submitted.',
                    timer: 2000, showConfirmButton: false, allowOutsideClick: false }).then(doSubmit);
            } else {
                const left = limit > 0 ? (limit - warnings) : '∞';
                Swal.fire({ icon: 'warning', title: 'Warning', text: `${reason} — ${left} warning(s) left.`,
                    timer: 2200, showConfirmButton: false });
            }
        }
        if (!isTouch) {
            let cooldown = false;
            function violation(reason) {
                if (submitted || cooldown) return;
                cooldown = true; setTimeout(() => { cooldown = false; }, 1000);
                bumpWarning(reason);
            }
            ['copy','cut','paste'].forEach(ev => document.addEventListener(ev, e => { e.preventDefault(); violation('Copying/pasting is not allowed'); }));
            document.addEventListener('contextmenu', e => e.preventDefault());
            document.addEventListener('visibilitychange', () => { if (document.hidden) violation('You left the assessment'); });
            window.addEventListener('blur', () => violation('You left the assessment'));
            document.addEventListener('mouseleave', () => violation('Your cursor left the page'));

            // Devtools / view-source shortcuts. NOTE: browsers open their own devtools above the
            // page, so preventDefault can't always stop F12 — but the attempt is caught + counted.
            document.addEventListener('keydown', e => {
                const k = (e.key || '').toUpperCase();
                const blocked = e.key === 'F12'
                    || ((e.ctrlKey || e.metaKey) && e.shiftKey && (k === 'I' || k === 'J' || k === 'C'))
                    || ((e.ctrlKey || e.metaKey) && k === 'U');
                if (blocked) { e.preventDefault(); violation('Developer tools are not allowed'); }
            });

            // Heuristic devtools-open detector (docked panel changes the viewport delta). Fires
            // once per open; resets when closed. Can occasionally false-positive.
            const gapNow = () => Math.max(window.outerWidth - window.innerWidth, window.outerHeight - window.innerHeight);
            let devtoolsOpen = gapNow() > 170; // seed from current state so a small window doesn't false-fire
            setInterval(() => {
                const open = gapNow() > 170;
                if (open && !devtoolsOpen) violation('Developer tools appear to be open');
                devtoolsOpen = open;
            }, 1000);
        }
    })();
    </script>
    @endpush
@endsection
