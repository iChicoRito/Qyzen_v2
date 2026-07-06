<!DOCTYPE html>
{{-- Qyzen public landing page (Task 35). Standalone doc — signed-out visitors only;
     the root route (routes/web.php) already bounces authenticated users to their dashboard.
     Uses the Metronic bundle for the theme system + tokens; layout is scoped CSS below
     (the compiled styles.css ships only demo-used utilities, so bespoke layout is hand-rolled
     against the theme's CSS vars, which flip with the .dark class). --}}
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>Qyzen — Academic assessment platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Qyzen is an all-in-one classroom assessment platform: timed quizzes, instant scoring, live monitoring, and course files in one place." />
    <link rel="shortcut icon" href="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/css/styles.css') }}" rel="stylesheet" />
    <style nonce="{{ $cspNonce ?? '' }}">
        :root { --lp-max: 1120px; }
        .lp-mono { font-family: ui-monospace, "Cascadia Code", "SF Mono", Menlo, Consolas, monospace; }
        #lp { color: var(--foreground); }
        .lp-wrap { max-width: var(--lp-max); margin-inline: auto; padding-inline: 24px; }
        .lp-section { padding-block: 88px; border-top: 1px solid var(--border); }
        .lp-label { font-size: 12px; letter-spacing: .18em; text-transform: uppercase; color: var(--muted-foreground); margin-bottom: 18px; }
        .lp-h2 { font-size: clamp(1.6rem, 3vw, 2.15rem); font-weight: 600; line-height: 1.2; max-width: 30ch; margin-bottom: 18px; letter-spacing: -.01em; }
        .lp-lead { color: var(--muted-foreground); font-size: 1.02rem; line-height: 1.7; max-width: 62ch; }

        /* Top bar */
        .lp-bar { position: fixed; inset: 0 0 auto 0; z-index: 30; height: 72px; display: flex; align-items: center;
            border-bottom: 1px solid var(--border); background: color-mix(in srgb, var(--background) 78%, transparent);
            -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); }
        .lp-bar .lp-wrap { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .lp-brand { display: flex; align-items: center; gap: 10px; }
        .lp-brand img { height: 80px; width: auto; }
        .lp-logo-dark-mode { display: none; }
        .dark .lp-logo-light-mode { display: none; }
        .dark .lp-logo-dark-mode { display: block; }
        .lp-toggle { display: flex; align-items: center; gap: 8px; color: var(--muted-foreground); cursor: pointer; }

        /* Hero */
        .lp-hero { position: relative; overflow: hidden; padding-top: 160px; padding-bottom: 104px; }
        .lp-mist { position: absolute; inset: -20% -10% auto -10%; height: 620px; z-index: 0; pointer-events: none; filter: blur(90px); opacity: .55; }
        .lp-mist span { position: absolute; width: 380px; height: 380px; border-radius: 9999px; }
        .lp-mist .b1 { background: #4f7bff; top: 40px; left: 6%; }
        .lp-mist .b2 { background: #9b5cf6; top: 0; left: 34%; }
        .lp-mist .b3 { background: #ff6fae; top: 90px; left: 58%; }
        .lp-mist .b4 { background: #ffcf5c; top: 20px; left: 78%; }
        .lp-hero .lp-wrap { position: relative; z-index: 1; }
        .lp-eyebrow { font-size: 13px; letter-spacing: .04em; color: var(--muted-foreground); margin-bottom: 22px; }
        .lp-h1 { font-size: clamp(2.3rem, 5.5vw, 3.6rem); font-weight: 700; line-height: 1.05; letter-spacing: -.025em; max-width: 16ch; margin-bottom: 22px; }
        .lp-hero .lp-lead { font-size: 1.12rem; margin-bottom: 34px; }
        .lp-cta { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: var(--primary-foreground);
            font-weight: 600; font-size: .98rem; padding: 12px 26px; border-radius: 9999px; transition: opacity .15s; }
        .lp-cta:hover { opacity: .9; }

        /* Live activity */
        .lp-two { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: center; }
        .lp-term { background: #0c0c0f; border: 1px solid #26262c; border-radius: 12px; overflow: hidden;
            box-shadow: 0 24px 60px -30px rgba(0,0,0,.6); }
        .lp-term-bar { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-bottom: 1px solid #1e1e24; }
        .lp-dots { display: flex; gap: 7px; }
        .lp-dots i { width: 12px; height: 12px; border-radius: 9999px; display: block; }
        .lp-term-title { color: #8a8a94; font-size: 12.5px; }
        .lp-log { padding: 18px 18px 22px; font-size: 13.5px; line-height: 2; min-height: 316px; }
        .lp-log .ln { white-space: pre-wrap; word-break: break-word; }
        .lp-log .n { color: #d4d4d8; }
        .lp-log .m { color: #6f6f79; }
        .lp-log .a { color: #f5b338; }
        .lp-log .g { color: #34d17b; }
        .lp-cursor { display: inline-block; margin-left: 1px; color: #e4e4e7; animation: lp-blink 1.05s steps(1) infinite; }
        @keyframes lp-blink { 50% { opacity: 0; } }

        /* Feature grid */
        .lp-grid { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .lp-card { padding: 30px; border-right: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .lp-grid .lp-card:nth-child(3n) { border-right: 0; }
        .lp-grid .lp-card:nth-last-child(-n+3) { border-bottom: 0; }
        .lp-card i { font-size: 24px; color: var(--foreground); }
        .lp-card h3 { font-size: 1.02rem; font-weight: 600; margin: 16px 0 8px; }
        .lp-card p { color: var(--muted-foreground); font-size: .92rem; line-height: 1.6; }

        /* Roles */
        .lp-roles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
        .lp-role { border-top: 3px solid var(--foreground); padding-top: 20px; }
        .lp-role h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: 12px; }
        .lp-role p { color: var(--muted-foreground); font-size: .95rem; line-height: 1.65; }

        /* FAQ */
        .lp-faq { border-bottom: 1px solid var(--border); }
        .lp-faq summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: space-between;
            gap: 16px; padding: 22px 4px; font-weight: 500; font-size: 1.05rem; }
        .lp-faq summary::-webkit-details-marker { display: none; }
        .lp-faq summary i { color: var(--muted-foreground); transition: transform .2s; }
        .lp-faq[open] summary i { transform: rotate(45deg); }
        .lp-faq p { color: var(--muted-foreground); line-height: 1.7; padding: 0 4px 24px; max-width: 68ch; }

        .lp-foot { border-top: 1px solid var(--border); padding: 28px 0; color: var(--muted-foreground); font-size: .9rem; }
        .lp-foot .lp-wrap { display: flex; align-items: center; justify-content: space-between; gap: 12px; }

        @media (max-width: 900px) {
            .lp-two { grid-template-columns: 1fr; gap: 36px; }
            .lp-grid { grid-template-columns: repeat(2, 1fr); }
            .lp-grid .lp-card:nth-child(3n) { border-right: 1px solid var(--border); }
            .lp-grid .lp-card:nth-child(2n) { border-right: 0; }
            .lp-grid .lp-card:nth-last-child(-n+3) { border-bottom: 1px solid var(--border); }
            .lp-grid .lp-card:nth-last-child(-n+2) { border-bottom: 0; }
            .lp-roles { grid-template-columns: 1fr; gap: 28px; }
        }
        @media (max-width: 560px) {
            .lp-section { padding-block: 60px; }
            .lp-grid { grid-template-columns: 1fr; }
            .lp-grid .lp-card { border-right: 0 !important; border-bottom: 1px solid var(--border) !important; }
            .lp-grid .lp-card:last-child { border-bottom: 0 !important; }
        }
        @media (prefers-reduced-motion: reduce) { .lp-cursor { animation: none; } }
    </style>
</head>
<body class="antialiased text-base text-foreground bg-background">
{{-- FOUC-free theme boot — same pattern as layouts/auth.blade.php. --}}
<script nonce="{{ $cspNonce ?? '' }}">
    const defaultThemeMode = 'light';
    let themeMode;
    if (document.documentElement) {
        if (localStorage.getItem('kt-theme')) { themeMode = localStorage.getItem('kt-theme'); }
        else if (document.documentElement.hasAttribute('data-kt-theme-mode')) { themeMode = document.documentElement.getAttribute('data-kt-theme-mode'); }
        else { themeMode = defaultThemeMode; }
        if (themeMode === 'system') { themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
        document.documentElement.classList.add(themeMode);
    }
</script>

<div id="lp">
    <!-- Sticky top bar -->
    <header class="lp-bar">
        <div class="lp-wrap">
            <a class="lp-brand" href="{{ url('/') }}">
                <img class="lp-logo-light-mode" src="{{ asset('assets/img/logo-dark.png') }}" alt="Qyzen" />
                <img class="lp-logo-dark-mode" src="{{ asset('assets/img/logo-light.png') }}" alt="Qyzen" />
            </a>
            <label class="lp-toggle">
                <i class="ki-filled ki-moon"></i>
                <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" value="1" aria-label="Toggle dark mode" />
            </label>
        </div>
    </header>

    <!-- Hero -->
    <section class="lp-hero">
        <div class="lp-mist" aria-hidden="true"><span class="b1"></span><span class="b2"></span><span class="b3"></span><span class="b4"></span></div>
        <div class="lp-wrap">
            <div class="lp-eyebrow lp-mono">Academic assessment platform</div>
            <h1 class="lp-h1">The classroom assessment platform built for live classes.</h1>
            <p class="lp-lead">Qyzen runs timed quizzes, posts scores the instant they're submitted, keeps every course
                file in one place, and gives teachers a live view of the room. Sign in to pick up where your class left off.</p>
            <a class="lp-cta" href="{{ route('login') }}">
                Sign in <i class="ki-filled ki-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Why Qyzen -->
    <section class="lp-section">
        <div class="lp-wrap">
            <div class="lp-label">Why Qyzen</div>
            <h2 class="lp-h2">Running a graded quiz usually means three tools and a spreadsheet.</h2>
            <p class="lp-lead">One app makes the quiz, another grades it, a third stores the files, and someone copies the
                marks in by hand. Qyzen does the whole loop in one place — write the quiz, let it grade and post results on
                its own, watch the class as it happens, and export everyone's scores when you're done. Less switching between
                apps, fewer marks entered by hand, nothing lost along the way.</p>
        </div>
    </section>

    <!-- Live activity demo -->
    <section class="lp-section">
        <div class="lp-wrap lp-two">
            <div>
                <div class="lp-label">What it looks like in use</div>
                <h2 class="lp-h2">Every assessment, accounted for.</h2>
                <p class="lp-lead">Qyzen records each step of an assessment as it happens — a quiz opening, a student
                    submitting, the automatic grade, an integrity flag, the final export — so nothing about a quiz is a
                    mystery afterward.</p>
            </div>
            <div class="lp-term" role="img" aria-label="Simulated activity log for a live Qyzen quiz">
                <div class="lp-term-bar">
                    <span class="lp-dots"><i style="background:#ff5f57"></i><i style="background:#febc2e"></i><i style="background:#28c840"></i></span>
                    <span class="lp-term-title lp-mono">qyzen — live activity</span>
                </div>
                <div class="lp-log lp-mono" id="lp-log"></div>
            </div>
        </div>
    </section>

    <!-- Feature grid -->
    <section class="lp-section">
        <div class="lp-wrap">
            <div class="lp-label">What it does</div>
            <div class="lp-grid">
                <div class="lp-card"><i class="ki-filled ki-timer"></i><h3>Timed assessments</h3><p>Set a time limit, optional hints, and whether answers can be reviewed or retaken. It grades the moment a student submits.</p></div>
                <div class="lp-card"><i class="ki-filled ki-chart-line-up"></i><h3>Automatic scoring</h3><p>Results appear with a pass-or-fail status as soon as a student submits, and a whole class exports to a spreadsheet in one click.</p></div>
                <div class="lp-card"><i class="ki-filled ki-eye"></i><h3>Live monitoring</h3><p>Watch a class take a quiz in real time: who's online, who's answering, and who has finished.</p></div>
                <div class="lp-card"><i class="ki-filled ki-shield-tick"></i><h3>Integrity checks</h3><p>During a quiz the page flags tab-switching, copy and paste, and leaving the window, counting them as warnings against the attempt.</p></div>
                <div class="lp-card"><i class="ki-filled ki-folder"></i><h3>Learning materials</h3><p>Upload course files once and keep them in one place for every enrolled student to open and download.</p></div>
                <div class="lp-card"><i class="ki-filled ki-messages"></i><h3>Class group chats</h3><p>Each subject gets its own group chat so teachers and students can talk without leaving Qyzen.</p></div>
            </div>
        </div>
    </section>

    <!-- User roles -->
    <section class="lp-section">
        <div class="lp-wrap">
            <div class="lp-label">Three roles, one platform</div>
            <div class="lp-roles">
                <div class="lp-role"><h3>Administrator</h3><p>Sets up the institution: creates accounts, assigns roles, and manages academic terms and access.</p></div>
                <div class="lp-role"><h3>Educator</h3><p>Runs the classroom: handles enrollment, builds assessments, posts scores and materials, and leads class chats.</p></div>
                <div class="lp-role"><h3>Student</h3><p>Does the work: takes assessments, checks scores, opens materials, and joins class group chats.</p></div>
            </div>
        </div>
    </section>

    <!-- FAQ (native exclusive accordion via shared name="faq") -->
    {{-- ponytail: <details name> = zero-JS exclusive accordion. Ceiling: needs Chrome 120+/Safari 17.2+/FF130+
         (all shipped 2024); older browsers just toggle rows independently. Upgrade path: 5-line close-others JS. --}}
    <section class="lp-section">
        <div class="lp-wrap">
            <h2 class="lp-h2" style="margin-bottom:28px">Good to know.</h2>
            <details class="lp-faq" name="faq">
                <summary>How do I get an account? <i class="ki-filled ki-plus"></i></summary>
                <p>Accounts are created by an administrator at your institution. There's no public sign-up; once your account exists you sign in with your email and password or with Google.</p>
            </details>
            <details class="lp-faq" name="faq">
                <summary>What does Qyzen monitor during a quiz? <i class="ki-filled ki-plus"></i></summary>
                <p>While a quiz is open, the page records your progress and flags actions that often signal cheating: switching tabs, leaving the window, and copy or paste. Your teacher sees these as counted warnings. It's used only for academic integrity, nothing else.</p>
            </details>
            <details class="lp-faq" name="faq">
                <summary>Can students retake an assessment? <i class="ki-filled ki-plus"></i></summary>
                <p>Only when the teacher allows it. Retakes, hints, and reviewing answers are each turned on per quiz, so a quiz behaves exactly the way the teacher set it up.</p>
            </details>
            <details class="lp-faq" name="faq">
                <summary>Where is our data stored? <i class="ki-filled ki-plus"></i></summary>
                <p>Qyzen is hosted on Hostinger, which runs its MySQL database and file storage; sign-in and live features are handled by Qyzen itself. Google is involved only if you choose to sign in with Google. There's no third-party analytics or ad tracking.</p>
            </details>
        </div>
    </section>

    <footer class="lp-foot">
        <div class="lp-wrap">
            <span>{{ date('Y') }} © Qyzen</span>
            <a class="kt-link" href="{{ route('login') }}">Sign in</a>
        </div>
    </footer>
</div>

<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/js/core.bundle.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/ktui/ktui.min.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    // Live-activity typewriter. Respects prefers-reduced-motion by rendering the log at once.
    (function () {
        const log = document.getElementById('lp-log');
        if (!log) return;
        const lines = [
            { t: '$ qyzen open "Algebra — Quiz 3"',            c: 'n' },
            { t: '→ 18 students enrolled · 20-minute limit',    c: 'm' },
            { t: '← maria.santos submitted · 12/15 correct',    c: 'n' },
            { t: '✓ auto-graded: PASS',                         c: 'g' },
            { t: '⚠ integrity warning · tab switch flagged',    c: 'a' },
            { t: '→ assessment closed · all attempts in',       c: 'm' },
            { t: '✓ results posted to gradebook',               c: 'g' },
            { t: '← exported 18 scores → algebra-quiz-3.xlsx',  c: 'n' },
        ];
        const row = (l) => { const d = document.createElement('div'); d.className = 'ln ' + l.c; log.appendChild(d); return d; };

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            lines.forEach((l) => { row(l).textContent = l.t; });
            return;
        }

        const cursor = document.createElement('span');
        cursor.className = 'lp-cursor';
        cursor.textContent = '▋';
        let li = 0, ci = 0, cur = null;

        function tick() {
            if (li >= lines.length) { setTimeout(reset, 2400); return; }
            if (ci === 0) { cur = row(lines[li]); cur.appendChild(cursor); }
            const full = lines[li].t;
            if (ci < full.length) {
                cur.insertBefore(document.createTextNode(full[ci]), cursor);
                ci++; setTimeout(tick, 30);
            } else { li++; ci = 0; setTimeout(tick, 520); }
        }
        function reset() { log.innerHTML = ''; li = 0; ci = 0; cur = null; tick(); }
        tick();
    })();
</script>
</body>
</html>
