# Qyzen — Laravel Build Order (Exact Step-by-Step)

> The **linear "what to build first" checklist** for the migration. Each step is
> small, ordered, and shippable. Do them **top to bottom** — later steps assume
> earlier ones exist.
>
> Phase-level reasoning lives in
> [IMPLEMENTATION_ROADMAP_LARAVEL.md](IMPLEMENTATION_ROADMAP_LARAVEL.md); this file
> is the executable order. Stack is locked: **Laravel MVC + Blade + MySQL**,
> **real-time deferred**.
>
> Convention: `[ ]` = todo. Each step names the **deliverable** and the
> **done-when** check. Don't start a step until every step above it is done.

---

## STAGE A — Foundation (must be done before anything else)

- [ ] **A1. Point Laravel at MySQL.** Edit `config/database.php` default → `mysql`;
  set `.env` DB creds; create the empty MySQL database.
  **Done when:** `php artisan migrate` runs clean on the stock migrations.

- [ ] **A2. Set core env.** `.env`: `APP_KEY` (artisan key:generate), app timezone/URL,
  mail/SMTP, filesystem disk (local/S3). Remove all Supabase keys.
  **Done when:** `php artisan about` shows MySQL + mail + correct app config.

- [ ] **A3. Fix conventions (write them down once).** Keep `tbl_*` table names;
  models in `app/Models`; requests in `app/Http/Requests`; policies in
  `app/Policies`; services in `app/Services`; one route group per role.
  **Done when:** a one-paragraph CONVENTIONS note exists and is followed below.

- [ ] **A4. Base Blade layout.** `resources/views/layouts/app.blade.php` = the shell
  (role sidebar + header), Tailwind wired via existing `vite`.
  **Done when:** a dummy page extends the layout and renders with nav.

---

## STAGE B — Database schema (all 21 tables, in FK order)

> Build migration + Eloquent model **together** for each group. Order matters —
> a table's FK targets must already exist.

- [ ] **B1. `tbl_users`** (+ `SoftDeletes`, `$casts`, self-service lock fields).
- [ ] **B2. `tbl_roles`, `tbl_permissions`** (independent).
- [ ] **B3. `tbl_role_permissions`, `tbl_user_roles`** (M:N; user_roles soft-delete).
- [ ] **B4. `tbl_academic_year` → `tbl_academic_term`.**
- [ ] **B5. `tbl_sections` → `tbl_sections_term` → `tbl_subjects`.**
- [ ] **B6. `tbl_enrolled`.**
- [ ] **B7. `tbl_assessments` → `tbl_quizzes`** (`choices` json cast; `correct_answer`
  hidden by default).
- [ ] **B8. `tbl_scores`** (`student_answer` json cast) **→ `tbl_student_assessment_retakes`.**
- [ ] **B9. `tbl_group_chats` → `tbl_group_chat_messages` → `tbl_group_chat_reads`.**
- [ ] **B10. `tbl_learning_materials`, `tbl_notifications`** (metadata json cast),
  **`tbl_student_presence`** (UNIQUE on `student_id`).
- [ ] **B11. Replicate constraints.** Every composite UNIQUE + every btree index from
  [LiveSchemaExport.sql §2](LiveSchemaExport.sql), and `ON DELETE CASCADE/SET NULL`.
- [ ] **B12. Port the two triggers as app logic.** Self-service column lock
  (Observer/Form Request) and section-term uniqueness (service check).
  **Stage B done when:** `migrate:fresh` builds the whole schema; factories create a
  user with roles and an assessment with questions via tinker.

---

## STAGE C — Auth (replace Supabase Auth)

- [ ] **C1. Install Fortify (Blade stack).** Login, logout, registration, password
  reset, email verification scaffolded.
  **Done when:** you can register + log in a test user against `tbl_users`.
- [ ] **C2. Bind identity by lowercased email**; keep `user_id`. Email-verification on.
- [ ] **C3. Google OAuth via Socialite** — reproduce callback: not-registered /
  inactive / link-existing flows.
- [ ] **C4. Password reset** — broker + Mailable, **rate-limited + non-enumerating**.
- [ ] **C5. Auth-context middleware** — load user + active roles, compute primary role
  (**admin > educator > student**, fallback `user_type`), redirect to
  `/{role}/dashboard`; block inactive/unverified.
  **Stage C done when:** password + Google login both land on the right dashboard;
  blocked users are bounced.

---

## STAGE D — Authorization core ⚠ BUILD THIS BEFORE ANY FEATURE

- [ ] **D1. RBAC helpers** on the User model/trait: `hasRole()`,
  `hasPermission('resource:action')` (active joins only).
- [ ] **D2. Query scopes** — `visibleTo($user)` per resource, re-applying the RLS
  `USING` predicates (admin all / educator ownership / student enrollment). **Every
  list query uses one.**
- [ ] **D3. Policies** — one per resource, three shapes (admin full / educator
  ownership + permission / student enrollment-gated).
- [ ] **D4. Role route-group middleware** (`requireRole`), replacing
  `requireServerAuthContext`.
- [ ] **D5. Notification-emit rules** — educator (all types except `quiz_submitted`,
  to taught students) vs student (`quiz_submitted` only, to the assessment's educator).
- [ ] **D6. Authorization test matrix** — for each table × role × action, allowed
  passes / forbidden denied. Mirror the 35 RLS policies as test cases.
  **Stage D done when:** the matrix is green. **No feature code starts until then.**

---

## STAGE E — Data import (can run in parallel once B–D exist)

- [ ] **E1. Export** live data (`export_public_schema_data()` or `pg_dump --data-only`).
- [ ] **E2. Transform** types (jsonb→json, timestamptz→UTC, bool).
- [ ] **E3. Load preserving `id` + `user_id`**, soft-deletes, `is_active`.
- [ ] **E4. Re-add FK constraints; verify** row counts + sampled relationships.
- [ ] **E5. Reconcile passwords** — force reset on first login (default).
  **Stage E done when:** counts match and a migrated user logs in (post-reset) and
  sees only their own data.

---

## STAGE F — Admin features (first feature stage)

> Pattern every time: route → controller → Form Request (from the matching Zod
> schema) → service/Eloquent (with `visibleTo` scope) → Blade. All behind D-Policies.

- [ ] **F1. Admin dashboard** (read-only page; live-refresh later).
- [ ] **F2. Users** — create single, view, delete, resend-verification.
- [ ] **F3. Users — bulk student import (xlsx).**
- [ ] **F4. Resolve 🚧 Edit-user** (finish, default).
- [ ] **F5. Roles** — CRUD + assign permissions (all-or-nothing replace).
- [ ] **F6. Permissions** — bulk create + delete; **resolve 🚧 Edit-permission.**
- [ ] **F7. Academic Year** — create + cascade-delete; **resolve 🚧 view/edit.**
- [ ] **F8. Academic Term** — create + delete; **resolve 🚧 view/edit.**
  **Stage F done when:** admin manages users/roles/permissions/calendar end to end.

---

## STAGE G — Educator features

- [ ] **G1. Educator dashboard** (read-only).
- [ ] **G2. Sections** — CRUD + section↔term + name-per-term uniqueness.
- [ ] **G3. Subjects** — CRUD (one row per section) + code/name uniqueness.
- [ ] **G4. Enrollment** — single + bulk xlsx + dedupe (+ enrollment notifications).
- [ ] **G5. Assessments** — CRUD + schedule/options; status flip = publish/notify.
- [ ] **G6. Quizzes (questions)** — CRUD + bulk xlsx + delete-all-for-assessment.
- [ ] **G7. Scores review + grant retake** (scores read-only).
- [ ] **G8. Score export** — single xlsx, then bulk zip (`TERM/SUBJECT/SECTION.xlsx`,
  exact formatting) via PhpSpreadsheet/Laravel Excel.
- [ ] **G9. Materials** — upload/list/edit/delete + temporary-URL download.
- [ ] **G10. Group chats (request/response)** — create/list/delete + send + mark-read.
- [ ] **G11. Realtime monitoring (request/response)** — view + manual refresh.
  **Stage G done when:** an educator runs a class end to end (section→export).

---

## STAGE H — Student features (incl. quiz engine) ⚠ core invariant

- [ ] **H1. Student dashboard** (read-only).
- [ ] **H2. Assessment list** — enrolled only + availability badges + can-take logic.
- [ ] **H3. Take-quiz session load** — eligibility + draft restore + shuffle.
- [ ] **H4. Autosave draft** (`mode=draft`, debounced).
- [ ] **H5. Anti-cheat** — client detectors → `warning_attempts` + autosave; force
  submit at limit / timer zero.
- [ ] **H6. ⚠ Server-side grading** (`mode=submit`) — load `correct_answer`
  server-only, compare, `is_passed` ≥75%, write score, notify educator.
  **`correct_answer` never reaches the client.**
- [ ] **H7. Result / review** — gated correct-answer display (`allow_review` OR correct).
- [ ] **H8. Scores history** — filter/sort/paginate (own only).
- [ ] **H9. Materials (student)** — enrollment-gated list + download.
- [ ] **H10. Chats (request/response)** — view/send/mark-read; cannot create.
- [ ] **H11. Profile (shared)** — name (students read-only), email, Google link,
  picture/cover, password; self-service lock enforced.
  **Stage H done when:** a student takes a scheduled quiz with autosave + anti-cheat,
  is graded server-side, and a test confirms no `correct_answer` in any student response.

---

## STAGE I — Real-time (deferred — only after F–H ship)

- [ ] **I1. Pick transport per feature** (Reverb/WebSockets vs polling).
- [ ] **I2. Presence heartbeat** (~25s upsert; 60s-stale online).
- [ ] **I3. Group-chat live delivery.**
- [ ] **I4. Educator monitoring live updates.**
- [ ] **I5. Notification bell live unread.**
- [ ] **I6. Dashboard live-refresh (admin/educator).**

---

## STAGE J — Hardening & cutover (last)

- [ ] **J1. Security-header middleware** (HSTS, X-Frame DENY, nosniff, etc.).
- [ ] **J2. CSP nonce** in middleware + Blade.
- [ ] **J3. Rate limiting** (throttle; Redis-backed).
- [ ] **J4. Re-run authorization matrix** against built features (no cross-tenant leak).
- [ ] **J5. Feature-parity audit** — every FEATURE_MATRIX row reproduced/dropped/resolved.
- [ ] **J6. Cutover** — final data sync, host switch, smoke test, rollback plan.

---

## The first five things, if you only read one section

1. **A1** — Laravel on MySQL, `migrate` clean.
2. **B1** — `tbl_users` migration + model.
3. **C1** — Fortify login working against `tbl_users`.
4. **D1–D6** — authorization core + green test matrix. **(Gate.)**
5. **F2** — first real feature: admin create/list users.

Everything else follows the order above.
