# Repository Guidelines

## Project Structure & Module Organization
This is a Laravel 13 app. Core backend code lives in `app/` (`Http/Controllers`, `Http/Requests`, `Models`, `Policies`, `Services`). Database work belongs in `database/migrations`, `database/seeders`, and `database/factories`. Blade views live in `resources/views`, frontend assets in `resources/css`, `resources/js`, and `public/`, and route definitions in `routes/`. Tests are under `tests/Feature` and `tests/Unit`. Project notes and migration references live in `docs/`.

## Build, Test, and Development Commands
- `composer dev` starts the local stack: Laravel server, queue listener, logs, Reverb, and Vite.
- `composer setup` installs dependencies, creates `.env`, generates an app key, migrates, installs npm packages, and builds assets.
- `composer test` clears config and runs the full PHPUnit suite.
- `php artisan test --filter=Name` runs one test class or method.
- `php artisan migrate` applies database changes; `php artisan migrate:fresh` rebuilds the schema.
- `npm run dev` starts the Vite dev server; `npm run build` creates production assets.

## Coding Style & Naming Conventions
Follow `.editorconfig`: spaces, 4-space indentation for PHP, 2 spaces for JSON, YAML, JS, and CSS. Use PSR-4 class names and Laravel naming conventions: `SomethingController`, `StoreSomethingRequest`, `SomethingPolicy`, and one model per table in `app/Models`. Keep table names `tbl_*` where the existing schema uses them. Run `./vendor/bin/pint` before committing PHP changes.

## Testing Guidelines
Use PHPUnit for all automated tests. Place user-facing behavior tests in `tests/Feature` and isolated logic in `tests/Unit`. Name test files `SomethingTest.php` and prefer descriptive method names such as `it_rejects_invalid_input`. Add or update tests when changing authorization, migrations, or request validation.

## Commit & Pull Request Guidelines
Commit history follows conventional commits with scopes and task numbers, for example `feat(messaging): task 33 real-time chat via WebSocket broadcast`. Keep subjects short and action-focused. Pull requests should summarize the change, list verification steps, and include screenshots for UI changes or notes for schema migrations.

## Agent-Specific Notes
Read `CLAUDE.md` and `CONVENTIONS.md` before migration or authorization work. This codebase treats authorization as explicit and security-sensitive, so avoid broad query changes without checking scopes and policies.
