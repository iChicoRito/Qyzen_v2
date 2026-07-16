# Special Access — duration investigation

**Date:** 2026-07-16
**Task:** `prompts/tasks-assignment/24.md`, secondary objective
**Status:** findings confirmed from code; configurable duration implemented; one open question for the requester

---

## The question

> How long does a student retain special access after an educator grants it — 24 hours, 1 hour, only on the day it was granted?

## The answer: none of those. There was no time limit at all.

Special access was **use-bounded, not time-bounded**. Before this task, `tbl_student_assessment_access` had no
`expires_at`, `granted_at`, `valid_until`, or `duration` column, and there was no date arithmetic anywhere in
the special-access code path. The columns were exactly:

```
id, educator_id, student_id, assessment_id, is_active, created_at, updated_at
```

A grant issued today would still work a year later, as long as the student never used it.

### What actually bounded a grant

1. **It only applies after the assessment window closes.** `AssessmentAvailabilityService::summarize()` requires
   `! $windowOpen && $scheduleValid && $now->gte($end)`. During the window the grant is inert — it is strictly a
   post-deadline mechanism, for students who missed the quiz or need an extra retake.
2. **It is worth exactly one attempt.** `'remaining' => max($remaining, 1)`.
3. **It is consumed on submit.** `QuizGradingService` flips `is_active => false` once a submitted score exists.
   A second check compares `submitted_at >= updated_at`, so an attempt made after the grant also disarms it.
4. **Re-saving a grant re-arms it.** `updateOrCreate` bumps `updated_at`, which hands the student another attempt.
   That is how educators intentionally issue a second chance.
5. **Exemption beats special access.** The exemption gate returns before the access check, so an exempted student
   is never takeable regardless of any grant.

### What that meant in practice

A student granted access for a missed midterm could sit on it indefinitely and take the quiz weeks later, long
after the material was covered and the answers had circulated. That is the risk the question was really about.
Nothing in `docs/` or `prompts/` ever stated an intended duration rule — the behavior was emergent, not designed.

---

## What changed (Task 24)

Duration is now **chosen by the educator per grant**, in the Manage Special Access modal.

- New nullable column `tbl_student_assessment_access.expires_at`
  (`2026_07_16_000001_add_expires_at_to_tbl_student_assessment_access_table.php`).
- Options: **1 / 6 / 24 / 48 / 72 hours**, plus **No expiry — until used**. Default is **24 hours**.
- The countdown starts at the moment of the save, and applies to the students granted access on that save.
  Re-saving a grant resets the clock along with the attempt — consistent with the existing re-arm behavior.
- `AssessmentAvailabilityService` treats a grant past its `expires_at` as dead, used or not.
- The student's notification and chat message now name the deadline:
  *"You have been granted special access to assessment MIDTERM. This access expires on Jul 17, 2026 6:58 AM."*
- The educator sees each student's deadline under their Special Access badge (`Until Jul 17, 7:05 AM`).

### Backward compatibility

`expires_at` is nullable and null means *never expires* — precisely the old behavior. The 265 grants already in
the database were left untouched and keep working exactly as before. No backfill was performed, deliberately:
retroactively expiring grants students were already promised would revoke access without warning.

---

## Open question for the requester

**Do you want "end of day" / calendar-day semantics rather than fixed hour offsets?**

This was deliberately left out, because it is not a UI decision — it is a timezone decision the app has not yet made:

- `APP_TIMEZONE=UTC`, and every view renders raw UTC with no conversion to local time
  (e.g. `student/dashboard.blade.php`, `educator/enrollment/_import-timeline.blade.php`).
- So an "end of day" rule implemented today would expire access at **8:00 AM Manila time**, not midnight — actively
  misleading to both educator and student.

Fixed hour offsets have no such trap: "24 hours from now" means the same thing in every timezone.

If calendar-day semantics are wanted ("access expires when today ends"), the app needs a display-timezone decision
first — either a global `Asia/Manila` display timezone, or a per-user preference. That is a larger change touching
every date render in the app, and should be its own task.

**Secondary question:** is 24 hours the right default, or should it be shorter (1–6 hours) to keep a missed-quiz
grant close to the moment the educator agreed to it?
