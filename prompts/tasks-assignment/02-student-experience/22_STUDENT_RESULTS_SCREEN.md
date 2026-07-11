# Objective
## Student Results Screen Implementation Guide

---

## Description
Build a complete student results screen that displays assessment attempt outcomes, enables review of question-by-question performance, and supports navigation between multiple attempts. The screen must present a clear performance snapshot through a summary panel while providing detailed question review in a separate panel. The implementation must enforce server-side security rules that control access to correct answers based on assessment settings and per-question performance. The screen serves as the destination after assessment submission and the entry point for reviewing past results, supporting both single and multiple attempt scenarios with consistent data presentation.

---

## Primary Objective
Build a functional student results screen that displays one submitted attempt at a time, shows summary performance data and question-level review, enforces server-side answer visibility rules, and enables switching between multiple attempts when they exist.

---

## Secondary Objectives
- Display a two-panel responsive layout with a narrower summary panel on the left and a wider review panel on the right, stacking vertically on smaller screens
- Show an attempt history list that numbers all submitted attempts, marks the highest-scoring attempt, and allows switching between attempts
- Provide retake actions when attempts remain, with confirmation before starting a fresh attempt
- Include a clear exit path back to the assessment list at all times

---

## Success Criteria
- The screen loads and displays a valid submitted attempt belonging to the signed-in student, showing all required summary and review data
- The summary panel displays the subject name, section, assessment code, term, score dial with percentage, correct/incorrect counts, instructor name, passing requirement, score snapshot with best score, retake details with attempt counts, assessment details with status badge, schedule, and plain-language result summary
- The review panel displays each question with its number, text, correct/incorrect badge, and answer details with proper color-coding
- Correct answers appear only when the educator enabled review or when the student answered the question correctly
- The attempt history lists all submitted attempts, marks the highest score, expands the currently viewed attempt, and switches the full screen to a selected attempt
- The retake action appears only when attempts remain and starts a fresh attempt after confirmation
- All ownership, submission status, and answer-visibility checks are performed on the server
- The screen handles missing or invalid attempts by showing a "Result not found" message
- The best-score figure, highest-score badge, and primary-result behavior agree on the highest-scoring attempt consistently

---

## Constraints
- The server must be the sole source of truth for result ownership, submission status, answer-key visibility rules, best-score selection, and retake eligibility
- Correct answers must never reach the student's screen except where settings explicitly allow review or the student answered correctly
- Unfinished attempts must not open on the results screen
- The browser must only display data the server sends and must not enforce security rules on its own
- Only one attempt may be displayed on screen at a time

---

## Out of Scope
- The assessment-taking experience and submission flow
- Building the educator-facing administration interface
- Creating the assessment list page
- Implementing the server-side APIs or data models
- Database schema design

---

## Context & Dependencies
- The screen loads after a student submits an assessment or opens a past result
- The screen depends on server-provided data including the attempt's score and details, assessment settings, educator and subject names, the student's list of submitted attempts, and questions with answer keys
- The existing system has an assessment-taking guide that covers everything before submission
- Wide screens display two panels side by side; smaller screens stack them vertically
- The attempt history list excludes unfinished attempts

---

## Stated Assumptions
- The starting point occurs when a student lands on the results screen for a finished attempt, either right after submitting or by opening a past result

---

## Stakeholders
- Students who view their assessment results and review their performance
- Educators who own assessments and control review settings
- Developers who implement the screen according to the blueprint
- Product or design stakeholders who defined the two-panel layout and user experience

---

## Supporting Tasks

### Phase 1: Loading a Result
- [Sequential] Fetch a specific finished attempt for the signed-in student using a reference to that attempt
- [Sequential] Verify the attempt belongs to the viewing student and has actually been submitted
- [Sequential] Load all required data including attempt score and details, assessment settings, educator and subject/section/term names, the student's list of submitted attempts, and questions with answer keys
- [Conditional] Display a "Result not found" message when no valid attempt is referenced, the attempt does not belong to the student, or the attempt is not submitted
- [Sequential] Prevent any scores from displaying when no valid attempt is found

### Phase 2: Two-Panel Layout
- [Sequential] Place a narrower summary panel on the left and a wider review panel on the right side by side for wide screens
- [Sequential] Stack panels vertically with summary first for smaller screens
- [Sequential] Refresh both panels when switching attempts

### Phase 3: Summary Panel Construction
- [Sequential] Display header with subject name, section, assessment code, and term in order
- [Sequential] Show a radial gauge score dial displaying the percentage prominently with "Overall Score" label, with Correct and Incorrect counts beneath it
- [Sequential] Display the educator's name
- [Sequential] Show the passing percentage requirement
- [Sequential] Display score snapshot with raw score, percentage, and best score across all attempts
- [Sequential] Show retake details including whether retakes are allowed, how many are allowed, attempts used, and attempts remaining
- [Conditional] Add a note clarifying that the numbers describe the selected attempt when viewing a non-best attempt
- [Sequential] Display assessment details including submitted time, time limit, shuffle status, warnings used, and PASSED/FAILED status badge
- [Sequential] Show the assessment's start and end date and time
- [Sequential] Display a plain-language result message with passing wording or non-passing wording
- [Conditional] Show the Retake Assessment action only when attempts remain, with confirmation before starting
- [Sequential] Always show the Back to Assessments action

### Phase 4: Attempt History and Switching
- [Sequential] List every submitted attempt, skipping unfinished ones, numbered in order
- [Sequential] Mark the highest-scoring attempt with a Highest Score badge
- [Sequential] Show the currently viewed attempt as open by default
- [Sequential] Display score, percentage, submitted time, and PASSED/FAILED status for each entry when expanded
- [Conditional] Offer a View This Attempt action on entries other than the one currently displayed
- [Sequential] Reload both panels to match the selected attempt when switching
- [Sequential] Ensure consistent highest-score determination across the badge, summary best-score figure, and primary-result behavior

### Phase 5: Question-by-Question Review
- [Sequential] Show each question with its number, text, and Correct/Incorrect badge
- [Sequential] For multiple-choice questions, list every option with its letter and text with proper color-coding
- [Conditional] Highlight the student's answer in green when correct, highlight the correct option in green only when review is allowed or the student got it right, highlight the student's answer in red when wrong, and leave other options plain
- [Sequential] For identification questions, show the correct answer only when review is allowed or the student got it right
- [Sequential] For identification questions, show the student's typed answer marked Correct or Incorrect
- [Conditional] For identification questions with blank answers, display "No answer submitted"
- [Sequential] Match a student's answer whether stored as an option's letter or its text when recognizing their selection

### Phase 6: Retake and Exit Actions
- [Conditional] Show the Retake Assessment action only when attempts remain
- [Sequential] Open a confirmation explaining a fresh attempt will start when retake is selected
- [Sequential] Begin a new attempt for the same assessment upon confirmation
- [Sequential] Always show the Back to Assessments action that returns the student to their assessment list
- [Sequential] Recompute attempts remaining from submitted attempts versus total allowed when the new attempt actually starts

---

## Detailed Breakdown

### Server-Side Security Enforcement
The server determines what a student is allowed to see and hands over only permitted data. The browser displays only what it is given without enforcing security independently. The server performs ownership verification, submission status checks, and answer-key visibility decisions. Correct answers are only included in the server response when the educator explicitly enabled review or when the student answered that question correctly. The server computes the best score consistently and determines retake eligibility.

### Summary Panel Content Hierarchy
The summary panel presents information in a specific order from top to bottom. The header shows subject, section, assessment code, and term. The score dial displays the percentage prominently with a radial gauge and shows the overall score label. The correct and incorrect counts appear beneath the dial. The instructor name follows, then the passing requirement percentage. The score snapshot shows the raw score, percentage, and the student's best score across all attempts. Retake details display whether retakes are allowed, how many are allowed, how many attempts have been used, and how many remain. When viewing a non-best attempt, a note clarifies that the summary describes the selected attempt. Assessment details show the submitted time, time limit, shuffle status, warnings used, and a PASSED/FAILED status badge. The schedule displays the assessment's start and end date and time. A plain-language result summary provides a passing or non-passing message.

### Attempt History and Switching Mechanics
The attempt history lists all submitted attempts and excludes unfinished ones. Each entry is numbered in order and marked either as the Highest Score or as a plain Attempt. The currently viewed attempt expands by default. Each expanded entry shows its score, percentage, submitted time, and PASSED/FAILED status. Only entries other than the one on screen offer a View This Attempt action. When the student selects another attempt, both panels refresh to display the selected attempt's data. The highest-scoring attempt remains the student's headline result consistently across the badge, the summary's best-score figure, and the primary-result behavior.

### Question Review Display Rules
Each question appears in its own card with the question number, heading, question text, and a Correct or Incorrect badge. For multiple-choice questions, every option displays with its letter and text. The student's answer is highlighted in green when correct. The correct option is highlighted in green only when review is allowed or the student got that question right. The student's answer is highlighted in red when wrong. Other options remain plain. For identification questions, the correct answer appears only when review is allowed or the student got it right. The student's typed answer shows with a Correct or Incorrect label. When the student left the answer blank, the display shows "No answer submitted." The system matches a student's answer whether stored as an option's letter or its text.

#### Edge Cases
- The incorrect count must never fall below zero and is computed as total questions minus correct count
- When a student's answer is stored as an option letter or text, the matching mechanism must handle either format
- For blank identification questions, the system displays "No answer submitted" rather than showing an empty field
- When review is off, the system must not send the answer key for missed questions to the screen, regardless of how the data would be styled

### Retake Eligibility and Actions
The retake action appears only when attempts remain after counting submitted attempts against the total allowed, including any individually granted retakes. The retake check must be recomputed when the new attempt actually starts, not only when the button was shown. Selecting retake opens a confirmation explaining that a fresh attempt will begin, and confirming starts a new attempt for the same assessment. The Back to Assessments action always appears and returns the student to their assessment list.

### Responsive Design Requirements
The two-panel layout adapts to screen size. Wide screens display the narrower summary panel on the left and the wider review panel on the right side by side. Smaller screens stack the panels vertically with the summary panel first. Every section remains readable on a narrow screen.

### Result Not Found Handling
When no valid attempt is referenced, the attempt does not belong to the student, or the attempt is not submitted, the screen displays a simple "Result not found" message. No scores or question data appear in this state. The ownership and submission status checks are performed on the server and are not enforced by the browser or the link.