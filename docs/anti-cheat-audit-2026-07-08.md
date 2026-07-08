# Anti-Cheat Audit — 2026-07-08

## Scope
Student quiz-taking view (`resources/views/student/take-quiz.blade.php`).

## Detections Catalogued

| Detection | Event/Mechanism | Pre-fix guard |
|---|---|---|
| Copy / Cut / Paste | `document copy/cut/paste` | Desktop only |
| Context menu | `document contextmenu` | Desktop only |
| Tab switch / minimize | `document visibilitychange` | Desktop only |
| Window blur | `window blur` | Desktop only |
| Mouse leaves page | `document mouseleave` | Desktop only |
| DevTools shortcuts | `document keydown` (F12, Ctrl+Shift+I/J/C, Ctrl+U) | Desktop only |
| DevTools open heuristic | `setInterval` outer/inner gap > 170 px | Desktop only |
| Split-screen / height reduction | — | **Not implemented** |

## Bugs Found

### Bug 1 — Split-screen not detected (reported trigger)
**Root cause:** The devtools gap heuristic (`outerHeight − innerHeight`) is constant when the user resizes the window, because both values decrease together. When a student drags the browser window shorter, `outerHeight` and `innerHeight` both shrink proportionally, keeping the gap at a steady ~80–120 px (browser chrome), which never exceeds the 170 px threshold. A dedicated `resize` listener comparing the live `outerHeight` against `screen.availHeight` is required.

### Bug 2 — Mobile fully unprotected
**Root cause:** All detection code is wrapped in a single `if (!isTouch)` guard. Events that work identically on mobile (`visibilitychange`, copy/paste) are unnecessarily excluded alongside the pointer/keyboard-only detections that genuinely shouldn't run on touch devices.

## Fixes Applied

- Added `window.addEventListener('resize', ...)`: fires a violation when `window.outerHeight < screen.availHeight * 0.55` (window covers less than 55 % of the available screen height). Uses `outerHeight` (OS window frame) so a mobile soft keyboard, which only shrinks `innerHeight`/`visualViewport`, does not produce a false positive.
- Moved `visibilitychange`, `copy/cut/paste`, and `contextmenu` outside the `!isTouch` guard so they run on both desktop and mobile.
- `violation()` / `cooldown` hoisted outside the `!isTouch` guard to support shared detections.
- Kept `blur`, `mouseleave`, keyboard shortcuts, and devtools heuristic inside `!isTouch` (correct for pointer devices only).

## Post-Fix Detection Matrix

| Detection | Desktop | Mobile |
|---|---|---|
| Copy / Cut / Paste | ✅ | ✅ |
| Context menu | ✅ | ✅ |
| Tab switch / minimize | ✅ | ✅ |
| Split-screen / height reduction | ✅ | ✅ (tablet) |
| Window blur | ✅ | — (too noisy on notifications) |
| Mouse leaves page | ✅ | — (no pointer) |
| DevTools shortcuts | ✅ | — (N/A) |
| DevTools open heuristic | ✅ | — (N/A) |
