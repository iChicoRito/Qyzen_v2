# Objective: Changes on form validation

---

## Description
Modify the form validation behavior across the application. Currently, validation works correctly but when a user submits a form with empty or invalid fields, the submit button displays a spinner indicating submission is in progress. The desired behavior is to first display validation feedback (red text below the form) when invalid or blank inputs exist, and only proceed with submission (including spinner display) when all fields are valid.

---

## Primary Objective
Change form validation so that validation feedback (red text below the form) displays first when invalid or blank inputs are submitted, and the submit button (including spinner display) only activates after all fields pass validation.

---

## Secondary Objectives
- Apply the validation behavior change across all forms in the application
- Ensure the current validation functionality remains intact

---

## Supporting Tasks

### Validation Feedback Display
- Show validation feedback (red text below the form) when the submit button is clicked with invalid or blank input fields

### Submission Behavior
- Prevent the submit button from showing the spinner or initiating submission when invalid or blank inputs exist
- Allow the submit button to display the spinner and proceed with submission only when all fields are valid

---

## Detailed Breakdown

### Current State
The form validation is working correctly with no issues, but when the user submits with empty fields, the button shows a spinner indicating submission is in progress.

### Desired State
The validation feedback (red text below the form) must display first upon submission attempt with invalid or blank inputs. The spinner button and submission process should only trigger after validation passes with no wrong fields.

### Execution Order
The validation feedback check must occur and be displayed before the submission process (including spinner display) begins.