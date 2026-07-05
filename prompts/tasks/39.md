# Objective
## Educator Bulk Enrollment Upload — Excel File Format, Styling, and Validation Specification

---

## Description
This document defines the precise layout, visual styling, column rules, and processing behavior for the Excel template used by educators to bulk-enroll students into their subjects and sections. It serves as the authoritative reference for generating the download template and for validating uploaded files. The specification covers the required sheet structure, cell formatting, data validation constraints that link student identities to the educator's owned subjects, and the server-side handling logic, including error reporting. Adherence to this specification ensures consistent, reliable bulk enrollment that integrates seamlessly with the educator's existing subject and section assignments.

---

## Primary Objective
Define the complete technical specification for the `enrollment-upload-template.xlsx` file, including its structure, styling, column validation rules, and the associated upload processing behavior.

---

## Secondary Objectives
- Specify the exact visual styling applied to the template's title, header, and data rows.
- Define the validation rules, requiredness, and accepted formats for each data column, including paired validation of subject and section.
- Describe the server-side behavior for processing uploaded files, validating against the educator's available subjects and existing student accounts, and reporting errors.

---

## Success Criteria
- The generated template file is named `enrollment-upload-template.xlsx` and contains a single readable worksheet named `Enrollment Upload Template`.
- The worksheet layout matches the specification: a merged title banner in row 1, column headers in row 2, and 10 pre-bordered data rows starting at row 3.
- All cell styles, frozen panes, and column widths conform exactly to the defined styling section.
- Upload processing correctly interprets data by column position, skips fully blank rows, and reports errors with the correct spreadsheet row number.
- Validation enforces that student user IDs exist, subject codes belong to the educator, and the subject-section pairing is valid and owned by the educator.
- The upload halts at the first invalid row and presents an error message identifying the file and the offending row.

---

## Constraints
- Only `.xlsx` file format is accepted for upload.
- The system reads only the first worksheet of any uploaded file.
- Column matching relies strictly on position (columns A–D); the header text is not used for mapping.
- Enrollment is restricted to students and subject-section pairs that belong to the uploading educator.
- No failed-rows export file is generated; processing stops at the first encountered error.

---

## Out of Scope
- Renaming column headers, as matching is positional and header text changes do not affect functionality.
- Generating a downloadable failed-rows file for correction and re-upload.

---

## Context & Dependencies
- Validation of `student_user_id` depends on a case-insensitive match against existing student account identifiers.
- Validation of `subject_code` depends on the educator's owned subjects.
- Validation of `section_name` is paired with `subject_code`; the combination must resolve to a real subject-section pairing owned by the educator.
- The `status` value is automatically lowercased and must be exactly `active` or `inactive`.

---

## Stated Assumptions
- Multiple files can be queued and uploaded simultaneously.

---

## Stakeholders
- Educators responsible for managing student enrollment in their subjects and sections.
- Backend system handling file processing and enrollment record insertion.
- Database containing student accounts and the educator's subject-section ownership records.

---

## Supporting Tasks

### Template Generation
- Create a worksheet named `Enrollment Upload Template` as the first and only sheet.
- Merge cells A1:D1 and populate with the title text `Qyzen Enrollment Upload Template`.
- Insert column headers `student_user_id`, `subject_code`, `section_name`, and `status` into row 2.
- Pre-format 10 empty data rows (rows 3–12) with white fill and thin `#E4E4E7` borders.
- Apply a frozen pane split at `ySplit: 2` to keep the title and header rows visible during scrolling.
- Set column widths to: A 18, B 18, C 24, D 16.
- Style row 1 with bold, size 16, white (`FFFFFFFF`) font, center alignment, and a solid `#171717` fill.
- Style row 2 with bold, white font, center alignment, a solid `#0A0A0A` fill, and thin `#D4D4D8` borders.

### Upload Validation and Processing
- Accept only `.xlsx` files, processing multiple files if queued.
- Read data exclusively from the first worksheet, ignoring any additional sheets.
- Treat row 2 as the header row and begin reading data from row 3 onward.
- Skip any row where all cells are completely blank.
- Require `student_user_id` to match an existing student account case-insensitively.
- Require `subject_code` to match one of the educator's owned subjects.
- Require the `subject_code` and `section_name` pairing to resolve to a valid subject-section combination owned by the educator.
- Require `status` to be `active` or `inactive`, automatically lowercasing the input.
- Halt processing at the first invalid row and report an error message that identifies the file name and the specific row number, referencing the actual spreadsheet row (starting from row 3).