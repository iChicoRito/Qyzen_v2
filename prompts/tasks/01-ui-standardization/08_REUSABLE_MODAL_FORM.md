# Objective
## Reusable Modal Form

---

## Description
The objective is to create a reusable modal form component based on the existing modal implementation found at /admin/users for adding a new user. This modal must be reusable across the entire system for consistent user experience. The add functionality at /admin/roles must be converted from a separate page into a modal. This modal approach must apply to all add and edit functionalities everywhere in the system, not limited to specific pages. Additionally, any checkbox used within these modals must follow the same layout and design as the checkboxes found on the /admin/users page. At /admin/permissions, adding a new permission must also use a modal, including the bulk add feature which utilizes a form repeater. The form repeater within the bulk add modal must function properly.

---

## Primary Objective
Create a reusable modal form component based on the /admin/users reference implementation to standardize form interactions across the entire system.

---

## Secondary Objectives
- Convert the add new roles functionality at /admin/roles from a separate page to a modal
- Apply the modal approach to all add and edit functionalities system-wide for better UX
- Standardize all checkbox layouts to match the /admin/users checkbox design
- Convert the add new permission functionality at /admin/permissions to a modal
- Implement bulk add permissions using a modal with a form repeater and ensure the form repeater works properly

---

## Supporting Tasks

### Reusable Modal Component
- Extract the modal design and behavior from /admin/users (used for adding new users)
- Make the modal reusable so it can be implemented anywhere in the system

### Add Functionality Migration
- Convert the add new roles form at /admin/roles from a full page into a modal
- Apply this modal pattern to all add functionalities across every page in the system

### Edit Functionality Migration
- Convert all existing edit functionalities to use modals instead of separate pages
- Ensure this applies universally, not only to specific pages

### Checkbox Standardization
- Identify the checkbox layout and design used at /admin/users
- Replicate that exact checkbox layout in every modal form that includes checkboxes

### Permissions Modal Integration
- Convert the add new permission functionality at /admin/permissions to use a modal
- Implement the bulk add permissions feature within a modal
- Use a form repeater inside the bulk add modal
- Verify and ensure the form repeater functions correctly