{{-- Task 36: Terms of Service + Privacy Policy on the sign-in page.
     Native <dialog> — centered modal on desktop, bottom drawer on mobile (CSS only).
     Fixed header + scrollable body + fixed footer, per docs/legal/TOS_PP.md. --}}

<p class="lp-legal-note">
    By clicking continue, you agree to our
    <button type="button" data-legal-open="terms">Terms of Service</button>
    and
    <button type="button" data-legal-open="privacy">Privacy Policy</button>.
</p>

<dialog id="legal-terms" class="lp-dialog" aria-labelledby="legal-terms-title">
    <div class="lp-dialog-head">
        <div>
            <h3 id="legal-terms-title">Terms of Service</h3>
            <p>Last updated June 23, 2026</p>
        </div>
        <button type="button" class="lp-dialog-x" data-legal-close aria-label="Close"><i class="ki-filled ki-cross"></i></button>
    </div>
    <div class="lp-dialog-body lp-legal kt-scrollable-y">
        <p>Qyzen is an academic assessment and classroom management platform operated by
            <strong>Mr. Mark Adrianne Salunga</strong> ("we," "us"). These terms cover your use of the platform.
            By signing in, you agree to them.</p>

        <h4>1. Who can use Qyzen</h4>
        <p>Qyzen is not open to the public. Accounts are created and assigned by an administrator at your institution.
            There is no self-service registration. When an administrator adds you, you are given one or more roles —
            administrator, educator, or student — that determine what you can see and do. You may only use the account
            assigned to you, and you are responsible for keeping your sign-in credentials confidential.</p>

        <h4>2. Signing in</h4>
        <p>You can sign in with the email and password set for your account, or with a Google account whose email matches
            an account already registered in the system. If you sign in with Google and your email is not registered,
            access is denied. Deactivated accounts cannot sign in.</p>

        <h4>3. Acceptable use</h4>
        <p>You agree not to:</p>
        <ul>
            <li>access or attempt to access data or areas outside the role assigned to you;</li>
            <li>share your credentials or let another person use your account;</li>
            <li>upload material you do not have the right to share, or that is unlawful or harmful;</li>
            <li>interfere with the platform's operation, including its assessment-integrity features.</li>
        </ul>

        <h4>4. Assessments and academic integrity</h4>
        <p>Educators create timed assessments. While you are taking an assessment, the platform monitors for activity
            that may indicate cheating and records it for your educator. This is described in the Privacy Policy under
            "How assessments are monitored." Retakes, hints, and review of answers are available only when the educator
            enables them. Repeated integrity warnings during an assessment may affect your ability to continue or your
            final result, at your educator's and institution's discretion.</p>

        <h4>5. Content you submit</h4>
        <p>Quiz answers, chat messages, uploaded learning materials, and profile information you submit remain associated
            with your account and your institution. Educators and administrators at your institution can view the academic
            content you submit as part of running their classes.</p>

        <h4>6. Availability</h4>
        <p>We aim to keep Qyzen available but do not guarantee uninterrupted access. The platform relies on third-party
            infrastructure (see the Privacy Policy) and may be unavailable for maintenance, updates, or reasons outside
            our control.</p>

        <h4>7. Account suspension and changes</h4>
        <p>Your institution may deactivate or remove your account at any time. We may update these terms; continued use
            after a change means you accept the updated terms. The "Last updated" date above reflects the current version.</p>

        <h4>8. Contact</h4>
        <p>Questions about these terms can be directed to your institution's administrator or to
            <a href="mailto:markadrianne.salunga@ncst.edu.ph">markadrianne.salunga@ncst.edu.ph</a>.</p>
    </div>
    <div class="lp-dialog-foot">
        <button type="button" class="kt-btn kt-btn-primary" data-legal-close>I understand</button>
    </div>
</dialog>

<dialog id="legal-privacy" class="lp-dialog" aria-labelledby="legal-privacy-title">
    <div class="lp-dialog-head">
        <div>
            <h3 id="legal-privacy-title">Privacy Policy</h3>
            <p>Last updated June 23, 2026</p>
        </div>
        <button type="button" class="lp-dialog-x" data-legal-close aria-label="Close"><i class="ki-filled ki-cross"></i></button>
    </div>
    <div class="lp-dialog-body lp-legal kt-scrollable-y">
        <p>This policy explains what Qyzen collects, why, and who can see it. Qyzen is operated by
            <strong>Mr. Mark Adrianne Salunga</strong>, who decides how the platform is used for your classes.</p>

        <h4>What we collect</h4>
        <p>When your account is set up and as you use the platform, we hold:</p>
        <ul>
            <li><strong>Account details:</strong> your email, first name, last name, and the role(s) assigned to you.</li>
            <li><strong>Profile media:</strong> an optional profile picture and cover photo, if you add them.</li>
            <li><strong>Academic records:</strong> your class enrollments, assessment scores, the answers you submit to quiz questions, retake history, and notifications.</li>
            <li><strong>Messages:</strong> messages you send in group chats, along with the time sent.</li>
            <li><strong>Uploaded files:</strong> learning materials uploaded by educators (file name, type, and size are recorded).</li>
        </ul>

        <h4>How assessments are monitored</h4>
        <p>Qyzen is built for graded assessments, so while you are signed in — and especially while taking a quiz — it
            records information to support academic integrity:</p>
        <ul>
            <li>your online status, the page you are currently on, and a periodic "last seen" timestamp (updated roughly every 25 seconds), so educators can see live progress;</li>
            <li>during a quiz, integrity events such as switching tabs, leaving the quiz window, moving your mouse off the page, copy and paste, right-click, opening developer tools, taking a screenshot, or shrinking the window into a split-screen.</li>
        </ul>
        <p>These events are shared with the educator running the assessment and counted as warnings. They are used for
            academic integrity, not for any other purpose.</p>

        <h4>Why we use it</h4>
        <p>We use this data to give you access at the right role, run and grade assessments, let you and your educators
            communicate, share learning materials, and maintain the integrity of graded work. We do not sell your data or
            use it for advertising.</p>

        <h4>Who can see your data</h4>
        <ul>
            <li><strong>You</strong> can see your own profile, scores, materials, and messages.</li>
            <li><strong>Educators</strong> can see the academic data, assessment activity, and chat messages for students in their classes.</li>
            <li><strong>Administrators</strong> at your institution can manage accounts, roles, and system-wide academic data.</li>
        </ul>
        <p>Profile pictures and cover photos are stored in a public storage location, which means the image file can be
            reached by anyone who has its direct link. Do not use a profile or cover image you would not want to be
            publicly accessible. Learning materials are stored in an access-controlled location that requires sign-in.</p>

        <h4>Service providers</h4>
        <p>We rely on the following to run the platform:</p>
        <ul>
            <li><strong>Hostinger</strong> provides the hosting infrastructure for Qyzen, including our MySQL database and the storage that holds uploaded files. Your data is stored on Hostinger infrastructure. Sign-in, authentication, and live features are handled by Qyzen itself.</li>
            <li><strong>Google</strong> is used only if you choose to sign in with Google, which shares your Google account email with us to match it to your account.</li>
        </ul>
        <p>We do not use third-party analytics or ad-tracking services.</p>

        <h4>Retention and deletion</h4>
        <p>Your data is kept for as long as your account is active and your institution needs it for academic
            recordkeeping. When an account is removed, related records are deleted or marked as deleted. To request access
            to, correction of, or deletion of your data, contact your institution's administrator or
            <a href="mailto:markadrianne.salunga@ncst.edu.ph">markadrianne.salunga@ncst.edu.ph</a>.</p>

        <h4>Changes</h4>
        <p>We may update this policy. The "Last updated" date above reflects the current version.</p>
    </div>
    <div class="lp-dialog-foot">
        <button type="button" class="kt-btn kt-btn-primary" data-legal-close>I understand</button>
    </div>
</dialog>

@push('styles')
<style nonce="{{ $cspNonce ?? '' }}">
    .lp-legal-note { text-align: center; font-size: 12.5px; line-height: 1.6; color: var(--muted-foreground); padding: 0 12px; margin: 0; }
    .lp-legal-note button { color: var(--muted-foreground); text-decoration: underline; text-underline-offset: 2px; background: none; cursor: pointer; }
    .lp-legal-note button:hover { color: var(--foreground); }

    {{-- Center explicitly: Tailwind/Metronic's base reset zeroes element margins, which kills the
         native dialog `margin:auto` centering, so pin with fixed + inset:0 + margin:auto. --}}
    .lp-dialog { position: fixed; inset: 0; margin: auto; width: min(560px, 92vw); max-height: 90vh; padding: 0;
        border: 1px solid var(--border); border-radius: 14px; background: var(--background); color: var(--foreground);
        box-shadow: 0 30px 80px -30px rgba(0,0,0,.55); }
    .lp-dialog::backdrop { background: rgba(0,0,0,.5); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
    .lp-dialog[open] { display: flex; flex-direction: column; }

    .lp-dialog-head { flex: 0 0 auto; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
        padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .lp-dialog-head h3 { font-size: 1.12rem; font-weight: 600; line-height: 1.2; }
    .lp-dialog-head p { font-size: .78rem; color: var(--muted-foreground); margin-top: 5px; }
    .lp-dialog-x { color: var(--muted-foreground); font-size: 15px; padding: 2px; cursor: pointer; line-height: 1; }
    .lp-dialog-x:hover { color: var(--foreground); }

    .lp-dialog-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 20px 24px; }
    .lp-dialog-foot { flex: 0 0 auto; padding: 16px 24px; border-top: 1px solid var(--border); }
    .lp-dialog-foot .kt-btn { width: 100%; justify-content: center; }

    .lp-legal { font-size: 13.5px; line-height: 1.65; color: var(--muted-foreground); }
    .lp-legal p { margin-bottom: 12px; }
    .lp-legal h4 { font-size: .95rem; font-weight: 600; color: var(--foreground); margin: 20px 0 8px; }
    .lp-legal ul { margin: 0 0 12px; padding-left: 20px; list-style: disc; }
    .lp-legal li { margin-bottom: 6px; }
    .lp-legal strong { color: var(--foreground); font-weight: 600; }
    .lp-legal a { color: var(--primary); text-decoration: underline; text-underline-offset: 2px; }

    @keyframes lp-dialog-up { from { transform: translateY(100%); } to { transform: none; } }
    @media (max-width: 560px) {
        .lp-dialog { width: 100vw; max-width: 100vw; max-height: 85vh; margin: 0; inset: auto 0 0 0;
            border-radius: 16px 16px 0 0; border-bottom: 0; }
        .lp-dialog[open] { animation: lp-dialog-up .25s ease; }
    }
    @media (prefers-reduced-motion: reduce) { .lp-dialog[open] { animation: none; } }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        const open = (name) => { const d = document.getElementById('legal-' + name); if (d && d.showModal) d.showModal(); };
        document.querySelectorAll('[data-legal-open]').forEach((b) =>
            b.addEventListener('click', () => open(b.dataset.legalOpen)));
        document.querySelectorAll('dialog.lp-dialog').forEach((d) => {
            d.querySelectorAll('[data-legal-close]').forEach((c) => c.addEventListener('click', () => d.close()));
            // Click outside the dialog box (on the backdrop) closes it. Escape is handled natively.
            d.addEventListener('click', (e) => {
                const r = d.getBoundingClientRect();
                const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
                if (!inside) d.close();
            });
        });
    })();
</script>
@endpush
