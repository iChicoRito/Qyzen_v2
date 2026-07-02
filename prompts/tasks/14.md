# Objective: Bulk Upload with Targeted Assessment

---

## Description
Redesign the add quiz modal to include bulk upload functionality with targeted assessment selection. The current modal does not resemble the working quiz system. The new design should feature an upload file button in the header that opens a dedicated bulk upload section. This section must include a template download option and a "Target Assessment" selector that displays all available assessments by subject and section, allowing the educator to choose where the uploaded quiz rows should be applied.

---

## Primary Objective
Implement a bulk upload feature within the add quiz modal that allows educators to upload quiz files and target specific assessments for the uploaded quiz rows.

---

## Secondary Objectives
- Ensure the modal matches the look and functionality of the working quiz system
- Provide a template download option for educators
- Display all available assessments organized by subject and section in the target selector

---

## Supporting Tasks

### Modal Header Enhancement
- Add an "Upload File" button in the modal header
- Ensure the button triggers the bulk upload interface within the modal

### Bulk Upload Interface
- Open a dedicated section or UI area for bulk uploading when the upload button is clicked
- Display a template download option for educators to obtain the correct file format
- Show a "Target Assessment" selection area

### Target Assessment Selector
- Display all available assessments in the selector
- Organize assessments by subject and section
- Allow selection of the specific assessment where uploaded quiz rows should be targeted
- Immediately target the selected assessment upon upload

### Functionality
- Ensure uploaded quizzes are correctly assigned to the selected target assessment
- Maintain the existing working quiz system functionality alongside the new bulk upload feature

---

## Detailed Breakdown

### Current State
The add quiz modal does not match the look or functionality of the working quiz system. There is no bulk upload capability.

### Desired State
The add quiz modal should include:
1. **Upload File Button**: Located in the modal header, triggers the bulk upload section
2. **Bulk Upload Section**: Opens within the modal, containing:
   - Template download option for the quiz file format
   - Target Assessment selector
3. **Target Assessment Selector**: Displays every available assessment organized by subject and section, allowing the educator to select where the uploaded quiz rows will be applied

### Functional Flow
1. Educator opens the add quiz modal
2. Clicks the "Upload File" button in the header
3. Bulk upload section appears
4. Educator can download the template for proper file formatting
5. Educator selects a Target Assessment from the list (organized by subject and section)
6. Educator uploads the quiz file
7. The uploaded quiz rows are immediately targeted and applied to the selected assessment