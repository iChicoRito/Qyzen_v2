# The Qyzen Landing Page — What's On It, Explained Simply

This is a plain-language walk-through of the very first page a visitor sees when
they open Qyzen without being signed in. No jargon — just what's actually on the
screen, from top to bottom, and how it behaves.

## The big picture

The landing page is a single, scrolling page. There are no separate pages to
click through — everything lives on one long screen that a visitor reads by
scrolling down. Its whole job is to explain what Qyzen is and to get the visitor
to sign in.

One important behavior: if someone who is **already signed in** lands on this
page, they never actually see it. Qyzen quietly sends them straight to their own
dashboard (admin, educator, or student, depending on who they are). The landing
page is only ever shown to people who are signed out.

The look is deliberately clean and minimal — mostly black, white, and grey, with
plenty of empty space. Color shows up in exactly one spot (the top section), and
that's on purpose. It also respects light and dark mode, and it can be viewed
comfortably on a phone or a large screen.

---

## Top to bottom, section by section

### 1. The top bar (always visible)

A slim strip runs across the very top of the page and **stays stuck in place as
you scroll**, so it's always reachable. It has a faint frosted-glass blur behind
it. On it:

- **Left side:** the Qyzen logo (a small square mark) next to the word "Qyzen".
  The logo automatically swaps between a dark version and a light version so it
  always stays readable, whether the visitor is in light or dark mode.
- **Right side:** a small toggle button that switches the whole page between
  light mode and dark mode.

That's it — no big navigation menu, no links to other pages. Nothing to get lost
in.

### 2. The hero (the first thing you see)

This is the large opening section that fills the screen when the page loads. It's
the only place on the whole page that uses color: a soft, blurred cloud of blue,
purple, pink, and yellow blooms gently behind the text, like a colorful mist.
It's purely decorative and sits quietly in the background.

On top of that, in order:

- A small label in the corner-style typewriter font reading **"Academic
  assessment platform"** — a quick tag telling you what kind of product this is.
- A **big bold headline:** *"The classroom assessment platform built for live
  classes."*
- A short paragraph underneath explaining the promise in one breath: Qyzen runs
  timed quizzes, posts scores the instant they're submitted, keeps all course
  files in one place, and gives teachers a live view of the room — then invites
  you to sign in to pick up where your class left off.
- A single rounded **"Sign in"** button. This is the main call to action, and
  clicking it takes the visitor to the sign-in screen.

### 3. "Why Qyzen" — the problem it solves

The next section down explains *why* the product exists, in relatable terms.

- A small label: **"Why Qyzen"**.
- A headline: *"Running a graded quiz usually means three tools and a
  spreadsheet."*
- A paragraph making the point plainly: normally one app makes the quiz, another
  grades it, a third stores the files, and someone copies the marks in by hand.
  Qyzen does the whole loop in one place — write the quiz, let it grade and post
  results on its own, watch the class as it happens, and export everyone's scores
  when you're done. Less switching between apps, fewer marks entered by hand,
  nothing lost along the way.

### 4. "What it looks like in use" — the live activity demo

This section has two halves sitting side by side (they stack on top of each other
on a phone).

**On one side**, some explaining text:

- A small label: **"What it looks like in use"**.
- A headline: *"Every assessment, accounted for."*
- A paragraph saying Qyzen records each step of an assessment as it happens — a
  quiz opening, a student submitting, the automatic grade, an integrity flag, the
  final export — so nothing about a quiz is a mystery afterward.

**On the other side**, a little animated demo made to look like a computer
activity log or "terminal" window. It has a title bar with three small dots
(mimicking a real app window) labeled **"qyzen — live activity"**. Inside, lines
of text appear one after another, typing themselves out and then looping back to
the start, like a live feed. A blinking cursor sits at the newest line. The lines
walk through a realistic sequence of events:

- A quiz called "Algebra — Quiz 3" being opened
- A note that 18 students are enrolled with a 20-minute time limit
- A student submitting and getting 12 out of 15 correct
- The quiz being auto-graded as a PASS (shown with a green checkmark)
- An integrity warning flagged for switching tabs (shown in amber as a caution)
- The assessment closing once all attempts are in
- Results being posted to the gradebook (another green checkmark)
- 18 scores being exported to a spreadsheet file

The different kinds of lines are color-coded — normal actions, quiet background
notes, amber cautions, and green successes — so it reads at a glance. If the
visitor's device is set to reduce on-screen motion (an accessibility setting), the
whole log simply appears all at once instead of animating, so it never causes
discomfort.

### 5. "What it does" — the feature grid

A tidy grid of six boxes, each describing one real thing Qyzen can do. Every box
has a small line-drawn icon, a short title, and a sentence or two. The six:

1. **Timed assessments** — Set a time limit, optional hints, and whether answers
   can be reviewed or retaken. Students take the quiz and it grades the moment
   they submit.
2. **Automatic scoring** — Results appear with a pass-or-fail status as soon as a
   student submits, and a whole class can be exported to a spreadsheet in one
   click.
3. **Live monitoring** — Teachers can watch a class take a quiz in real time:
   who's online, who's answering, and who has finished.
4. **Integrity checks** — During a quiz, the page flags tab-switching, copy and
   paste, and leaving the window, and counts those as warnings against the
   attempt.
5. **Learning materials** — Upload course files once and keep them in one place
   for every enrolled student to open and download.
6. **Class group chats** — Each subject gets its own group chat so teachers and
   students can talk without leaving Qyzen.

The boxes sit together in a clean grid with thin dividing lines between them. On a
wide screen they line up three across; on smaller screens they rearrange to fit.

### 6. "Three roles, one platform" — who uses it

A section explaining that Qyzen has three kinds of users, shown as three short
columns side by side (each column marked with a bold line across its top). They're
listed in the natural order things happen:

1. **Administrator** — Sets up the institution: creates accounts, assigns roles,
   and manages academic terms and access.
2. **Educator** — Runs the classroom: handles enrollment, builds assessments,
   posts scores and materials, and leads class chats.
3. **Student** — Does the work: takes assessments, checks scores, opens materials,
   and joins class group chats.

### 7. "Questions" — the FAQ

The final section is a set of common questions. Each question is a row you can
**click to expand**, revealing its answer underneath; clicking again collapses it.
Only one is open at a time. The heading over them reads **"Good to know."** The
four questions and answers:

- **How do I get an account?** — Accounts are created by an administrator at your
  institution. There's no public sign-up; once your account exists you sign in
  with your email and password or with Google.
- **What does Qyzen monitor during a quiz?** — While a quiz is open, the page
  records your progress and flags actions that often signal cheating: switching
  tabs, leaving the window, and copy or paste. Your teacher sees these as counted
  warnings. It's used only for academic integrity, nothing else.
- **Can students retake an assessment?** — Only when the teacher allows it.
  Retakes, hints, and reviewing answers are each turned on per quiz, so a quiz
  behaves exactly the way the teacher set it up.
- **Where is our data stored?** — Qyzen runs on a service called Supabase for its
  database, sign-in, file storage, and live features. Google is involved only if
  you choose to sign in with Google. There's no third-party analytics or ad
  tracking.

---

## How the page behaves overall

- **It's honest.** Everything described on the page maps to a feature Qyzen
  actually has. There are no invented statistics, fake testimonials, or made-up
  numbers.
- **One clear action.** The only thing the page asks a visitor to do is sign in.
  There's no "buy now", no pricing, no sign-up form — because accounts come from
  an administrator, not from the public.
- **Light and dark friendly.** The whole page, including the logo, adapts to
  whichever mode the visitor prefers.
- **Works on any screen.** Sections that sit side by side on a computer stack
  neatly on a phone.
- **Gentle on accessibility.** The animated activity log respects the "reduce
  motion" setting and simply shows everything at once for anyone who's asked their
  device to limit animation.

## In one sentence

The Qyzen landing page is a single, calm, scrolling page that tells a signed-out
visitor what Qyzen is — an all-in-one platform for running, grading, watching, and
storing classroom quizzes — shows a live-looking demo of it working, lists its
features and its three types of users, answers a few common questions, and points
everyone toward a single "Sign in" button.