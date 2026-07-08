# Question Bank + Randomized Quizzes — What Changed

Date: 2026-07-08

## The problem this solves

Before this change, every question an educator wrote was locked to a single
quiz. If the same question should appear in two different quizzes, the
educator had to type it out twice — there was no way to reuse a question.
And every student who took a given quiz saw the exact same questions, in the
same set, every time — which made it easy for students to share answers
with classmates who hadn't taken it yet.

## What changed

Educators now build a **Question Bank**: a library of questions written
once per subject, that can be reused across as many quizzes as needed.

For each quiz, the educator picks two things:
1. **Which bank questions are allowed to be used** for this quiz.
2. **How many questions** each student should get (for example, "draw 5
   questions").

When a student starts the quiz, the app randomly picks that many questions
out of the allowed set — just for them. Two students taking the same quiz
at the same time will very likely get a different mix of questions, even
though both quizzes are drawn from the same pool and cover the same
material at the same difficulty. That randomization is locked in the moment
the student starts — refreshing the page, or coming back later to finish,
always shows them the exact same questions they started with. Only
resubmitting from scratch (a retake) gives them a new random draw.

## Before vs. After, in practice

| Situation | Before | After |
|---|---|---|
| Writing a question for two quizzes | Type it twice, as two separate questions | Write it once, then simply allow it in both quizzes |
| What each student sees | Every student gets the identical set of questions | Each student gets a random, same-size subset of the allowed questions |
| Deleting a quiz | Its questions were deleted along with it | Its questions stay in the bank, ready to be reused elsewhere |
| Managing questions | Questions were listed grouped under each quiz | Questions now live in one central "Question Bank" list, searchable and filterable by subject/type/assessment/batch |
| Setting up a quiz's questions | Add/remove questions directly on the quiz | A new "Question Pool" screen lets the educator tick which bank questions are allowed, and set how many to draw |
| Notifications to students | Sent whenever a question was added/edited/removed | Sent when the quiz's question pool is set up or changed (the point where it actually affects students) |

## Finding the right question, quickly

Once a subject has 50-100+ questions in the bank, just having a list isn't
enough — an educator needs to quickly tell questions apart. A few things
were added specifically for this:

- **"Used In" column** on the Question Bank list shows which quiz(zes) each
  question is currently allowed in, so it's obvious at a glance whether a
  question has been assigned anywhere yet.
- **Filters** on the Question Bank list — narrow the list down by subject,
  question type, which quiz it's used in, or which "batch" it came from.
- **Batch labels** — every question is automatically tagged with where it
  came from: either "Manual" plus the date/time it was typed in, or
  "Upload: filename" plus the date/time it was bulk-uploaded. No typing
  required; it's stamped on automatically the moment the question is
  created. This makes it easy to answer "which questions did I add in that
  batch of 50 I uploaded last week?"
- **Tagging a question to a quiz right when you create it** — the Add
  Question form (and the Bulk Upload form) now has an optional "Also Add To
  These Assessments" picker. Pick one or more quizzes there and the new
  question is immediately made eligible for them — no separate trip to each
  quiz's Question Pool screen required.
- On the **Question Pool screen itself** (where an educator ticks which
  bank questions are allowed for one specific quiz), the same kind of
  filtering was added: a live search box, a type filter, and a batch
  filter, all of which narrow the checkbox list together. Each question
  also shows whether it's "Also used in" another quiz, so it's easy to
  decide whether to reuse it or pick something else.
- **Select All / Select None** buttons on the Question Pool screen respect
  whatever the current search/filter has narrowed the list down to — so
  narrowing to "just the questions from this batch" and clicking "Select
  All" only selects that narrowed set, not the whole bank.

## Why this matters

- **Less repetitive work** for educators — write a question once, reuse it anywhere.
- **Harder to cheat** — since each student's question mix is randomized and pinned to their own attempt, sharing answers with someone else who took the same quiz is far less useful.
- **Fair and consistent** — everyone still answers the same number of questions, on the same topics, to the same difficulty; only which specific questions they see differs.
- **Manageable at scale** — with the "Used In" tracking, batch labels, and filters, having 50-100+ questions in one subject's bank stays organized instead of becoming an undifferentiated wall of text.

## Bugs found and fixed along the way

**1. A "Best Score" display bug.** While testing, we found that the "Best
Score" shown on a student's results page could occasionally display a
nonsensical number (like "200%"). This happened because the app could
compare scores from two attempts that had a different total number of
questions (which is now possible, since the number of questions per
attempt can be configured) without accounting for that difference. Fixed
— "Best Score" is now always compared and shown correctly, as a genuine
percentage.

**2. A "greyed-out page" bug after visiting the Question Pool screen.**
Clicking "Question Pool" from the Assessments list, then pressing the
browser's Back button, could leave the Assessments page looking disabled
and greyed-out. The cause: the Assessments list's table has behind-the-
scenes code meant only to smoothly reload the table when clicking a
pagination link (like "Next page") — but it was accidentally also
grabbing every other link in the table, including "Question Pool". That
caused the table to dim itself as if it were reloading, then the browser
navigated away before it ever got to undo that dimming — and the browser's
back button restored that dimmed, stuck-looking snapshot. Fixed by making
that behind-the-scenes code only ever apply to actual pagination links, so
row actions like "Question Pool" navigate normally.

## How this was tested before shipping

- Set up a question bank and a quiz pool as an educator, and confirmed it worked end-to-end in the browser.
- Confirmed a validation message appears if an educator tries to draw more questions than are allowed in the pool.
- Confirmed that once a student starts a quiz, refreshing the page never changes their questions.
- Confirmed that retaking a quiz gives a fresh, independent random set, without disturbing the record of the earlier attempt.
- Confirmed batch labels are stamped correctly and distinctly for a manually-added question vs. a bulk-uploaded file, and that the new filters (subject/type/assessment/batch) narrow the list correctly, including in combination with each other.
- Confirmed clicking "Question Pool" then pressing Back no longer leaves the Assessments page greyed out.
- Ran the app's full automated test suite (172 checks) — all passing.
