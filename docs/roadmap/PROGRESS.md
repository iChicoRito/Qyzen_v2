# Qyzen v2 — Migration Progress

> Running log of what's been built, for picking up across sessions. The plan lives in
> `BUILD_ORDER_STEP_BY_STEP.md` (Stages A–J) and `IMPLEMENTATION_ROADMAP_LARAVEL.md`
> (Phases 0–9). This file records **what is done and verified** vs **what's next**.
>
> Last updated: 2026-06-30 (Stage F complete).

## Stage ↔ Phase map

| Build Order | Roadmap | Theme | Status |
|---|---|---|---|
| A | 0 | Foundation / setup | ✅ done |
| B | 1 | Database schema (21 `tbl_*`) | ✅ done |
| C | 2 | Auth (replace Supabase Auth) | ✅ done |
| **D** | **3** | **Authorization core ⚠ the gate** | ✅ done — gate green |
| E | 4 | Data import | ⬜ not started |
| F | 5 | Admin features | ✅ done — first real UI |
| G | 6 | Educator features | ⬅ **NEXT** |
| H | 7 | Student / quiz engine ⚠ | ⬜ |
| I | 8 | Real-time (deferred) | ⬜ |
| J | 9 | Hardening + cutover | ⬜ |

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
Ports `docs/LiveSchemaExport.sql` RLS (35 policies + 3 helpers) into app code:
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

---

## What's next

**Stage G / Phase 6 — Educator features** (G1–G11): dashboard, sections, subjects, enrollment
(+ notifications), assessments, quizzes, scores review + grant retake, score export (xlsx/zip),
materials, group chats (request/response), realtime monitoring. Same pattern as F, but educator
data is **`educator_id`-ownership gated** — every list query MUST carry the `visibleTo` scope
(unlike admin). Policies for Section/Subject/Assessment/Score already exist (Stage D); add the
remaining educator-resource policies as their modules land.

**Stage E / Phase 4 (data import)** can still run in parallel — export live PG data, transform
types, load preserving `id`+`user_id`, force-reset passwords, verify counts.

---

## Open decisions still pending

1. ~~**Stub resolution (F):** finish vs drop each 🚧.~~ **Resolved — all four 🚧 admin stubs (edit-user, edit-permission, view/edit year & term) were finished.**
2. **Per-feature realtime transport (I):** WebSockets (Reverb) vs polling, per feature.

## Verify commands

```bash
php artisan migrate:fresh   # rebuild all 21 tbl_* tables (MySQL qyzen_v2)
php artisan test            # full suite (SQLite :memory:), 22 tests; AuthorizationMatrixTest is the D gate
php artisan test --filter=AuthorizationMatrixTest
php artisan test --filter=AdminFeaturesTest   # Stage F admin tests
composer dev                # serve + log in as an admin to smoke the admin pages
```

## Commit trail (main)

```
bf66513 feat(frontend): swap Tabler template for Metronic
58e159f feat(authz): Stage D - authorization core (RLS -> app layer)
d498125 feat(auth): Stage C - Fortify auth on tbl_users + Google OAuth
1ce7048 feat(db): Stage B2-B12 - remaining 20 tbl_* tables, models, services
f3299b2 feat(db): Stage B1 - tbl_users migration, model, factory
1dedd14 feat(frontend): add Tabler template assets and base Blade layout   (superseded by bf66513)
9ea554c chore(phase-0): env-driven timezone and layout-check route
156bf20 docs: add CLAUDE.md guide and CONVENTIONS summary
```
