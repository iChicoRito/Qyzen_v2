# Objective
## Assessment Interface Refinements: Question Format, UI Hierarchy, and Save Indicator Removal

---

## Description
Refine the student assessment interface by standardizing the question presentation format, enhancing the visual hierarchy and appeal of the top navigation bar (containing timer, warnings, and progress), and removing the persistent save confirmation indicator while preserving the underlying auto-save functionality. These changes aim to improve clarity, visual balance, and user experience without introducing new features.

---

## Primary Objective
Standardize the question display format to follow a consistent "Question No. X" structure, redesign the top navigation bar with centered timer, warnings, and progress elements for improved hierarchy and visual appeal, and eliminate the save progress indicator while keeping auto-save operational.

---

## Secondary Objectives
- Format every question with a consistent header structure: "Question No. [number]" followed by the question text on a new line, removing any other labels or tags.
- Redesign the top navigation bar to center the timer, warning counter, and progress bar, creating a visually balanced and hierarchically organized interface that is clean yet appealing.
- Remove all visual save indicators (e.g., "Saved" toast messages, status badges, or animation cues) from the assessment interface while maintaining the background auto-save mechanism.

---

## Success Criteria
- Every question displays the label "Question No. X" (where X is the sequential number) above the question text, with no additional type tags or extraneous labels.
- The top navigation bar is centered, featuring a well-structured hierarchy of timer (remaining time), warning count, and progress bar, with improved visual design (e.g., spacing, typography, subtle styling) that remains simple but engaging.
- No save confirmation messages, icons, or indicators appear anywhere on the assessment page during or after auto-save operations.
- Auto-save continues to function silently in the background, persisting student answers without any visible feedback.

---

## Constraints
- The save functionality must remain fully operational despite the removal of its visual indicator.
- The timer must continue to display remaining time in hours:minutes:seconds and change color as time depletes (green → yellow → red).
- The warning count must continue to display the number of warnings remaining.
- The progress bar must accurately reflect the student's completion status (e.g., "Question X of Y").

---

## Detailed Breakdown

### Question Format Standardization
Modify the question rendering logic to display each question with a consistent, clean header. Remove all type labels (e.g., "Multiple Choice," "Identification") and tags from the question card. The format should be:

```
Question No. 1
[Question text appears here]
```

This applies to all question types, creating a uniform and distraction-free presentation. The question number should increment sequentially based on the shuffled order if shuffling is enabled.

### Top Navigation Bar Redesign
Redesign the fixed/sticky top bar to achieve a centered, balanced layout with clear visual hierarchy. The bar should contain:

- **Timer** (remaining time) positioned centrally or as a prominent element
- **Warning count** displayed alongside the timer
- **Progress indicator** (e.g., "Question 3 of 10" or a visual progress bar)

The design should elevate the current implementation from "too plain" to "simple yet appealing" through:
- Proper spacing and alignment of elements
- Clear typographic hierarchy (size, weight, color)
- Subtle visual enhancements (e.g., background treatment, borders, or shadows that unify the bar)
- A visual structure that guides the eye naturally to the most important information (time remaining, then progress, then warnings)

The bar should remain fixed/sticky at the top of the viewport and be visually distinct from the question content area.

### Save Indicator Removal
Remove all user-visible save confirmation cues from the assessment interface, including:
- Toast or snackbar notifications that appear after auto-save
- "Saved" status text or badges near the progress bar
- Any animation or icon indicating a save operation is in progress or completed

The auto-save mechanism itself must continue to function as before, capturing answers a short moment after the student stops typing or selecting, and on the manual Save Progress button (if retained). The only acceptable feedback may be a silent background operation with no visual disturbance to the student.

---

## Context & Dependencies
- The assessment interface currently displays a full question label including type (e.g., "Multiple Choice - Question 1").
- The top bar currently shows timer and warnings but lacks a centered, hierarchical design with progress integration.
- The save indicator appears as a visible confirmation message each time auto-save triggers.