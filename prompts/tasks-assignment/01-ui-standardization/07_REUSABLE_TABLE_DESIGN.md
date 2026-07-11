# Objective
## Standardize and Reusable Table Design/Layout

---

## Description
The objective is to create a reusable table component based on the existing design found at /admin/users. This table design must be applied consistently throughout the entire system. Each table row must include a three-dot icon for actions that triggers a context menu. Destructive actions triggered from the context menu must not execute instantly; instead, they must use SweetAlert for confirmation. If SweetAlert2 is not already installed or added, it must be installed or added to the project. The table must also include functional filtration mechanisms, specifically dropdowns and search, that target and filter specific data.

---

## Primary Objective
Create a standardized, reusable table component based on the /admin/users reference design to ensure consistent table layouts across the entire system.

---

## Secondary Objectives
- Implement a three-dot icon for row actions with a context menu
- Prevent instant destructive actions by requiring SweetAlert confirmation
- Install or add SweetAlert2 if it is not already present in the project
- Ensure filtration for specific targets works correctly, including dropdown filters and search functionality

---

## Supporting Tasks

### Reusable Table Component Implementation
- Create a reusable table component if technically feasible
- Base the table design entirely on the reference implementation at /admin/users
- Apply this consistent table design to every table across the whole system

### Action Menu
- Use a three-dot icon to represent available actions for each table row
- Attach a context menu that opens when the three-dot icon is interacted with

### Destructive Action Handling
- Prevent any destructive action from executing immediately
- Integrate SweetAlert to serve as a confirmation step before performing destructive actions
- Check for SweetAlert2 availability; install or add it if it is missing from the project

### Table Filtration
- Ensure filtration for specific targets works properly
- Implement dropdown filters that target and filter specific data
- Implement search functionality that targets and filters specific data