# Subject Rename Cascade Audit — 2026-07-10

## Scope

Educator "rename a subject" flow (`SubjectController::update()`) and its dependent tables:
`tbl_assessments`, `tbl_quizzes`, `tbl_scores`, `tbl_enrolled` — all of which carry
`subject_id` foreign keys constrained with `->cascadeOnDelete()` against `tbl_subjects.id`
(see `database/migrations/2026_06_29_000005_create_assessment_tables.php` and
`database/migrations/2026_06_29_000004_create_classroom_tables.php`).

## Why surrogate-key cascades are safe by construction — usually

The schema uses surrogate integer primary keys (`id`) everywhere, and every cascade rule in
the migrations is `ON DELETE CASCADE` — none are `ON UPDATE CASCADE` (MySQL/Eloquent
`cascadeOnDelete()` never touches `ON UPDATE`). That means a plain field-only rename —
`$model->update(['name' => $new])` — can never trigger a cascade, because the primary key
never changes and no foreign key is keyed off a renamable column. This holds for:

- **Section rename** (`SectionController::update()`, line 81): `$section->update([...])`
  mutates `section_name`/etc. in place. Safe.
- **Assessment rename** (`AssessmentController::update()`, line 146): `$assessment->update($data + [...])`
  mutates in place. Safe.

## Bug found: Subject "rename" was delete + recreate, not update

`SubjectController::update()` did not follow the same pattern. A subject group is one row per
enrolled section, keyed loosely by `(educator_id, subject_code, subject_name)`; the edit form
posts the group's row ids (`row_ids[]`) and the target section list (`section_ids[]`). The
pre-fix implementation was:

```php
Subject::where('educator_id', Auth::id())->whereIn('id', $data['row_ids'])->delete();
foreach ($data['section_ids'] as $sectionId) {
    Subject::create([...]);
}
```

This unconditionally deleted every row in the group and recreated fresh rows with **new
auto-increment ids** — even when the section list was unchanged and the edit was a pure
rename (e.g. `subject_code` typo fix). Because `tbl_assessments.subject_id`,
`tbl_quizzes.subject_id`, `tbl_scores.subject_id`, and `tbl_enrolled.subject_id` all
`cascadeOnDelete()` off `tbl_subjects.id`, the `delete()` call cascaded through every table and
destroyed every assessment, quiz, score, and enrollment tied to the old subject rows. The
recreated rows were empty shells with new ids — nothing was migrated forward. This was a live,
shipped data-loss bug: any educator renaming a subject (fixing a typo in `subject_code` or
`subject_name`, or toggling `is_active`) silently wiped that subject's entire history.

`SubjectController::store()` was and remains unaffected — it only ever creates new rows, never
deletes.

## Fix

`update()` now diffs the group's *current* rows (keyed by `sections_id`) against the *target*
`section_ids` from the form, instead of delete-then-recreate:

- **Section in both current and target (kept):** the existing `Subject` row is updated in place
  (`subject_code`/`subject_name`/`is_active` only) — its `id` never changes, so every FK
  pointing at it stays valid.
- **Section only in current, not target (removed from the group):** that row is deleted —
  this is an intentional removal, and correctly cascades its scores/assessments/quizzes/
  enrollments. This is the legitimate use of the cascade, not the bug.
- **Section only in target, not current (newly added to the group):** a fresh `Subject::create(...)`
  row is inserted, same as `store()`.

All three branches run inside the existing `DB::transaction()`. Ownership scoping is unchanged:
`$this->authorize('update', $subject)` still gates the route, and the row lookup is still
filtered by `where('educator_id', Auth::id())` before any row is read, updated, or deleted.

The current rows are keyed by `sections_id` (`Collection::keyBy()`) to build the diff, which
assumes every submitted `row_ids` entry has a distinct `sections_id`. That's guaranteed by the
group's own DB uniqueness constraints *within one code+name group*, but nothing stops a forged
request from supplying `row_ids` that span two of the caller's own groups sharing a section —
`keyBy()` would silently keep only the last row on a collision, stranding the other. `update()`
now rejects any request whose `row_ids` don't all resolve to distinct `sections_id` values with a
422 before doing anything else, rather than risk that silent strand.

## Tests added

`tests/Feature/Educator/EducatorFeaturesTest.php`:

- `test_subject_rename_with_unchanged_sections_preserves_ids_and_dependents` — creates a
  subject with an assessment, an enrollment, and a score attached; renames it with the same
  section list; asserts the `Subject` id is unchanged and the assessment/enrollment/score rows
  still exist with `subject_id` pointing at the *same* id (not just that some row with that id
  exists — that the original dependent rows were never touched).
- `test_subject_update_removing_a_section_still_cascades_that_sections_data` — a two-section
  group, one section dropped from the edit; asserts the kept row survives with its original id
  while the dropped section's row and its dependent assessment/enrollment are actually deleted.
  This proves the fix didn't also remove the legitimate cascade path for real removals.

## Best practice going forward

Any "edit" endpoint that reconciles a *set* against form input (add some, remove some, keep the
rest) — the subject-per-section group here being the clearest example, but the same shape
would apply to any future many-row-per-logical-entity feature — must diff old-vs-new state and
mutate/insert/delete only what actually changed. Never delete-and-recreate a whole set as a
shortcut for "apply the new state," when any of those rows are cascade-linked-to by data the
user doesn't intend to lose. Delete-then-recreate is only safe when the deleted rows have no
downstream FK dependents, or when destroying those dependents is the explicit, documented
intent of the action.
