# Objective
## Display Profile Icons in User Tables and Implement Enhanced Profile Modals Across Admin and Educator Sides

---

## Description
The admin users table at `/admin/users` already displays a profile icon for each user. This existing implementation should be extended to the educator side at `/educator/enrollment`, where the same profile icon must appear in the table. When a user row is clicked or viewed in either context, a modal currently opens. The modal’s user interface should be improved by adopting the profile card layout found in the reference file `metronic-tailwind-html-demos/dist/html/demo1/network/user-cards/team-crew.html`. The new modal layout must display the user’s name, student number, and email address directly below the profile picture, while preserving the existing UI for the remaining fields: User ID, Type, Roles, Status, and Verified. The overall goal is to achieve a consistent, visually improved profile display across both admin and educator interfaces.

---

## Primary Objective
Implement a consistent, enhanced profile modal with a profile card layout for both the admin users table and the educator enrollment table, triggered by clicking a user row.

---

## Secondary Objectives
- Display the user profile icon within the table on the educator enrollment page.
- Integrate the profile card layout from the specified reference file into the existing modal, positioning the name, student number, and email below the profile picture.
- Retain the current UI and display of the User ID, Type, Roles, Status, and Verified fields within the modal.

---

## Success Criteria
- The educator enrollment table at `/educator/enrollment` shows each user’s profile icon.
- Clicking a user row in either the admin users table or the educator enrollment table opens an enhanced modal.
- The modal’s profile card area displays the user’s name, student number, and email address below the profile picture, matching the reference layout.
- The User ID, Type, Roles, Status, and Verified fields remain visible in their existing UI.

---

## Constraints
- Use the profile card layout from `metronic-tailwind-html-demos/dist/html/demo1/network/user-cards/team-crew.html` as the reference.

---

## Context & Dependencies
- The admin side at `/admin/users` already has a functioning profile icon in the table and an existing working modal.
- The educator enrollment page is located at `/educator/enrollment`.
- The profile card UI reference exists at the specified file path within the project.

---

## Supporting Tasks

### Admin Side Enhancements
- Modify the existing modal on `/admin/users` to adopt the profile card layout from the reference HTML file, placing the name, student number, and email below the profile picture.
- Ensure the User ID, Type, Roles, Status, and Verified fields remain in their current UI within the same modal.

### Educator Side Implementation
- Add the profile icon to the user table on the `/educator/enrollment` page.
- Implement a modal trigger on user row click that opens an enhanced modal identical in layout to the admin side.
- Populate the profile card area with the name, student number, and email, and retain the User ID, Type, Roles, Status, and Verified fields in their existing UI.