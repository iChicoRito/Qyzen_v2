# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this project is

`qyzen_v2` is a **ground-up rewrite** of Qyzen — a school quiz / learning-management system — migrating it from **Next.js 16 + Supabase + PostgreSQL** (the `qyzen/` source, not in this repo) onto **Laravel (MVC + Blade) + MySQL**. The migration is driven by Supabase free-tier Disk IO limits.

This is a **near-rewrite, not a port**. The relational data (~21 `tbl_*` tables) ports cleanly; three pillars have no MySQL equivalent and are being rebuilt from scratch:
- **Identity** — Supabase Auth → Laravel Fortify + Socialite + server sessions.
- **Authorization** — Postgres RLS (the old gate) → Laravel Policies + Eloquent query scopes + route middleware. **This is the highest-risk theme.**
- **Real-time** — Supabase Realtime → built as plain request/response first; live transport (Reverb/polling) is the final, optional phase.

Docs are organized under `docs/` by category — **read these before migration-adjacent work**:
- `docs/roadmap/BUILD_ORDER_STEP_BY_STEP.md` — the linear, shippable checklist (Stages A–J).
- `docs/roadmap/IMPLEMENTATION_ROADMAP_LARAVEL.md` — phase-level reasoning behind that checklist.
- `docs/roadmap/PROGRESS.md` — **the running log of what's actually built vs. what's next**; check this first for current status, it's more current than the plan docs above.
- `docs/roadmap/FEATURE_MATRIX.md` — per-role acceptance checklist; every row must be reproduced or deliberately dropped.
- `docs/roadmap/PARITY_AUDIT.md` — feature-matrix rows checked off against the built app.
- `docs/architecture/ARCHITECTURE_TECHNICAL.md` / `docs/architecture/ARCHITECTURE_OVERVIEW.md` — how the original Next.js/Supabase system worked.
- `docs/architecture/MIGRATION_LARAVEL_MYSQL.md` — coupling analysis: exactly what has no Laravel equivalent.
- `docs/architecture/LIVE_SCHEMA_EXPORT.sql` — source-of-truth DDL the schema migrations were ported from.
- `docs/architecture/REALTIME_MESSAGING_TRANSPORTS.md` — WebSocket/polling transport notes for chat and notifications.
- `docs/audits/` — point-in-time audits (CRUD repair, DB memory, anti-cheat, question-bank pool).
- `prompts/tasks/<NN-phase>/` — the original task-by-task build history, grouped by objective (foundation, UI standardization, student experience, messaging, auth/security, admin bulk-ops, hardening).

`CONVENTIONS.md` is the short, binding rules summary; this file expands on it.

## Current status

The migration (Stages A–J) is **complete and hardened**: schema, auth, authorization, admin/educator/student features, and the quiz engine are all built and tested on Laravel + MySQL. Feature work has continued past the migration itself (see recent commits and `prompts/tasks/`) — bulk import, messaging, notifications, landing page, anti-cheat, and a question bank/randomized pool have since shipped. Two gates from the migration remain binding on any related work:
- **Authorization (Stage D) gates all feature work.** Every list/read query and every route must carry a scope/Policy — there is no RLS to fall back on.
- **Server-side grading (Stage H6)** must preserve the invariant exactly: `correct_answer` is loaded server-side only, never serialized to a student; pass mark is ≥75%, computed on the server.

What's genuinely unbuilt: real Supabase→MySQL data import (Stage E, blocked on a live data export) and the optional live-transport upgrade for chat/notifications (Stage I — currently request/response or polling). See `docs/roadmap/PROGRESS.md` for the authoritative, regularly-updated detail.

## Conventions (binding)

- **Keep `tbl_*` table names.** They match the data being imported; each Eloquent model pins `protected $table = 'tbl_...'`.
- Models in `app/Models`, Form Requests in `app/Http/Requests` (one per source Zod schema), Policies in `app/Policies`, business logic in service classes under `app/Services`.
- **One route group per role** — `admin`, `educator`, `student`, shared `profile`, public `auth` — the equivalent of the source's role-based route groups.
- **Every list/read query carries an ownership or enrollment scope** (e.g. a `visibleTo($user)` Eloquent scope), and every route carries a Policy/middleware. A forgotten scope is a data leak.
- Timestamps store **UTC** (`APP_TIMEZONE=UTC`); jsonb columns become `json` with Eloquent `$casts => 'array'`.
- **Don't reproduce dead buttons.** Actions tagged 🚧 STUB in `docs/roadmap/FEATURE_MATRIX.md` were never wired — finish or drop them deliberately, never silently reproduce.

## Frontend: Metronic template (Tailwind v9 / KTUI)

The UI uses **Metronic v9 (Tailwind CSS + KTUI)**, in `public/metronic-tailwind-html-demos/`. The app is built on the **demo1** layout. (The old Bootstrap-5 Metronic was removed — class names are NOT interchangeable: it's `kt-btn`/`kt-card`/`kt-menu-link` + Tailwind utilities, and `ki-filled` icons, not `btn`/`card`/`ki-outline`.)

- **Compiled assets** live in `public/metronic-tailwind-html-demos/dist/assets/{css,js,media,vendors}`. The core bundles are `assets/css/styles.css` (+ `vendors/keenicons/styles.bundle.css`, `vendors/apexcharts/apexcharts.css`) and `assets/js/core.bundle.js` + `vendors/ktui/ktui.min.js` (+ `vendors/apexcharts/apexcharts.min.js`). Reference them from Blade with `{{ asset('metronic-tailwind-html-demos/dist/assets/css/styles.css') }}` etc. Class/component names live in `dist/assets/css/styles.css` — grep it to confirm a `kt-*` class exists before using it (e.g. there is no `kt-btn-warning`).
- **`dist/html/demo1/` holds the reference HTML** — `index.html` (the shell: `#sidebar` + `<header id="header">` + `<main id="content">` + `<footer>`), `authentication/classic/*.html` (auth), `account/members/team-members-datatable.html` / `team-members.html` (tables), `account/home/*` + `account/security/*` (forms). Demo pages set `<base href="../../">` so `assets/...` resolves to `dist/assets/...`.

**When building any layout or view, copy the real markup from the matching `dist/html/demo1/**/*.html` file** rather than hand-writing structure from memory — the `data-kt-*` attributes and `kt-*` class nesting must match for the bundled JS (KTUI) to initialize dropdowns, drawers, modals, sticky header, theme toggle. Translate demo `assets/...` paths to `{{ asset('metronic-tailwind-html-demos/dist/assets/...') }}` in Blade; do NOT use a `<base>` tag. The base layout is `resources/views/layouts/app.blade.php` (sidebar/header/content shell, `$navItems` loop); the auth shell is `resources/views/layouts/auth.blade.php` (centered `kt-card`). Keep the `nonce="{{ $cspNonce ?? '' }}"` on inline `<script>`/`<style>` — CSP is enforced (Stage J). **Note:** as of the template migration, only the shells + auth + admin views are on the new classes; educator/student feature views (`educator/**`, `student/**`) still carry old Bootstrap-Metronic classes in their content and are pending conversion.

## Commands

```bash
# Full local dev (server + queue + logs + vite, concurrently)
composer dev

# First-time / fresh setup (install, .env, key, migrate, npm build)
composer setup

# Database
php artisan migrate            # apply migrations (MySQL: qyzen_v2)
php artisan migrate:fresh      # rebuild whole schema
php artisan migrate:fresh --seed   # + demo dataset for local click-through (DemoSeeder)

# Tests (clears config first, then runs)
composer test
php artisan test                          # all tests
php artisan test --filter=SomeTestName    # a single test / method
php artisan test tests/Feature/FooTest.php

# Frontend assets (Vite-managed css/js: resources/js/app.js, resources/css/app.css)
npm run dev      # vite dev server
npm run build    # production build — required before pages render (base layout uses @vite)
```

Environment: Windows, PHP 8.4 + `pdo_mysql`, Laragon/XAMPP MySQL (`root`, no password, db `qyzen_v2`). No `mysql` CLI on PATH — use `php artisan` or PDO for DB checks. The Metronic template itself is static CSS/JS served from `public/metronic-tailwind-html-demos/` (no build step); `npm run dev`/`build` is only for the small amount of first-party JS/CSS in `resources/`.

## Security invariants to preserve (from the source system)

- Server-side grading; `correct_answer` never reaches the client (Stage H6).
- Enrollment-gated student access; educator ownership (`educator_id`) on all educator data.
- Self-service column lock: users cannot edit `user_id`, `email`, `user_type`, `is_active` on themselves (was a Postgres trigger → now a model Observer / Form Request).
- Private file downloads via temporary/signed URLs after an access check (was Supabase 60s signed URLs).
- Notification emit rules: educators emit all event types *except* `quiz_submitted` to students they teach; students emit only `quiz_submitted` to the assessment's educator.
