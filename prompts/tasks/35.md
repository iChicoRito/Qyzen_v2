# Objective
## Qyzen Landing Page Specification

---

## Description
This document defines the structure, content, and behavior of the Qyzen landing page. The page serves as a single, scrolling introduction to the platform for signed-out visitors. Its purpose is to explain what Qyzen is, demonstrate its core value, and direct visitors to a single sign-in action. The design is deliberately minimal, using a monochromatic palette with a single decorative color accent in the hero section, and it adapts for light and dark modes as well as various screen sizes. The page respects accessibility preferences, such as reduced motion, and presents no invented claims, statistics, or testimonials. Signed-in users are automatically redirected to their respective dashboards and never see this page. For the reference look at docs\landing-page-overview.md

---

## Primary Objective
Direct a signed-out visitor to understand Qyzen’s purpose and sign in, while automatically redirecting already authenticated users to their role-specific dashboard.

---

## Secondary Objectives
- Communicate Qyzen’s core value proposition as an all-in-one classroom assessment platform.
- Visually demonstrate the platform’s live activity logging capability.
- Detail the six primary features in a clear, scannable grid.
- Define the three user roles and their responsibilities.
- Answer common prospective-user questions through an expandable FAQ section.

---

## Success Criteria
- The page renders as a single, scrollable view with no separate navigable pages.
- Authenticated visitors are redirected immediately upon landing and never see the page content.
- The sticky top bar remains fixed during scrolling and displays the Qyzen logo with a light/dark mode toggle.
- The animated activity log types out a looping sequence of realistic assessment events.
- The activity log respects the user's `prefers-reduced-motion` setting by displaying all text at once without animation.
- The FAQ section permits only one expanded answer at a time, toggling closed when another is opened.
- The layout adapts responsively: side-by-side sections stack vertically on smaller screens, and the feature grid reflows from three columns to fewer.

---

## Constraints
- The page uses color exclusively in the decorative background of the hero section.
- The only call to action is a "Sign in" button; there is no public sign-up, pricing, or purchasing flow.
- The content must describe only existing Qyzen features without fabricated data, testimonials, or metrics.

---

## Out of Scope
- Any form of public account registration.
- Displaying pricing information or purchase flows.

---

## Context & Dependencies
- Qyzen uses Supabase for its database, authentication, file storage, and real-time features.
- Google authentication is an optional sign-in method.
- The platform has three distinct user roles—administrator, educator, and student—each with its own post-authentication dashboard.

---

## Stakeholders
- Signed-out visitors (prospective users learning about the platform).
- Signed-in users (administrators, educators, students), who must be transparently redirected.

---

## Supporting Tasks

### Sticky Top Bar
- [Tag: Sequential] Render a fixed-position top bar with a frosted-glass blur effect.
- Display the Qyzen logo mark and wordmark on the left, with the logo asset swapping between dark and light variants based on the active mode.
- Provide a light/dark mode toggle button on the right.

### Hero Section
- Render a full-screen introductory section with a soft, blurred, multi-colored background mist.
- Display a typewriter-font label reading “Academic assessment platform.”
- Show the headline: “The classroom assessment platform built for live classes.”
- Include a descriptive paragraph explaining timed quizzes, instant scoring, centralized course files, and live monitoring.
- Place a single rounded “Sign in” button that navigates to the sign-in screen.

### Why Qyzen Section
- Display the label “Why Qyzen.”
- Show the headline: “Running a graded quiz usually means three tools and a spreadsheet.”
- Include a paragraph contrasting a fragmented workflow with Qyzen’s unified quiz creation, auto-grading, live observation, and score export.

### Live Activity Demo Section
- [Tag: Parallel] Present two side-by-side panels that stack vertically on smaller screens.
- Left panel: Include the label “What it looks like in use,” the headline “Every assessment, accounted for,” and a descriptive paragraph about comprehensive activity recording.
- Right panel: Render a simulated terminal window titled “qyzen — live activity” with three traffic-light dots. Animate lines of text that type out sequentially and loop, showing quiz open, enrollment count, student submission with score, auto-grade pass, integrity warning, assessment close, gradebook posting, and spreadsheet export.
- Apply distinct text colors for normal actions, background notes, amber cautions, and green successes.
- Detect the `prefers-reduced-motion` setting and display all log text instantly without animation when active.

### Feature Grid
- Render a grid of six feature cards, each with a line-drawn icon, title, and short description.
- Include: Timed assessments, Automatic scoring, Live monitoring, Integrity checks, Learning materials, and Class group chats.
- Arrange the grid responsively (three columns on wide screens, collapsing as needed).

### User Roles Section
- Display three columns, each headed by a bold line, representing the roles in order: Administrator, Educator, Student.
- Describe the Administrator role as managing institution setup, accounts, roles, terms, and access.
- Describe the Educator role as handling enrollment, assessment creation, scoring, materials, and chats.
- Describe the Student role as taking assessments, checking scores, accessing materials, and joining group chats.

### FAQ Section
- Render a heading “Good to know.”
- Provide four expandable question rows that reveal answers on click and collapse any previously open row.
- Questions: “How do I get an account?”, “What does Qyzen monitor during a quiz?”, “Can students retake an assessment?”, “Where is our data stored?”

### Authenticated User Redirection
- [Tag: Conditional] On page load, check authentication state. If the visitor has an active session, instantly redirect to the dashboard corresponding to their role without rendering the landing page.