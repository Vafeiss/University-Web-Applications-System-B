# University Web Applications System B

This repository contains the implementation of our university web application, **Student Forum System (System B)**.

The purpose of this repository is to support version control, team collaboration, and progress tracking throughout the development process.

---

## Project Overview

The system currently focuses on:

- user authentication,
- role-based access control,
- referral-based registration,
- token reward management,
- secure session and password reset handling.

This module represents the core account and access management functionality of the application.
The system has been extended with a full forum and interaction module, including post creation, moderation workflows, notification handling, and user-driven requests (reports, delete requests, and category requests).
---

## Implemented Features

### Authentication System
- User registration with password hashing
- Secure login with session handling
- Logout functionality
- Password reset using time-limited tokens
- Session ID regeneration to reduce session fixation risks

### Role-Based Access Control (RBAC)
- Support for two user roles:
  - `admin`
  - `user`
- Access restriction for admin-only pages
- Middleware-based route protection using `AuthGuard`
- Conditional interface rendering depending on user role

### Referral and Token System
- Automatic generation of unique referral codes
- Optional referral code usage during user registration
- Token rewards when a valid referral code is used:
  - `+10` tokens for the new user
  - `+10` tokens for the referring user
- Transaction-safe token updates
- Token balance tracking per user account

### Security Practices
- Password hashing using `password_hash()`
- Password verification using `password_verify()`
- Prepared statements through PDO
- Transaction handling for sensitive database operations
- Session regeneration after login
- Secure password reset with expiration control

### Post and Feed System
- Users can create posts that appear in a shared feed
- Posts are subject to moderation (pending, approved, rejected)
- Filtering options based on category, date, and followers

### Moderation System
- Admins can approve or reject posts
- Structured moderation workflow with status tracking
- Users are notified about moderation outcomes

### Notification System
- Notifications for:
  - post approval / rejection
  - delete request decisions
  - report outcomes
  - category request decisions
  - follow interactions
- Notifications redirect users to the relevant system view

### Reports and Delete Requests
- Users can report posts
- Users can request deletion of their posts
- Admin review and decision process
- Status tracking per request

### Category Requests
- Users can request new categories
- Admin approval or rejection
- Feedback through notifications

### Profile Management
- Users can view and edit their profile
- Interest selection and management
- Profile completion validation
---

## Database Setup

To set up the database locally:

1. Open **phpMyAdmin**
2. Create a new database  
   Example: `university_web`
3. Import the following file:

```text
database/schema.sql
```

4. The database will then be ready for use.

---

## Core Database Structure

### `users` table
The `users` table includes the following core fields:

- `user_id` (Primary Key)
- `username`
- `email`
- `password` (hashed)
- `role` (`admin` / `user`)
- `token_balance`
- `referral_code` (unique)
- `referred_by` (nullable)
- `reset_token`
- `reset_expires`
- `university` (nullable)
- `year` (nullable)
- `department` (nullable)

---

## How to Run the Project Locally

1. Clone the repository
2. Move the project folder into:

```text
xampp/htdocs/
```

3. Start **Apache** and **MySQL** from XAMPP
4. Open your browser and visit:

```text
http://localhost/University-Web-Applications-System-B/frontend/login.php
```

---

## Project Structure

```text
backend/
    config/
    controllers/
    middleware/
    modules/

frontend/
    login.php
    register.php
    index.php
    admin.php
    posts.php
    post.php
    create_post.php
    profile_view.php
    profile_setup.php
    edit_profile_setup.php
    edit_interests.php
    token_history.php
    category_request.php
    admin_dashboard.php
    admin_delete_requests.php
    admin_reports.php
    forgot_password.php
    reset_password.php
    logout.php

database/
    schema.sql
```

---

## Role-Based Access Control

The system supports the following user roles:

- `user`
- `admin`

### Access Rules
- `login.php` → Public
- `register.php` → Public
- `index.php` → Authenticated users only
- `admin.php` → Admin users only

Access control is enforced through middleware (`AuthGuard`).

### Admin Entry Point
- Canonical admin panel entry point: `admin_dashboard.php`
- Legacy admin pages are maintained as redirects for compatibility

---

## Referral and Token Logic

- Each user receives a unique referral code.
- A referral code may be used during registration.
- If a valid referral code is provided:
  - the new user receives `+10` tokens,
  - the referring user also receives `+10` tokens.
- All token-related updates are executed through database transactions to ensure consistency.

---

## Security Implementation

The system applies the following security practices:

- Password hashing with `password_hash()`
- Password verification with `password_verify()`
- Prepared statements using PDO
- Transaction handling for atomic updates
- Session ID regeneration after successful login
- Password reset tokens with a 1-hour expiration period

---

## Functional Scope

This module implements the following functional requirements:

- User authentication and session management
- Role-based authorization
- Secure password reset mechanism
- Referral-based token reward system
- Token balance tracking per user

---

## Project Status

**System B – Authentication, Forum, and Moderation System: Complete**

Planned future extension:
- Advertisement integration
- UI/UX improvements and system design refinement
---

## Notes

This repository is intended for academic development purposes and team collaboration during the implementation of the project.
### Password Reset Setup

To enable the forgot password functionality, valid SMTP credentials must be configured in the `.env` file.

Without proper email configuration, password reset links will not be delivered.

Make sure to set:

SMTP_HOST  
SMTP_USER  
SMTP_PASS  
SMTP_PORT  

before testing this feature.
