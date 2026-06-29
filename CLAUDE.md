# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this project is

`qyzen_v2` is a **ground-up rewrite** of Qyzen — a school quiz / learning-management system — migrating it from **Next.js 16 + Supabase + PostgreSQL** (the `qyzen/` source, not in this repo) onto **Laravel (MVC + Blade) + MySQL**. The migration is driven by Supabase free-tier Disk IO limits.

This is a **near-rewrite, not a port**. The relational data (~21 `tbl_*` tables) ports cleanly; three pillars have no MySQL equivalent and are being rebuilt from scratch:
- **Identity** — Supabase Auth → Laravel Fortify + Socialite + server sessions.
- **Authorization** — Postgres RLS (the old gate) → Laravel Policies + Eloquent query scopes + route middleware. **This is the highest-risk theme.**
- **Real-time** — Supabase Realtime → built as plain request/response first; live transport (Reverb/polling) is the final, optional phase.

The full plan and rationale live in `docs/` — **read these before doing migration work**, in this order:
- `docs/roadmap/BUILD_ORDER_STEP_BY_STEP.md` — the linear, shippable checklist (Stages A–J). This is the executable order; do steps top to bottom.
- `docs/roadmap/IMPLEMENTATION_ROADMAP_LARAVEL.md` — phase-level reasoning behind that checklist.
- `docs/MIGRATION_LARAVEL_MYSQL.md` — coupling analysis: exactly what has no Laravel equivalent.
- `docs/FEATURE_MATRIX.md` — per-role acceptance checklist; every row must be reproduced or deliberately dropped.
- `docs/ARCHITECTURE_TECHNICAL.md` / `docs/ARCHITECTURE_OVERVIEW.md` — how the source system works today.
- `docs/roadmap/LiveSchemaExport.sql` — source-of-truth DDL for the schema migrations (Stage B).

`CONVENTIONS.md` is the short, binding rules summary; this file expands on it.

## Build order is a hard sequence

Work follows `BUILD_ORDER_STEP_BY_STEP.md` strictly. Two gates are non-negotiable:
- **Stage D (Authorization core) gates all feature work.** No feature controller/view is built until the authorization test matrix is green. With no RLS, application code is the *only* thing stopping cross-tenant data leaks.
- **Stage H6 (server-side grading)** must preserve the core invariant exactly: `correct_answer` is loaded server-side only, never serialized to a student; pass mark is ≥75%, computed on the server.

Stages so far: **A (foundation) and Phase 0 are complete** — Laravel runs on MySQL (`qyzen_v2` DB), base Blade layout exists. Next is **Stage B** (the 21 `tbl_*` migrations + models, in FK-dependency order).

## Conventions (binding)

- **Keep `tbl_*` table names.** They match the data being imported; each Eloquent model pins `protected $table = 'tbl_...'`.
- Models in `app/Models`, Form Requests in `app/Http/Requests` (one per source Zod schema), Policies in `app/Policies`, business logic in service classes under `app/Services`.
- **One route group per role** — `admin`, `educator`, `student`, shared `profile`, public `auth` — the equivalent of the source's role-based route groups.
- **Every list/read query carries an ownership or enrollment scope** (e.g. a `visibleTo($user)` Eloquent scope), and every route carries a Policy/middleware. A forgotten scope is a data leak.
- Timestamps store **UTC** (`APP_TIMEZONE=UTC`); jsonb columns become `json` with Eloquent `$casts => 'array'`.
- **Don't reproduce dead buttons.** Actions tagged 🚧 STUB in `FEATURE_MATRIX.md` were never wired — finish or drop them deliberately, never silently reproduce.

## Frontend: Tabler template

The UI uses the **Tabler** admin template (Bootstrap-based, not Tailwind for app UI — though Tailwind is wired via Vite, the real styling comes from Tabler).

- **Compiled Tabler assets** live in `public/tabler/{css,js,img,libs}`. Reference them from Blade with `{{ asset('tabler/css/tabler.min.css') }}`, `{{ asset('tabler/js/tabler.min.js') }}`, etc.
- **`template/` (repo root) holds 103 reference HTML pages** — the Tabler demo set: `dashboard.html`, `layout-vertical.html`, `sign-in.html`, `sign-up.html`, `forgot-password.html`, `datatables.html`, `form-elements.html`, `empty.html`, error pages, and more.

**When building any layout or view, copy the real markup from the matching `template/*.html` file** rather than hand-writing Tabler structure from memory — the class names and component nesting must match for the CSS/JS to work. The template files reference assets as `../public/tabler/...`; translate that to `{{ asset('tabler/...') }}` in Blade. The base layout is `resources/views/layouts/app.blade.php`; build it (and the role sidebars/headers) from `layout-vertical.html` + `dashboard.html`.

## Commands

```bash
# Full local dev (server + queue + logs + vite, concurrently)
composer dev

# First-time / fresh setup (install, .env, key, migrate, npm build)
composer setup

# Database
php artisan migrate            # apply migrations (MySQL: qyzen_v2)
php artisan migrate:fresh      # rebuild whole schema (Stage B verification)

# Tests (clears config first, then runs)
composer test
php artisan test                          # all tests
php artisan test --filter=SomeTestName    # a single test / method
php artisan test tests/Feature/FooTest.php

# Frontend assets (Tabler is static in public/; this is for Vite-managed css/js)
npm run dev      # vite dev server
npm run build    # production build (needed before @vite-using pages render in browser)
```

Environment: Windows, PHP 8.4 + `pdo_mysql`, Laragon/XAMPP MySQL (`root`, no password, db `qyzen_v2`). No `mysql` CLI on PATH — use `php artisan` or PDO for DB checks. Tabler is plain static CSS/JS served from `public/`, so app pages render without an asset build; only views that explicitly use `@vite` need `npm run build`/`dev` (the base layout does not).

## Security invariants to preserve (from the source system)

- Server-side grading; `correct_answer` never reaches the client (Stage H6).
- Enrollment-gated student access; educator ownership (`educator_id`) on all educator data.
- Self-service column lock: users cannot edit `user_id`, `email`, `user_type`, `is_active` on themselves (was a Postgres trigger → now a model Observer / Form Request).
- Private file downloads via temporary/signed URLs after an access check (was Supabase 60s signed URLs).
- Notification emit rules: educators emit all event types *except* `quiz_submitted` to students they teach; students emit only `quiz_submitted` to the assessment's educator.
