# Objective
## Notification Workflow Implementation Guide

---

## Description
Build a complete notification workflow system that handles the creation, addressing, delivery, and read-state management of notifications across educator and student roles in the Qyzen platform. The system must generate one stored message per recipient for every qualifying event, resolve recipient lists from enrollment and ownership records on the server, deliver messages live through role-specific subscriptions, and enforce strict owner-scoping for all read operations. The implementation must treat notification sending as a best-effort side process that never blocks or undoes the primary action that triggered it.

---

## Primary Objective
Build a functional notification workflow that creates one message per recipient for triggered events, resolves recipient lists from server-side enrollment and ownership records, delivers messages live through filtered subscriptions, and manages read states with strict owner-scoping.

---

## Secondary Objectives
- Define and implement a fixed set of event types that trigger notifications for educator-to-student and student-to-educator directions
- Derive recipient lists from active enrollment records for class-wide educator events and from assessment ownership for quiz submissions
- Save messages as a best-effort step after the triggering action succeeds, catching and logging failures without blocking the main action
- Provide live delivery through per-recipient subscriptions that reload latest messages and unread counts on insert or change
- Enable single-message marking as read and bulk mark-all-as-read operations filtered to the signed-in user's messages

---

## Success Criteria
- The notification record stores recipient, actor, event type, title, message, link, context ids, metadata bag, read state with timestamp, and created/updated timestamps
- Educator class-wide events resolve recipients by looking up active enrollments for that educator and subject, creating one message per distinct student
- Educator single-student events for enrollment changes and retake updates message only the affected student
- Student quiz-submission events create a single message to the assessment's owning educator only
- Active enrollment filtering excludes inactive or removed students from class-wide notifications
- Notifications are saved after the main action succeeds, with any failure caught and logged without affecting the primary operation
- Each recipient's session maintains a live subscription filtered to their own recipient id
- Subscriptions reload latest messages and unread count on insert or change for that recipient
- Unread count reflects the number of the user's own messages still marked unread
- Opening a single message marks that message as read with a read timestamp
- Mark all read clears all unread messages for the current user
- All read, count, and mark operations are filtered to the signed-in user's own messages
- Only educators and students have notification bells and open subscriptions

---

## Constraints
- Notifications are one stored message per recipient; class-wide events require saving many messages, never one shared message
- Recipient lists must be derived from enrollment and ownership records on the trusted side and never supplied by the browser
- Notifying is best-effort and happens after the real action succeeds; it must never block or undo the triggering action if it fails
- Reads are owner-scoped and every query must be filtered to the current user's own messages
- The event-type list must remain fixed and shared so triggers and display logic use consistent names
- De-duplicate recipients so a student enrolled twice under the same subject is not messaged twice
- Subscriptions must be torn down when the session or user changes to prevent message bleed across accounts
- Only roles with a bell (educators and students) may open notification subscriptions

---

## Out of Scope
- The on-screen design and visual appearance of the notification bell and notification list
- Building the notification UI components
- Implementing the front-end display styling
- Admin participation in the notification system

---

## Context & Dependencies
- The notification system serves both educators and students in the Qyzen platform
- Notifications are triggered by real actions including assessment creation, updates, and deletion; learning material uploads and deletion; quiz item creation, bulk-upload, updates, and deletion; enrollment creation, updates, and deletion; retake updates; and quiz submissions
- Educator actions fan out to actively enrolled students; the sole student trigger is quiz submission, which goes to the assessment's educator
- The notification workflow is distinct from the on-screen bell interface which already exists
- Active enrollments determine which students receive class-wide notifications
- The system must handle events where context ids may be empty, such as deleted assessments

---

## Stated Assumptions
- The starting point occurs when an action triggers a notification, and the system must determine who should receive it
- Admins do not take part in the notification bell system
- A student who drops a class with inactive enrollment stops receiving class-wide notifications from that point forward
- Best-effort delivery means notification failures are logged and quietly ignored, never undoing or blocking the real action

---

## Stakeholders
- Educators who trigger class-wide notifications and receive quiz submission alerts
- Students who receive notifications about assessments, materials, enrollment, and retakes
- Developers who implement the notification workflow according to the blueprint
- System administrators who may need to investigate logged notification failures

---

## Supporting Tasks

### Phase 1: Notification Record
- [Sequential] Design the notification record shape with recipient id and actor id fields
- [Sequential] Store event type from a fixed set of values
- [Sequential] Include title and message text fields
- [Sequential] Store link path for navigation when opened
- [Sequential] Store context ids for related assessment, subject, and section with allowance for empty values
- [Sequential] Include a metadata bag for flexible display details
- [Sequential] Store read state flag, read timestamp, and created/updated timestamps

### Phase 2: Addressing Rules
- [Sequential] Implement educator-to-enrolled-students direction for all educator events except quiz submission
- [Sequential] Resolve recipients by looking up active enrollments for that educator and subject
- [Sequential] Create one message per distinct student from the resolved recipient list
- [Sequential] De-duplicate recipients to prevent duplicate messages for students enrolled twice under the same subject
- [Sequential] Implement class-wide events for assessment changes, quiz-item changes, and learning-material changes
- [Sequential] Implement single-student events for enrollment changes and retake updates targeting only the affected student
- [Sequential] Implement student-to-owning-educator direction for quiz-submission events only
- [Sequential] Filter only active enrollments to receive class-wide messages
- [Sequential] Ensure inactive or removed students drop out of recipient lists immediately

### Phase 3: Event Triggers
- [Sequential] Create notifications when an assessment is created, updated, or deleted, messaging active students with appropriate wording and linking to assessments list
- [Conditional] On assessment deletion, omit the assessment id from context since it no longer exists
- [Sequential] Create notifications when quiz items are added, bulk-uploaded, updated, or deleted, including the count in bulk action messages and linking to assessments list
- [Sequential] Create notifications when learning material is uploaded or deleted, including the file count in upload messages and linking to materials page
- [Sequential] Create notifications when an enrollment is created, updated, or removed, messaging the affected student with enrollment status and linking to assessments list
- [Sequential] Create notifications when a retake is updated, messaging the affected student with their new retake count or removal notice and linking to assessments list
- [Sequential] Create a notification when a quiz is submitted, messaging the assessment's educator with student name, assessment, and subject, linking to the educator's scores page

### Phase 4: Saving Messages
- [Sequential] Build the full list of resolved recipient messages after the triggering action succeeds
- [Sequential] Save all messages together as a batch operation
- [Sequential] Stamp all messages as unread with fresh created and updated timestamps
- [Sequential] Treat saving an empty list as a no-op
- [Sequential] Wrap the entire notification saving step so any failure is caught and logged
- [Sequential] Prevent notification failures from being thrown or propagated to the caller
- [Sequential] Ensure the main action has already succeeded on its own before the notification step runs

### Phase 5: Delivery to Recipient
- [Sequential] Maintain a live subscription for each recipient's session to notification changes
- [Sequential] Filter each subscription to the recipient's own id only
- [Sequential] Reload the latest messages and unread count on any insert or change for that recipient
- [Sequential] Limit the reloaded message set to a small recent set
- [Sequential] Open subscriptions only for educators and students who have notification bells
- [Sequential] Tear down subscriptions when the session or user changes to prevent message bleed

### Phase 6: Reading and Unread Counts
- [Sequential] Compute unread count as the number of the user's own messages still marked unread
- [Sequential] Display the user's newest messages in a recent list, most recent first, capped to a small limit
- [Sequential] Mark a single message as read when opened, setting the read flag and read timestamp
- [Sequential] Mark all unread messages as read when the mark-all-read action is triggered
- [Sequential] Filter every read, count, and mark operation by the signed-in user's own messages
- [Sequential] Scope all operations to recipient id on every query

---

## Detailed Breakdown

### Notification Record Structure
The notification record is the shape of one stored message. It stores the recipient and actor identifiers to track who the message is for and who triggered it. The event type is selected from a fixed set of values that the system recognizes. Title and message text fields hold the human-readable content. The link path tells the application where to navigate when the recipient opens the notification. Context ids store the related assessment, subject, and section, with any of these allowed to be empty when the related entity no longer exists. A metadata bag holds flexible display details including assessment code, subject and section names, student name, file name or count, question count, retake count, and enrollment status. The read state includes a flag, a read timestamp, and created and updated timestamps.

### Addressing Direction Rules
The system has two fixed notification directions that never cross. Educator-to-student direction applies to every educator event except quiz submission. The system resolves recipients by looking up active enrollments for that educator and subject, creating one message per distinct student. The recipient list is derived from enrollment records on the trusted side and never supplied by the browser. Class-wide events target the entire subject's class and include assessment changes, quiz-item changes, and learning-material changes. Two events target a single student: enrollment changes and retake updates. Student-to-educator direction applies only to the quiz-submission event, creating a single message for the assessment's educator. A student can trigger only the quiz-submission event, and it can reach only that assessment's educator. Only active enrollments receive class-wide messages; inactive or removed students drop out of the recipient list immediately.

#### Edge Cases
- A student enrolled twice under the same subject must be de-duplicated to prevent receiving duplicate messages
- On assessment deletion, the assessment id must be omitted from context since the entity no longer exists
- Bulk uploads must include the file or question count in the message
- Enrollment status changes must be included in the message for enrollment events

### Event Trigger Definitions
Each event trigger creates notifications with specific wording and linking behavior. Assessment creation, update, and deletion messages tell active students the assessment is available, changed, or gone, linking to the assessments list. Quiz item addition, bulk-upload, update, and deletion messages tell students the questions changed, with bulk actions including the count, linking to the assessments list. Learning material upload and deletion messages tell students new material is available or was removed, with uploads of several files including the count, linking to the materials page. Enrollment creation, update, and removal messages tell the affected student about their enrollment status, linking to the assessments list. Retake update messages tell the affected student their new retake count or that extra access was removed, linking to the assessments list. Quiz submission messages tell the assessment's educator which student submitted which assessment for which subject, including the student's name and linking to the educator's scores page.

### Best-Effort Saving Process
The notification saving process runs as a separate step after the triggering action succeeds. The system builds the full list of recipient messages, then saves them together as a batch. Saving an empty list is a no-op. All messages are stamped as unread with fresh created and updated times. The entire notify step is wrapped so any failure is caught and logged without being thrown. The notification failure never undoes or blocks the real action. The main action such as assessment save, material upload, or quiz submission must already have succeeded on its own before the notification step runs. Notification saving is not interleaved into the middle of the main action's success path.

### Live Delivery Subscription Model
Each recipient's session maintains a live subscription to notification changes. The subscription is filtered to the recipient's own id only, meaning a person only listens for messages addressed to them. On any insert or change for that recipient, the subscription reloads the latest messages and the unread count. The reloaded message set is limited to a small recent set. Subscriptions are opened only for roles that actually have a notification bell, namely educators and students. Subscriptions are torn down when the session or user changes so messages never bleed across accounts. The subscription filter serves as a correctness boundary, not merely an optimization.

### Read and Unread Count Management
The unread count is computed as the number of the user's own messages still marked unread. The recent list shows the user's newest messages, most recent first, capped to a small limit. Opening a single notification marks that one message as read, setting the read flag and a read timestamp. The mark-all-read action clears all of the user's unread messages at once. Every read, count, and mark operation is filtered to the signed-in user's own messages. All operations are scoped by recipient id on every query to prevent one user from ever reading or clearing another user's messages.