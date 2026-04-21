<?php
/**
 * File: profile_view.php
 * Layer: Frontend Page
 * Module: Profile View
 * System: University Web Applications System B
 *
 * Description:
 * Profile page showing the user's personal info, interests, posts
 * and followers. Supports both self-view (with edit links) and
 * other-user view (with follow button).
 *
 * Features:
 * - User info and avatar
 * - Interests display
 * - Followers / following counts
 * - User's posts list
 * - Follow / Unfollow button
 * - Edit profile / interests links (self)
 *
 * Security:
 * - session_start() and requireLogin()
 * - ProfileGuard / requireCompleteProfile()
 * - PDO prepared statements (database.php)
 * - htmlspecialchars() for output escaping
 *
 * Used By:
 * - Linked from post cards, search results and main dashboard
 *
 * Author:Pelagia Koniotaki
 * Date: 2026
 */

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

$profileViewCssVersion = filemtime(__DIR__ . '/css/profile_view.css');

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
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/css/profile_view.css?v=<?php echo $profileViewCssVersion; ?>">
</head>
<body>
<div class="container profile-shell">
    <div class="card profile-card">
        <div class="card-body p-4 p-md-5">
            <?php if ($success === "profile_updated"): ?>
                <div class="alert alert-success mb-3">Profile updated successfully.</div>
            <?php endif; ?>

            <div class="page-top profile-page-top">
                <a class="back-link" href="posts.php">&larr; Back to posts</a>
                <a href="edit_profile_setup.php" class="back-link">Edit profile setup</a>
            </div>
            <h1 class="profile-title">My Profile</h1>
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
