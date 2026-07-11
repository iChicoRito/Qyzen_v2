# Student Announcements Sidepanel Design

## Goal

Use a simple two-column announcement page. The narrow left panel contains a static scrollable timeline; the wide right panel keeps the existing announcement cards as a normal feed.

## Layout

- Use a responsive four-column grid on large screens: one column for the timeline and three columns for the card feed.
- Give the panels a clear boundary through the existing card surfaces, borders, and spacing.
- Keep the timeline panel independently scrollable.
- On smaller screens, stack the timeline above the card feed.

## Timeline Panel

- Render the announcements from the current server-paginated result set.
- Each timeline entry displays its date, audience, title, and a shortened plain-text preview.
- Timeline entries are display-only and are not clickable.

## Card Feed

- Render every existing announcement card in the current result set in the right panel.
- Do not redesign the announcement card content or styling.
- Show the existing empty state when no announcements are available.

## Data and Security

- Preserve the existing `Announcement::visibleTo(...)` query, authorization checks, eager loading, image authorization, ordering, and pagination.
- Pagination continues to bound the HTML payload and database work.

## Verification

- Update the focused announcement feature test to assert the one-column/three-column structure, static timeline, and complete card feed.
- Run the focused announcement test and the frontend production build.
- Verify the layout and selection interaction in both light and dark themes when browser access is available.

## Out of Scope

- Timeline selection, AJAX detail loading, read/unread tracking, search, filtering, and infinite scrolling.
