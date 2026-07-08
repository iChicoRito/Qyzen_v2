# Qyzen v2 — Feature-Parity Audit (Stage J5)

> Every action in [`docs/FEATURE_MATRIX.md`](FEATURE_MATRIX.md) checked against the built
> Laravel app. Status: ✅ reproduced · 🔵 deferred (Stage I real-time) · ⛔ deliberately dropped ·
> 🟡 partial (parity minus a non-essential sub-behavior).
>
> Last run: 2026-06-30 (after Stages A–H + J1–J4). Suite: **42 tests green**.

## ADMIN (Stage F)

| Action | Status | Where |
|--------|--------|-------|
| Dashboard (summary cards, top students) | ✅ | `Admin\DashboardController` |
| Dashboard live-refresh | 🔵 | Stage I (static now) |
| Create single user (+ verification email) | ✅ | `Admin\UserController@store` + `StoreUserRequest` (user_id format per type) |
| Bulk create students (xlsx) | ✅ | `UserController@import` + `StudentsImport` (chunk 100, failed-row export) |
| View user | ✅ | `@show` |
| **Edit user (was 🚧 STUB)** | ✅ finished | `@update` + `UpdateUserRequest` |
| Resend verification | ✅ | `@resendVerification` (gated on unverified) |
| Delete user (hard) | ✅ | `@destroy` (`forceDelete`) |
| List / filter / sort / paginate | ✅ | faceted status + user_type |
| Create role | ✅ | `Admin\RoleController@store` (name `^[a-z_]+$`) |
| View role (+ permissions) | ✅ | `@show` |
| Edit role + assign permissions (all-or-nothing) | ✅ | `@update` (`permissions()->sync`) |
| Delete role | ✅ | `@destroy` |
| Bulk create permissions (computed string) | ✅ | `PermissionController@store` (server-computed `resource:action`) |
| View permission | ✅ | `@show` |
| **Edit permission (was 🚧 STUB)** | ✅ finished | `@update` |
| Delete permission | ✅ | `@destroy` |
| Create academic year | ✅ | `AcademicYearController@store` (format `YYYY - YYYY`) |
| **View/Edit year (was 🚧 STUB)** | ✅ finished | `@show`/`@edit`/`@update` |
| Delete year (cascade to terms) | ✅ | `@destroy` (txn; FK is restrictOnDelete) |
| Create academic term | ✅ | `AcademicTermController@store` (composite-unique) |
| **View/Edit term (was 🚧 STUB)** | ✅ finished | `@show`/`@edit`/`@update` |
| Delete term | ✅ | `@destroy` |

**All four source 🚧 STUBs finished.** No dead buttons reproduced.

## EDUCATOR (Stage G)

| Action | Status | Where |
|--------|--------|-------|
| Dashboard (cards, top students) | ✅ | `Educator\DashboardController` (visibleTo-scoped) |
| Dashboard live-refresh | 🔵 | Stage I |
| Sections CRUD + section↔term + name-per-term uniqueness | ✅ | `SectionController` + `SectionService` |
| Subjects CRUD (one row per section) + code/name uniqueness | ✅ | `SubjectController` (group by code::name) |
| Group chat: create/read/delete | ✅ | `Educator\ChatController` |
| Group chat: send message / mark read | ✅ (req/resp) | live delivery 🔵 Stage I |
| Group chat: fetch list/history (RPC) | ✅ | Eloquent queries replace the RPCs |
| Enrollment: create single (student×subject pairs) + notify | ✅ | `EnrollmentController@store` |
| Enrollment: bulk xlsx + dedupe | ✅ | `EnrollmentsImport` (in-file + DB dedupe) |
| Enrollment: read / update / delete-deactivate + notify | ✅ | full CRUD |
| Assessments CRUD; status flip = publish/notify | ✅ | `AssessmentController` (inactive→active fires `assessment_created`) |
| Quizzes CRUD (MC / identification) + notify | ✅ | `QuizController` |
| Quizzes bulk xlsx | ✅ | `QuizzesImport` (single bundled `quiz_uploaded`) |
| Quizzes delete-all-for-assessment | ✅ | `@destroyForAssessment` |
| Scores review (best/latest) — read-only | ✅ | `ScoreController@index` |
| Grant retake | ✅ | `@grantRetake` → `tbl_student_assessment_retakes` |
| View attempt detail (per-question correct + student answer) | ✅ | `@show` (server-side; never a student page) |
| Score export single xlsx | ✅ | `ScoresExport` |
| Score export bulk zip (`TERM/SUBJECT/SECTION.xlsx`) | ✅ | `ScoreExportService` (`ZipArchive`) |
| Materials upload / read / edit / delete | ✅ | `MaterialController` (local disk) |
| Materials download (signed temp URL) | ✅ | `URL::temporarySignedRoute` 60s + access re-check |
| Realtime monitoring: view status + refresh | ✅ (req/resp) | `MonitoringController` (manual refresh) |
| Realtime monitoring: live updates | 🔵 | Stage I |
| Realtime monitoring: students drill-down modal | 🟡 | counts shown; per-student modal not built (low value; add on request) |

## STUDENT (Stage H)

| Action | Status | Where |
|--------|--------|-------|
| Dashboard (cards, recent results) | ✅ | `Student\DashboardController` |
| Dashboard performance-trend chart | 🟡 | numeric cards shown; Recharts trend chart not ported (cosmetic) |
| Assessment list (enrolled only) + tab/filter | ✅ | `Student\QuizController@index` |
| Availability badge (Upcoming/Available/Reopened/Expired/Schedule) | ✅ | `AssessmentAvailabilityService` |
| View details + start gate (can-take) | ✅ | `@details` / `@take` |
| Take: load session (eligibility + draft restore + shuffle) | ✅ | `@take` |
| Take: answer MC / identification | ✅ | take-quiz view |
| Autosave draft (debounced) | ✅ | `@saveDraft` + vanilla JS ~800ms |
| Timer (green→yellow→red, auto-submit at 0) | 🟡 | timer + auto-submit built; color-stage styling simplified |
| Anti-cheat (tab-hidden/blur/paste/context-menu) + force-submit | ✅ | vanilla detectors → `warning_attempts` → force-submit at limit/zero |
| Hints (random-timed) | ⛔ dropped | low value, no server state; documented drop (add on request) |
| View mode list/slideshow · manual save | ⛔ dropped | autosave covers save; single list view kept |
| **Submit → server-side grading, pass ≥75%, notify** | ✅ **invariant** | `QuizGradingService` — `correct_answer` server-only, test-asserted |
| Result: score summary + warnings | ✅ | `ScoreController@show` |
| Result: attempt history switcher | ✅ | `@show` |
| Result: per-question review (gated correct-answer) | ✅ | shown only if `allow_review` OR correct |
| Scores history: summary + filter + sort + paginate (own only) | ✅ | `@index` |
| Materials: list / view / download (enrollment-gated) | ✅ | `Student\MaterialController` |
| Chats: view / send / mark-read; **cannot create** | ✅ (req/resp) | `Student\ChatController` (no store/destroy) |
| Chats: presence/live | 🔵 | Stage I |

## PROFILE (shared, Stage H11)

| Action | Status | Where |
|--------|--------|-------|
| Edit name (educators/admins; students read-only) | ✅ | `ProfileController` + `UpdateProfileRequest` (name dropped for students) |
| Change email (unique) | ✅ | explicit set (self-service lock allows self-email) |
| Link Google | ✅ | reuses Stage C Socialite route |
| Profile picture / cover upload (≤2MB, image mimes) | ✅ | local disk |
| Profile picture crop dialog (320², zoom) | ⛔ dropped | native file input; crop UI not ported (documented) |
| Change password (≥8, mixed/number/symbol) | ✅ | `@updatePassword` |
| Self-service lock (user_id/user_type/is_active immutable) | ✅ | enforced in Form Request (never in `$fillable`) |

## Summary

- **Every write action is reproduced** except a small set of **deliberately dropped** cosmetic/low-value items (quiz hints, list/slideshow toggle, image crop dialog) and **deferred real-time** upgrades (Stage I) for features that already work request/response.
- **No source 🚧 STUB was silently reproduced** — all four admin stubs were finished.
- The **H6 grading invariant** (`correct_answer` never reaches the client) is reproduced and test-asserted.
- 🟡 partials (dashboard charts, timer color-staging, monitoring drill-down modal) are cosmetic; flagged here, buildable on request.

**Remaining before production cutover:** Stage E (real data import — needs the live PG export) and
Stage J6 (cutover: final sync, host switch, smoke, rollback). Stage I (real-time) is optional.
