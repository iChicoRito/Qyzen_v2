# Objective
## Export Scores Feature Implementation

---

## Description
Build a complete "download student grades to Excel" feature for educators, modeled after Qyzen's educator score export functionality. The feature enables educators to export student assessment scores as structured Excel workbooks, with support for both single-class exports and bulk exports across multiple classes, terms, and semesters. The implementation spans data modeling, backend query construction, file-generation logic, and user interface flow, all designed to be stack-agnostic and adaptable to any technology stack.

The system retrieves enrollment rosters and submitted scores, resolves each student's best attempt when retakes are permitted, and generates properly formatted Excel sheets with summary statistics, detailed student rows, and consistent styling. Bulk exports are packaged as structured zip archives with organized folder hierarchies. The feature prioritizes data integrity through validation, ownership scoping to prevent unauthorized access, and comprehensive edge-case handling.

---

## Primary Objective
Implement a fully functional score export system that allows educators to download student grades for single assessments or multiple classes, with Excel output that includes complete rosters (including non-submitting students), best-attempt resolution for retakes, and professionally formatted spreadsheets.

---

## Secondary Objectives
- Ensure all queries are scoped by educator ownership to prevent unauthorized data access
- Support both single-export and bulk-export workflows with appropriate file packaging (Excel workbooks and zip archives)
- Provide a user-friendly frontend interface with cascading dropdowns, preview summaries, and clear loading states
- Maintain separation of concerns between data fetching, business logic, file generation, and presentation layers

---

## Success Criteria
- Single export produces one Excel workbook with one sheet per assessment, downloadable as `.xlsx`
- Bulk export produces a zip archive containing organized Excel workbooks structured by term, subject, and section
- Exported sheets include all enrolled students, with non-submitters marked as "No Submission"
- Best-attempt resolution correctly identifies the highest score when retakes exist
- Summary statistics (total enrolled, with submission, no submission) are accurately calculated and displayed
- Sheet headers are frozen, columns are properly sized, and banded row styling is applied
- All exported files are sanitized for safe filesystem and sheet naming
- Bulk exports handle large datasets through pagination/chunking without truncation
- Frontend modal presents both Single and Multiple export options with appropriate selection controls
- Preview summaries display before export to allow sanity-checking
- All fetch operations include error handling with user-facing notifications

---

## Constraints
- The system must be stack-agnostic; implementation examples should reference concepts rather than specific frameworks
- All queries must be scoped by the current educator's ID (or equivalent ownership check)
- Database row caps (e.g., Supabase default of 1000 rows per request) must be handled through pagination/chunking
- Excel sheet names must be ≤ 31 characters and sanitized to remove disallowed characters
- Bulk exports must avoid N+1 queries by fetching data once and grouping in memory
- Percentage calculation must handle `totalQuestions` of 0 or missing values without throwing errors
- The row-building logic must be a pure function with no database or file-system dependencies for testability

---

## Out of Scope
- No explicit exclusions are stated in the input.

---

## Context & Dependencies
- The feature builds on existing database schemas for students, subjects, sections, terms, enrollments, assessments, questions, and scores
- The frontend must integrate with an existing Scores page, adding a "Download Grades" button
- The implementation assumes a relational data model with specific key relationships (e.g., assessments belong to one subject, section, term, and educator)
- File generation libraries (ExcelJS, openpyxl, PhpSpreadsheet, SheetJS, Apache POI) and zip utilities (JSZip, Python zipfile) are required dependencies
- The system relies on an authentication/authorization context to determine the current educator's identity
- Frontend state management must cache export options on modal open for cascading dropdown performance

---

## Stated Assumptions
- No explicit assumptions are stated in the input.

---

## Stakeholders
- Educators (primary users) who download grades for their classes
- Students whose scores are included in the exports
- System administrators who may need to support or troubleshoot the feature

---

## Supporting Tasks

### Data Modeling and Schema Design
- [Sequential] Define minimum required entities: students, subjects, sections, terms, enrollments, assessments, questions, and scores
- [Sequential] Document key relationships and ownership scoping rules (educator ownership, assessment to subject/section/term mapping)
- [Parallel] Ensure schema supports multiple score rows per student per assessment (for retake tracking)
- [Parallel] Maintain enrollment as the source of truth for roster membership

### Backend Query Operations
- [Sequential] Build export-options endpoint to fetch all assessments owned by the educator with joined subject, section, and term data
- [Sequential] Implement single-export endpoint with parallel queries for assessment validation, enrollment roster, submitted scores, and question count
- [Sequential] Implement bulk-export endpoint with pagination/chunking, in-memory grouping, and support for all/by-term/by-semester filters
- [Conditional] If exceeding database row caps, implement offset/range pagination loops until all data is retrieved

### Row-Building Business Logic
- [Sequential] Create pure function that accepts roster and scores, groups scores by student, and resolves the best attempt per student
- [Sequential] Implement best-attempt resolution logic: sort by raw score descending, then percentage descending, then most recent submission id
- [Sequential] For each enrolled student, emit row with score/percentage/null/status/remark/submittedAt values
- [Sequential] Handle edge cases: no submissions, null scores on submitted rows, zero/missing total questions, tied scores
- [Sequential] Sort final rows alphabetically by student name and roll up summary counts

### Input Validation
- [Sequential] Define validation schemas for single export payload (subjectName, sectionId, assessmentCode, termId)
- [Parallel] Define validation schemas for bulk export filters as discriminated unions (all/term/semester)
- [Parallel] Reject malformed or partial filter combinations

### Excel File Generation
- [Sequential] Implement per-sheet layout: title banner, summary block, column headers, student data rows with styling
- [Sequential] Apply formatting: merged title cells, frozen header row, banded row shading, thin borders, fixed column widths
- [Sequential] Format percentage as string with % suffix, handle "Not submitted" placeholder for missing submissions
- [Sequential] Sanitize sheet names (≤31 chars, remove disallowed characters, de-duplicate collisions)
- [Conditional] For single export: generate one workbook with one sheet
- [Conditional] For multi-sheet exports: generate one workbook with one sheet per assessment

### Bulk Export Packaging
- [Sequential] Group fetched results by subjectId:sectionId:termId
- [Sequential] Build multi-sheet workbook for each group
- [Sequential] Serialize each workbook to buffer and add to zip archive at structured path: `{term}/{subject}/{section}.xlsx`
- [Sequential] Sanitize all path segments: uppercase, replace non-A-Z0-9 with hyphens, trim leading/trailing hyphens, fall back to placeholders
- [Sequential] Generate final zip with naming convention: `{educator-name}-{filter-type}-{date}.zip`

### Frontend UI Implementation
- [Sequential] Add "Download Grades" button on Scores page that opens a modal
- [Sequential] On modal open, fetch export-options list once and cache in local state
- [Sequential] Build Single tab with four cascading selectors (Subject → Section → Assessment → Term)
- [Sequential] Implement downstream selector disabling and reset logic on parent changes
- [Sequential] Add debounced preview fetch when all four selections are made, render summary card
- [Sequential] Build Multiple tab with three method choices (All/By Term/By Semester), revealing extra selectors as needed
- [Parallel] Implement download trigger: generate Blob, create object URL, synthesize anchor click, revoke URL
- [Parallel] Add loading states for bulk exports and error handling with toast/inline notifications

### Code Organization and Testing
- [Sequential] Separate code into modules: export-options-and-data-fetching, row-building-logic, workbook-builder, download-modal (UI)
- [Sequential] Ensure row-building-logic is a pure function for unit testability
- [Sequential] Write unit tests for edge cases: zero enrolled, zero submissions, multiple retakes, tied scores, missing total questions, null scores, pagination limits, sanitization

---

## Detailed Breakdown

### Data Model Requirements
The system requires a relational database structure with specific entities and relationships to support the export queries. The `students` table stores learner identity including names, display IDs, and active status. `subjects` defines course/class definitions with names, codes, and default sections. `sections` represents class groups, and `terms` tracks academic periods with semester and year information. `enrollments` establishes the student-to-subject relationship with active status flags. `assessments` defines quizzes or exams with codes, foreign keys to subjects, sections, terms, and educators, plus retake configuration. `questions` counts total items per assessment. `scores` stores submitted attempts with student references, assessment references, score values, total questions, submission timestamps, status, and pass/fail indicators.

Key relationships: assessments belong to one subject, section, term, and educator (scoping every query). Students may have multiple score rows per assessment when retakes are allowed, requiring best-attempt resolution at query time rather than storing it separately. Enrollment data serves as the source of truth for the full roster, ensuring students without submissions still appear in exports as "No Submission" rows.

### Backend Query Operations: Export Options
The export-options endpoint fetches every assessment owned by the educator, joined with its subject, section, and term data. The result is flattened into a simple option list containing assessmentRowId, assessmentCode, subjectId, subjectName, sectionId, sectionName, termId, termName, semester, and academicYear. This single list enables client-side cascading dropdown derivation, eliminating the need for separate round-trip endpoints per selection level. All queries must be ownership-scoped to prevent educators from accessing each other's data.

### Backend Query Operations: Single Export
Given subjectId, sectionId, assessmentRowId, and termId, the system runs three parallel queries and one count query. First, it validates the assessment exists and belongs to the educator/subject/section/term combination as defense against tampered requests. Second, it fetches the enrollment roster with active enrollments joined to student identity, filtered to the target section. Third, it retrieves submitted score rows where submitted_at is not null for that assessment/subject/section combo, excluding drafts or in-progress attempts. Finally, it counts question rows for the assessment to use as the total questions fallback for students without submissions.

### Backend Query Operations: Bulk Export
The bulk export follows the same data shape as single export but processes every assessment owned by the educator matching an optional filter (all, one termId, or one academicYear + semester pair). The system first fetches assessments, derives the distinct subject IDs, section IDs, and assessment IDs needed, then fetches enrollments and scores only for classes in scope. To handle scale, it implements pagination or chunking when database row caps are exceeded, using offset/range loops until pages return fewer rows than the page size. It also chunks large IN(...) lists of assessment IDs (e.g., 50 at a time) to avoid oversized queries. The system fetches enrollments and scores once for the entire set, groups them in memory per assessment to avoid N+1 queries, and builds one result object per assessment including assessments with zero submissions.

### Row-Building Business Logic
The core business logic operates as a pure function taking a roster of enrolled students and a set of submitted score rows for one assessment. It first groups scores by student, then resolves the "best" attempt per student by sorting by raw score descending, percentage descending, and most recent submission id as tiebreaker. For each enrolled student, if they have no submitted score, the function emits a row with null score/percentage, "No Submission" status, "No Submission" remark, and null submittedAt, using the roster's labels for full identification. If they have a best attempt, it computes percentage as rounded score divided by total questions (or null if totalQuestions is 0 or unknown), sets status based on the stored pass/fail flag, and sets remark to indicate the highest submitted score or flags data anomalies when a submitted row has a null score. The function sorts final rows alphabetically by student name and rolls up summary counts for total enrolled, students with submission, and students without submission.

### Input Validation
The system validates selection payloads using schema libraries (Zod, Yup, class-validator) before querying. Single export validation requires subjectName (required string), sectionId (required numeric id as string), assessmentCode (required string), and termId (required string). Bulk export validation uses discriminated unions: `{ type: 'all' }`, `{ type: 'term', termId }`, or `{ type: 'semester', academicYear, semester }`. This approach prevents malformed or partial filter combinations from being accepted.

### Excel File Generation
The per-sheet layout follows a consistent structure: Row 1 contains a merged title banner across all columns with dark fill, white bold text centered. Row 2 is a blank spacer. Rows 3-6 form a two-column key/value summary block showing Subject, Section, Assessment Code, and Academic Term on the left and Total Enrolled, With Submission, and No Submission on the right, with light gray fill and bold labels. Row 7 is a blank spacer. Row 8 contains column headers with dark fill, white bold text, centered and frozen to stay visible when scrolling. Rows 9 and onward contain one row per student with columns: Student Name, Student ID, Subject, Section, Assessment Code, Academic Term, Highest Score, Total Questions, Percentage, Status, Remark, and Highest Submitted At (or "Not submitted" placeholder).

Styling applies alternate row shading for readability, thin borders on every data cell, frozen header row, fixed column widths, and percentages formatted as strings with % suffix. Sheet names are sanitized to ≤31 characters, stripped of disallowed characters, and de-duplicated with appended suffixes when collisions occur.

### Bulk Export Packaging
When bulk exporting spans multiple subject/section/term combinations, the system groups all fetched results by subjectId:sectionId:termId and builds a multi-sheet workbook for each group with one sheet per assessment. Each workbook is serialized to a buffer and added to a zip archive at structured paths like `PRELIM/CS201-INFORMATION-MANAGEMENT/BSCS21M1.xlsx`. Path segments are sanitized by uppercasing, replacing non-A-Z0-9 characters with hyphens, trimming leading/trailing hyphens, and falling back to generic placeholders if empty. The final zip is named using the educator's identity, filter type, and current date (e.g., `juan-dela-cruz-all-grades-2026-07-04.zip`) to distinguish repeated exports.

### Frontend Flow
The feature is triggered by a "Download Grades" button on the scores/grades list, opening a modal rather than navigating to a new page. On modal open, the system fetches the full export-options list once and caches it in local state for the session. The Single tab presents four cascading selectors (Subject → Section → Assessment → Term), with downstream selects disabled until parents are chosen and options filtered from the cached list. Changing an upstream field resets downstream fields and clears any preview. Once all four selections are made, the system debounces a fetch for the export preview and renders a summary card with enrollment and submission counts. The "Export" button is disabled while loading or without a valid preview.

The Multiple tab offers three method choices: All, By Term, or By Semester, each revealing an extra selector only when relevant. The "Download" button triggers the bulk fetch and zip pipeline, showing a loading state for the longer operation. The download trigger builds a Blob from the generated file (using `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` for Excel or `application/zip` for bulk), creates an object URL, synthesizes an anchor click, and revokes the URL immediately after. All fetch steps include error handling with toast or inline error messages, including the "no assessments found" empty state.

### Edge Cases and Testing
The system must handle and test several edge cases: assessments with zero enrolled students produce empty rosters with correct labels; assessments with enrolled students but zero submissions include every student with "No Submission" status rather than dropping rows; students with multiple retake attempts export the highest score, not the latest; identical scores on multiple attempts use tiebreakers (higher percentage, then most recent id) for deterministic selection; totalQuestions of 0 or missing degrades to null/blank percentages without throwing errors; submitted score rows with null score values surface as distinct "Data Anomaly" status rather than being miscounted or causing crashes; bulk export pagination/chunking actually loops to completion when exceeding database caps; and filenames/sheet names with special characters sanitize to valid, unique values.