# Objective
## Refine the Offline Score Upload Interface and Excel Template Structure

---

## Description
The offline score upload interface currently displays assessment options in a single dropdown that concatenates subject code, subject name, section, assessment code, and term into one cluttered inline string, making it difficult for users to read and select the correct assessment. The goal is to reorganize the assessment dropdown so it displays only the assessment code on one line and the subject code with subject name on a second line, improving scanability. A separate searchable dropdown for section must be introduced, displaying the section identifier on one line and the term on a second line. Both dropdowns must support search functionality to allow users to quickly filter and find the desired option. Additionally, the Excel file template used to upload offline grades must be modified to remove the date column and the warning attempts column, simplifying the file structure for users.

---

## Primary Objective
Improve the user experience of the offline score upload interface by replacing the current cluttered assessment dropdown with two separate, searchable dropdowns presenting assessment and section information in a clean, multi-line format, and by removing the date and warning attempts columns from the upload Excel template.

---

## Secondary Objectives
- Redesign the assessment dropdown to show only the assessment code on the first line and the subject code with subject name on the second line.
- Introduce a new searchable dropdown for section that displays the section identifier on the first line and the term on the second line.
- Ensure both dropdowns include search functionality for efficient user filtering.
- Remove the date column from the Excel file used for offline grade upload.
- Remove the warning attempts column from the Excel file used for offline grade upload.

---

## Success Criteria
- The assessment dropdown displays options in the format: first line showing the assessment code, second line showing the subject code and subject name.
- The section dropdown displays options in the format: first line showing the section identifier, second line showing the term.
- Both dropdowns provide searchable input fields to filter available options.
- Users no longer see a single inline string containing all assessment and section details.
- The downloadable or uploadable Excel template no longer includes a date column.
- The downloadable or uploadable Excel template no longer includes a warning attempts column.

---

## Context & Dependencies
- The current offline score upload feature already exists and includes a dropdown for selecting assessments.
- The existing dropdown displays concatenated inline details in the order: subject code, subject name, section, assessment code, term.
- The existing Excel template for uploading offline grades includes a date column and a warning attempts column.

---

## Supporting Tasks

### Assessment Dropdown Redesign
- Modify the assessment dropdown data source to structure each option as two lines: assessment code on top, subject code and subject name below.
- Implement or enable search functionality on the assessment dropdown.
- Remove all extraneous fields (section, term) from the assessment dropdown display.

### Section Dropdown Implementation
- Create a new searchable dropdown for section selection, separate from the assessment dropdown.
- Structure each section option to display the section identifier on the first line and the term on the second line.
- Wire the section dropdown to filter or relate appropriately to the selected assessment, if required by existing logic.

### Excel Template Modification
- Locate the Excel file or generation logic used for the offline grade upload template.
- Remove the date column from the template structure.
- Remove the warning attempts column from the template structure.
- Ensure the modified template remains functional for uploading grades without these columns.