# Objective
## Fix Change Email Address Functionality for Google Accounts and Adjust Modal Width

---

## Description
The objective is to resolve a bug preventing users from successfully changing the email address associated with their Google account via the profile settings. When a user attempts to change the email address and clicks the continue button, the system enters a loading state indefinitely without completing the action or providing feedback. The task involves identifying and correcting the underlying error or bug that causes this unresponsive behavior. Additionally, the modal dialog used for this process must be restyled to not occupy the full width of the screen, conforming instead to the standard, reusable modal component width used elsewhere in the application.

---

## Primary Objective
Diagnose and fix the bug that causes the change email address flow for Google accounts to hang on loading after clicking continue, and adjust the modal's width to match the standard reusable modal style instead of displaying full width.

---

## Secondary Objectives
- Identify the root cause of the unresponsive loading state when changing a Google account's email address.
- Restyle the change email modal to use the application's standard, non-full-width modal dimensions.

---

## Success Criteria
- Clicking the continue button after entering a new email address for a Google account successfully initiates and completes the email change process without hanging.
- The change email modal renders with the same width as other reusable modals in the application, not at full width.