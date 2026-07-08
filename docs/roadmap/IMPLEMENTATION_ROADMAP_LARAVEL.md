# Qyzen — Laravel Migration: Implementation Roadmap

> Execution plan for migrating Qyzen from **Next.js + Supabase + PostgreSQL** to
> **Laravel (natural MVC + Blade) + MySQL**.
>
> **Decisions locked for this roadmap:**
> - **Frontend:** natural MVC with **Blade** server-rendered views (no Inertia, no React).
> - **Real-time:** **deferred.** Every real-time feature is built as plain
>   request/response first; the live transport (WebSockets/polling) is a final,
>   optional phase (§ Phase 8).
>
> Companions: [ARCHITECTURE_TECHNICAL.md](../architecture/ARCHITECTURE_TECHNICAL.md) (how it works today),
> [FEATURE_MATRIX.md](FEATURE_MATRIX.md) (per-role acceptance checklist),
> [MIGRATION_LARAVEL_MYSQL.md](../architecture/MIGRATION_LARAVEL_MYSQL.md) (coupling analysis),
> [LIVE_SCHEMA_EXPORT.sql](../architecture/LIVE_SCHEMA_EXPORT.sql) (source-of-truth DDL).

---

## Context — why this roadmap exists

The live system runs on Supabase free tier and is hitting **Disk IO limits**. The
fix is to move onto a self-hostable **Laravel + MySQL** stack. The relational data
ports cleanly, but three pillars have **no Laravel/MySQL equivalent and must be
rebuilt**: **identity** (Supabase Auth), **authorization** (Postgres RLS — the
single biggest risk), and **real-time** (Supabase Realtime — deferred here).

The Blade decision means the **entire `src/app/*` UI is rewritten** as Blade views +
controllers; the existing shadcn/React components do **not** carry over. What carries
over is the *domain logic and data shapes*, which we translate from the ~35
`src/lib/supabase/*.ts` modules and 17 Zod schemas in the `qyzen/` source.

**Ground state (verified):**
- `qyzen/` — full Next.js source (translate FROM).
- `qyzen_v2/` — a **fresh default Laravel skeleton** (build INTO). Only stock
  `users`/cache/jobs migrations, default `User` model, empty `routes/web.php`.

---

## Guiding principles

1. **Authorization is explicit, everywhere.** RLS used to refuse rows in the DB.
   In Laravel, *application code is the only gate.* Every query carries an ownership
   /enrollment scope; every route carries a Policy/middleware. A forgotten scope =
   a data leak. This is the highest-risk theme and recurs in every phase.
2. **Preserve the security invariants exactly** — server-side grading (correct
   answers never sent to the client, ≥75% pass), self-service column locking,
   enrollment-gated access, signed/temporary file URLs.
3. **Data fidelity** — keep integer `id` values and `tbl_users.user_id` on import
   so every relationship and historical reference survives.
4. **Vertical slices after the foundation.** Once auth + DB + the authorization
   primitives exist, build role by role, module by module, each slice shippable.
5. **Don't reproduce dead buttons.** The 🚧 STUB actions in
   [FEATURE_MATRIX.md](FEATURE_MATRIX.md) are finished or dropped deliberately —
   never silently reproduced.

---

## Phase map (at a glance)

| Phase | Theme | Depends on | Outcome |
|-------|-------|-----------|---------|
| **0** | Project setup & conventions | — | Configured Laravel app, MySQL connected |
| **1** | Database schema (Postgres → MySQL) | 0 | All 21 tables as migrations + models |
| **2** | Identity & Auth | 1 | Login, OAuth, verification, password reset |
| **3** | Authorization core (RLS → app layer) | 2 | Roles/permissions, Policies, query scopes, middleware |
| **4** | Data migration (live data import) | 1–3 | Production data in MySQL, IDs preserved |
| **5** | Feature build — Admin | 3 | Admin module complete |
| **6** | Feature build — Educator | 3,5 | Educator module complete |
| **7** | Feature build — Student (incl. quiz engine) | 3,6 | Student module + grading complete |
| **8** | Real-time layer (deferred) | 5–7 | Live transport added over built features |
| **9** | Hardening, parity audit, cutover | 5–7 | Security headers, parity check, go-live |

Phases 5–7 each contain real-time-touching features (chat, notifications,
monitoring, presence, live dashboards). Per the locked decision, those are built
**request/response only** in 5–7; the **live transport is added in Phase 8**.

---

## Phase 0 — Project setup & conventions

**Goal:** a configured Laravel app talking to MySQL, with conventions fixed before
any feature code.

- **0.1 Environment.** `.env`: `APP_KEY`, MySQL credentials, mail/SMTP (verification
  + recovery), filesystem/S3 (replaces Supabase Storage). Remove all Supabase keys.
- **0.2 MySQL connection.** Switch default connection from the skeleton's SQLite to
  MySQL in `config/database.php`; confirm `php artisan migrate` runs clean.
- **0.3 Conventions.** Decide and document: table naming (**keep `tbl_*`** to match
  preserved data), models in `app/Models`, Form Requests in `app/Http/Requests`,
  Policies in `app/Policies`, service classes in `app/Services`, route groups per
  role. This is the Laravel equivalent of the role-based route groups in `src/app/`.
- **0.4 Layout shell.** One Blade master layout (`resources/views/layouts/app.blade.php`)
  reproducing `DashboardShell` (role-driven sidebar + header). Tailwind v4 is already
  wired via `vite`.

**Exit:** `artisan migrate` succeeds against MySQL; base layout renders.

---

## Phase 1 — Database schema (Postgres → MySQL)

**Goal:** all 21 tables as Laravel migrations + Eloquent models, faithful to
[LIVE_SCHEMA_EXPORT.sql](../architecture/LIVE_SCHEMA_EXPORT.sql).

Build migrations in **FK-dependency order** so constraints apply cleanly:

1. **Identity & RBAC:** `tbl_users`, `tbl_roles`, `tbl_permissions`,
   `tbl_role_permissions`, `tbl_user_roles`.
2. **Academic:** `tbl_academic_year`, `tbl_academic_term`, `tbl_sections`,
   `tbl_sections_term`, `tbl_subjects`.
3. **Enrollment & assessments:** `tbl_enrolled`, `tbl_assessments`, `tbl_quizzes`,
   `tbl_scores`, `tbl_student_assessment_retakes`.
4. **Ancillary:** `tbl_group_chats`, `tbl_group_chat_messages`,
   `tbl_group_chat_reads`, `tbl_learning_materials`, `tbl_notifications`,
   `tbl_student_presence`.

**Postgres → MySQL conversions (per [MIGRATION §1.4](../architecture/MIGRATION_LARAVEL_MYSQL.md)):**

| Postgres | MySQL / Laravel |
|----------|-----------------|
| `bigint` identity / sequences | `bigIncrement` / `AUTO_INCREMENT` |
| `jsonb` (`tbl_quizzes.choices`, `tbl_scores.student_answer`, `tbl_notifications.metadata`) | `json` column + Eloquent `$casts => 'array'` |
| `timestamptz` | `timestamp` (store UTC; set app timezone) |
| `text` | `text` / `varchar` as appropriate |
| `CHECK` constraints (`user_type`, `semester`, `quiz_type`, `status`, `event_type`) | validation in Form Requests + (optional) MySQL 8 `CHECK` / enum-cast |
| `deleted_at` soft-delete | `SoftDeletes` trait (`tbl_users`, `tbl_user_roles`) |
| `ON DELETE CASCADE / SET NULL` | replicate via FK constraint definitions |
| Unique constraints (composite) | `$table->unique([...])` — preserve every one (they back app dedupe logic) |
| Indexes (§2 of DDL) | replicate all btree indexes; `tbl_student_presence.student_id` is **UNIQUE** |
| Trigger `enforce_tbl_users_self_service_update_columns` | model `$fillable` + an Observer/Form Request blocking `user_id,email,user_type,is_active` self-edits |
| Trigger `enforce_section_term_uniqueness` | a `Section` service check / Form Request rule |
| `rls_auto_enable` event trigger | **drop** — no RLS in MySQL |

**Models:** one Eloquent model per table with relationships, `$casts`, `$fillable`,
soft-deletes where applicable. Mirror the ERD in
[ARCHITECTURE_TECHNICAL.md §2](../architecture/ARCHITECTURE_TECHNICAL.md#2-database).

**Exit:** fresh `artisan migrate` builds the full schema; models + relationships
unit-checkable via tinker/factories.

---

## Phase 2 — Identity & Authentication

**Goal:** replace Supabase Auth. Source flow: [ARCHITECTURE_TECHNICAL §4.1–4.3](../architecture/ARCHITECTURE_TECHNICAL.md#41-request--middleware--auth-context--role-redirect).

- **2.1 Auth scaffolding.** Laravel **Fortify** (or Breeze, Blade stack) for
  login/logout/registration/password reset/email verification. Server sessions
  replace JWT-in-cookie.
- **2.2 Identity mapping.** Auth identity binds to `tbl_users` via **lowercased
  email** (the existing join key). **Preserve `tbl_users.user_id`** as the historical
  key. Map password storage to Laravel's hasher (passwords reset on cutover if not
  portable — note in Phase 4).
- **2.3 Google OAuth.** Laravel **Socialite** (Google) reproducing
  `(auth)/auth/callback`: existing-email check → not-registered / inactive / link
  flows.
- **2.4 Password reset.** Laravel password broker + Mailable; reproduce the
  **rate-limited, non-enumerating** request endpoint (`throttle` middleware).
- **2.5 Email verification.** Laravel's `MustVerifyEmail` + resend (admin-triggered
  resend reproduced in Phase 5).
- **2.6 Auth context + role redirect.** Middleware reproducing `fetchAuthContext`:
  load user + active roles, compute **primary role (admin > educator > student,**
  fallback `user_type`)`, redirect to `/{role}/dashboard`. Block inactive/unverified.

**Exit:** a seeded user can sign in (password + Google), reset password, and land on
the correct dashboard; inactive/unverified users are blocked.

---

## Phase 3 — Authorization core (RLS → application layer) ⚠ highest risk

**Goal:** reproduce, in app code, what 35 RLS policies + 3 SQL helpers did in the DB.
Source: [LIVE_SCHEMA_EXPORT.sql §3,§5](../architecture/LIVE_SCHEMA_EXPORT.sql) and
[MIGRATION §1.3](../architecture/MIGRATION_LARAVEL_MYSQL.md#13-rls-is-the-authorization-layer).

- **3.1 RBAC primitives.** Translate the SQL helpers to a service/trait:
  - `has_role('x')` → `$user->hasRole('x')`
  - `user_has_permission('resource:action')` → `$user->hasPermission(...)`
    (joins user_roles → roles → role_permissions → permissions, active only)
  - `get_current_tbl_user_id()` → `auth()->user()->id`
- **3.2 Policies.** One Laravel Policy per resource encoding the three shapes seen in
  the RLS:
  - **Admin:** full access (`has_role('admin')`).
  - **Educator:** ownership (`educator_id === current user`) **plus**
    `user_has_permission('sections:view' …)` for sections/subjects.
  - **Student:** enrollment-gated via an active `tbl_enrolled` row.
- **3.3 Query scopes.** Eloquent **global/local scopes** that re-apply the RLS
  `USING` predicates so list/read queries can never return another user's rows
  (e.g. `Assessment::visibleTo($user)`). This is the safety net behind Policies.
- **3.4 Notification authorization.** Reproduce the nuanced notification INSERT
  rules: educators may emit all event types *except* `quiz_submitted` to students
  they teach; students may emit only `quiz_submitted` to the assessment's educator.
- **3.5 Quiz-answer protection.** Encode that `tbl_quizzes.correct_answer` is
  **never serialized to a student** outside grading (Phase 7) — at the
  model/resource layer (hidden attribute + dedicated read paths).
- **3.6 Route middleware.** Role-group middleware reproducing the per-page
  `requireServerAuthContext('role')` check.

**Exit:** authorization test matrix passes — for each table/role/action, allowed
cases succeed and forbidden cases are denied (mirror the RLS policy list as test
cases). **No feature work starts until this matrix is green.**

---

## Phase 4 — Data migration (live import)

**Goal:** move live data Postgres → MySQL with relationships intact.
Source: [MIGRATION §5](../architecture/MIGRATION_LARAVEL_MYSQL.md#5-data-migration-notes).

- **4.1 Export** from the live DB (the `export_public_schema_data()` helper dumps
  every table as JSON, or use `pg_dump --data-only`).
- **4.2 Transform** Postgres types → MySQL (jsonb→json, timestamptz→UTC timestamp,
  booleans).
- **4.3 Load preserving keys** — **keep the same integer `id` values** and
  `tbl_users.user_id`; carry over soft-deleted rows and `is_active`.
- **4.4 Re-add FK constraints** after load (load order is forgiving — no hard cascade
  chains today).
- **4.5 Reconcile identity** — link imported `tbl_users` to Laravel auth; decide
  password strategy (force reset on first login is the safe default).
- **4.6 Verify** row counts and spot-check relationships per table.

**Exit:** counts match source; sampled relationships resolve; a migrated user can log
in (post-reset) and see exactly their own data.

---

## Phase 5 — Feature build: ADMIN

**Goal:** the 6 admin modules from [FEATURE_MATRIX — ADMIN](FEATURE_MATRIX.md#admin).
Pattern per module: route (role-grouped) → controller → Form Request (from the Zod
schema) → service/Eloquent → Blade views, all behind Phase 3 Policies.

- **5.1 Users** — create (single), **bulk student import (xlsx)**, view, resend
  verification, delete. **Finish or drop the 🚧 Edit-user stub** (decision required;
  default: finish it). Uses the privileged path that was the service-role client —
  now just an authorized admin controller.
- **5.2 Access Control — Roles** — CRUD + assign permissions (all-or-nothing replace).
- **5.3 Access Control — Permissions** — bulk create (auto `resource:action`), delete.
  **🚧 Edit-permission stub:** finish or drop.
- **5.4 Academic Year** — create, delete (**cascade to terms**). **🚧 view/edit stubs.**
- **5.5 Academic Term** — create, delete. **🚧 view/edit stubs.**
- **5.6 Admin dashboard** — summary cards/insights as a normal page (live-refresh
  deferred to Phase 8).

**Exit:** admin can manage users/roles/permissions/calendar end to end; stubs
resolved deliberately.

---

## Phase 6 — Feature build: EDUCATOR

**Goal:** the 10 educator modules from
[FEATURE_MATRIX — EDUCATOR](FEATURE_MATRIX.md#educator). All writes gated by
`educator_id` ownership + RBAC permission (Phase 3).

- **6.1 Sections** — CRUD; M:N section↔term; name-per-term uniqueness (the
  section-term trigger logic, now in a service).
- **6.2 Subjects** — CRUD; one row per section (Cartesian); code/name uniqueness.
- **6.3 Enrollment** — single + **bulk xlsx**; dedupe; fires `enrollment_*`
  notifications. *(Notification write = request/response now.)*
- **6.4 Assessments** — CRUD with schedule/options; status `inactive→active` is the
  publish/notify trigger; cascade-delete questions.
- **6.5 Quizzes (questions)** — CRUD, **bulk xlsx upload**, delete-all-for-assessment.
- **6.6 Scores & Retakes** — review (best+latest, read-only scores); **grant retake**;
  attempt detail; **single + bulk Excel export** (PhpSpreadsheet/Laravel Excel,
  reproducing merged title row, summary block, frozen panes, `TERM/SUBJECT/SECTION.xlsx`
  zip — [MIGRATION §4](../architecture/MIGRATION_LARAVEL_MYSQL.md#4-migration-mapping-reference-port-checklist)).
- **6.7 Materials** — upload (Laravel Storage), list, edit metadata (soft deactivate),
  delete (+ storage cleanup); enrollment-checked **temporary URL** download replaces
  the 60s signed URL.
- **6.8 Group chats (request/response)** — create/list/delete; send message + mark-read
  as normal POST endpoints (these were direct-browser writes with **no API route** —
  [MIGRATION §2](../architecture/MIGRATION_LARAVEL_MYSQL.md#2-direct-browsersupabase-calls-with-no-api-route-new-endpoints-required)). Live delivery → Phase 8.
- **6.9 Realtime monitoring (request/response)** — the view + manual refresh now;
  live updates → Phase 8.

**Exit:** an educator can run a class end to end — sections → subjects → enroll →
assessment → questions → review/export — and chat/monitor via manual refresh.

---

## Phase 7 — Feature build: STUDENT (incl. quiz engine) ⚠ core invariant

**Goal:** the 7 student modules from
[FEATURE_MATRIX — STUDENT](FEATURE_MATRIX.md#student), enrollment + schedule + attempt
gated. The quiz engine is the highest-stakes feature.

- **7.1 Assessment list** — enrolled only; availability badges
  (Upcoming/Available/Reopened/Expired); can-take logic
  (`firstAttempt OR (canRetake AND remaining>0)`) — translate
  `assessment-availability.ts`.
- **7.2 Take-quiz session** — server-side eligibility (enrollment/schedule/retake),
  restore in-progress draft, shuffle if `is_shuffle`. Translate
  `student-quiz.ts` session loader.
- **7.3 Autosave draft** — `mode=draft` POST (debounced client JS over a normal
  endpoint), `status=in_progress`, `student_answer` json.
- **7.4 Anti-cheat** — client JS detectors (tab-hidden, blur, copy/paste, devtools,
  PrintScreen) increment `warning_attempts` + autosave; **force submit** at
  `cheating_attempts` or timer zero. Detection client-side, **enforcement server-side**.
- **7.5 ⚠ Server-side grading — preserve exactly.** `mode=submit` controller:
  re-validate enrollment, load `correct_answer` **server-only**, compare, compute
  `is_passed` (**≥75%**), write `tbl_scores`, emit `quiz_submitted` to the educator.
  **`correct_answer` is never serialized to the student.** This is the non-negotiable
  invariant from [MIGRATION §4](../architecture/MIGRATION_LARAVEL_MYSQL.md#4-migration-mapping-reference-port-checklist).
- **7.6 Result / review** — score summary; per-question review showing the correct
  answer **only if `allow_review=true` OR the answer was correct**; attempt history;
  retake.
- **7.7 Scores history** — filter/sort/paginate (own scores only).
- **7.8 Materials (student)** — enrollment-gated list + temporary-URL download.
- **7.9 Chats (request/response)** — view/send/mark-read; **cannot create chats**.
  Live delivery → Phase 8.
- **7.10 Profile (shared, all roles)** — name (educators/admins editable, **students
  read-only**), email change (unique), Google link, picture/cover upload, password
  change. Reproduce the self-service column lock (Phase 1 observer).

**Exit:** a student takes a scheduled quiz with autosave + anti-cheat, is graded
server-side at ≥75%, sees the gated review, and can retake when permitted. A targeted
test confirms `correct_answer` never appears in any student-facing response.

---

## Phase 8 — Real-time layer (deferred)

**Goal:** add live behavior *over* the request/response features already built in
5–7. Source: [MIGRATION §2](../architecture/MIGRATION_LARAVEL_MYSQL.md#2-direct-browsersupabase-calls-with-no-api-route-new-endpoints-required).

- **8.0 Transport decision (per feature).** Laravel **Reverb/Echo (WebSockets)** for
  low-latency (chat) vs **polling** for simple freshness (dashboards). Was the second
  open decision; deferred here, can be mixed.
- **8.1 Student presence heartbeat** — `POST /student/presence` upsert every ~25s
  (already a plain endpoint; add the client heartbeat + 60s-stale online logic).
- **8.2 Group chat delivery** — push new messages (Reverb) or poll.
- **8.3 Educator monitoring** — live presence + score updates (Reverb or poll).
- **8.4 Notification bell** — live unread (Reverb or poll the list endpoint).
- **8.5 Dashboard live-refresh** — admin/educator "changes-since" (poll).

**Exit:** the deferred live behaviors work without changing the underlying data
contracts built in 5–7.

---

## Phase 9 — Hardening, parity audit, cutover

- **9.1 Security headers** — response middleware reproducing HSTS, `X-Frame-Options:
  DENY`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP, X-Permitted-Cross-
  Domain ([ARCHITECTURE_TECHNICAL §10](../architecture/ARCHITECTURE_TECHNICAL.md#10-security-response-headers)).
- **9.2 CSP nonce** — middleware generates per-request nonce; Blade stamps
  `<script nonce>`; allow the WebSocket origin if Reverb is used.
- **9.3 Rate limiting** — `throttle` (Redis-backed for multi-worker correctness)
  replacing the in-memory reset-password limiter.
- **9.4 Authorization audit** — re-run the Phase 3 matrix against the *built*
  features; confirm no list endpoint leaks cross-tenant rows. (Highest-priority sign-off.)
- **9.5 Feature-parity audit** — walk every row of [FEATURE_MATRIX.md](FEATURE_MATRIX.md):
  reproduced ✓ / deliberately dropped ✓ / stub-resolved ✓.
- **9.6 Cutover** — final data sync (Phase 4 re-run), DNS/host switch (Vercel
  serverless → persistent PHP-FPM/Octane), smoke test, rollback plan.

**Exit:** parity audit complete, authorization audit signed off, production on
Laravel + MySQL.

---

## Critical-path & risk summary

```
0 → 1 → 2 → 3 ──┬─→ 5 (Admin) ───┐
                ├─→ 6 (Educator) ─┤→ 8 (Real-time) → 9 (Cutover)
   4 (import) ──┴─→ 7 (Student) ──┘
```

- **Phase 3 gates everything** — no feature work before the authorization matrix is
  green. This is where RLS-in-the-DB becomes code, and the biggest leak risk lives.
- **Phase 7.5 is the non-negotiable invariant** — server-only grading, answers never
  sent to the client.
- **Phase 4 can run in parallel** with feature work once schema (1) + auth (2) exist,
  but real users can't be validated until import is reconciled.
- **Real-time (8) never blocks** functional delivery — features ship usable without it.

## Open items to confirm before/while executing

1. **Stub resolution** (Phase 5): finish vs drop each 🚧 — edit-user, edit-permission,
   view/edit academic year & term. *Default: finish.*
2. **Password portability** (Phase 4): can Supabase hashes migrate, or force reset on
   first login? *Default: force reset.*
3. **Per-feature transport** (Phase 8): which features get WebSockets vs polling.
