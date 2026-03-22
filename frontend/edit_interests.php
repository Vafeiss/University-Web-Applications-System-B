<?php
session_start();

require_once "../backend/middleware/AuthGuard.php";
requireLogin();

require_once "../backend/config/database.php";

$db = new Database();
$conn = $db->connect();

$categoriesStmt = $conn->query(" 
    SELECT MIN(category_id) AS category_id, name
    FROM categories
    GROUP BY name
    ORDER BY name ASC
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$selectedStmt = $conn->prepare(" 
    SELECT DISTINCT c.name
    FROM user_interest ui
    JOIN categories c ON c.category_id = ui.category_id
    WHERE ui.user_id = :id
");
$selectedStmt->execute([
    ":id" => $_SESSION["user_id"]
]);
$selectedRows = $selectedStmt->fetchAll(PDO::FETCH_COLUMN);

$selectedMap = [];
foreach ($selectedRows as $categoryName) {
    $selectedMap[(string)$categoryName] = true;
}

$success = isset($_GET["success"]);
?>
<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Interests</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/assets/style.css">
</head>

<body>
<div class="container auth-container">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-4 text-center">Edit Interests</h3>

            <?php if ($success): ?>
                <div class="alert alert-success">Interests updated successfully.</div>
            <?php endif; ?>

            <form method="POST" action="../backend/controllers/ProfileController.php?action=updateInterests">
                <div class="mb-3">
                    <label class="form-label">Select Your Interests</label>
                    <div class="border rounded p-3 interests-box">
                        <?php foreach ($categories as $cat): ?>
                            <?php $id = (int)$cat['category_id']; ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="categories[]"
                                    value="<?= $id ?>"
                                    id="edit-cat<?= $id ?>"
                                    <?= isset($selectedMap[(string)$cat['name']]) ? 'checked' : '' ?>
                                >
                                <label class="form-check-label" for="edit-cat<?= $id ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Save Interests</button>
            </form>

            <div class="text-center mt-3">
                <a href="posts.php">Back to posts</a>
            </div>
        </div>
    </div>
</div>
</body>

</html>
