# Qyzen v2 — Migration Progress

> Running log of what's been built, for picking up across sessions. The plan lives in
> `BUILD_ORDER_STEP_BY_STEP.md` (Stages A–J) and `IMPLEMENTATION_ROADMAP_LARAVEL.md`
> (Phases 0–9). This file records **what is done and verified** vs **what's next**.
>
> Last updated: 2026-06-30 (Stage J1–J5 + full smoke-test; J6 cutover deferred).

## Stage ↔ Phase map

| Build Order | Roadmap | Theme | Status |
|---|---|---|---|
| A | 0 | Foundation / setup | ✅ done |
| B | 1 | Database schema (21 `tbl_*`) | ✅ done |
| C | 2 | Auth (replace Supabase Auth) | ✅ done |
| **D** | **3** | **Authorization core ⚠ the gate** | ✅ done — gate green |
| E | 4 | Data import | ⬜ not started |
| F | 5 | Admin features | ✅ done — first real UI |
| G | 6 | Educator features | ✅ done — ownership-gated |
| H | 7 | Student / quiz engine ⚠ | ✅ done — H6 invariant green |
| I | 8 | Real-time (deferred) | ⬜ optional |
| J | 9 | Hardening + cutover | 🟡 J1–J5 done; J6 cutover deferred (needs data + host) |

Critical path: `A→B→C→D ─┬→ F → G → H → I → J`, with `E` parallel after B–C. **Stage D
gates all feature work** (no feature pages until its matrix is green — it is). **Stage H6
(server-side grading)** is the non-negotiable invariant.

---

## What's done (verified)

### Stage A / Phase 0 — Foundation
- **A1** Laravel on MySQL: `.env` → `mysql`, DB `qyzen_v2` (utf8mb4) created; `php artisan migrate` clean. `config/database.php` already reads env (no edit needed).
- **A2** Core env: `APP_NAME=Qyzen`, `APP_TIMEZONE=UTC` (config now env-driven). Mail `log`, filesystem `local` (dev). No Supabase keys ever existed to remove.
- **A3** `CONVENTIONS.md` written (keep `tbl_*`, folder layout, scope-every-query, UTC, no dead stubs).
- **A4** Base Blade layout + `/layout-check` route. *(Originally Tabler — since rebuilt on Metronic, see Frontend below.)*

### Stage B / Phase 1 — Database schema (21 `tbl_*` tables)
- Migrations grouped, FK order, constraints+indexes inline:
  - `..._000001_create_tbl_users_table` (B1) — enum `user_type`, unique `user_id`/`email`, soft-delete, btree indexes. **No password column** (auth was Supabase; see Stage C decision).
  - `..._000002_create_rbac_tables` — roles, permissions, role_permissions, user_roles (soft-delete).
  - `..._000003_create_academic_tables` — academic_year, academic_term.
  - `..._000004_create_classroom_tables` — sections, sections_term, subjects.
  - `..._000005_create_assessment_tables` — enrolled, assessments, quizzes, scores, retakes.
  - `..._000006_create_ancillary_tables` — group chats/messages/reads, presence, learning_materials, notifications.
- **19 Eloquent models** + `User` repointed to `tbl_users`. `Quiz` hides `correct_answer` (`$hidden`). jsonb→`json` array casts. Self-service-locked columns (`user_id`, `email`, `user_type`, `is_active`) excluded from `User::$fillable`.
- **B12 trigger ports:** `App\Services\SectionService::nameTakenForTerms()` (section-term uniqueness); self-service lock via `$fillable`.
- **Postgres→MySQL translations:** jsonb→json, closed CHECKs→`enum` (semester, quiz_type, status, event_type), `storage_path` text→varchar (indexable), long composite index names shortened to ≤64 chars, soft-deletes only on `tbl_users`/`tbl_user_roles`.
- **Verified:** `migrate:fresh` builds all 21 tables; tinker built user→roles and academic→section→subject→assessment→quiz; `correct_answer` hidden from `toArray()` but readable server-side; SectionService correct.

### Stage C / Phase 2 — Auth
- Committed as `d498125 feat(auth): Stage C - Fortify auth on tbl_users + Google OAuth`.
- **Password storage decision (locked):** auth credentials (`password`, `remember_token`, `email_verified_at`) live **on `tbl_users`** (added nullable via a follow-up migration), authenticated by lowercased email. `user_id` preserved.
- **Migrated-password strategy (locked):** imported users get **force-reset on first login** (null password → reset flow). Applied in Stage E.
- Fortify (Blade stack), Socialite (Google OAuth callback: not-registered / inactive / link-existing), password reset (rate-limited, non-enumerating), auth-context middleware computing primary role (admin > educator > student, fallback `user_type`) → `/{role}/dashboard`.

### Stage D / Phase 3 — Authorization core ⚠ THE GATE (green)
Ports `docs/architecture/LIVE_SCHEMA_EXPORT.sql` RLS (35 policies + 3 helpers) into app code:
- **D1** `User::hasRole()` / `hasPermission('resource:action')` — active joins only.
- **D2** `scopeVisibleTo()` on Section, Subject, Assessment, Quiz, Score, Enrolled, LearningMaterial — admin all / educator ownership / student enrollment. *(Scopes for presence, chat reads, notifications deferred to their feature stages — YAGNI until a list query needs them.)*
- **D3** Policies (auto-discovered): SectionPolicy + SubjectPolicy (with `sections:*`/`subjects:*` permission gate), AssessmentPolicy, ScorePolicy.
- **D4** `App\Http\Middleware\RequireRole` (alias `role:` in `bootstrap/app.php`) — replaces `requireServerAuthContext`; bounces role-mismatch to own dashboard. Applied to placeholder role dashboards.
- **D5** `App\Services\NotificationAuthorizer` — educator emits all events except `quiz_submitted` to taught students (incl. `enrollment_deleted` special case); student emits `quiz_submitted`-only to the assessment's educator.
- **D6** `tests/Feature/AuthorizationMatrixTest.php` — **8 tests / 38 assertions green**; full suite 10/10. Runs on SQLite `:memory:` (also confirms schema builds there).

### Frontend — Metronic (replaced Tabler 2026-06-30)
- **Tabler removed** (`public/tabler/` + 103-page `template/`). **Metronic added** at `public/metronic/dist/` (served). `public/metronic/src/` (uncompiled source) is **gitignored** — local only.
- Assets: `public/metronic/dist/assets/{css,js,media,plugins}`. Core bundles: `plugins/global/plugins.bundle.{css,js}`, `css/style.bundle.css`, `js/scripts.bundle.js` → `{{ asset('metronic/dist/assets/...') }}`.
- ~206 demo HTML pages under `public/metronic/dist/` (`index.html`, `dashboards/*.html`, `apps/user-management/{users,roles}/*.html`, auth, datatables, forms, errors).
- `layouts/app.blade.php` rebuilt from `index.html` shell (`#kt_app_root` → header → `#kt_app_wrapper` → `#kt_app_sidebar` + `#kt_app_main`); sidebar driven by `$navItems` with `menu-item`/`menu-link` markup. Verified rendering clean.
- **Convention:** copy real markup from `public/metronic/dist/**/*.html`; match `data-kt-*` / `kt_app_*` / `app-*` so bundled JS initializes.

### Stage F / Phase 5 — Admin features (first real UI)
Pattern used everywhere: route (admin group) → controller (`$this->authorize()`) → Form Request
(one per source Zod schema) → service/Eloquent → Blade (from `metronic/dist/**`). Admin sees all,
so admin list queries skip `visibleTo`; every route still carries a Policy.

- **Foundations:** admin route group in `routes/web.php` (`auth`+`verified`+`role:admin`, `admin.` names; dropped `/layout-check`). Shared admin shell `resources/views/admin/layout.blade.php` (wraps `layouts/app` with `$navItems`, active item via `request()->routeIs()`). Base `Controller` now uses `AuthorizesRequests`. `User::getNameAttribute()` accessor (layout referenced `$user->name`). 5 admin-only policies (auto-discovered): `User/Role/Permission/AcademicYear/AcademicTermPolicy`. **`maatwebsite/excel ^3.1`** installed (composer `platform.ext-gd` override set — gd bundled but disabled, unused by xlsx import/export).
- **F1 Admin dashboard** — `DashboardController` + `admin/dashboard.blade.php` (Metronic cards from `dashboards/school.html`): user/educator/student/assessment counts + top-students table. Read-only; live-refresh deferred to I.
- **F2 Users** — create single / view / delete (hard `forceDelete`, source parity) / resend-verification (gated on unverified). `UserController` + `StoreUserRequest` (user_id format per type, ≥1 role) + `UserService` (locked columns `user_id`/`email`/`user_type`/`is_active` set via `forceFill` — admin-manages-others isn't the self-service lock). Server-side list filter/sort/paginate.
- **F3 Bulk student import** — `StudentsImport` (`ToCollection`+`WithHeadingRow`+`WithChunkReading` chunk 100, per-row validate, failures collected not thrown) + `StudentImportTemplateExport` / `FailedStudentRowsExport`. Import modal + template download on the users list.
- **F4 Edit-user (was 🚧 STUB → finished)** — `UpdateUserRequest` (unique ignores self; admin may change email/type/status/roles) + `UserService::update`.
- **F5 Roles** — full CRUD + permission assign (all-or-nothing `permissions()->sync()`); name `^[a-z]+(_[a-z]+)*$`; `withCount('permissions')` on list.
- **F6 Permissions (edit was 🚧 STUB → finished)** — bulk create (repeater; `permission_string` server-computed `resource:action`, in-batch + DB dup check), single edit (recomputes string), delete. `module`/`description`/`name` defaulted (NOT NULL in schema).
- **F7 Academic Year (view/edit were 🚧 STUB → finished)** — create + view/edit + **cascade-delete** (FK is `restrictOnDelete`, so child terms are deleted first inside a `DB::transaction`).
- **F8 Academic Term (view/edit were 🚧 STUB → finished)** — create/view/edit/delete; composite uniqueness `(term_name, semester, academic_year_id)`; semester enum is `'1st Semester'|'2nd Semester'` (the matrix abbreviated it).
- **Tests:** `tests/Feature/Admin/AdminFeaturesTest.php` — 12 tests / 44 assertions (authz gate: non-admin bounced; per-module CRUD; user_id format; role permission-replace; permission dup-reject; year cascade; term composite-unique). **Full suite 22/22 green** (incl. the Stage D `AuthorizationMatrixTest` gate). `migrate:fresh` clean on MySQL; `view:cache` compiles all admin Blade.

### Stage G / Phase 6 — Educator features (ownership-gated)
Same pattern as F, but **`educator_id`-ownership gated** — every list query carries `visibleTo($educator)`
(a forgotten scope is a cross-tenant leak; admin skipped it, educators must not). 11 controllers under
`app/Http/Controllers/Educator/`, route group `role:educator` + `educator.` names, shell
`resources/views/educator/layout.blade.php`.

- **Foundations:** educator route group (54 routes); 3 new policies `Enrolled/LearningMaterial/GroupChatPolicy` + `QuizPolicy` (all auto-discovered); **`NotificationService`** — the write-path companion to the existing `NotificationAuthorizer` (authorizes via `canEmit` then inserts; best-effort, never blocks the feature action).
- **G1 Dashboard** — `visibleTo`-scoped counts (sections/subjects/assessments) + top students.
- **G2 Sections** — CRUD; section↔term M:N (`->sync()` replaces links); name-per-term-per-educator uniqueness via the existing `SectionService::nameTakenForTerms` (B12). Delete cascades `tbl_sections_term`.
- **G3 Subjects** — one row per section (Cartesian); list groups by `code::name`; edit replaces the whole group (delete row_ids → recreate); case-insensitive code+name uniqueness per section.
- **G4 Enrollment** — single (studentIds × subjectIds → one row/pair, `firstOrCreate` dedupe) + bulk xlsx (`EnrollmentsImport`, in-file + DB dedupe) + template; fires `enrollment_created/updated/deleted` to **students only** (deleted verified by subject ownership).
- **G5 Assessments** — CRUD + schedule/options; **status `inactive→active` is the publish trigger** → `assessment_created` to enrolled students; otherwise `assessment_updated`; delete → `assessment_deleted` + FK-cascade to quizzes. Unique `(code, subject, section, term)`.
- **G6 Quizzes** — MC (choices A–D + correct key) / identification; bulk xlsx upload (single bundled `quiz_uploaded`); delete-one + delete-all-for-assessment. **`correct_answer` stays `$hidden` — never serialized to a student.**
- **G7 Scores + retake** — scores **read-only** (educators never edit raw scores); attempt-detail page loads `correct_answer` **server-side** (educator view, never a student page); grant retake writes `tbl_student_assessment_retakes` + `retake_updated` notification.
- **G8 Export** — single-assessment xlsx (`ScoresExport`) + **bulk zip** `TERM/SUBJECT/SECTION.xlsx` via `ScoreExportService` (`ZipArchive`, `ext-zip` enabled; temp dir streamed then cleaned). method = all/term/semester.
- **G9 Materials** — upload to the `local` disk; list (grouped subject+section) / edit metadata (soft `is_active`, leaves the object) / delete (removes object + row); download via a **signed temporary route** (`URL::temporarySignedRoute`, 60s) with an access re-check — ports the source's Supabase 60s signed URLs. `learning_material_uploaded` to enrolled students.
- **G10 Group chats** — request/response (live delivery deferred to I): create per subject+section / list / delete / send message / mark-read (`updateOrCreate` on open). Students can't create.
- **G11 Monitoring** — request/response, view-only + manual refresh (page reload): per active assessment, counts enrolled/online(60s-stale)/answering/finished from `tbl_student_presence` + `tbl_scores`.
- **Tests:** `tests/Feature/Educator/EducatorFeaturesTest.php` — 8 tests / 34 assertions, focused on the **ownership gate** (educator A 403 on B's data; scope hides cross-tenant rows), name-per-term uniqueness scoped per educator, one-row-per-section, publish-on-activate notification, and `correct_answer` hidden. **Full suite 30/30 green.** `migrate:fresh` clean; all educator Blade compiles.

### Stage H / Phase 7 — Student features + quiz engine ⚠ (core invariant green)
Enrollment-gated; quiz actions add schedule + attempt gates. Route group `role:student` + `student.`
names; shell `resources/views/student/layout.blade.php`. Profile is a shared group (all roles).

- **Two services carry the engine:** `AssessmentAvailabilityService` (badge Upcoming/Available/Reopened/Expired/Schedule-issue + can-take = `firstAttempt OR (effectiveRetakes>0 AND remaining>0)`, where `effective = (allow_retake?retake_count:0) + granted extra`); **`QuizGradingService`** — the H6 heart.
- **H1 Dashboard** — own-data counts (pending/completed/avg) + recent results.
- **H2 Assessment list** — enrolled-only (`Assessment::visibleTo`), availability badge + attempts-left + Start gate; details page with attempt history.
- **H3 Take-quiz load** — server re-checks eligibility; questions selected as `id/question/quiz_type/choices` **only** (no `correct_answer`); shuffles if `is_shuffle`; restores an `in_progress` draft.
- **H4 Autosave** — debounced ~800ms `fetch` → `take-quiz/{a}/draft` → `QuizGradingService::saveDraft` (`status=in_progress`); JSON response carries no answers.
- **H5 Anti-cheat** — vanilla JS detectors (tab-hidden / window-blur / paste-blocked / context-menu-blocked) each `warning_attempts++` + autosave; **force-submit at `cheating_attempts` limit or timer zero**.
- **H6 ⚠ Server-side grading** — `QuizGradingService::grade` loads `correct_answer` **inside the service only**, compares, `is_passed = score/total ≥ 0.75`, writes the Score (`passed`/`failed`), emits `quiz_submitted` to the assessment's educator. Eligibility re-validated server-side on submit (client gate not trusted). **`correct_answer` is never serialized to the client** — `$hidden` on the model + explicit column selection + a test asserting the take-quiz HTML contains neither `correct_answer` nor the identification answer.
- **H7 Result/review** — gated correct-answer display: a question's correct answer is shown **only if `allow_review=true` OR the student got it right**; otherwise null. Attempt-history switcher.
- **H8 Scores history** — own scores only (`ScorePolicy` + `student_id` guard), filter/sort/paginate, pass/fail summary.
- **H9 Materials** — enrollment-gated list + download (`LearningMaterial::visibleTo`, active-only for students).
- **H10 Chats** — request/response: view/send/mark-read; **students cannot create** (no store/destroy routes); access gated by `GroupChatPolicy` (enrollment in the chat's subject).
- **H11 Profile (shared)** — `UpdateProfileRequest` enforces the self-service lock: `user_id`/`user_type`/`is_active` never editable; **name read-only for students** (rule dropped from the request for students), editable for educators/admins; email/picture/cover editable by all; password change (`current_password` + `Password::min(8)->mixedCase->numbers->symbols`, hashed via cast); Google link reuses the Stage C Socialite route.
- **Tests:** `tests/Feature/Student/StudentFeaturesTest.php` — 9 tests / 22 assertions. **The H6 gate: take-quiz HTML contains no `correct_answer` and no identification answer; model hides it in JSON.** Plus server-side grading (3/4 = 75% passes, 1/4 fails), educator notified on submit, review-display gate hides answers when review off + wrong, own-scores-only (403 on another student's score), enrollment gating. **Full suite 39/39 green.** `migrate:fresh` clean; all Blade compiles.

### Stage J / Phase 9 — Hardening (J1–J5 done; J6 cutover deferred)
- **J1/J2 `App\Http\Middleware\SecurityHeaders`** (appended to the `web` group in `bootstrap/app.php`): `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies: none`, HSTS (HTTPS only), and a **CSP** with a per-request **nonce** shared to Blade as `$cspNonce`. The four inline `<script>` blocks (base layout `hostUrl`, take-quiz JS, quiz-type toggle, permission repeater) carry `nonce="{{ $cspNonce }}"`. CSP: `script-src 'self' 'nonce-…' 'strict-dynamic'`, `frame-ancestors 'none'`, `form-action 'self'`.
- **J3 rate limiting:** Fortify's `login` limiter (5/min per email+IP) already existed; added a **`quiz-writes` limiter** (60/min per user) on the take-quiz `draft` + `submit` routes.
- **J4 authz re-audit:** the full suite is the matrix re-run against built features — role gates (wrong-role → 302), cross-tenant denials (educator B → 403 on A's section/**assessment/quiz** edit+delete; student → 403 on another's score/non-enrolled assessment), and the model-scope `AuthorizationMatrixTest`. Added `test_educator_cannot_reach_another_educators_assessment_or_quiz` (HTTP-level). **No cross-tenant leak.**
- **J5 parity audit:** [`docs/roadmap/PARITY_AUDIT.md`](PARITY_AUDIT.md) — every FEATURE_MATRIX action marked ✅ reproduced / 🔵 deferred (Stage I) / ⛔ deliberately dropped / 🟡 cosmetic-partial. All writes reproduced; all four admin 🚧 STUBs finished; no dead buttons; H6 invariant reproduced + asserted. Dropped (documented): quiz hints, list/slideshow toggle, image-crop dialog. Cosmetic 🟡: dashboard charts, timer color-staging, monitoring drill-down modal.
- **Tests:** `tests/Feature/HardeningTest.php` — 3 tests (headers present, CSP nonce flows into rendered HTML, quiz routes throttled). **Full suite 43/43 green.**
- **J6 cutover — deferred:** final data sync, host switch, smoke, rollback. Needs real data (Stage E) + a target host; nothing to build until those exist.

### Full-app smoke test (2026-06-30) — beyond the unit suite
Booted the **real app against the seeded MySQL DB** and drove actual HTTP requests as each role
(what feature tests, on fresh fixtures, don't cover — real render-path 500s):
- **46/46 major routes returned HTTP 200** as the correct role — every admin/educator/student index + create + edit page + profile. No render-time errors, missing view vars, or broken `route()` calls.
- **Core interactive flows work end-to-end:** student quiz **submit → server-side grading** (all-correct = 4/4, `passed`), `quiz_submitted` educator notification fired, draft **autosave → 200**.
Conclusion: the dev build is verified working, not just green in isolation.

### Post-migration — Admin database backups (2026-07-10)
- **On-demand:** Admin Settings → Download Database streams a full schema+data SQL export (`DatabaseBackupService`, driver-branched for mysql/sqlite, never materialized in memory).
- **Scheduled:** `php artisan backup:database` runs daily with overlap protection, writes to the private `database-backups` disk (`DATABASE_BACKUP_ROOT`), fails rather than retaining partial/table-skipping exports, emits restore-safe SQL, and prunes to the 7 most recent matching files.
- **Hostinger:** create a Custom cron scheduled every minute with `/usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan schedule:run`; hPanel owns the schedule and output settings, so the command has no cron prefix or shell redirection. See `docs/deployment/TASK15_PRODUCTION_RELIABILITY.md`.

---

## What's next

The functional rewrite is **complete and hardened**. Remaining work is dependency-gated:

- **Stage E — Data import** (blocked on the live PG export; `docs/architecture/LIVE_SCHEMA_EXPORT.sql` is DDL only). A `DemoSeeder` exists for local click-through (`migrate:fresh --seed`), but real Supabase→MySQL import is unbuilt.
- **Stage J6 — Cutover** (needs E + a host): final sync, host switch, smoke test, rollback plan.
- **Stage I — Real-time** (optional): upgrade chat/monitoring/dashboards/notifications from request/response to live transport (Reverb/polling) + presence heartbeat + live notification bell. Everything works without it.

---

## Open decisions still pending

1. ~~**Stub resolution (F):** finish vs drop each 🚧.~~ **Resolved — all four 🚧 admin stubs (edit-user, edit-permission, view/edit year & term) were finished.**
2. **Per-feature realtime transport (I):** WebSockets (Reverb) vs polling, per feature.

## Verify commands

```bash
php artisan migrate:fresh --seed   # rebuild 21 tbl_* tables + load the demo dataset (MySQL qyzen_v2)
php artisan test            # full suite (SQLite :memory:), 43 tests; AuthorizationMatrixTest is the D gate
php artisan test --filter=AuthorizationMatrixTest
php artisan test --filter=AdminFeaturesTest      # Stage F admin tests
php artisan test --filter=EducatorFeaturesTest   # Stage G educator tests (ownership gate + J4 cross-tenant)
php artisan test --filter=StudentFeaturesTest    # Stage H student tests (incl. H6 correct_answer gate)
php artisan test --filter=HardeningTest          # Stage J1–J3 (headers, CSP nonce, throttle)
composer dev                # serve + log in to smoke the pages
```

**Demo dataset** (`database/seeders/DemoSeeder.php`, for local click-through — NOT the real Stage E
import): `migrate:fresh --seed` loads 1 admin / 2 educators / 8 students, an academic year+2 terms,
2 educator classrooms (sections+term links, subjects, enrollment, an active assessment with 4
questions, submitted scores, a material, a group chat). **All logins use password `password`** and
are email-verified. Try `admin@qyzen.test`, `educator@qyzen.test`, `student1@qyzen.test`. The two
educators have separate data so the ownership gate is visible in the UI. Real Supabase→MySQL import
remains **Stage E** (needs the live PG data export; `docs/architecture/LIVE_SCHEMA_EXPORT.sql` is DDL only, no rows).

## Commit trail (main)

```
2d19f1e feat(hardening): Stage J1-J5 - security headers, CSP, throttle, audits
7b85f9c chore(seed): demo dataset for local click-through
d7372ad feat(student): Stage H - student features + quiz engine (H1-H11)
8dd34c6 feat(educator): Stage G - educator features (G1-G11)
f6fc707 feat(admin): Stage F - admin features (F1-F8)
bf66513 feat(frontend): swap Tabler template for Metronic
58e159f feat(authz): Stage D - authorization core (RLS -> app layer)
d498125 feat(auth): Stage C - Fortify auth on tbl_users + Google OAuth
1ce7048 feat(db): Stage B2-B12 - remaining 20 tbl_* tables, models, services
f3299b2 feat(db): Stage B1 - tbl_users migration, model, factory
1dedd14 feat(frontend): add Tabler template assets and base Blade layout   (superseded by bf66513)
9ea554c chore(phase-0): env-driven timezone and layout-check route
156bf20 docs: add CLAUDE.md guide and CONVENTIONS summary
```
