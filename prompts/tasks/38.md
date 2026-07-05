# Objective
## Admin Bulk Student Upload — Excel File Format, Styling, and Validation Specification

---

## Description
This document defines the precise layout, visual styling, column rules, and processing behavior for the Excel template used to bulk-create student accounts via the admin interface. It serves as the authoritative reference for generating the download template and for validating uploaded files. The specification covers the required sheet structure, cell formatting, data validation constraints, and the server-side handling logic, including error reporting and failed-row remediation. Adherence to this specification ensures consistent, reliable bulk uploads that integrate seamlessly with the backend account creation process.

---

## Primary Objective
Define the complete technical specification for the `student-upload-template.xlsx` file, including its structure, styling, column validation rules, and the associated upload processing behavior.

---

## Secondary Objectives
- Specify the exact visual styling applied to the template's title, header, and data rows.
- Define the validation rules, requiredness, and accepted formats for each data column.
- Describe the server-side behavior for processing uploaded files, handling errors, and providing a failed-row correction mechanism.

---

## Success Criteria
- The generated template file is named `student-upload-template.xlsx` and contains a single readable worksheet named `Student Upload Template`.
- The worksheet layout matches the specification: a merged title banner in row 1, column headers in row 2, and 10 pre-bordered data rows starting at row 3.
- All cell styles, frozen panes, and column widths conform exactly to the defined styling section.
- Upload processing correctly interprets data by column position, skips fully blank rows, and reports errors with the correct spreadsheet row number.
- A `student-upload-failed.xlsx` file containing only failed rows with a reason column is generated for correction and re-upload.

---

## Constraints
- Only `.xlsx` file format is accepted for upload.
- The system reads only the first worksheet of any uploaded file.
- Column matching relies strictly on position (columns A–E); the header text is not used for mapping.

---

## Out of Scope
- Renaming column headers, as matching is positional and header text changes do not affect functionality.

---

## Context & Dependencies
- The upload process depends on a database of existing roles; every value supplied in the `role_names` column must exactly match a role already present in the database.
- The `user_id` field, when left blank, triggers automatic assignment of a unique placeholder identifier in the `PENDING-xxxxxx` format for later administrative correction.

---

## Stated Assumptions
- Multiple files can be queued and uploaded simultaneously.

---

## Stakeholders
- Admin users responsible for bulk student account creation.
- Backend system handling file processing and account insertion.
- Database containing the authoritative list of valid roles.

---

## Supporting Tasks

### Template Generation
- Create a worksheet named `Student Upload Template` as the first and only sheet.
- Merge cells A1:E1 and populate with the title text `Qyzen Student Upload Template`.
- Insert column headers `user_id`, `given_name`, `surname`, `email`, and `role_names` into row 2.
- Pre-format 10 empty data rows (rows 3–12) with white fill and thin `#E4E4E7` borders.
- Apply a frozen pane split at `ySplit: 2` to keep the title and header rows visible during scrolling.
- Set column widths to: A 18, B 22, C 22, D 36, E 28.
- Style row 1 with bold, size 16, white (`FFFFFFFF`) font, center alignment, and a solid `#171717` fill.
- Style row 2 with bold, white font, center alignment, a solid `#0A0A0A` fill, and thin `#D4D4D8` borders.

### Upload Validation and Processing
- Accept only `.xlsx` files, processing multiple files if queued.
- Read data exclusively from the first worksheet, ignoring any additional sheets.
- Treat row 2 as the header row and begin reading data from row 3 onward.
- Skip any row where all cells are completely blank.
- Validate `user_id` against the `NNNN-NNNNN` pattern if provided; assign a `PENDING-xxxxxx` placeholder if empty.
- Require non-empty values for `given_name`, `surname`, and `email`, automatically lowercasing the email.
- Require at least one role in `role_names`, accepting a pipe-separated list and verifying each role's exact existence in the database.
- Report errors referencing the actual spreadsheet row number, starting from row 3.

### Error Remediation
- Generate a downloadable `student-upload-failed.xlsx` file containing only the failed rows.
- Include columns for `user_id`, `email`, and `reason` in the failure report.
- Allow admins to correct the failed file and re-upload it for processing.