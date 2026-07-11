# Objective: Quizzes

---

## Description
Modify the /educator/quizzes section to change how quizzes are displayed and managed. Currently, quizzes automatically appear in a table immediately after an assessment is created. The desired behavior, based on the Qyzen Next.js version, requires manual quiz addition (via Excel upload or manual creation) and displays assessments as accordion items with their associated questions listed beneath each assessment.

---

## Primary Objective
Replace the automatic table display of quizzes with an accordion-based layout that groups questions under their respective assessments, and implement manual quiz addition methods (Excel upload and manual creation).

---

## Secondary Objectives
- Match the layout and functionality shown in the provided screenshot from the Qyzen Next.js version
- Prevent quizzes from automatically displaying in a table upon assessment creation

---

## Supporting Tasks

### Quiz Display Restructuring
- Remove the current table that automatically shows all quizzes
- Implement an accordion layout where each assessment is displayed as an expandable section
- Display all questions belonging to each assessment as a list beneath the corresponding assessment accordion

### Quiz Addition Methods
- Implement manual quiz addition functionality (creating quizzes individually)
- Implement Excel file upload functionality for batch quiz addition

---

## Detailed Breakdown

### Current State
The /educator/quizzes section automatically displays all quizzes in a table format immediately after an assessment is created, resulting in a crowded view.

### Desired State (Based on Qyzen Next.js)
The section should display assessments as accordion items rather than listing every quiz in a table. Each assessment accordion should show its associated questions as a list beneath it. Quizzes should only be added manually through either:
- Individual manual creation
- Excel file upload

### Functional Requirements
- Assessments should be the primary organizational structure
- Questions should be nested under their parent assessment
- Accordion interaction should allow users to expand/collapse assessments to view questions
- Manual addition methods should be the only way to create new quizzes