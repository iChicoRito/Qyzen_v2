# Terms of Service & Privacy Policy on the Sign-In Page

A plain-language write-up of the legal documents shown on Qyzen's sign-in area —
both **how they're presented** (the layout) and their **full content**.

Source: `src/app/(auth)/auth/components/legal-dialogs.tsx`
**Last updated:** June 23, 2026

---

## How they're shown (the layout)

**Where they live.** On the sign-in area there's a small line of grey text at the
bottom that reads: *"By clicking continue, you agree to our Terms of Service and
Privacy Policy."* The words **"Terms of Service"** and **"Privacy Policy"** are
underlined links. Neither one sends you to a separate page — clicking a link opens
the document in a **pop-up window (a modal)** right on top of the sign-in screen.

**It adapts to your screen size.** The same content is presented two different
ways depending on the device:

- **On a computer / wide screen:** it appears as a **centered box floating in the
  middle of the screen**, with the page behind it dimmed and gently blurred.
  There's an "×" close button in the corner. The box won't grow taller than about
  90% of the screen — if the text is longer than that, the box stays put and the
  text area inside scrolls.
- **On a phone / narrow screen:** it appears instead as a **panel that slides up
  from the bottom of the screen** (a drawer), covering up to about 85% of the
  screen height, again with the content scrolling inside.

**The inside of the pop-up has the same three-part layout in both cases:**

1. **A fixed header at the top** — the title ("Terms of Service" or "Privacy
   Policy") with a smaller line underneath showing *"Last updated June 23, 2026."*
   This part stays in place.
2. **A scrollable middle** — the actual document text (all the sections below).
   This is the only part that scrolls, so long documents stay readable without
   moving the header or the button.
3. **A fixed footer at the bottom** — a single **"I understand"** button,
   separated from the text by a thin line. Clicking it (or the "×", or tapping
   outside the box) closes the pop-up and returns you to the sign-in screen.

**Two separate pop-ups.** Terms and Privacy are independent — opening one shows
only that document; you close it and open the other to read the second. You're
never taken away from the sign-in page to read either.

---

## Terms of Service

Qyzen is an academic assessment and classroom management platform operated by
**Mr. Mark Adrianne Salunga** ("we," "us"). These terms cover your use of the
platform. By signing in, you agree to them.

**1. Who can use Qyzen**
Qyzen is not open to the public. Accounts are created and assigned by an
administrator at your institution. There is no self-service registration. When an
administrator adds you, you are given one or more roles — administrator, educator,
or student — that determine what you can see and do. You may only use the account
assigned to you, and you are responsible for keeping your sign-in credentials
confidential.

**2. Signing in**
You can sign in with the email and password set for your account, or with a Google
account whose email matches an account already registered in the system. If you
sign in with Google and your email is not registered, access is denied.
Deactivated accounts cannot sign in.

**3. Acceptable use**
You agree not to:
- access or attempt to access data or areas outside the role assigned to you;
- share your credentials or let another person use your account;
- upload material you do not have the right to share, or that is unlawful or harmful;
- interfere with the platform's operation, including its assessment-integrity features.

**4. Assessments and academic integrity**
Educators create timed assessments. While you are taking an assessment, the
platform monitors for activity that may indicate cheating and records it for your
educator. This is described in the Privacy Policy under "How assessments are
monitored." Retakes, hints, and review of answers are available only when the
educator enables them. Repeated integrity warnings during an assessment may affect
your ability to continue or your final result, at your educator's and
institution's discretion.

**5. Content you submit**
Quiz answers, chat messages, uploaded learning materials, and profile information
you submit remain associated with your account and your institution. Educators and
administrators at your institution can view the academic content you submit as part
of running their classes.

**6. Availability**
We aim to keep Qyzen available but do not guarantee uninterrupted access. The
platform relies on third-party infrastructure (see the Privacy Policy) and may be
unavailable for maintenance, updates, or reasons outside our control.

**7. Account suspension and changes**
Your institution may deactivate or remove your account at any time. We may update
these terms; continued use after a change means you accept the updated terms. The
"Last updated" date above reflects the current version.

**8. Contact**
Questions about these terms can be directed to your institution's administrator or
to **markadrianne.salunga@ncst.edu.ph**.

---

## Privacy Policy

This policy explains what Qyzen collects, why, and who can see it. Qyzen is
operated by **Mr. Mark Adrianne Salunga**, who decides how the platform is used for
your classes.

**What we collect**
When your account is set up and as you use the platform, we hold:
- **Account details:** your email, first name, last name, and the role(s) assigned to you.
- **Profile media:** an optional profile picture and cover photo, if you add them.
- **Academic records:** your class enrollments, assessment scores, the answers you submit to quiz questions, retake history, and notifications.
- **Messages:** messages you send in group chats, along with the time sent.
- **Uploaded files:** learning materials uploaded by educators (file name, type, and size are recorded).

**How assessments are monitored**
Qyzen is built for graded assessments, so while you are signed in — and especially
while taking a quiz — it records information to support academic integrity:
- your online status, the page you are currently on, and a periodic "last seen" timestamp (updated roughly every 25 seconds), so educators can see live progress;
- during a quiz, integrity events such as switching tabs, leaving the quiz window, moving your mouse off the page, copy and paste, right-click, opening developer tools, taking a screenshot, or shrinking the window into a split-screen.

These events are shared with the educator running the assessment and counted as
warnings. They are used for academic integrity, not for any other purpose.

**Why we use it**
We use this data to give you access at the right role, run and grade assessments,
let you and your educators communicate, share learning materials, and maintain the
integrity of graded work. We do not sell your data or use it for advertising.

**Who can see your data**
- **You** can see your own profile, scores, materials, and messages.
- **Educators** can see the academic data, assessment activity, and chat messages for students in their classes.
- **Administrators** at your institution can manage accounts, roles, and system-wide academic data.

Profile pictures and cover photos are stored in a public storage location, which
means the image file can be reached by anyone who has its direct link. Do not use a
profile or cover image you would not want to be publicly accessible. Learning
materials are stored in an access-controlled location that requires sign-in.

**Service providers**
We rely on two third parties to run the platform:
- **Supabase** hosts our database, authentication, file storage, and real-time features. Your data is stored on Supabase infrastructure.
- **Google** is used only if you choose to sign in with Google, which shares your Google account email with us to match it to your account.

We do not use third-party analytics or ad-tracking services.

**Retention and deletion**
Your data is kept for as long as your account is active and your institution needs
it for academic recordkeeping. When an account is removed, related records are
deleted or marked as deleted. To request access to, correction of, or deletion of
your data, contact your institution's administrator or
**markadrianne.salunga@ncst.edu.ph**.

**Changes**
We may update this policy. The "Last updated" date above reflects the current
version.