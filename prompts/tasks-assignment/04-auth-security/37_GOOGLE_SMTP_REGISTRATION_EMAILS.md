# Objective
## Google SMTP Integration for User Registration Email Notifications

---

## Description
The current user registration process in the admin panel creates user accounts without sending any email confirmation. The objective is to integrate Google’s SMTP service so that a confirmation email is automatically sent to users upon registration. This applies to both individual manual registration and bulk user uploads. To maintain performance during bulk operations, the upload will process users in chunks with rest intervals rather than as a single continuous operation. The provided Gmail account credentials will be used to authenticate with Google’s SMTP server.

---

## Primary Objective
Integrate Google SMTP email sending into the admin user registration flow so that a confirmation email is dispatched whenever users are registered manually or via bulk upload.

---

## Secondary Objectives
- Process bulk user uploads in optimized chunks with rest intervals to avoid performance issues.
- Utilize the specified Gmail account and app password for SMTP authentication.

---

## Constraints
- Bulk upload must not process all users in a single operation; it must use chunked processing with rest periods.
- SMTP authentication must use the provided credentials:
  - App Name: Qyzen_v2
  - App Password: fqfg frpu gfum waxj
  - Gmail: adrianne.marksalunga@gmail.com

---

## Context & Dependencies
- A user registration system already exists in the admin panel but currently lacks email confirmation functionality.
- The system supports both manual single-user registration and bulk user upload.

---

## Supporting Tasks

### SMTP Configuration
- Configure the application to send emails via Google’s SMTP server using the provided Gmail address and app password.

### Email Trigger Integration
- Trigger a confirmation email when a user is registered manually through the admin panel.
- Trigger a confirmation email for each user processed during a bulk upload.

### Bulk Upload Optimization
- Implement chunked processing for bulk user uploads.
- Introduce rest intervals between chunks to prevent server overload and optimize performance.