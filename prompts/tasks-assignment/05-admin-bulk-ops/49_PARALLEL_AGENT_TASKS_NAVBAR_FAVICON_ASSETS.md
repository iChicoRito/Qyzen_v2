# Objective
## Parallel Agent Tasks for Navbar Cleanup, Favicon Update, Profile Image Symlink Removal, and Asset Loading Optimization

---

## Description
Four independent agents will execute separate front-end improvements concurrently to streamline the user interface, resolve a deployment issue, and optimize page load performance. The first agent will remove a non-functional interface element, the second will update a site-wide asset, the third will refactor an asset handling mechanism that breaks on a restricted hosting environment, and the fourth will implement conditional asset loading to prevent unused libraries and files from being fetched on pages where they are not needed. All four tasks are designed to run simultaneously without dependencies on each other. The expected outcome is a cleaner navigation bar, an updated browser tab icon, functional profile images in the deployed production environment, and faster page loads achieved by loading only the assets relevant to each specific page.

---

## Primary Objective
Execute four isolated front-end modifications in parallel to remove a redundant UI element, update the application favicon, eliminate the dependency on symbolic links for profile images, and ensure each page loads only the assets and libraries it actually requires.

---

## Secondary Objectives
- Remove the non-functional 4-grid icon from the navigation bar for all user roles.
- Replace the current favicon with the file located at `public\assets\img\favicon.ico`.
- Refactor the profile image serving mechanism to function without symbolic links, ensuring compatibility with a shared hosting plan that lacks SSH or terminal access.
- Restructure asset loading so that libraries and files are fetched conditionally, only on the specific pages where they are functionally required, and omitted everywhere else.

---

## Success Criteria
- The 4-grid icon is no longer rendered in the navbar beside the chat and profile icons for any user role.
- The browser tab displays the favicon sourced from `public\assets\img\favicon.ico`.
- Profile images display correctly in the deployed production environment without relying on symbolic links.
- Assets and libraries are loaded exclusively on the pages that require them. For example, the ApexCharts library loads only on pages that render charts, the FullCalendar library loads only on pages that display a calendar, and layout-specific or widget-specific scripts load only on pages where their corresponding components are active. Unused file-type icons, brand logos, avatar placeholders, and illustration files are not loaded on pages where they are absent from the rendered UI.

---

## Constraints
- The hosting provider's most economical plan does not grant SSH or terminal access.
- The current local development environment uses symbolic links for image access, which fails in production.
- Asset optimization must not break functionality on any page; libraries essential to a specific page must continue to load normally on that page.

---

## Context & Dependencies
- Agent 1, Agent 2, Agent 3, and Agent 4 must operate completely independently and simultaneously, not in a linear workflow.
- The 4-grid icon targeted for removal currently resides on the navbar adjacent to the chat icon and profile icon and possesses no associated functionality.
- The new favicon asset is already present in the project at `public\assets\img\favicon.ico`.
- Profile images are currently served via a symlink-based method that is incompatible with the production hosting environment.
- Server request logs from 2026-07-08 between 06:40:18 and 06:41:31 show that pages are loading assets not required for their specific content, such as dashboard pages fetching calendar and chart libraries, and various pages loading unused avatar images, brand logos, file-type icons, and illustration files.

---

## Supporting Tasks

### Agent 1: Navbar Cleanup
- [Tag: Sequential] Locate the navbar component code that renders the 4-grid icon beside the chat and profile icons.
- Remove the 4-grid icon element and its associated markup for all user roles, ensuring no visual regression or errors.

### Agent 2: Favicon Update
- [Tag: Sequential] Identify the current favicon reference in the application's global header or asset configuration.
- Replace the existing favicon reference with the file path `public\assets\img\favicon.ico`.

### Agent 3: Profile Image Symlink Removal
- [Tag: Sequential] Investigate the current profile image loading logic to understand how symbolic links are used in local mode.
- Determine if profile images can be served through a direct path or an alternative mechanism that does not require symbolic links.
- Implement a symlink-free solution for profile image serving that is fully compatible with the restricted production hosting environment.

### Agent 4: Conditional Asset Loading
- [Tag: Sequential] Audit each page template and its rendered components to map exactly which assets and libraries are functionally required.
- Refactor asset imports and script tags so that each library and static file is loaded only on pages where its corresponding component or feature is actively used, based on the page-level requirement mapping.
- Verify that essential assets remain available on their target pages while unused assets such as ApexCharts, FullCalendar, layout scripts, widget scripts, default avatars, file-type icons, brand logos, and illustrations no longer load on pages where they serve no purpose.