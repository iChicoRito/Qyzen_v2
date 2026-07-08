# Qyzen — Feature / Capability Matrix

> Per-role, per-module list of **every concrete action** a user can take. Source-verified from the page-client components and data layer (`src/lib/supabase/*`), not inferred. Doubles as an **acceptance checklist for the Laravel + MySQL rewrite** ([MIGRATION_LARAVEL_MYSQL.md](../architecture/MIGRATION_LARAVEL_MYSQL.md)) — every row must be reproduced (or deliberately dropped).
>
> Companion to [ARCHITECTURE_TECHNICAL.md](../architecture/ARCHITECTURE_TECHNICAL.md) (mechanisms) and [ARCHITECTURE_OVERVIEW.md](../architecture/ARCHITECTURE_OVERVIEW.md) (plain-language).
>
> ⚠️ **Stub markers:** actions tagged **🚧 STUB** render in the UI but are **not wired to a backend** today (e.g. a `setTimeout` placeholder). Do **not** treat them as working features to "preserve" — they are unfinished. Tagged **⚡ live** = handled via Supabase Realtime / direct table write, no `/api` route (see [ARCHITECTURE_TECHNICAL.md §7](../architecture/ARCHITECTURE_TECHNICAL.md#7-realtime--direct-from-browser-data-access)).

---

## ADMIN

### Dashboard — `(admin)/admin/dashboard`
Read-only. Summary cards (total/active users, educators, students), assessment-status breakdown, top students, student/educator/assessment insight panels. Live-refreshes via `DashboardRealtimeShell` (Realtime on 11 tables). No write actions.

### Users — `(admin)/admin/users` · `src/lib/supabase/users.ts`, `/api/users*`
| Action | Notes |
|--------|-------|
| Create single user | userId (format `YYYY-NNNNN` student / `YYYY-NNNN` educator), name, email, status, userType, roleNames (≥1). Sends verification email. |
| Bulk create students (xlsx) | Template download; chunked 100/request; per-row success/failure report; download failed rows to retry. |
| View user | In-row modal. |
| Edit user | ✅ **Resolved in Laravel (Stage F4)** — was a 🚧 STUB in the source (`setTimeout(400)` placeholder). Now real: `UpdateUserRequest` + `UserService::update`. |
| Resend verification | Only when `isEmailVerified=false` **and** `hasAuthUser=true`. |
| Delete user | Hard delete (auth account + profile) via `DELETE /api/users/[userId]`. |
| List / filter / sort | Faceted filters (status, userType), sortable, paginated. |

### Access Control → Roles — `(admin)/admin/access-control/roles` · `access-control.ts`
| Action | Notes |
|--------|-------|
| Create role | roleName (`^[a-z]+(_[a-z]+)*$`), description, status, isSystem flag. |
| View role | Shows details + assigned permissions. |
| Edit role + assign permissions | All-or-nothing: deletes all role↔permission rows, re-inserts selected. Supports rename. |
| Delete role | Hard delete by name. |
| List | Shows permission count (computed client-side), status, isSystem. |

### Access Control → Permissions — `(admin)/admin/access-control/permissions` · `access-control.ts`
| Action | Notes |
|--------|-------|
| Create permissions (bulk) | Repeater form; `permission_string` auto-computed `resource:action` (read-only). |
| View permission | In-row modal. |
| Edit permission | ✅ **Resolved in Laravel (Stage F6)** — was a 🚧 STUB in the source. Now real: `UpdatePermissionRequest`; `permission_string` recomputed server-side. |
| Delete permission | Hard delete by `permission_string`. |
| List | Sortable / filterable. |

### Academic Settings → Year — `(admin)/admin/academic-settings/academic-year` · `academic-settings.ts`
| Action | Notes |
|--------|-------|
| Create academic year | Format `YYYY - YYYY`, status. |
| View / Edit year | ✅ **Resolved in Laravel (Stage F7)** — were 🚧 STUBs in the source. Now real view + edit. |
| Delete year | **Cascades**: deletes child `tbl_academic_term` rows first, then the year. |
| List | Ordered year DESC. |

### Academic Settings → Term — `(admin)/admin/academic-settings/academic-term` · `academic-settings.ts`
| Action | Notes |
|--------|-------|
| Create term | termName (e.g. "Prelim"), semester (1st/2nd), academicYear (dropdown — requires ≥1 year to exist), status. |
| View / Edit term | ✅ **Resolved in Laravel (Stage F8)** — were 🚧 STUBs in the source. Now real view + edit. |
| Delete term | Hard delete by composite key (term_name, semester, academic_year_id). |
| List | Joined to year; ordered id DESC. |

---

## EDUCATOR

All educator writes are gated by **`educator_id` ownership** + RBAC permission checks (`sections:view/create/...` etc.) resolved server-side before render.

### Dashboard — `(educator)/educator/dashboard` · `educator-dashboard.ts`
Read-only. Summary cards (sections, subjects, top students), assessment overview (total/active/shuffled/scheduled), per-section insights, top students, assessment-completion insights. Live-refreshes.

### Classroom → Sections — `classroom/sections` · `sections.ts`
| Action | Notes |
|--------|-------|
| Create | sectionName, academicTermIds[] (M:N → `tbl_sections_term`), status. Uniqueness: name per term per educator. |
| Read | Grouped by section, own sections only, ordered id DESC. |
| Update | Same uniqueness (excl. self); replaces term links (delete-all + re-insert — no transaction). |
| Delete | Cascade to `tbl_sections_term`; ownership-checked. |

### Classroom → Subjects — `classroom/subjects` · `subjects.ts`
| Action | Notes |
|--------|-------|
| Create | code, name, sectionIds[] → **one row per section** (Cartesian). Case-insensitive code+name uniqueness per section. |
| Read | Grouped client-side by `code::name::active`; tracks rowIds[] + sections[]. |
| Update | Replaces a code+name group: delete all rowIds → re-create with new sections (no transaction). |
| Delete | Removes all rowIds for the group; ownership-checked. |

### Classroom / Group Chats — `group-chats` (+`/create`, `/messages`), `classroom/group-chats` · `group-chats.ts`, `group-chat-shared.ts`
| Action | Notes |
|--------|-------|
| Create chat | Per subject (educator-owned). |
| Read managed chats | Filtered by `educator_id`; includes subject/section context. |
| Delete chat | Ownership-checked; fires notification (best-effort). |
| Send message ⚡ | Insert into `tbl_group_chat_messages` (direct, no `/api`). |
| Mark read ⚡ | Upsert `tbl_group_chat_reads` (`onConflict: group_chat_id,user_id`). |
| Fetch list / history | RPC `get_group_chat_list` / `get_group_chat_messages`. |

### Enrollment — `enrollment` · `enrollments.ts`
| Action | Notes |
|--------|-------|
| Create (single) | studentIds[] × subjectIds[] → **one row per pair**. Uniqueness on (student, subject, educator). Fires `enrollment_created` to each student. |
| Create (bulk xlsx) | Rows (student_user_id, subject_code, section_name, status); dedupes within file + against DB; batch insert. |
| Read | Own enrollments, ordered id DESC. |
| Update | Change student/subject/status; uniqueness excl. self; fires `enrollment_updated`. |
| Delete / deactivate | Hard delete (`enrollment_deleted`) or set `is_active=false` (`enrollment_updated`). |
| Notes | Notifications to **students only**; partial-failure risk on batch (Supabase atomicity). |

### Assessments — `assessment/assessments` · `assessments.ts`
| Action | Notes |
|--------|-------|
| Create | code, selections[] (subject×section → **one row each**), academicTermId, timeLimit, cheatingAttempts?, isShuffle, allowReview, allowRetake (+retakeCount?), allowHint (+hintCount?), status, start/end date+time. Unique per (term, subject, section, code). Fires `assessment_created` to enrolled students. |
| Read | Joined to term/subject/section. |
| Update | Single row, all fields; fires `assessment_updated`. |
| Delete | Cascades to `tbl_quizzes` (questions); fires `assessment_deleted`. |
| "Publish" | No explicit button — status `inactive→active` is the publish/notify trigger. |

### Quizzes (Questions) — `assessment/quizzes` · `quizzes.ts`
| Action | Notes |
|--------|-------|
| Create question | quizType `multiple_choice` (choices A–D + single correct key) or `identification` (correctAnswer string or string[]). Fires `quiz_created`. |
| Bulk upload (xlsx) | Batch insert per assessment; single bundled `quiz_uploaded` notification. |
| Read | Grouped by assessment (counts MC vs identification). |
| Update | Edit one question; fires `quiz_updated`. |
| Delete one | Fires `quiz_deleted`. |
| Delete all for assessment | `deleteQuizzesByAssessment`. |
| Notes | No order/position column (DB order); not versioned; never sent to students before assessment opens. |

### Scores & Retakes — `scores` · `educator-scores.ts`
| Action | Notes |
|--------|-------|
| Review scores | Best + latest attempt per student; canRetake / remainingRetakes / effectiveRetakeCount. **Scores are read-only** (educator can't edit raw scores). |
| Grant retake | Writes `tbl_student_assessment_retakes`; `effective = (allowRetake?retakeCount:0) + grantedRetakeCount`, `remaining = effective − submittedAttempts`. |
| View attempt detail | Per-question correct answer + student answer + isCorrect + attempt history. |
| Export (single) | One assessment → `.xlsx` (ExcelJS). |
| Export (bulk) | method `all` / `term` / `semester`; bundles multiple workbooks into `.zip` (JSZip); `TERM/SUBJECT/SECTION.xlsx` structure. |

### Materials — `materials` · `learning-materials.ts`, `/api/learning-materials*`
| Action | Notes |
|--------|-------|
| Upload | files[] × selectionKeys[] (subject:section); uploads to Storage `learning-materials` + inserts metadata. Fires `learning_material_uploaded` to enrolled students. |
| Read | Grouped by (subject, section); own educator. |
| Edit metadata | file_name, is_active (soft deactivate — leaves storage object). |
| Delete | Removes metadata **and** storage object; ownership-checked. |
| Notes | Grouped by subject+section, not educator — multiple educators can share a subject+section. |

### Realtime Monitoring — `realtime-monitoring` · `educator-realtime-monitoring.ts` ⚡
| Action | Notes |
|--------|-------|
| View live status | Per (subject, code, term), parent/child by section. presence ONLINE/OFFLINE × assessment NOT_STARTED/ANSWERING/FINISHED; counts enrolled/online/answering/finished/offline. |
| View students modal | Drill-down: names, status, last-action time. |
| Refresh | Manual refetch. |
| Live updates ⚡ | Subscribes to `postgres_changes` on `tbl_student_presence` + `tbl_scores`. |
| Notes | **View-only** — no force-offline / pause-assessment action. |

---

## STUDENT

All student reads/writes are **enrollment-gated** (active `tbl_enrolled` row); quiz actions add **schedule** + **attempt** gates.

### Dashboard — `(student)/student/dashboard` · `student-dashboard.ts`
Read-only. Summary cards (total/pending/completed assessments, average score), performance-trend chart, next scheduled assessment (links into take-quiz if open), progress by subject/section, recent results, materials by subject.

### Assessment List — `assessment/quiz` · `student-assessments.ts`, `assessment-availability.ts`
| Action | Notes |
|--------|-------|
| List | Enrolled assessments only. |
| Tab filter | Pending vs Finished. |
| Filter by code | Dropdown. |
| Select | Local zustand `useQuiz` state. |
| View details | Title, educator, subject/section/term, question count, time limit, shuffle, retake policy, attempt history, best score, availability badge (Upcoming / Available / Reopened / Expired / Schedule issue), countdown. |
| Start quiz | Only if available + schedule open + attempt remaining. Can-take = `firstAttempt OR (canRetake AND remaining>0)`. |

### Take Quiz — `assessment/take-quiz` · `student-quiz.ts`, `/api/student/assessment/scores/[assessmentId]`
| Action | Notes |
|--------|-------|
| Load session | `fetchStudentQuizSession` validates enrollment/schedule/retake; restores in-progress draft; redirects if ineligible. |
| Answer | MC (radio) or identification (text). Questions/choices shuffled if `isShuffle`. |
| Autosave draft | Debounce ~800ms → `mode=draft` (`status=in_progress`). |
| Timer | Draggable badge; green→yellow(50%)→red(20%), shake <20%; hide/show; **auto-submit at 0**. |
| Anti-cheat | Detects tab-hidden, window-blur, copy/paste (blocked), context-menu (blocked), devtools shortcuts (blocked), PrintScreen (blur). Each counts `warning_attempts` + autosaves; **auto-submit when limit (`cheating_attempts`) reached**. (See [ARCHITECTURE_TECHNICAL.md §8](../architecture/ARCHITECTURE_TECHNICAL.md#8-quiz-runtime-behavior-anti-cheat-hints-autosave).) |
| Hints | If `allow_hint`: up to `hint_count` random-timed toast hints (not student-requested). |
| View mode | List vs slideshow. |
| Manual save | Optional (autosave covers it). |
| Submit | Confirms unanswered count → `mode=submit`; **server-side grading** (correct answers never sent to client); pass ≥75%; redirect to result. |

### Result / Review — `assessment/take-quiz/result` · `student-quiz.ts`
| Action | Notes |
|--------|-------|
| View score summary | Correct/incorrect, %, pass/fail, best score, submitted-at, warnings used. |
| Attempt history | Switch between attempts (loads other scoreId); "Highest Score" marked. |
| Per-question review | Student answer vs correct answer — **correct answer shown only if `allow_review=true` OR the answer was correct**; MC color-coded. |
| Retake | If `canRetake` → back into take-quiz. |
| Back to assessments | — |
| Gate | Score must belong to the current student. |

### Scores History — `assessment/scores` · `student-quiz.ts`
| Action | Notes |
|--------|-------|
| Summary cards | Total, passed, failed, average %. |
| Filter | Code / subject / term / status (multi-select, chainable). |
| Sort + paginate | TanStack table. |
| View attempt | Row action → result page. |
| Gate | Own scores only. |

### Materials — `materials` · `learning-materials.ts`, `/api/learning-materials/[id]/file`
| Action | Notes |
|--------|-------|
| List | Accordion by subject+section (own enrollments); shows educator, file name, size, type badge, updated-at. |
| View | Opens file in new tab (signed URL). |
| Download | `?download=true` flag. |
| Gate | Enrolled subjects only; private bucket served via **60s signed URL** after access check. |

### Chats — `chats` · `group-chats.ts`, `group-chat-shared.ts` ⚡
| Action | Notes |
|--------|-------|
| View conversations | Chats the student belongs to; unread badges; sorted by last message. |
| View thread | Messages with sender/avatar/timestamp. |
| Send message ⚡ | Insert `tbl_group_chat_messages` (direct). |
| Mark read ⚡ | On select → upsert `tbl_group_chat_reads`. |
| Presence ⚡ | Online indicators via Realtime. |
| Gate | Read/write only in enrolled chats; **students cannot create chats** (educator-only). |

---

## PROFILE (shared — all roles) — `(profile)/profile` · `/api/profile/settings`
| Action | Notes |
|--------|-------|
| Edit name | **Educators/admins** editable; **students: read-only** ("students can update email and media only"). |
| Change email | Editable; uniqueness enforced server-side. |
| Link Google | OAuth link flow (`LinkGoogleButton`). |
| Profile picture | png/jpeg/webp, ≤2MB, crop dialog (320², zoom 1–3×) → Storage `profile-media`. |
| Cover photo | png/jpeg/webp, ≤2MB, no crop. |
| Change password | ≥8 chars, upper+lower+number+special → `supabase.auth.updateUser`. |
| Save | `POST /api/profile/settings` (multipart); refreshes session. |

---

## Summary — actions per role

| Role | Modules | Write actions | Notable gaps / stubs |
|------|---------|---------------|----------------------|
| **Admin** | 6 (dashboard, users, roles, permissions, year, term) | user CRUD, role/perm/year/term CRUD | ✅ all source 🚧 STUBs (edit user, edit permission, view/edit year & term) finished in Laravel Stage F |
| **Educator** | 10 | full CRUD on sections/subjects/enrollment/assessments/quizzes/materials, grant retake, exports, chat | ✅ built in Laravel Stage G (ownership-gated). Chat + monitoring built request/response (live transport deferred to Stage I); monitoring view-only; quizzes have no reorder |
| **Student** | 7 + profile | take/submit quiz, autosave, retake, send chat, download materials, edit profile | ✅ built in Laravel Stage H. **H6 server-side grading invariant verified — `correct_answer` never reaches the client** (test-asserted). Chat live transport deferred to Stage I; cannot create chats; students can't edit own name (self-service lock) |

> **Migration note:** the 🚧 STUB rows are the only places where "what the UI shows" ≠ "what works." A faithful Laravel rewrite should either finish them or drop them deliberately — not silently reproduce a dead button.
