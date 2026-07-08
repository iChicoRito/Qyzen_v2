# Objective
## Student Scores Page Implementation Guide

---

## Description
Build a comprehensive Scores page that serves as the student's complete historical record of all finished assessment attempts. The page must display a summary overview through four at-a-glance cards, present a sortable and filterable table listing every submitted attempt, and provide a read-only review window that opens any attempt for detailed question-by-question review. The implementation must enforce server-side security controls that restrict access to correct answers based on assessment review settings and per-question performance. The page functions as the student's long-term record repository, distinct from the post-submission results screen, and must remain usable across both desktop and mobile devices.

---

## Primary Objective
Build a functional Scores page that loads all submitted attempts belonging to the signed-in student, displays them in a sortable and filterable table with summary cards, and provides a read-only review window for examining any attempt's full details without retake or navigation actions.

---

## Secondary Objectives
- Display four summary cards showing Total Scores, Passed, Failed, and Average percentage computed across all loaded attempts
- Present a scores table with columns for Assessment, Subject, Academic Term, Score with Best score beneath, Attempts count, Percentage, Status badge, Submitted At, and Actions menu
- Provide filtering controls with Assessment, Subject, Academic Term, and Status dropdowns, plus a search box and Reset Filters control
- Enable sortable column headers and pagination for long lists
- Build a View Score pop-up window that reuses the post-submission results screen styling for review only
- Include a column show/hide control for table customization

---

## Success Criteria
- The page loads and displays only submitted attempts belonging to the signed-in student, ordered newest first
- The summary cards correctly compute Total Scores as the number of attempts, Passed and Failed as counts of attempts meeting or failing the passing requirement, and Average as total correct answers divided by total questions across all attempts
- The scores table displays each row with all required columns and shows the best score and attempt count beneath the score for each assessment
- Filtering, searching, sorting, and pagination work correctly and combine to narrow the list as expected
- The Reset Filters control clears all active filters and search and is enabled only when filters are applied
- The View Score window opens with the attempt header, score dial with correct/incorrect counts, assessment summary with all required fields, attempt history list, and question-by-question review
- Correct answers appear in the review window only when the educator enabled review or when the student answered the question correctly
- All ownership and submission status checks are performed on the server
- The scores table, summary cards, and attempt figures consistently count attempts rather than unique assessments
- The page and review window remain usable on narrow screens

---

## Constraints
- The server must be the sole source of truth for which attempts a student may see and for answer-key visibility rules
- Correct answers must never reach the screen except where the review setting explicitly allows or the student answered correctly
- The browser must only display data the server sends and must not enforce security rules independently
- The review window must not include retake actions or attempt-switching navigation
- Entries are per attempt, not per unique assessment, and this counting rule must remain consistent across summary cards, table, and attempt figures

---

## Out of Scope
- The assessment-taking experience and submission flow
- The post-submission results screen that appears immediately after submitting
- Building the educator-facing administration interface
- Implementing the server-side APIs or data models
- Database schema design

---

## Context & Dependencies
- The page opens when the student selects Scores from their menu
- The page loads every finished attempt belonging to the student, with one entry per submitted attempt
- The page depends on server-provided data including score and percentage for each attempt, assessment details, best score and attempt counts per assessment, and questions with student answers and answer keys
- The View Score window reuses the same score dial and review styling as the post-submission results screen
- The page is distinct from the results screen which focuses on one attempt immediately after submission
- Filters dropdown choices are built dynamically from the student's own rows rather than hard-coded

---

## Stated Assumptions
- The starting point occurs when the student opens the Scores page from their menu
- Per-assessment figures such as best score and attempts used will repeat across rows of the same assessment, and this is expected behavior

---

## Stakeholders
- Students who view their complete assessment history and review past attempts
- Educators who own assessments and control review settings
- Developers who implement the page according to the blueprint
- Product or design stakeholders who defined the page layout and functionality

---

## Supporting Tasks

### Phase 1: Loading the Student's Scores
- [Sequential] Fetch every finished attempt belonging to the signed-in student
- [Sequential] Filter to include only submitted attempts and skip in-progress ones
- [Sequential] Return one entry per submitted attempt, ordered newest first
- [Sequential] Include for each entry the score and percentage, assessment details, best score and attempt counts, and questions with student answers and answer keys
- [Sequential] Perform ownership and submission status checks on the server

### Phase 2: Page Frame and Summary Cards
- [Sequential] Display a "Scores" heading with a short explanatory line
- [Sequential] Show four summary cards computed across all loaded attempts: Total Scores as the number of attempts, Passed as how many passed, Failed as how many failed, and Average as total correct answers divided by total questions across all attempts
- [Conditional] Show 0% for Average if there are no questions at all to prevent division by zero
- [Sequential] Ensure summary cards count attempts, not unique assessments

### Phase 3: Scores Table Columns
- [Sequential] Display Assessment column showing the assessment code
- [Sequential] Display Subject column showing the subject name with the section beneath it
- [Sequential] Display Academic Term column
- [Sequential] Display Score column showing correct out of total with the best score for that assessment shown beneath
- [Sequential] Display Attempts column showing how many times that assessment was submitted
- [Sequential] Display Percentage column
- [Sequential] Display Status column with a colored PASSED/FAILED badge
- [Sequential] Display Submitted At column
- [Sequential] Display Actions column with a menu offering View Score
- [Sequential] Keep the table readable on narrow screens with scrolling or wrapping

### Phase 4: Filtering, Searching, Sorting, and Paging
- [Sequential] Build four dropdown filters for Assessment, Subject, Academic Term, and Status with an "All" option for each
- [Sequential] Build each dropdown's choices from the student's own rows except Status which is simply Passed/Failed
- [Sequential] Provide a search box that matches across assessment code, subject, section, and term
- [Sequential] Provide a Reset Filters control that clears all filters and search, enabled only when something is applied
- [Sequential] Enable sortable column headers
- [Sequential] Implement pagination for long lists
- [Sequential] Combine filters and search so they narrow together, with Reset returning the list to its full unfiltered state
- [Sequential] Include a column show/hide control

### Phase 5: View Score Review Window
- [Sequential] Build a read-only pop-up showing the full breakdown of one attempt
- [Sequential] Display a header with the assessment code and PASSED/FAILED badge, with subject, section, and term beneath
- [Sequential] Show a top section with two columns on wider screens and stacking on narrow screens
- [Sequential] On the left of the top section, display a score dial showing the percentage with correct and incorrect counts
- [Sequential] On the right of the top section, display an assessment summary with status badge, educator name, Score, Percentage, Best Score, Retakes Remaining, Submitted At, Academic Term, Time Limit, Schedule, Attempts Used, and whether retakes are allowed
- [Sequential] Display Attempt History showing every submitted attempt for that assessment with its number, Highest Score or Attempt badge, PASSED/FAILED badge, score, percentage, and submitted time
- [Sequential] Display question-by-question review with each question showing a Correct/Incorrect badge and its text
- [Sequential] For multiple-choice questions, list every option with color-coding
- [Conditional] Highlight the student's answer in green when correct, highlight the correct option in green only when review is allowed or the student got it right, highlight the student's answer in red when wrong, and leave other options plain
- [Sequential] For identification questions, show the correct answer only when review is allowed or the answer was right
- [Sequential] For identification questions, show the student's own answer marked Correct or Incorrect
- [Conditional] For identification questions with blank answers, display "No answer submitted"
- [Sequential] Match a student's answer whether stored as an option's letter or its text when recognizing their selection
- [Sequential] Include a Close button to dismiss the window
- [Sequential] Exclude retake or attempt-switching actions from this window

---

## Detailed Breakdown

### Server-Side Security Enforcement
The server determines what a student may see and hands over only permitted data. The browser displays only what it is given without enforcing security independently. The server performs ownership verification to confirm attempts belong to the viewing student and checks submission status to exclude in-progress attempts. The server enforces answer-key visibility rules by including correct answers only when the review setting allows or when the student answered correctly. The browser does not filter someone's records or enforce access rules on its own.

### Summary Cards Computation
The four summary cards are computed across all loaded attempts. Total Scores is the number of submitted attempts. Passed counts how many of those attempts passed. Failed counts how many did not pass. Average is computed as total correct answers across all attempts divided by total questions across all attempts, expressed as a percentage. If there are no questions at all, Average displays 0% to prevent division by zero. The wording and math make it clear that these totals count attempts, not unique assessments.

### Scores Table Display Rules
The main table lists one row per finished attempt, ordered newest first. The Assessment column displays the assessment code. The Subject column shows the subject name with the section beneath it. The Academic Term column displays the term. The Score column shows correct out of total with the best score for that assessment shown beneath it. The Attempts column shows how many times that assessment was submitted. The Percentage column displays the attempt's result as a percentage. The Status column shows a colored PASSED or FAILED badge. The Submitted At column shows when the attempt was turned in. The Actions column provides a per-row menu offering View Score. The table remains readable on narrow screens through scrolling or wrapping.

### Filtering, Searching, Sorting, and Paging Controls
Four dropdown filters provide options for Assessment, Subject, Academic Term, and Status, each with an "All" option. The dropdown choices are built from the student's own rows rather than hard-coded, except Status which is simply Passed or Failed. The search box matches across assessment code, subject, section, and term. The Reset Filters control clears all filters and search and is enabled only when something is applied. Column headers are sortable, and pagination handles long lists. Filters and search combine to narrow together, and Reset returns the list to its full, unfiltered state. A column show/hide control allows customization of the table view.

#### Edge Cases
- The Submitted At timestamp may be missing, in which case the column displays "Not submitted"
- When matching a student's answer, the system handles whether it is stored as an option's letter or its text
- For blank identification questions, the review displays "No answer submitted" rather than showing an empty field
- When review is off, the system must not send the answer key for missed questions to the screen, regardless of styling
- The Average calculation must handle cases with no questions at all by showing 0%

### View Score Review Window Structure
The read-only pop-up displays the full breakdown of one attempt. The header shows the assessment code with a PASSED/FAILED badge, and subject, section, and term beneath. The top section displays two columns on wider screens and stacks on narrow screens. On the left, a score dial shows the percentage with correct and incorrect counts derived from the questions. On the right, an Assessment Summary shows the status badge and educator name, then Score, Percentage, Best Score, Retakes Remaining, Submitted At, Academic Term, Time Limit, Schedule, Attempts Used, and whether retakes are allowed.

### Attempt History in Review Window
The attempt history lists every submitted attempt for that assessment. Each entry shows its number, a Highest Score or Attempt badge, a PASSED/FAILED badge, and its score, percentage, and submitted time. This provides context for how the viewed attempt compares to other attempts on the same assessment.

### Question-by-Question Review in Window
Each question displays with a Correct/Incorrect badge and its text. For multiple-choice questions, every option is listed with color-coding. The student's answer is highlighted in green when correct and in red when wrong. The correct option is highlighted in green only when the educator allowed review or the student got that question right. Other options remain plain. For identification questions, the correct answer appears only when review is allowed or the answer was right. The student's own answer is marked Correct or Incorrect. When the student left the answer blank, the display shows "No answer submitted."

### Responsive Design Requirements
The page and review window remain usable on narrow screens. The scores table scrolls or wraps rather than breaking the layout. The View Score window's top section stacks vertically on narrow screens instead of displaying two columns.

### Review Window Limitations
The View Score window is for looking back only. It does not include retake actions or attempt-switching navigation. Those actions belong to the post-submission results screen. The window includes only a Close action to dismiss it and return to the table.