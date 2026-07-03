# Objective
## Student Assessments & Quiz UI and Functionality Enhancements

---

## Description
This objective covers a set of targeted improvements to the student-facing assessment and quiz interfaces. The goal is to correct existing functional defects and introduce new interface controls that improve usability. Changes include properly reflecting finished assessments with an accurate button state, repairing the quiz question shuffle mechanism so it works consistently on every page refresh, and adding a view toggle that allows students to switch between a card grid layout and a list layout on the assessments page. Additionally, dropdown filters will be incorporated to help students quickly narrow down assessments by attributes such as subject, assessment type, and section.

---

## Primary Objective
Enhance the student assessment and quiz interfaces by fixing the finished-assessment button state, repairing the quiz shuffle functionality, and adding a view toggle with dropdown filters on the assessments page.

---

## Secondary Objectives
- Ensure finished assessments on the `/student/assessments` page display a persistent "Already Taken" button state.
- Make the quiz question shuffle function correctly on every page refresh or visit when the database shuffle flag is active.
- Implement a toggle control on the assessments page to switch between card grid view and list view, supplemented by dropdown filters.

---

## Success Criteria
- On `/student/assessments`, any assessment with a finished status shows a button labeled “Already Taken” and that button is not actionable for re-taking.
- On `/student/take-quiz`, questions are presented in a different, randomized order each time the page is refreshed or visited, provided the database shuffle column is set to active (value of 1).
- The `/student/assessments` page includes a toggle control that switches the presentation of assessment cards between a grid layout and a list layout.
- Dropdown filters for subject, assessment, section, and similar attributes are present and functional on the assessments page, working in both grid and list views.

---

## Constraints
- The view toggle and layout implementation on `/student/assessments` should use the existing design patterns found in `demo1/public-profile/teams.html` as a reference template.

---

## Context & Dependencies
- The shuffle functionality is controlled by a database column that currently holds a value of 1 (true) but does not produce the expected randomized question order on the front end.
- The reference implementation for the card/list toggle and layout resides in the `demo1/public-profile/teams.html` template path.

---

## Supporting Tasks

### Assessment Status Correction
- Update the `/student/assessments` page logic so that when an assessment is marked as finished, its corresponding button renders as "Already Taken" and cannot be clicked to start a new attempt.

### Quiz Shuffle Repair
- Debug and fix the shuffle mechanism on `/student/take-quiz` so that the front end correctly reads the active shuffle flag from the database.
- Ensure the question order is randomized on every full page load or navigation event, not cached between visits.

### View Toggle and Filter Implementation
- Add a toggle control to `/student/assessments` that allows users to switch between a card grid layout and a list layout, following the patterns shown in the provided reference template.
- Implement dropdown filter controls for attributes including subject, assessment, section, and any other relevant categories to filter the displayed assessments in both views.