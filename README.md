# University-Web-Applications-System-B

This repository currently contains only the Token Reward System process for the project.

## Database

Main schema copied from the provided project database:

- `database/university_web_main.sql`

Default database connection:

- host: `127.0.0.1`
- port: `3306`
- database: `university_web`
- user: `root`
- password: empty

Connection file:

- `backend/config/db.php`

## Feature Scope

Implemented from the documents:

- unique referral code for each user
- optional referral code during registration
- `+10` tokens to the new user if a valid referral is used
- `+10` tokens to the referrer
- transaction-safe token updates
- token balance stored per user

Uses the existing project tables:

- `users`
- `transactions`

The `university_web_main.sql` file now already includes:

- `users.referral_code`
- `users.referred_by`

## Files

- `frontend/index.php`
- `backend/token_reward.php`
- `backend/modules/referral.php`

## Run

1. Start `Apache` and `MySQL` in XAMPP.
2. Import `database/university_web_main.sql`.
3. Open:
   - `http://localhost/University-Web-Applications-System-B/frontend/index.php`

## API

POST endpoint:

- `backend/token_reward.php`

Actions:

- `ensure_referral_code`
- `apply_referral_reward`

Use this feature from another teammate's registration flow:

1. Create the user normally in the main system.
2. Call `ensure_referral_code` for that `user_id`.
3. If the new user entered a referral code, call `apply_referral_reward`.
