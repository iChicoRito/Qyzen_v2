# Objective
## Assessment-Taking Experience Revisions

---

## Description
Refine the student assessment interface by simplifying the layout, enforcing a sequential slideshow-only navigation, and enhancing the timer and progress indicators. Additionally, address critical security vulnerabilities by obfuscating identifiable resource IDs in URLs and preventing re-access to completed quizzes, ensuring that students cannot bypass or retake an assessment after submission.

---

## Primary Objective
Implement a cleaner, slideshow-only assessment interface with a fixed, sticky timer and progress bar, while securing URL parameters and preventing re-entry to finished quizzes.

---

## Secondary Objectives
- Eliminate the list view and enforce exclusive slideshow navigation for a more focused question-by-question experience.
- Remove all assessment metadata details (quiz, subject, section, etc.) from the header to achieve a cleaner and more minimal UI.
- Position the timer as a fixed, sticky element at the top of the page, functioning as a navigation bar, and integrate a progress bar for enhanced user orientation.
- Hide the radio-button (circular) indicators from multiple-choice options, displaying only the highlighted selection to indicate the chosen answer.
- Encrypt/obfuscate numeric resource IDs in URLs (e.g., `/student/take-quiz/1` and `/student/scores/1`) to prevent direct enumeration and unauthorized access.
- Enforce a strict session lock so that once a student finishes a quiz, they cannot navigate back to the quiz page or resubmit/modify their attempt.

---

## Success Criteria
- The assessment page presents questions exclusively in slideshow mode, with no list-view toggle available.
- The header contains no visible quiz metadata (subject, section, schedule, educator, etc.).
- The timer is fixed at the top of the screen, remains visible while scrolling, and includes a progress bar showing completion status.
- Multiple-choice options appear without radio-button circles; the selected answer is distinguished solely by a visual highlight.
- All quiz and score URLs use non-sequential, obfuscated identifiers that do not expose numeric IDs.
- Students who have submitted a completed assessment are unable to revisit the quiz page or resubmit answers via browser back/forward or direct URL entry.

---

## Context & Dependencies
- The existing assessment-taking system currently supports both list and slideshow views, and displays detailed quiz metadata in the header.
- Current URL patterns follow predictable numeric IDs for both assessment attempts and score pages.
- The system relies on browser navigation controls; existing behavior allows students to use the back button to return to a completed quiz.

---

## Supporting Tasks

### UI Simplification & Navigation
- [Sequential] Remove the layout-toggle control and associated list-view rendering logic, retaining only the slideshow component.
- [Sequential] Strip the header of all metadata elements (quiz code, subject, section, term, educator, schedule, instructions) while preserving only essential controls (e.g., save progress, view toggle if any).
- [Sequential] Redesign the timer component to be fixed at the top of the viewport, styled as a persistent navigation bar, and add a progress bar (e.g., question X of Y) next to or within it.

### Choice Presentation
- [Sequential] Modify the multiple-choice rendering to hide the native radio input or custom circle indicator, using only background color or border highlighting to denote the selected option.

### Security Hardening
- [Sequential] Replace numeric IDs in all assessment-related URLs (take-quiz, scores, etc.) with encrypted, hashed, or UUID-based identifiers that cannot be sequentially guessed or directly manipulated.
- [Sequential] Implement a server-side check to invalidate the session or attempt token upon quiz submission, blocking any subsequent GET or POST requests to the quiz endpoint for that specific attempt.
- [Sequential] Configure the browser history or use a redirect-after-submit pattern so that the back button does not reload a completed quiz; instead, redirect to a "quiz finished" page or the results screen.

---

## Detailed Breakdown

### Slideshow-Only Navigation
Transition the assessment experience to a single-question-at-a-time format. Remove all code and UI elements associated with the list view, including any toggle controls. The student advances using Previous/Next buttons, with the Submit button appearing only on the final question. This change is intended to create a more linear and distraction-free workflow.

### Minimal Header and Timer Placement
Eliminate all quiz metadata (subject, section, educator name, schedule, etc.) from the top header to reduce cognitive load and visual clutter. The only persistent element in the header should be the timer, which must be fixed to the top of the screen (sticky) and remain visible regardless of scrolling. The timer should be styled to resemble a navigation bar, and it must incorporate a progress indicator (e.g., "Question 3 of 10" or a horizontal fill bar) to give students immediate context on their overall completion.

### Choice UI Refinement
For multiple-choice questions, suppress the standard radio-button control entirely. Rely solely on a distinct visual highlight (e.g., background color change, border emphasis) to indicate the student's selection. This creates a cleaner, more modern answer-selection experience.

### URL Obfuscation and Access Control
Replace all predictable numeric identifiers in URLs with a secure, opaque representation (e.g., using hashids, UUIDs, or encrypted parameters). This prevents students from guessing or enumerating other assessments or score records. Additionally, implement a post-submission lock: once an assessment is finalized, the server must reject any subsequent request to load that attempt's quiz page. The student should be redirected to the results page or a "submitted" state, and any attempt to navigate back via the browser should not grant access to the quiz content again.