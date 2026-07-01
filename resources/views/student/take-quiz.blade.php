{{-- H3/H4/H5: take-quiz. Questions carry NO correct_answer (server hides it). Vanilla JS:
     debounced autosave (H4), anti-cheat detectors → warning_attempts + autosave, force-submit
     at limit or timer zero (H5). Grading is server-side on submit (H6). --}}
@extends('student.layout')
@section('title', 'Take Quiz')
@section('heading', $assessment->assessment_code)
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>Time left: <span id="timer" class="fw-bold fs-3">--:--</span></div>
        <div>Warnings: <span id="warns" class="fw-bold text-danger">{{ $warnings }}</span> / {{ $assessment->cheating_attempts ?: '∞' }}
            <span id="save-state" class="text-muted fs-7 ms-3"></span></div>
    </div>

    <form id="quiz-form" method="POST" action="{{ route('student.take-quiz.submit', $assessment) }}">
        @csrf
        <input type="hidden" name="warnings" id="warnings-input" value="{{ $warnings }}">
        @foreach ($questions as $i => $q)
            <div class="card mb-4"><div class="card-body">
                <p class="fw-bold">{{ $i + 1 }}. {{ $q->question }}</p>
                @if ($q->quiz_type === 'multiple_choice')
                    @foreach (($q->choices ?? []) as $key => $text)
                        <label class="form-check mb-2">
                            <input class="form-check-input answer" type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}"
                                @checked(($draftAnswers[$q->id] ?? ($draftAnswers[(string)$q->id] ?? null)) === $key)>
                            <span class="form-check-label">{{ $key }}. {{ $text }}</span>
                        </label>
                    @endforeach
                @else
                    <input class="form-control answer" type="text" name="answers[{{ $q->id }}]"
                        value="{{ $draftAnswers[$q->id] ?? ($draftAnswers[(string)$q->id] ?? '') }}" placeholder="Your answer">
                @endif
            </div></div>
        @endforeach
        <button type="submit" class="btn btn-primary" id="submit-btn">Submit</button>
    </form>

    @push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        const draftUrl = "{{ route('student.take-quiz.draft', $assessment) }}";
        const token = "{{ csrf_token() }}";
        const limit = {{ (int) ($assessment->cheating_attempts ?: 0) }};
        const timeLimitMin = parseInt("{{ (int) $assessment->time_limit }}", 10) || 0;
        let warnings = {{ (int) $warnings }};
        let submitted = false;

        function collectAnswers() {
            const data = {};
            document.querySelectorAll('.answer').forEach(el => {
                if (el.type === 'radio') { if (el.checked) data[el.name.replace(/answers\[|\]/g, '')] = el.value; }
                else if (el.value !== '') data[el.name.replace(/answers\[|\]/g, '')] = el.value;
            });
            return data;
        }

        let saveTimer = null;
        function autosave() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => {
                fetch(draftUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
                    body: JSON.stringify({answers: collectAnswers(), warnings})
                }).then(() => { document.getElementById('save-state').textContent = 'saved'; }).catch(() => {});
            }, 800); // debounce ~800ms (matches source)
        }
        document.querySelectorAll('.answer').forEach(el => el.addEventListener('change', autosave));
        document.querySelectorAll('input[type=text].answer').forEach(el => el.addEventListener('input', autosave));

        function forceSubmit() {
            if (submitted) return;
            submitted = true;
            document.getElementById('warnings-input').value = warnings;
            document.getElementById('quiz-form').submit();
        }

        // anti-cheat: count violations → warning_attempts + autosave; force-submit at limit.
        function violation() {
            warnings++;
            document.getElementById('warns').textContent = warnings;
            document.getElementById('warnings-input').value = warnings;
            autosave();
            if (limit > 0 && warnings >= limit) forceSubmit();
        }
        document.addEventListener('visibilitychange', () => { if (document.hidden) violation(); });
        window.addEventListener('blur', violation);
        document.addEventListener('paste', e => { e.preventDefault(); violation(); });
        document.addEventListener('contextmenu', e => e.preventDefault());

        // timer → force-submit at zero.
        if (timeLimitMin > 0) {
            let remaining = timeLimitMin * 60;
            const tEl = document.getElementById('timer');
            const tick = () => {
                const m = Math.floor(remaining / 60), s = remaining % 60;
                tEl.textContent = `${m}:${s.toString().padStart(2, '0')}`;
                if (remaining <= 0) { forceSubmit(); return; }
                remaining--; setTimeout(tick, 1000);
            };
            tick();
        }

        document.getElementById('quiz-form').addEventListener('submit', () => { submitted = true; });
    })();
    </script>
    @endpush
@endsection
