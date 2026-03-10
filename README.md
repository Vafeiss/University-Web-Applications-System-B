# University-Web-Applications-System-B

This branch is scoped only to the System B content and search filtering feature.

## Database

Shared connection file:

- `backend/config/db.php`

Default database:

- host: `127.0.0.1`
- port: `3306`
- database: `university_web`
- user: `root`
- password: empty

Optional environment variables:

- `SYSTEM_B_DB_HOST`
- `SYSTEM_B_DB_PORT`
- `SYSTEM_B_DB_NAME`
- `SYSTEM_B_DB_USER`
- `SYSTEM_B_DB_PASS`

Main schema file copied from the provided project database:

- `database/university_web_main.sql`

## Search Feature

Files:

- `frontend/index.php`
- `frontend/search.php`
- `frontend/js/search.js`
- `backend/controllers/search_controllers.php`
- `backend/modules/search.php`

Supported by the current main schema:

- keyword search in post title/content
- category filter
- date range filter
- sorting
- followed-users filter through the existing `followers` table

## Run

1. Start `Apache` and `MySQL` in XAMPP.
2. Import `database/university_web_main.sql` if you want the exact main schema in your local database.
3. Ensure the database is `university_web`.
4. Open:
   - `http://localhost/University-Web-Applications-System-B/frontend/index.php`
   - `http://localhost/University-Web-Applications-System-B/frontend/search.php`
