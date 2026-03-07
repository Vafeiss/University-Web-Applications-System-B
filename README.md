# University-Web-Applications-System-B

This is the repository of our web application **Student Forum System (System B)** for our University.  
The system implements authentication, role-based access control, referral system, and token economy.

The purpose of this repository is version control, collaboration, and tracking team progress.

---

# Features Implemented

## Authentication System
- User Registration (with password hashing)
- Login (secure session handling)
- Logout
- Password Reset (token-based, expiring links)
- Session regeneration (anti-session fixation)

## Role-Based Access Control (RBAC)
- `admin` and `user` roles
- Admin-only protected pages
- Middleware protection (`AuthGuard`)
- Conditional UI rendering based on role

## Referral & Token System
- Automatic generation of unique referral codes
- Optional referral code usage during registration
- Token rewards:
  - +10 tokens to new user (if referral used)
  - +10 tokens to referrer
- Transaction-safe token updates
- Token balance stored per user

## Security Practices
- Password hashing (`password_hash`)
- Password verification (`password_verify`)
- Prepared statements (PDO)
- Transaction handling for atomic operations
- Session ID regeneration
- Secure password reset with expiration

---

# Database Setup

To set up the database locally:

1. Open phpMyAdmin
2. Create a new database (e.g. `university_web`)
3. Import the file located at:  
   `database/schema.sql`
4. The database will be ready to use.

---

# Database Structure (Core Tables)

## users table includes:

- user_id (Primary Key)
- username
- email
- password (hashed)
- role (admin/user)
- token_balance
- referral_code (unique)
- referred_by (nullable)
- reset_token
- reset_expires
- university (nullable)
- year (nullable)
- department (nullable)

---

# How to Run the Project Locally

1. Clone the repository
2. Move the project folder into:
   xampp/htdocs/
3. Start Apache and MySQL from XAMPP
4. Open your browser and visit:
   http://localhost/University-Web-Applications-System-B/frontend/login.php

---

# System Architecture

backend/
    config/
    controllers/
    middleware/

frontend/
    login.php
    register.php
    index.php
    admin.php
    forgot_password.php
    reset_password.php
    logout.php

database/
    schema.sql

---

# Role-Based Access Control

The system supports two roles:

- user
- admin

Access Rules:

- login.php → Public
- register.php → Public
- index.php → Authenticated users only
- admin.php → Admin only

Access is enforced via middleware (AuthGuard).

---

# Referral & Token System

- Each user receives a unique referral code.
- A referral code can be used during registration.
- If valid referral is used:
    - New user receives +10 tokens.
    - Referrer receives +10 tokens.
- All token updates are handled using database transactions.

---

# Security Implementation

- Password hashing (password_hash)
- Password verification (password_verify)
- Prepared statements (PDO)
- Transaction handling for atomic operations
- Session ID regeneration after login
- Reset tokens expire after 1 hour

---

# Project Status

System B – Authentication & Referral Module: COMPLETE

Future extension: Forum / Posting module.

---

# Functional Scope (System B)

This module implements the following SRS requirements:

- User authentication and session management
- Role-based authorization
- Secure password reset mechanism
- Referral-based token reward system
- Token balance tracking per user
This is the repository of our  web application Student Forum system
for our University with the purpose of keeping versions , updates and 
tracking our progress as a team.
---

# Database Setup
To set up the database locally:
1. Open phpMyAdmin
2. Create a new database (e.g. `university_web`)
3. Import the file located at:
database/schema.sql
4. The database will be ready to use.
---
# How to Run the Project Locally
1. Clone the repository
2. Move the project folder into:
   xampp/htdocs/
3. Start Apache and MySQL from XAMPP
4. Open your browser and visit:
   http://localhost/University-Web-Applications-System-B/frontend/login.php
---


