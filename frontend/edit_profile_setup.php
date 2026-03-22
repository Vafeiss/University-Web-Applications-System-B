<?php
session_start();

require_once "../backend/middleware/AuthGuard.php";
requireLogin();

require_once "../backend/config/database.php";

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare(" 
    SELECT university, year
    FROM users
    WHERE user_id = :id
    LIMIT 1
");

$stmt->execute([
    ":id" => $_SESSION["user_id"]
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: ["university" => "", "year" => ""];

$success = isset($_GET["success"]);
$error = $_GET["error"] ?? "";

function selected(string $value, ?string $current): string {
    return $value === (string)$current ? "selected" : "";
}

function checked(string $value, ?string $current): string {
    return $value === (string)$current ? "checked" : "";
}
?>
<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Profile Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">
</head>

<body>
<div class="container auth-container">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-4 text-center">Edit Profile Setup</h3>

            <?php if ($success): ?>
                <div class="alert alert-success">Profile updated successfully.</div>
            <?php endif; ?>

            <?php if ($error === "missing_fields"): ?>
                <div class="alert alert-danger">University and year are required.</div>
            <?php endif; ?>

            <form method="POST" action="../backend/controllers/ProfileController.php?action=updateProfile">
                <div class="mb-3">
                    <label class="form-label">University</label>
                    <select name="university" class="form-select" required>
                        <option value="">Select University</option>
                        <option value="TEPAK" <?= selected("TEPAK", $user["university"] ?? "") ?>>TEPAK</option>
                        <option value="UCY" <?= selected("UCY", $user["university"] ?? "") ?>>University of Cyprus</option>
                        <option value="CUT" <?= selected("CUT", $user["university"] ?? "") ?>>Cyprus University of Technology</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Year of Study</label>
                    <div>
                        <label class="me-3">
                            <input type="radio" name="year" value="1" <?= checked("1", $user["year"] ?? "") ?> required> Year 1
                        </label>
                        <label class="me-3">
                            <input type="radio" name="year" value="2" <?= checked("2", $user["year"] ?? "") ?>> Year 2
                        </label>
                        <label class="me-3">
                            <input type="radio" name="year" value="3" <?= checked("3", $user["year"] ?? "") ?>> Year 3
                        </label>
                        <label>
                            <input type="radio" name="year" value="4" <?= checked("4", $user["year"] ?? "") ?>> Year 4
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Save Profile</button>
            </form>

            <div class="text-center mt-3">
                <a href="profile_view.php">Back to profile view</a>
            </div>
        </div>
    </div>
</div>
</body>

</html>
