# Objective
## Google Sign-In Integration with Existing Email-Based Authentication

---

## Description
The current Google Sign-In functionality in the authentication module is broken and returns an authorization error: "Missing required parameter: client_id (Error 400: invalid_request)." This needs to be repaired and properly configured. Separately, the admin-side registration flow is working correctly — when a new user is registered, the system emails the recipient their credentials. The goal is to enable Google Sign-In such that when a user attempts to log in via Google, if the Gmail address they use already exists in the database, authentication proceeds successfully. This means users should be able to log in through either manual email/password entry or via Google Sign-In directly on their portal, using the same underlying account.

---

## Primary Objective
Repair and configure the Google Sign-In functionality so that users can authenticate using their Google account when the associated Gmail address already exists in the database, enabling dual login methods on the user portal.

---

## Secondary Objectives
- Fix the current Google Sign-In error caused by the missing or invalid client_id parameter.
- Allow users to log in using either email/password credentials or Google Sign-In interchangeably from their own portal.

---

## Success Criteria
- Clicking the Google Sign-In button no longer produces the "Missing required parameter: client_id" error.
- Users with an existing account in the database can successfully log in using Google Sign-In with the matching Gmail address.
- Users can choose either manual email/password login or Google Sign-In on the portal and access the same account.

---

## Context & Dependencies
- The admin-side registration flow is functional and sends credential emails to newly registered users.
- The Google Sign-In integration is currently broken and requires configuration work to become operational.
- The system has an existing database of user accounts that includes email addresses.

---

## Stated Assumptions
- The missing client_id parameter is the root cause of the current Google Sign-In failure and substantial configuration work is required to resolve it.