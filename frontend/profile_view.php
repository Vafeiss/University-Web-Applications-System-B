<?php
session_start();

require_once "../backend/middleware/AuthGuard.php";
requireLogin();

require_once "../backend/config/database.php";

$db = new Database();
$conn = $db->connect();

$userStmt = $conn->prepare(
    "SELECT user_id, username, email, university, year, token_balance, referral_code
     FROM users
     WHERE user_id = :id
     LIMIT 1"
);

$userStmt->execute([
    ":id" => $_SESSION["user_id"]
]);

$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: login.php");
    exit;
}

$interestsStmt = $conn->prepare(
    "SELECT c.name
     FROM user_interest ui
     JOIN categories c ON c.category_id = ui.category_id
     WHERE ui.user_id = :id
     ORDER BY c.name ASC"
);

$interestsStmt->execute([
    ":id" => $_SESSION["user_id"]
]);

$interests = $interestsStmt->fetchAll(PDO::FETCH_COLUMN);
$success = $_GET["success"] ?? "";
if ($success === "" && isset($_SESSION["flash_success"])) {
    $success = (string)$_SESSION["flash_success"];
}
unset($_SESSION["flash_success"]);

function displayValue($value): string {
    $normalized = trim((string)$value);
    if ($normalized === "") {
        return "Not set";
    }

    return htmlspecialchars($normalized, ENT_QUOTES, "UTF-8");
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">
<style>
body {
    background: linear-gradient(180deg, #f3f6fb 0%, #eef2f8 100%);
}

.profile-shell {
    max-width: 1100px;
    margin: 18px auto;
}

.profile-card {
    border: 1px solid #d9e2ef;
    border-radius: 14px;
    box-shadow: 0 8px 28px rgba(24, 42, 74, 0.08);
}

.profile-title {
    margin-bottom: 2px;
    text-align: center;
    font-size: 30px;
    font-weight: 800;
    color: #1f2d45;
}

.profile-subtitle {
    margin-bottom: 14px;
    text-align: center;
    color: #60708c;
    font-size: 11px;
}

.profile-info-item {
    border: 1px solid #d6dfec;
    border-radius: 12px;
    background: #f8fbff;
    padding: 9px 11px;
    height: 100%;
}

.profile-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #5f6f89;
}

.profile-value {
    margin-top: 4px;
    font-size: 14px;
    font-weight: 600;
    color: #1f2d45;
    line-height: 1.35;
}

.profile-section-title {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 16px;
    font-weight: 700;
    color: #1f2d45;
}

.profile-interests {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.profile-chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    border: 1px solid #c9d8f3;
    background: #eaf1ff;
    color: #214e9d;
    font-size: 11px;
    font-weight: 600;
}

.profile-actions {
    margin-top: 18px;
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.profile-btn {
    min-width: 150px;
    padding-top: 6px;
    padding-bottom: 6px;
    font-size: 13px;
}

@media (max-width: 768px) {
    .profile-title {
        font-size: 24px;
    }

    .profile-value {
        font-size: 13px;
    }

    .profile-btn {
        width: 100%;
    }
}
</style>
</head>
<body>
<div class="container profile-shell">
    <div class="card profile-card">
        <div class="card-body p-4 p-md-5">
            <?php if ($success === "profile_updated"): ?>
                <div class="alert alert-success mb-3">Profile updated successfully.</div>
            <?php endif; ?>

            <div class="page-top" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px;">
                <a class="back-link" href="posts.php">&larr; Back to posts</a>
                <a href="edit_profile_setup.php" class="back-link">Edit profile setup</a>
            </div>
            <h1 class="profile-title" style="margin-bottom: 2px;">My Profile</h1>
            <p class="profile-subtitle">Overview of your account details and registered interests.</p>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="profile-info-item">
                        <div class="profile-label">Username</div>
                        <div class="profile-value"><?= displayValue($user["username"] ?? "") ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-item">
                        <div class="profile-label">Email</div>
                        <div class="profile-value"><?= displayValue($user["email"] ?? "") ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-item">
                        <div class="profile-label">University</div>
                        <div class="profile-value"><?= displayValue($user["university"] ?? "") ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-item">
                        <div class="profile-label">Year Of Study</div>
                        <div class="profile-value"><?= displayValue($user["year"] ?? "") ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-item">
                        <div class="profile-label">Token Balance</div>
                        <div class="profile-value"><?= (int)($user["token_balance"] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-item">
                        <div class="profile-label">Referral Code</div>
                        <div class="profile-value"><?= displayValue($user["referral_code"] ?? "") ?></div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <h2 class="profile-section-title">Registered Interests</h2>

                <?php if (!empty($interests)): ?>
                    <div class="profile-interests">
                        <?php foreach ($interests as $interest): ?>
                            <span class="profile-chip"><?= htmlspecialchars((string)$interest, ENT_QUOTES, "UTF-8") ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No interests selected yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
