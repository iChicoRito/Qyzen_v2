# Objective: Educator UIUX Changes

---

## Description
Implement specific UI/UX improvements across three educator sections: subjects, enrollment, and assessments. These changes involve modifying modal layouts to single-column format, replacing broken multi-select dropdowns with searchable multi-select components, and reorganizing overcrowded assessment modals using tabbed navigation with stepper functionality.

---

## Primary Objective
Improve the educator interface by restructuring modals and form elements in the /educator/subjects, /educator/enrollment, and /educator/assessments sections to enhance usability and organization.

---

## Secondary Objectives
- Ensure the new select dropdowns in /educator/enrollment function with search and multi-select capabilities
- Implement tabs in the /educator/assessments modal to organize fields by category
- Add next/back navigation to the assessment tabs for stepper-like progression

---

## Supporting Tasks

### /educator/subjects Modal Modification
- Modify the "add subject" modal so that text fields and select dropdowns are arranged in a single column layout only

### /educator/enrollment Dropdown Implementation
- Replace the non-functional select dropdown/multiple select with a properly working component
- Implement search functionality in the dropdown as demonstrated in the official KTUI documentation (https://ktui.io/docs/select#search--filtering)
- Enable multiple data selection in the dropdown
- Apply this implementation for both Students and Subjects fields
- Arrange both fields in a single column layout

### /educator/assessments Modal Restructuring
- Reorganize the overcrowded assessment modal by adding tabs to separate fields based on their category or purpose
- Implement tabs using the KTUI Tabs component as referenced in the documentation (https://ktui.io/docs/tabs)
- Add next and back buttons to allow users to navigate between tabs in a stepper pattern

---

## Detailed Breakdown

### /educator/subjects Modal Changes
The current modal for adding a subject needs its layout simplified to a single-column structure for all text fields and select dropdowns.

### /educator/enrollment Select Component Fix
The select dropdown for Students and Subjects is not working properly and needs to be replaced with a functional component that includes:
- **Search functionality**: Enable users to search within the dropdown options using the `data-kt-select-enable-search` attribute or the search API options
- **Multiple selection**: Allow selection of multiple items using the `data-kt-select-multiple` attribute or configuration
- **Proper display**: Display selected items appropriately (tags or comma-separated values)
- **Single-column layout**: Both fields should be arranged in a single column

### /educator/assessments Modal Organization
The assessment modal is currently too crowded with unorganized fields. The solution involves:
- **Tab implementation**: Use KTUI Tabs to create separate tabs for different field categories (e.g., General Information, Assessment Details, Criteria, etc.)
- **Tab configuration**: Each tab should contain related fields grouped by purpose
- **Stepper navigation**: Add "Next" and "Back" buttons to allow users to progress through tabs sequentially, mimicking a stepper workflow
- **Field organization**: Distribute the existing fields across the appropriate tabs to reduce crowding and improve visual hierarchy