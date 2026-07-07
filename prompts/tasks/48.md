# Objective
## Full-Stack CRUD Audit and Repair Across All User Roles

---

## Description
Conduct a comprehensive audit of all Create, Read, Update, and Delete operations across the entire codebase for the admin, educator, and student roles. The immediate trigger is a specific bug on the educator side: after successfully adding a first section, any subsequent attempt to add another section results in a silent reload with no data persisted and no error message. This task expands beyond that single bug to verify that every CRUD interaction on every page, within every module, for every user role functions correctly end-to-end. The goal is to identify, document, and fix every broken operation, ensuring that all requests are properly processed by the backend and server-side logic.

---

## Primary Objective
Verify and repair every CRUD operation across all pages and modules for admin, educator, and student roles to ensure each action successfully executes and persists data as intended.

---

## Secondary Objectives
- Identify and resolve the specific educator-side bug where only the first section addition succeeds and all subsequent attempts silently fail.
- Confirm that every administrative process and page functions correctly.
- Validate that all network requests across every role and module are transmitted and processed properly.
- Document all discovered discrepancies before applying fixes.

---

## Success Criteria
- A complete, itemized list of all broken or non-functional CRUD operations across the entire application.
- Every identified CRUD discrepancy is fixed and verified as functional.

---

## Constraints
- No changes to the user interface; modifications are restricted exclusively to backend and server-side code.

---

## Out of Scope
- Any UI adjustments, styling changes, or front-end visual modifications.

---

## Context & Dependencies
- The codebase supports three distinct user roles: admin, educator, and student.
- A specific, reproducible bug exists on the educator side where a subsequent section creation request triggers a page reload without saving the new data.

---

## Stakeholders
- Educators
- Admins
- Students

---

## Supporting Tasks

### Audit
- [Tag: Sequential] Scan every module and page for the admin role, testing all CRUD operations and recording any that fail.
- [Tag: Sequential] Scan every module and page for the educator role, testing all CRUD operations and recording any that fail, with special attention to the section-creation flow.
- [Tag: Sequential] Scan every module and page for the student role, testing all CRUD operations and recording any that fail.
- Verify that every network request in each module and process reaches the server and receives a valid response.

### Repair
- Fix all CRUD operations identified as non-functional during the audit.
- Specifically debug and resolve the educator-side issue where adding a second section causes a silent reload with no data saved.