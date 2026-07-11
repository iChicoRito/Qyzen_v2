# Objective
## Student and Educator Private Messaging System

---

## Description
Implement a private, one-to-one messaging system that enables direct communication between students and educators. The system must provide real-time conversation functionality without page refreshes, using an optimized data-fetching strategy. It must integrate seamlessly with the existing notification and chat interface components, reusing the exact current layout and design. Messages must support read receipts and status indicators, along with the ability to edit or delete sent messages with visible edit and deletion markers. Access to conversations must be restricted so that only students enrolled with a specific educator can exchange messages with that educator.

---

## Primary Objective
Build a functional, private direct-messaging system between individual students and their enrolled educators, integrated into the existing interface without modifying the current design.

---

## Secondary Objectives
- Create and migrate the database table to support private educator-student messaging.
- Implement an Inbox notification tab that displays message notifications and opens the conversation drawer when clicked.
- Deliver a real-time chat experience that updates without manual page refresh.
- Display functional read receipts and sent indicators on all messages.
- Allow users to edit and delete their messages, with clear visual indicators showing when a message has been edited or deleted.
- Restrict messaging so that only students enrolled with an educator can initiate or participate in a conversation with that educator.

---

## Success Criteria
- A new database table for private messaging is created and migrated successfully.
- The existing group chat implementation remains untouched and non-functional.
- The Inbox tab under Notifications displays message notifications correctly.
- Clicking an Inbox notification opens the chat drawer to the relevant conversation.
- The Chat Icon next to the Notification Icon opens the chat box containing the conversation.
- The chat box updates with new messages without requiring a page refresh.
- Each message shows a functional sent or read status indicator.
- Users can edit their messages; edited messages display an edit indicator.
- Users can delete their messages; deleted messages display a deletion indicator.
- Only enrolled student-educator pairs can message each other.

---

## Constraints
- Use the exact existing layout, design, and UI components; do not recreate or alter them.
- Do not implement or activate the existing group chat functionality.
- The All notifications tab already works and must remain functional as the general notification view.

---

## Out of Scope
- Group chat functionality (explicitly not to be implemented at this stage).

---

## Context & Dependencies
- A group chat table currently exists but must remain inactive.
- The Notifications navigation template includes an "All" tab that is currently functional for general notifications.
- An Inbox tab already exists under Notifications and must be wired to the new private messaging system.
- A Chat Icon is already placed beside the Notification Icon and must trigger the chat box.

---

## Stated Assumptions
- The optimal approach for real-time updates, such as polling or an alternative, will be determined during implementation.

---

## Supporting Tasks

### Database
- Create and migrate a new table to store private educator-student messages.

### Notification Integration
- Wire the Inbox tab under Notifications to display message notifications.
- Ensure clicking an Inbox notification opens the chat drawer for that conversation.

### Chat Interface Integration
- Connect the Chat Icon beside the Notification Icon to open the chat box.

### Real-Time Messaging
- Implement an optimized data-fetching strategy so the chat box displays conversations without page refresh.
- Implement functional read receipts and sent indicators on messages.

### Message Actions
- Add the ability for users to edit their sent messages, with an indicator showing the message was edited.
- Add the ability for users to delete their sent messages, with an indicator showing the message was deleted.

### Access Control
- Enforce that only students enrolled with a specific educator can message that educator, and vice versa.