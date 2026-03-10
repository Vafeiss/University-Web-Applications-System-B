<?php
/**
 * File: profile_setup.php
 * Layer: Frontend
 * Module: User Profile Initialization
 * System: University Web Applications System B
 *
 * Description:
 * This page is displayed after the user's first successful login.
 * It allows the user to complete their basic profile information
 * required by the platform.
 *
 * The user must select:
 * - University
 * - Year of study
 * - Optional interest categories (filters)
 *
 * After submission:
 * Data is sent to ProfileController.php which updates:
 * - users.university
 * - users.year
 * - user_interest table
 *
 * Access Control:
 * - Only authenticated users can access this page
 * - Users who already completed their profile are redirected
 *
 * Security:
 * - Session-based authentication
 * - Prepared statements for database queries
 * - Output escaping using htmlspecialchars()
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */

session_start();

/* =========================
   ACCESS CONTROL
========================= */

require_once "../backend/middleware/AuthGuard.php";
requireLogin();

/* =========================
   DATABASE CONNECTION
========================= */

require_once "../backend/config/database.php";

$db = new Database();
$conn = $db->connect();

/* =========================
   CHECK PROFILE COMPLETION
========================= */

$stmt = $conn->prepare("
    SELECT university, year
    FROM users
    WHERE user_id = :id
");

$stmt->execute([
    ":id" => $_SESSION["user_id"]
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* Redirect if profile already completed */

if ($user && $user["university"] !== null && $user["year"] !== null) {
    header("Location: index.php");
    exit;
}

/* =========================
   LOAD INTEREST CATEGORIES
========================= */

$stmt = $conn->query("SELECT category_id, name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Complete Profile</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Custom Styles -->
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">

</head>

<body>

<div class="container auth-container">

<div class="card shadow-sm">

<div class="card-body p-4">

<h3 class="mb-4 text-center">Complete Your Profile</h3>

<!-- =========================
     PROFILE FORM
========================= -->

<form method="POST" action="../backend/controllers/ProfileController.php">

<!-- =========================
     UNIVERSITY SELECTION
========================= -->

<div class="mb-3">

<label class="form-label">University</label>

<select name="university" class="form-select" required>

<option value="">Select University</option>

<option value="TEPAK">TEPAK</option>
<option value="UCY">University of Cyprus</option>
<option value="CUT">Cyprus University of Technology</option>

</select>

</div>

<!-- =========================
     YEAR OF STUDY
========================= -->

<div class="mb-3">

<label class="form-label">Year of Study</label>

<div>

<label class="me-3">
<input type="radio" name="year" value="1" required> Year 1
</label>

<label class="me-3">
<input type="radio" name="year" value="2"> Year 2
</label>

<label class="me-3">
<input type="radio" name="year" value="3"> Year 3
</label>

<label>
<input type="radio" name="year" value="4"> Year 4
</label>

</div>

</div>

<!-- =========================
     INTEREST CATEGORIES
========================= -->

<div class="mb-3">

<label class="form-label">Select Interests (Optional)</label>

<div class="border rounded p-3 interests-box">

<?php foreach ($categories as $cat): ?>

<div class="form-check">

<input 
class="form-check-input"
type="checkbox"
name="categories[]"
value="<?= htmlspecialchars($cat['category_id']) ?>"
id="cat<?= htmlspecialchars($cat['category_id']) ?>"
>

<label class="form-check-label" for="cat<?= htmlspecialchars($cat['category_id']) ?>">

<?= htmlspecialchars($cat['name']) ?>

</label>

</div>

<?php endforeach; ?>

</div>

</div>

<!-- =========================
     SUBMIT BUTTON
========================= -->

<button type="submit" class="btn btn-primary w-100">
Save Profile
</button>

</form>

</div>
</div>
</div>

</body>

</html>