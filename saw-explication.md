# SAW Room Booking Application - Functional and Security Overview

This document provides a comprehensive explanation of the features and security measures implemented in the SAW Room Booking application. It is intended to help developers, auditors, and administrators understand the system's capabilities and protections.

---

## 1. User Registration
- Users can register with a valid email and password.
- Input validation and sanitization are enforced to prevent malicious data.
- Email verification is required before account activation.
- Passwords are securely hashed using industry-standard algorithms.

## 2. Login
- Users log in with their email and password.
- Only verified users can log in (email verification required).
- Brute-force protection is implemented (rate limiting, lockout after failed attempts).
- Sessions are securely managed.

## 3. Logging (Logs)
- All critical actions (login, logout, failed attempts, admin actions, reservation changes) are logged.
- Logs are stored securely and are only accessible to authorized personnel.

## 4. Security
- Input validation and sanitization are applied throughout the application.
- Custom validation rules (e.g., International Tax ID) are used for sensitive fields.
- SQL Injection prevention via prepared statements and ORM usage.
- Cross-Site Scripting (XSS) prevention by escaping output and filtering input.
- CSRF protection is enabled for all forms.
- File uploads are validated for type, size, and sanitized filenames.
- Rate limiting and throttling to prevent DDoS and brute-force attacks.
- Sensitive data is never exposed in logs or error messages.

## 5. User Profile
- Users can view and update their profile information.
- Profile updates are validated and sanitized.
- Users can upload a profile picture (with validation and storage in a secure directory).

## 6. Admins
- Admin users have access to a dedicated dashboard.
- Admins can manage users, rooms, reservations, and view logs.
- Only admins can perform critical actions (user deletion, room management, etc.).
- Role-based access control is enforced.

## 7. Rooms 
- Users can view available rooms and their details (including photos).
- Admins can create, edit, and delete rooms.
- Room images are validated and securely stored.

## 8. Reservations
- Users can create, view, and edit their reservations.
- Admins can view and manage all reservations.
- Editing past reservations is restricted based on business rules.
- All reservation actions are logged.

## 9. PDF Generation
- Users and admins can generate PDF summaries of reservations.
- PDFs are generated server-side to prevent client-side manipulation.

## 10. Google Integration
- Google OAuth is available for user authentication (if configured).
- Secure handling of OAuth tokens and user data.

## 11. Logout
- Users can securely log out, destroying their session.
- CSRF protection is enforced on logout requests.

## 12. Email Functionality
- Email is used for account verification, password resets, and notifications.
- All emails are sent via a secure mailer configuration.
- Email addresses are validated and sanitized.

## 13. Email Verification Before Login
- Users must verify their email before being allowed to log in.
- Prevents fake or unverified accounts from accessing the system.

## 14. Data Handling
- All user data is validated and sanitized before storage.
- Sensitive data is encrypted where necessary.
- Data access is restricted based on user roles.

## 15. Cybersecurity Measures
- SQL Injection: Prevented via prepared statements and input validation.
- XSS: Prevented by escaping output and filtering input.
- CSRF: All forms are protected with CSRF tokens.
- DDoS: Rate limiting and throttling are implemented.
- File Uploads: Strict validation and storage outside web root.
- Logging: Security events are logged for auditing.
- Session Management: Secure cookies, session timeouts, and regeneration.
- Passwords: Hashed and never stored in plain text.
- Email Verification: Required for all users.
- Role-Based Access: Only authorized users can access admin features.

---

This document covers all major functionalities and security features of the SAW Room Booking application. For further details, refer to the codebase and configuration files.