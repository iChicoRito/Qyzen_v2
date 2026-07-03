# Objective
## Enrollment UI/UX Improvement Objective

---

## Description
Improve the user interface and user experience of the `/educator/enrollment` page. The current page displays a table of enrolled students by subject/section, which is crowded and unorganized. The desired state is to display only the Subject/Section per table entry with a context menu containing a "View" option. Clicking "View" redirects to another page showing all students enrolled in that subject/section in a more organized format.

---

## Primary Objective
Improve the UI/UX of the `/educator/enrollment` page by reorganizing how enrolled student data is displayed.

---

## Secondary Objectives
- Change the display from showing all enrolled students to showing only Subject/Section per table
- Add a "View" option to the context menu
- Implement redirection to a dedicated page for viewing enrolled students per subject/section
- Organize the student list view more effectively than the current state

---

## Supporting Tasks

### Display Restructuring
- Modify the `/educator/enrollment` page to show only Subject/Section per table entry
- Remove the current display of all enrolled students in the main table

### Context Menu Enhancement
- Add a "View" menu item to the context menu for each Subject/Section entry

### Page Navigation
- Implement redirection to a separate page when "View" is clicked
- The redirected page should display all students enrolled in that specific subject/section

### Student List Organization
- Organize the student list on the redirected page
- Ensure the organized view is an improvement over the current crowded and unorganized state

---

## Detailed Breakdown

### Current State Assessment
The current `/educator/enrollment` page shows a table of enrolled students with their respective subject/section. The display is described as crowded and not organized.

### Desired State

#### Main Enrollment Page
- Display only Subject/Section per table entry
- Each entry should have a context menu with a "View" option

#### Student List Page
- Redirect to another page when "View" is clicked
- The redirected page should show all students enrolled in that subject/section
- The display should be more organized than the current state