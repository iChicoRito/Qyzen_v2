# Objective
## Fix Notification and Chat UX Flow to Open Conversation Directly

---

## Description
The current user experience for the Notification (Inbox) and Chat feature contains a friction point in the message interaction flow. When a user receives a message notification and clicks the chat icon, a drawer opens displaying the message list. The user must then click the specific message to open the conversation, which also closes the drawer. If the user clicks the message icon again, it only reopens the conversation drawer rather than taking them directly to the conversation. This multi-step interaction creates unnecessary hassle. The objective is to streamline this flow so that clicking a specific message immediately opens the conversation with that person, eliminating the redundant drawer-open-and-close behavior.

---

## Primary Objective
Modify the chat interaction so that clicking a message notification directly opens the conversation view with the relevant person, bypassing the intermediate step of opening and closing the drawer.

---

## Success Criteria
- Clicking a message notification opens the conversation directly without requiring the drawer to open first
- The user is taken immediately to the conversation view upon clicking a specific message
- The redundant drawer open-and-close behavior is eliminated from the flow