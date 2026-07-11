# Objective

## Refine and Structure the Learning Materials Feature Implementation Guide with UI/UX Design Requirements

---

## Description

Transform the provided technical implementation guide and feature explanation document into a structured, modular objective format that incorporates specific frontend design requirements. The documentation details a complete file upload feature for an educational platform where educators can upload learning materials (PDFs, PowerPoints, Word documents, and RTF files) and assign them to specific subject/section combinations they teach. Students enrolled in those classes can then view and download the materials through a secure, authenticated system.

The implementation guide covers the full implementation scope including data modeling, storage strategy with orphan cleanup, API contract with six endpoints, access control rules differentiating educators and students, frontend flow with drag-and-drop upload modal, and optional notification handling. The implementation guide emphasizes critical correctness rules such as row-per-target modeling, short-lived signed URLs for file access, and the orphan cleanup rule that prevents premature deletion of storage objects still referenced by other rows.

The UI/UX design must follow an existing visual pattern from the platform's Recent Uploads section, where files are displayed with appropriate icons or illustrations that reflect the uploaded file type. This design should be implemented using the template structure from `demo1/public-profile/profiles/default.html` as the foundation for the display components.

The primary goal is to create a clear, actionable specification that captures all functional requirements, business rules, technical constraints, frontend design patterns, and edge cases from both documents. The refined output must be precise enough for development teams to implement without ambiguity while remaining accessible to stakeholders who need to understand the feature's capabilities and limitations.

---

## Primary Objective

Produce a modular, well-structured specification document that captures all implementation requirements, business rules, access controls, data models, API contracts, UI flows (including file-type iconography and template-based design from the Recent Uploads section), and edge cases for an educator file upload feature where files are assigned to specific subject/section combinations and made available to enrolled students through secure, authenticated access.

---

## Secondary Objectives

- Document the data model with clear entity relationships, field definitions, and indexing requirements that support the row-per-target storage approach
- Define comprehensive row-level access rules that enforce educator ownership and student enrollment-based read permissions
- Specify storage strategy including path convention, bucket isolation, short-lived signed URL generation, and orphan cleanup logic
- Detail input validation requirements including file type allow-lists, per-file size caps, and selection key parsing
- Define six API endpoints with request/response patterns, processing order, and error handling behaviors
- Describe frontend implementation flow including modal interaction, file queuing, target selection, and post-submit behavior
- Outline optional notification handling for upload and delete events
- Explicitly document deliberate exclusions (no preview, no versioning, no metadata) to prevent scope creep
- Define the visual design requirements for file display with file-type specific icons or illustrations matching the Recent Uploads section pattern
- Specify the template structure from `demo1/public-profile/profiles/default.html` as the foundation for material display components

---

## Success Criteria

- All seven entities in the data model are defined with key fields and indexing requirements
- Two row-level access rules (educator read/write/delete scope and student enrollment-based read scope) are clearly specified
- Storage strategy covers bucket isolation, path convention (including example), and signed URL expiration
- Four input validation checks are documented: selection key parsing, extension allow-list, per-file size cap, and ownership validation
- Six API endpoints have request/response specifications with the critical POST endpoint detailing processing order across seven steps
- Frontend flow covers eight interaction stages from trigger through edit/delete operations
- Five edge cases are explicitly identified with expected behaviors to test
- Four deliberate exclusions are documented with rationale for why they're out of scope
- Orphan cleanup rule is articulated with condition: "never delete a storage object until no row references that exact bucket+path pair"
- The "row per (file, subject, section) pair, not per file" modeling principle is stated upfront
- File display components use the template from `demo1/public-profile/profiles/default.html` as their structural foundation
- Each displayed file shows an appropriate icon or illustration that visually indicates its file type (e.g., PDF icon for PDFs, PowerPoint icon for PPT files, Word icon for DOC/DOCX files, RTF icon for RTF files)
- The visual presentation of uploaded materials matches the established design pattern from the Recent Uploads section of the platform

---

## Constraints

- Total individual file size limit: 20 MB maximum per file
- Supported file extensions limited to PowerPoint (`.ppt`, `.pptx`, `.ppsx`), PDF (`.pdf`), Word (`.doc`, `.docx`), and Rich Text (`.rtf`)
- File paths must be sanitized to `[a-zA-Z0-9-_]` characters with lowercase conversion and collapsed separators
- Signed URLs must expire after approximately 60 seconds (referred to as "about a minute" and "seconds to low minutes")
- Each material row must store educator_id, subject_id, section_id, storage_bucket, storage_path, file_name, file_extension, mime_type, file_size, is_active, created_at, and updated_at
- At least one file and at least one subject/section selection key are required for upload submission
- Only one dedicated bucket/container for learning materials
- Upload and delete operations must run under elevated/service credentials, not end-user credentials
- File access endpoint must not distinguish between "material doesn't exist" and "material exists but you can't see it" (return 403/404 indistinguishably)
- The `default.html` template from `demo1/public-profile/profiles/` must be used as the structural foundation for file display components
- File-type icons or illustrations must be displayed consistently based on uploaded file extension (PDF, PPT/PPTX/PPSX, DOC/DOCX, RTF)

---

## Out of Scope

- Built-in file preview (View/Download redirect to browser's native handling; no in-app PDF/Office viewer)
- Version history (editing a material's file overwrites the prior one; old object deleted once orphaned)
- Metadata beyond target class assignment and file (no title, description, due date, or category)
- Publicly accessible storage (all files accessed via short-lived signed URLs only)
- Educator with zero subjects/sections assigned (not explicitly scoped but implied edge case)

---

## Context & Dependencies

- Feature exists within a broader educational platform with Users (identified by id and role), Subjects (course definitions with code and name), Sections (class groupings), and Enrollments (linking students, educators, subjects, sections with is_active flag)
- Authentication is required for all API routes; session must resolve caller's role explicitly
- Storage service with object storage capabilities and signed URL generation is available
- Database layer should ideally enforce row-level access rules as defense-in-depth (educator_id matches own id for educator access; active enrollment exists for student read access)
- Notification system exists for broadcasting upload and delete events to enrolled students (optional but recommended)
- A leftover/unused sample data file exists in the materials folder from development; not used in current functionality
- The frontend codebase contains an existing template at `demo1/public-profile/profiles/default.html` that defines the structural pattern for displaying items in list or grid views
- The platform has an existing Recent Uploads section that demonstrates the established visual pattern for displaying files with type-specific icons
- The design system includes or requires file-type icon sets for PDF, PowerPoint, Word, and RTF file formats

---

## Stated Assumptions

- Files are kept in secure storage, not publicly accessible via guessable links
- The system generates a temporary, one-time link that expires after about a minute for each View or Download request
- Adding the exact same file twice (same name and size) doesn't create a duplicate in the queue — it gets replaced in place
- If the same batch of files is assigned to more than one subject/section, every file becomes available to every checked class (cross-product behavior)
- An educator can never accidentally upload something into someone else's class because only subjects/sections they teach appear in the checklist
- The Recent Uploads section's display pattern and file-type iconography can be adapted and reused for the Learning Materials display
- The existing `default.html` template provides the appropriate structural elements (containers, styling hooks, layout patterns) needed for the materials list display
- Icon/illustration assets for file types already exist or can be created following the Recent Uploads design language

---

## Stakeholders

- Educators (primary users who upload, view, download, edit, and delete learning materials)
- Students (end-users who view and download materials; cannot edit or delete)
- System Administrators (mentioned as an access role for the file access endpoint)
- Development Team (implementing the feature based on the guide)
- UI/UX Designers (responsible for ensuring file icons and display patterns match the Recent Uploads section)
- Qyzen platform (the educational system where this feature lives)

---

## Supporting Tasks

### Data Modeling
- [Sequential] Define Users entity with id and role fields
- [Sequential] Define Subjects entity with id, subject_code, and subject_name
- [Sequential] Define Sections entity with id and section_name
- [Sequential] Define Enrollments entity with student_id, educator_id, subject_id, section_id, and is_active
- [Sequential] Define Learning Materials entity with all twelve required fields
- [Sequential] Establish index on educator_id, subject_id, section_id, and updated_at
- [Sequential] Create composite index on (storage_bucket, storage_path) for orphan cleanup queries

### Access Control Implementation
- [Parallel] Implement educator access rule: read/write/delete only rows where educator_id matches own id
- [Parallel] Implement student access rule: read only if is_active=true AND active enrollment exists
- [Parallel] Implement row-level security at database layer as defense-in-depth

### Storage Setup
- [Sequential] Provision dedicated bucket/container for learning materials
- [Sequential] Define path convention: {educatorId}/{unixTimestampMs}-{uuid}-{sanitized-original-filename}.{ext}
- [Sequential] Implement filename sanitization: strip to [a-zA-Z0-9-_], collapse separators, lowercase
- [Sequential] Configure bucket with no public access (only server-generated signed URLs)
- [Sequential] Set up signed URL generation with 60-second expiration
- [Sequential] Configure upload/delete operations to use elevated/service credentials

### Input Validation
- [Parallel] Validate selectionKeys as array of "<subjectId>:<sectionId>" strings, minimum 1 entry
- [Parallel] Validate filesCount as integer, minimum 1
- [Parallel] Implement extension allow-list check against fixed list (pptx, ppsx, ppt, pdf, docx, doc, rtf)
- [Parallel] Implement per-file size cap check against 20 MB maximum
- [Parallel] Implement ownership check: confirm each selectionKey's subject/section belongs to requesting educator

### API Development
- [Sequential] Implement GET /materials?view=list endpoint
- [Sequential] Implement GET /materials?view=targets endpoint
- [Sequential] Implement POST /materials endpoint with seven-step processing order
- [Sequential] Implement PATCH /materials/{id} endpoint
- [Sequential] Implement DELETE /materials/{id} endpoint
- [Sequential] Implement GET /materials/{id}/file endpoint
- [Parallel] Add authentication to all routes with explicit role-based responses (401 vs 403)

### Frontend Implementation
- [Sequential] Build upload trigger button on materials list page
- [Sequential] Build upload modal with file drop/pick zone and target checklist
- [Parallel] Implement file queuing with drag-and-drop and click-to-browse
- [Parallel] Implement target checklist bound to form array field
- [Parallel] Build submit logic with multipart form payload
- [Parallel] Implement success/error toast notifications
- [Parallel] Build edit modal reusing upload patterns
- [Parallel] Build unified confirmation dialog for all delete flows
- [Parallel] Implement View and Download as anchor links with ?download=1 flag

### UI Design Implementation
- [Sequential] Review and analyze the `demo1/public-profile/profiles/default.html` template structure
- [Sequential] Extract the display pattern from the Recent Uploads section for file visualization
- [Parallel] Map file extensions to appropriate icons/illustrations (PDF, PPT, PPTX, PPSX, DOC, DOCX, RTF)
- [Parallel] Implement file-type icon rendering based on uploaded file's extension
- [Parallel] Adapt the Recent Uploads display pattern for the Learning Materials list view
- [Parallel] Ensure consistent visual styling between Recent Uploads and Learning Materials sections

### Cleanup and Maintenance
- [Sequential] Implement orphan cleanup rule: query count of rows referencing bucket+path before deletion; delete from storage only if count is zero
- [Sequential] Apply orphan check on PATCH /materials/{id} file replacement
- [Sequential] Apply orphan check on DELETE /materials/{id}

### Notification Handling (Optional)
- [Conditional] Implement learning_material_uploaded notification for each enrolled student on upload success
- [Conditional] Implement learning_material_deleted notification for each enrolled student on delete
- [Conditional] Ensure notifications are best-effort side effect (don't fail upload/delete if notification fails)

---

## Detailed Breakdown

### Data Model Requirements

The data model must support answering two core questions: "For this class, which files has the teacher posted?" and "Is this student allowed to see them?" The minimum entities required are Users, Subjects, Sections, Enrollments, and Learning Materials. The critical relationship to establish upfront is that a material row represents a (file, subject, section) pair, not a file itself. This means if one uploaded file is assigned to two classes, you get two rows pointing at the same stored object, each independently editable and deletable per class. This design allows educators to later reassign or delete a material from one class without affecting the same file in another class. Indexing should cover educator_id, subject_id, section_id, updated_at (for sorting/recency), and a composite index on (storage_bucket, storage_path) to efficiently check whether a stored file is still referenced by any row before deleting it from storage.

### Row-Level Access Rules

Two access rules should be enforced at the database layer as defense-in-depth, in addition to application-level checks. For educators, they can read, write, and delete only rows where educator_id matches their own ID. For students, they can read a row only if is_active is true AND an active enrollment exists linking that student to that row's educator_id, subject_id, and (typically) section_id. Neither role should be able to mutate rows outside these bounds, and students should never receive write access at all.

### Storage Strategy

Storage should use a single dedicated bucket for learning materials, not shared with other upload features, to simplify lifecycle rules and access policies. The path convention should namespace by uploader and make the object name unguessable and collision-proof using the format {educatorId}/{unixTimestampMs}-{uuid}-{sanitized-original-filename}.{ext}. Filename sanitization must strip to [a-zA-Z0-9-_], collapse repeated separators, and convert to lowercase, never trusting raw filenames due to path traversal and Unicode risks. The random UUID plus timestamp guarantees no collisions even with identical filenames at the same millisecond.

The bucket must not be publicly accessible. Files should only be reachable through server-generated short-lived signed URLs (60 seconds is the reasonable default), issued only after access checks pass. This approach means the storage layer's permissions can be locked down entirely to backend/service credentials, as the API route serves as the sole gatekeeper. Uploads and deletes should run under elevated/service credentials, not end-user credentials, eliminating the need to grant students or educators direct storage permissions.

### Input Validation Requirements

Validation must occur server-side regardless of client-side convenience filters. Selection keys should be parsed as an array of "<subjectId>:<sectionId>" strings with a minimum of one entry. Files count should be a minimum of one entry for upload (zero for edit/replace-file endpoint). The extension allow-list must check against a fixed set including pptx, ppsx, ppt, pdf, docx, doc, rtf, rejecting anything else with a clear per-file error message. Per-file size cap of 20 MB must be enforced with rejection messages naming the specific offending file. For selection keys, parse as a compound key rather than two separate form fields to keep UI simple and avoid mismatched subject/section pairs. Ownership validation must occur before any I/O: for every selectionKey (or existing material being edited), confirm the target subject/section belongs to the requesting educator's own course load before uploading or writing any database row.

### API Endpoints Specification

Six endpoints are required. All routes require authenticated sessions with explicit role resolution returning 401 (not logged in) versus 403 (logged in, wrong role) distinctly. The POST /materials endpoint accepts multipart/form-data with selectionKeys (repeated field) and files (repeated field). Processing order matters: parse/validate payload shape, validate every file's extension and size (fail entire request if any invalid), validate every selectionKey resolves to a subject/section the caller owns, upload each unique file to storage once (building path per convention), insert one database row per (file, selectionKey) pair reusing the same storage_path, optionally fan out notifications to enrolled students, and return updated grouped list for UI update without second round-trip.

The PATCH /materials/{id} endpoint handles reassignment and file replacement. Fetch existing row scoped to caller's educator_id (not-found/not-owned should look identical), validate new target belongs to caller, upload replacement file if provided (validate, upload to new path, update row metadata), then check if old storage object is orphaned and remove if so, update subject_id/section_id if reassigned. The DELETE /materials/{id} endpoint deletes the row scoped to caller's educator_id, sends removal notifications, and cleans up underlying storage if orphaned. The GET /materials/{id}/file endpoint checks access (admin, owning educator, or enrolled student), generates signed URL with short expiry, responds with HTTP redirect (or proxy only if specific reasons exist like virus scanning), and supports ?download=1 flag for force-attachment behavior.

### Orphan Cleanup Rule

This is the single most important correctness rule. Because multiple rows can point at the same (storage_bucket, storage_path) pair, never delete a storage object just because one row referencing it was deleted or edited away. Before removing the object, query whether any other row still references that exact bucket+path. Only delete from storage if the count is zero. Skipping this rule either leaves orphaned files piling up in storage forever or deletes files still being served to different classes. This check must be applied on both PATCH (file replacement) and DELETE operations.

### Frontend Flow

The upload trigger appears as an "Upload Files" button on the materials list, opening a modal. A secondary trigger (per-row "Add File") can open the same modal pre-populated with that row's subject/section already checked, implemented as an optional prop rather than a separate component. On open, fetch target options once and reuse them. The file picker uses a single hidden native file-input control with accept multiple and allowed extensions restriction, wrapped in a clickable/droppable container giving both click-to-browse and native drag-and-drop. Merge newly picked/dropped files into queued state keyed by name and size so re-adding the same file doesn't duplicate it. Allow user removal of individual queued files and clear the file-input value after each selection to ensure future change events fire.

The target checklist is a plain multi-select checkbox list bound to a form array field. Submit builds a multipart form payload, shows loading state, disables submit until at least one file and one target are selected, replaces local list state with response (avoiding second fetch), shows success toast and closes modal, or surfaces specific error messages. Edit and delete reuse the same modal patterns with a unified confirmation dialog for all delete flows. View and Download are anchor links/redirects to the signed-URL endpoint with ?download=1 flag for download behavior.

### UI Design: File Display with Type-Specific Icons

The visual presentation of learning materials must follow the established design pattern from the platform's Recent Uploads section, providing users with immediate visual recognition of file types through appropriate icons or illustrations. The structural foundation for all file display components must be derived from the `demo1/public-profile/profiles/default.html` template, which defines the container layouts, styling hooks, and responsive patterns used throughout the platform.

When displaying uploaded files in the materials table (or any list/grid view), each file entry must render an icon or illustration that visually communicates the file type based on its extension. For PDF files, a PDF-specific icon should be displayed. For PowerPoint files (.ppt, .pptx, .ppsx), a PowerPoint icon or slide-themed illustration should be used. For Word documents (.doc, .docx), a Word document icon should appear. For Rich Text Format files (.rtf), an appropriate text document icon should be displayed. These icons should match the visual style and design language established in the Recent Uploads section to ensure visual consistency across the platform.

The display should also include the file name prominently, with file size shown as secondary information, maintaining the same layout hierarchy and spacing patterns from the Recent Uploads reference. The icons or illustrations should be sized consistently with other file displays in the platform and should update reactively when file metadata changes (such as after an edit operation that swaps the underlying file type).

If a file type is not recognized or falls outside the supported list, a default document or fallback icon should be displayed to prevent broken visual states. The icon mapping logic should be centralized to ensure consistent rendering across all views where materials appear (materials list, dashboard preview, notifications). This design approach eliminates reliance on file extension text alone, making the interface more scannable and visually intuitive for users who may quickly want to identify file types when reviewing multiple materials.

### Deliberate Exclusions

This feature explicitly excludes built-in file preview (View and Download both hand off to browser's native handling; adding in-app PDF/Office viewer is a separate larger feature requiring embedded viewer library and auth-scoped iframe sources). No versioning is implemented — editing a material's file overwrites the prior one and the old object is deleted once orphaned. If version history is needed later, add a separate learning_material_versions table rather than retrofitting. No metadata beyond a target class and file exists — no title, description, due date, or category. These can be added as nullable columns without redesigning the row-per-target model.

### Edge Cases to Test

Five critical edge cases must be tested. Uploading the same physical file to two different subject/section targets in one submission should store it once but produce two independent, independently-deletable rows. Deleting one of those rows should not remove the storage object while the other row still references it, but should remove it once the last referencing row is gone. Editing a material to swap its file should clean up the old storage object (if orphaned) and update all row metadata (extension, mime type, size, filename) together, not just the storage path. Uploading a file with unsupported extension or just over the size cap should error naming the specific file and reason with no partial upload. A request whose selectionKey maps to a subject/section the caller doesn't teach must be rejected before any storage or database write. A student attempting to hit the file-access endpoint for a class they're not enrolled in (or no longer actively enrolled) must be denied without returning a signed URL. Re-selecting the identical file in the picker right after removing it from the queue must still fire the change event (requires resetting input value after each selection). An educator with zero subjects/sections assigned should see an empty-state message in the upload modal's target checklist, not silently allow submission with no valid targets.

### UI Edge Cases to Test

The file-type icon rendering must handle several display scenarios. When a file is uploaded with an extension that maps to a supported file type, the correct icon must render immediately in the materials list. When a file is edited to replace the underlying file with a different type (e.g., swapping a PDF for a PowerPoint), the icon must update to reflect the new file type without requiring a page refresh. When displaying files from the Recent Uploads section alongside Learning Materials, the icon styles and sizes must be visually consistent to avoid jarring UX. If the `default.html` template structure changes, the materials display should continue to function correctly or the integration should fail gracefully with clear errors. For files with extensions that are not recognized (should not occur due to validation, but could happen in data migration scenarios), a default fallback icon should appear. All icons must be accessible, including appropriate alt text or aria labels that describe the file type for screen readers.