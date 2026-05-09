# UniSupport — Student Forum System

Πτυχιακή εργασία για το μάθημα CEI328 (University Web Applications) — Cyprus University of Technology.

Ένα web-based σύστημα κοινότητας φοιτητών για ανταλλαγή σημειώσεων, ασκήσεων και
υλικού μαθημάτων με σύστημα κατηγοριών, follow, σχολίων, διαχείρισης από admin
και token-based downloads.

## Δομή

```
student/
├── assets/                # CSS / static assets
├── backend/
│   ├── config/            # DB & mail configuration
│   ├── controllers/       # Endpoint logic (Auth, Post, Comment, ...)
│   ├── helpers/           # Helper functions
│   ├── middleware/        # AuthGuard, BanGuard, ProfileGuard
│   └── modules/           # Data models (PostModel, ProfileModule, ...)
├── css/                   # Page-specific styles
├── imgs/                  # Static images
├── js/                    # Frontend JavaScript
├── uploads/               # User-uploaded attachments
└── *.php                  # Frontend pages (login, posts, profile, ...)
```

## Τοπικό setup με XAMPP

### 1. Pre-requisites

- XAMPP εγκατεστημένο (Apache + MySQL + PHP 8.x)
- Composer (για composer install — προαιρετικό αν έχεις ήδη `vendor/`)

### 2. Files

Από τον φάκελο του project:

```bash
cp backend/config/database.example.php backend/config/database.php
cp .env.example .env
```

Άνοιξε το `database.php` και βάλε τα δικά σου credentials (για XAMPP συνήθως
`username = root`, `password = ''`). Το ίδιο και στο `.env` για SMTP credentials.

### 3. Database

1. Άνοιξε `http://localhost/phpmyadmin`
2. Δημιούργησε database με όνομα ίδιο με το `db_name` του `database.php`
3. Import τα `database/schema.sql` και τυχόν `database/*.sql` migrations

### 4. Composer dependencies

```bash
composer install
```

(Δημιουργεί τον `vendor/` φάκελο.)

### 5. Run

Άνοιξε στον browser:

```
http://localhost/student/login.php
```

## Authors

- Pelagia Koniotaki
- Antriani Theofanous

## Date

2026
