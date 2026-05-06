# Deployment Guide

This project can run under a subfolder such as `/student`, so the target URL
`https://cei328.live/student` is compatible with the current routing setup.

## 1. Prepare the files locally

Upload these items:

- `backend/`
- `frontend/`
- `database/`
- `vendor/`
- `.env`

Important:

- `vendor/` is required because the project uses `phpmailer/phpmailer` and `vlucas/phpdotenv`.
- If the hosting does not provide Composer/SSH access, upload your local `vendor/` folder through FTP.

## 2. Create the `.env` file

Create a project-root `.env` file based on `.env.example`.

Example:

```env
DB_HOST=localhost
DB_NAME=webvaria_student
DB_USER=webvaria_student
DB_PASS=your_database_password

SMTP_HOST=
SMTP_USER=
SMTP_PASS=
SMTP_PORT=587
```

Notes:

- Leave SMTP empty if password reset emails are not needed yet.
- The app will still work; only mail-based reset will be unavailable.

## 3. Import the database

1. Open phpMyAdmin.
2. Select the assigned database.
3. Open the `Import` tab.
4. Import `database/schema.sql`.
5. Import `database/ban_migration.sql` only if the ban columns are missing after the schema import.

## 4. Upload the project through FTP

Upload the whole project into the web root folder that maps to:

`https://cei328.live/student`

After upload, verify that these paths exist on the server:

- `/student/frontend/login.php`
- `/student/backend/controllers/AuthController.php`
- `/student/vendor/autoload.php`

## 5. Make uploads writable

The application stores attachments in:

`frontend/uploads/`

Make sure this folder exists on the server and is writable by PHP.

## 6. First test

Open:

`https://cei328.live/student/frontend/login.php`

Then test:

- login
- registration
- creating a post with attachment
- admin dashboard access
- password reset only if SMTP is configured
