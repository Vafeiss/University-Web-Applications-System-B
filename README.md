# UniSupport

Εργασία για το μάθημα CEI328 — Web Applications.
Ομαδική με την Αντριάνη Θεοφάνους.

Είναι ένα μικρό φόρουμ φοιτητών όπου ο καθένας μπορεί να ανεβάζει σημειώσεις,
παλιά διαγωνίσματα και ασκήσεις, να σχολιάζει, να κάνει follow άλλους χρήστες
και να μαζεύει tokens για να κατεβάζει υλικό. Ο admin βλέπει pending posts,
αιτήματα διαγραφής και reports.

## Πώς το τρέχω τοπικά (XAMPP)

Χρειάζεται XAMPP με Apache + MySQL + PHP 8.

1. Βάλε τον φάκελο μέσα στο `htdocs` (πρέπει να λέγεται `student`,
   γιατί ο κώδικας έχει paths που ξεκινούν από `/student/...`).

2. Φτιάξε δύο αρχεία αντιγράφοντας τα templates:
   - `backend/config/database.example.php` → `backend/config/database.php`
   - `.env.example` → `.env`

   Στο `database.php` βάλε τα στοιχεία σου (στον δικό μου XAMPP χρησιμοποιώ
   `root` χωρίς password). Στο `.env` τα SMTP του Gmail (για το forgot password).

3. Άνοιξε το phpMyAdmin και φτιάξε μια άδεια βάση με το ίδιο όνομα που έβαλες
   στο `database.php` (στο δικό μας: `webvaria_student`). Μετά κάνε import
   το `schema.sql` που έχουμε δίπλα στην εργασία.

4. `composer install` για να φτιαχτεί ο `vendor` φάκελος (PHPMailer κλπ).

5. Πάμε στο `http://localhost/student/login.php` και κάνουμε register.

## Φάκελοι

- `backend/` — config, controllers, middleware, modules (η "λογική" του site)
- `assets/`, `css/`, `js/`, `imgs/` — frontend assets
- `uploads/` — εκεί ανεβαίνουν τα attachments των posts
- Στο root είναι όλες οι σελίδες (`login.php`, `posts.php`, `profile_view.php`, κλπ)

## Σημειώσεις

- Τα `database.php`, `mail.php` και `.env` είναι git-ignored γιατί έχουν
  credentials. Χρησιμοποιήστε τα `.example` αντίγραφα ως template.
- Ο `vendor/` δεν ανεβαίνει στο git — τρέξτε `composer install` αφού κλωνοποιήσετε.

---

Pelagia Koniotaki & Antriani Theofanous, 2026
