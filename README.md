# System B – Referral & Token Reward System

This branch implements the **Referral & Token Reward System** for System B.

The feature allows users to invite other users using a unique referral code.  
If a valid referral code is used during registration, both users receive token rewards.

---

# Database Configuration

The project uses a shared database connection file:

backend/config/db.php

Default local configuration:

- Host: 127.0.0.1
- Port: 3306
- Database: university_web
- User: root
- Password: empty

Optional environment variables can be used:

SYSTEM_B_DB_HOST  
SYSTEM_B_DB_PORT  
SYSTEM_B_DB_NAME  
SYSTEM_B_DB_USER  
SYSTEM_B_DB_PASS  

---

# Database Schema

The schema used by this feature is included in:

database/university_web_main.sql

The schema already includes the following fields required by the referral system:

- users.referral_code
- users.referred_by

The system also uses the existing tables:

- users
- transactions

---

# Feature Functionality

The referral system provides the following functionality:

• Automatic generation of a **unique referral code** for each user  
• Optional **referral code input during registration**  
• Token rewards when a valid referral code is used  

Token rewards:

• **+10 tokens to the new user**  
• **+10 tokens to the referrer**

All token updates are performed using **database transactions** to ensure consistency.

The token balance is stored in:

users.token_balance

---

# Project Structure

Frontend file:

frontend/index.php

Backend files:

backend/token_reward.php  
backend/modules/referral.php  

---

# API Endpoints

POST endpoint:

backend/token_reward.php

Supported actions:

ensure_referral_code  
Generates a referral code for a user if it does not already exist.

apply_referral_reward  
Validates the referral code and assigns token rewards to both users.

---

# How to Run

1. Start **Apache** and **MySQL** in XAMPP.

2. Import the database schema:

database/university_web_main.sql

3. Ensure the database name is:

university_web

4. Open the application in your browser:

http://localhost/University-Web-Applications-System-B/frontend/index.php

---

# Integration with Registration

This feature is designed to work with the main registration system.

Typical usage flow:

1. The registration system creates a new user.
2. The system calls `ensure_referral_code` for the new user.
3. If a referral code was entered during registration, the system calls `apply_referral_reward`.

---

# Notes

This branch contains only the implementation of the **Referral & Token Reward System** and does not modify other parts of the project.