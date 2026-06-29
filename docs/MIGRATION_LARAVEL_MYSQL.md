# Qyzen — Migration Reference: Next.js + Supabase → Laravel MVC + MySQL

> Migration-only document. It describes what a move off Supabase/Postgres/Next.js onto **Laravel + MySQL** must reproduce, and where the hard parts are. The driver is Supabase free-tier Disk IO limits.
> Companions: [ARCHITECTURE_TECHNICAL.md](ARCHITECTURE_TECHNICAL.md) (how the system works today) and [ARCHITECTURE_OVERVIEW.md](ARCHITECTURE_OVERVIEW.md) (plain-language).
>
> **This is a near-rewrite, not a port** — every architectural pillar below has *no direct Laravel/MySQL equivalent* and must be re-implemented.

---

## Two decisions to make before building

The whole migration hinges on these; this doc deliberately leaves them open and notes the impact of each:

1. **Realtime transport** — Laravel **Reverb/Echo (WebSockets)** (closest to current UX, more infra to run) vs **polling** (simplest to host on shared PHP, higher request volume + slight lag). Can be chosen per-feature (e.g. polling for the admin dashboard, WebSockets for chat). Drives §2 and §3.
2. **Frontend rendering** — **Inertia + React** (keep most existing shadcn/Tailwind components, Laravel serves data) vs **Blade** (full view rewrite). Decides how much of the current UI survives. Drives §3 and §4.

---

## 1. Coupling — what the migration must replace

| Current (Supabase / Next.js) | Laravel / MySQL replacement | Risk |
|------------------------------|-----------------------------|------|
| **RLS policies = the authorization layer** (14 policy scripts in `database/policies/`, every table) | App-layer Policies/Gates + Eloquent query scopes | **Highest** — authz moves from the DB into app code; trivially under-enforced if a query forgets a scope |
| Supabase Auth (`auth.users`, JWT-in-cookie, Google OAuth, recovery emails) | Laravel Fortify/Breeze + Socialite + server sessions | High |
| Postgres helper fns `has_role`, `user_has_permission`, `get_current_tbl_user_id`; RPCs `get_group_chat_list`, `get_group_chat_messages` | Service classes / Eloquent scopes | High |
| Supabase Realtime (presence, monitoring, chat, admin dashboard, notifications) | Laravel Reverb/Echo (WebSockets) **or** polling — see §2 | High |
| `jsonb` columns, btree/partial indexes, `ON DELETE` rules, the self-service trigger | MySQL `JSON` type, MySQL indexes, app-enforced rules | Medium |
| Supabase Storage + 60s signed URLs (2 buckets) | Laravel Storage (local/S3) + temporary URLs | Medium |
| Server/client component split, edge middleware, per-request CSP nonce | Controllers + Blade/Inertia, route middleware | Medium |
| ExcelJS + JSZip export | Laravel Excel (maatwebsite) or PhpSpreadsheet + zip | Low |

**Good news first:** the domain tables are **already separate from Supabase's `auth.*` schema** — they're joined to identity only by `tbl_users.email` and `tbl_users.user_id`. The relational data model (the ~21 tables in [ARCHITECTURE_TECHNICAL.md §2](ARCHITECTURE_TECHNICAL.md#2-database)) ports to MySQL cleanly. What gets rebuilt is the **identity + authorization + realtime** layers wrapped around it.

### 1.1 Auth coupling
Session resolution runs entirely through Supabase on every request:

```
src/middleware.ts
  → updateSession() (src/lib/supabase/middleware.ts)   # refresh JWT from cookies
  → supabase.auth.getUser()                            # identity from JWT
  → fetchAuthContext() (src/lib/auth/auth-context.ts)  # app profile + roles
        ↳ tbl_users      WHERE lower(email)=… AND deleted_at IS NULL
        ↳ tbl_user_roles → tbl_roles  (active roles only)
```

- `auth.users.id` (UUID) is stored as `tbl_users.user_id`. The join key between auth and app data is the **lowercased email**.
- Role priority `admin > educator > student` (`getPrimaryRole`), falling back to `tbl_users.user_type`. `dashboardPath = /{role}/dashboard`.
- **Laravel equivalent:** Fortify/Breeze owns identity; replicate `fetchAuthContext` as middleware that loads the `User` + roles and gates routes. Email verification, Google OAuth (Socialite), and password recovery (Laravel password broker + Mailable) all replace Supabase Auth flows.

### 1.2 Three Supabase clients and the privilege boundary
| Client | Key | RLS | Used by |
|--------|-----|-----|---------|
| browser (`client.ts`) | anon (public) | **enforced** | client components, OAuth |
| server (`server.ts`) | anon + cookies | **enforced** | server components / middleware |
| **admin (`admin.ts`)** | **service-role (secret)** | **bypassed** | privileged API ops only |

Service-role (RLS-bypassing) escalation points to account for: `/api/users/*` (create/delete/resend-verification/bulk), `/api/auth/reset-password/request`, `/api/profile/settings` (storage), `/api/learning-materials/*` (storage), and OAuth orphan cleanup in the callback. **In Laravel these are just normal authorized controller actions** — there is no privilege-bypass client, because there is no RLS to bypass. The authorization those routes implicitly relied on RLS for must be made explicit in the controller/policy.

### 1.3 RLS *is* the authorization layer
Every table has Row-Level Security enabled; policies are written `TO authenticated` using SECURITY DEFINER helpers:

- `has_role('<role>')`, `user_has_permission('<perm_string>')`, `get_current_tbl_user_id()` — see `database/functions/`
- RPCs called from app code: `get_group_chat_list()`, `get_group_chat_messages(...)` (`src/lib/supabase/group-chat-shared.ts`)
- Per-table policy scripts: `database/policies/apply_*_rbac_policies.sql`

**None of this survives the move.** MySQL has no RLS. Every policy becomes a Laravel Policy/Gate or an Eloquent global/local scope, and every data-layer query must apply it explicitly. This is the highest-risk, highest-effort part of the migration: today the database refuses to return rows a user shouldn't see; after migration, *application code* is the only thing standing between a user and another user's data.

### 1.4 Postgres-specific features to convert
- `jsonb` → MySQL `JSON`: `tbl_quizzes.choices`, `tbl_scores.student_answer`, `tbl_notifications.metadata` (Eloquent `$casts = ['…' => 'array']`).
- Trigger `enforce_tbl_users_self_service_update_columns` (`database/sql/triggers/`) — blocks users editing sensitive columns (`user_id`, `email`, `user_type`, `is_active`) → Laravel model `$fillable` + an observer/Form Request.
- Soft deletes via `deleted_at` → Laravel `SoftDeletes` trait.
- `ON DELETE SET NULL / CASCADE` → MySQL FK constraints (note: today most FKs are nullable with soft-delete instead of hard cascade).
- Postgres `bigint` identity sequences → MySQL `AUTO_INCREMENT` (integer FK values are preserved on import — see §5).

## 2. Direct browser→Supabase calls with NO API route (new endpoints required)

⚠️ **The most easily-missed part of the migration.** The [API table in ARCHITECTURE_TECHNICAL.md §5](ARCHITECTURE_TECHNICAL.md#5-api-surface-srcappapi) implies all server interaction goes through `/api/*`. It does not. The features below talk to Supabase **directly from the browser** (Realtime subscriptions and direct table writes), so there is **no existing endpoint to translate** — each needs a brand-new Laravel server endpoint and/or a realtime transport.

| Feature | File(s) | Current mechanism | Laravel replacement |
|---------|---------|-------------------|---------------------|
| Student presence heartbeat | `take-quiz-page-client.tsx`, `src/lib/supabase/student-presence.ts` | direct `upsert tbl_student_presence` every 25s (`onConflict: student_id`) | **New** `POST /api/student/presence` write endpoint |
| Educator realtime monitoring | `realtime-monitoring-page-client.tsx` | `channel().on('postgres_changes', tbl_student_presence / tbl_scores)` | WebSocket (Reverb/Echo) **or** polling endpoint |
| Group chat | `group-chats-page-client.tsx` | Realtime `INSERT` on `tbl_group_chat_messages` + direct write to `tbl_group_chat_reads` (mark-read) | **New** send-message + mark-read endpoints; WebSocket **or** polling for delivery |
| Admin dashboard live refresh | `dashboard-realtime-shell.tsx` | Realtime on **11 tables** → `router.refresh()` | WebSocket **or** polling "changes since" endpoint |
| Notification bell | `notification-bell.tsx` | Realtime subscription on `tbl_notifications` | WebSocket **or** polling unread-count/list endpoint |

## 3. Runtime / deploy / config the migration must reproduce

**Environment variables.** The three Supabase keys (`NEXT_PUBLIC_SUPABASE_URL`, `NEXT_PUBLIC_SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_ROLE_KEY`) disappear. New: `APP_KEY`, DB credentials, mail/SMTP (recovery + verification emails), storage/S3 config, and Reverb/Pusher credentials if WebSockets are chosen.

**CSP nonce + dynamic rendering.** `src/middleware.ts` generates a per-request nonce (`base64(uuid)`), sets it on the request header, and Next.js stamps it onto injected scripts. Prod locks `script-src` to `'self' 'nonce-…' 'strict-dynamic'`; dev keeps `'unsafe-eval'` for webpack HMR. This requires **dynamic (non-static) rendering** — a known prod-freeze caveat if rendering is allowed to go static. In Laravel: a middleware generates the nonce and Blade stamps it onto `<script nonce="…">`. Keep `connect-src` allowing your new WebSocket origin if Reverb is used (today it allows `wss://*.supabase.co`).

**Security headers** (`next.config.ts` — full list in [ARCHITECTURE_TECHNICAL.md §10](ARCHITECTURE_TECHNICAL.md#10-security-response-headers): HSTS, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, COOP, `X-Permitted-Cross-Domain-Policies`) → a Laravel response middleware.

**Rate limiting:** reset-password is limited to 5/15min per IP+email via an **in-memory store** (resets on deploy/restart) → Laravel `throttle` middleware (or a Redis-backed limiter for correctness across processes).

**Hosting shift:** Vercel serverless → a persistent PHP host (PHP-FPM or Laravel Octane). The persistent process changes how realtime, queues, and scheduled work behave — Reverb needs a long-running process; the in-memory rate limiter behaves differently under multiple workers.

## 4. Migration mapping reference (port checklist)

**API surface → routes/controllers.** Reproduce the full [§5 endpoint table](ARCHITECTURE_TECHNICAL.md#5-api-surface-srcappapi) as Laravel `routes/api.php` / `routes/web.php` + controllers, **plus the 5 new endpoints from §2** (presence write, monitoring read, group-chat send + mark-read, admin changes, notifications). Preserve guards: admin-only, educator-only, enrolled-student checks — now as Policies/middleware instead of RLS.

**Validation → Form Requests.** Each `src/lib/validations/*` Zod schema maps 1:1 to a Laravel Form Request (17 schemas): sign-in, forgot/reset/change-password, profile-settings, student-upload, enrollment-upload, learning-materials, assessment, quiz, student-quiz, educator-score-export, group-chat, section, subject, enrollment, educator-retake.

**Data layer.** The ~35 `src/lib/supabase/*.ts` modules → Eloquent models + repository/service methods. Note the access pattern per module: **most rely on RLS** (must gain explicit scopes), **group chat uses RPC** (`get_group_chat_list/messages` — reimplement as a query/service), a few use explicit filters already.

**Quiz scoring guarantee — preserve exactly.** Grading happens server-side in `/api/student/assessment/scores/[assessmentId]/route.ts`; **correct answers never reach the client**. The Laravel controller must keep this: load correct answers server-side only, compare, compute `is_passed` (≥75%), write `tbl_scores`, notify the educator. Never serialize `correct_answer` to the student.

**Spreadsheet export.** ExcelJS/JSZip (`scores/utils/workbook-utils.ts`, `src/lib/spreadsheets/xlsx-reader.ts`) → PhpSpreadsheet / Laravel Excel. Reproduce the formatting: merged title row, summary block, data from row 8, frozen panes at row 8, alternating row colors, and the bulk `TERM/SUBJECT/SECTION.xlsx` zip folder structure.

## 5. Data migration notes
- Integer FKs are stable and portable — keep the same `id` values on import so all relationships survive.
- **Preserve `tbl_users.user_id`** even though Supabase auth goes away: it's the historical identity key referenced across tables and in OAuth linking.
- Carry over soft-deleted rows (`deleted_at`) and `is_active` flags; don't silently drop them.
- No hard `ON DELETE CASCADE` chains today, so export/import order is forgiving — but re-add the FK constraints in MySQL after load.
