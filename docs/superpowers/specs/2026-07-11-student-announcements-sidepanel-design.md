# Student Announcements Sidepanel Design

## Goal

Replace the student announcement feed with a master-detail interface. The left panel contains a scrollable timeline of announcements; the right panel displays exactly one selected announcement. Announcement summaries and full cards must never be interleaved vertically.

## Layout

- Use a responsive two-column grid on large screens with a 35% timeline and 65% detail panel.
- Give the panels a clear boundary through the existing card surfaces, borders, and spacing.
- Keep the timeline panel independently scrollable and the detail panel stable while the user changes selection.
- On smaller screens, stack the panels while continuing to render only one detail card.

## Timeline Panel

- Render the announcements from the current server-paginated result set.
- Each announcement is a semantic button containing its date, audience, title, and a shortened plain-text preview.
- Mark the newest announcement selected on initial load.
- Apply a visible selected state and expose it with `aria-pressed`.

## Detail Panel

- Render one detail article per announcement in the current result set, but hide every article except the selected one.
- Reuse the existing announcement card content: author, relative timestamp, title, description, body, images, and subject or global-audience badge.
- Show the existing empty state when no announcements are available.

## Interaction

- Use a small native JavaScript click handler scoped to the announcement panel.
- Clicking a timeline button updates the selected styling and `aria-pressed` value, hides the previous article, and reveals the matching article immediately.
- No AJAX endpoint, client framework, or new dependency is needed because the current page already contains the bounded paginated result set.

## Data and Security

- Preserve the existing `Announcement::visibleTo(...)` query, authorization checks, eager loading, image authorization, ordering, and pagination.
- Pagination continues to bound the HTML payload and database work. Changing page selects that page's newest announcement by default.

## Verification

- Update the focused announcement feature test to assert the master-detail structure, 35/65 column classes, selection hooks, and single initially visible detail.
- Run the focused announcement test and the frontend production build.
- Verify the layout and selection interaction in both light and dark themes when browser access is available.

## Out of Scope

- Infinite scrolling, AJAX detail loading, read/unread tracking, search, filtering, and persistent selection across pages.
