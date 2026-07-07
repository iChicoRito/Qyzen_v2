# Task 48 CRUD Audit Ledger

## Scope

Write-capable CRUD and CRUD-adjacent flows in `qyzen_v2`, using `routes/web.php` and `docs/FEATURE_MATRIX.md` as the audit surface.

## Baseline

- Branch: `codex/task-48-crud-audit`
- Worktree: `D:\Personal Files\Projects\WebApps\qyzen_v2\.worktrees\codex-task-48-crud-audit`
- Targeted pre-audit suite in the original checkout passed: `77` tests, `0` failures.
- First worktree suite run failed for environment bootstrapping only: missing `.env` / app key, not an app behavior regression.
- Current focus: reproduce the educator section modal bug before classifying any other discrepancies.

## Audit Matrix

| Area | Flow | Route(s) | Status | Notes |
| --- | --- | --- | --- | --- |
| Admin | Create/update/delete user | `admin.users.store`, `admin.users.update`, `admin.users.destroy` | Verified | Covered by existing feature tests; index modal isolation rechecked after shared table fix |
| Admin | Student import + report downloads | `admin.users.import`, `admin.users.import.report`, `admin.users.import.credentials` | Pending | Includes CRUD-adjacent import/report flow |
| Admin | Roles CRUD | `admin.roles.*` | Verified | Modal isolation rechecked after shared table fix |
| Admin | Permissions create/update/delete | `admin.permissions.*` | Verified | Modal isolation rechecked after shared table fix |
| Admin | Academic years CRUD | `admin.academic-years.*` | Verified | Modal isolation rechecked after shared table fix |
| Admin | Academic terms CRUD | `admin.academic-terms.*` | Verified | Modal isolation rechecked after shared table fix |
| Admin | Settings update | `admin.settings.update` | Pending | |
| Educator | Sections CRUD | `educator.sections.*` | Fixed | Shared data-table query form was nesting modal/action forms after the first row existed |
| Educator | Subjects CRUD | `educator.subjects.*` | Pending | |
| Educator | Enrollment CRUD | `educator.enrollment.*` | Pending | |
| Educator | Enrollment import | `educator.enrollment.import*` | Pending | CRUD-adjacent import flow |
| Educator | Assessments CRUD | `educator.assessments.*` | Pending | |
| Educator | Quizzes CRUD | `educator.quizzes.*` | Pending | Includes bulk upload and delete-for-assessment |
| Educator | Scores retake/upload | `educator.scores.grant-retake`, `educator.scores.upload` | Pending | Read-only score edits remain out of scope |
| Educator | Materials CRUD | `educator.materials.*` | Pending | |
| Educator | Chats create/delete/send | `educator.chats.*` | Pending | |
| Student | Quiz draft/submit | `student.take-quiz.draft`, `student.take-quiz.submit` | Verified | Covered by existing student feature suite; H6 invariant still holds |
| Student | Student chat send | `student.chats.messages.send` | Verified | Added direct regression test for send flow |
| Shared | Private messaging create/send/update/delete/read | `messaging.*` | Pending | |
| Shared | Notifications read/read-all | `notifications.read`, `notifications.read-all` | Pending | |
| Shared | Profile update/password/email intent | `profile.update`, `profile.password`, `profile.email.google` | Pending | |

## Confirmed Discrepancies

| ID | Area | Reproduction | Expected | Actual | Root Cause Class | Affected Code | Fix Status | Verification |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| T48-001 | Educator sections | Create one section, return to index, click `Add section`, try to create another section from the modal | Modal submit should remain a standalone POST and persist the new section | After the first row existed, the shared modal lived inside the server-backed table GET form, so the browser reparsed the injected controls into the query-controls form and the second create path broke | Nested-form markup / modal composition | `resources/views/components/data-table.blade.php`, `resources/views/educator/sections/index.blade.php`, `tests/Feature/Educator/EducatorFeaturesTest.php` | Fixed | Added regression test `test_sections_index_does_not_nest_modal_or_row_forms_inside_query_controls`; browser repro after reload created `Task48 Section C` successfully with `modalInsideForm=false` and a real POST form |

## Notes

- Nothing should move to "Confirmed Discrepancies" without a reproducible path or failing automated check.
- Fixes should stay backend-first; Blade or JS changes are allowed only when the root cause is in modal/validation feedback handling and the change stays non-visual.
- Student scores index modal isolation was rechecked after the shared table fix; no nested-form regression remains there.
