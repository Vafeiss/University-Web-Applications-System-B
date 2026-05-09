<?php
/**
 * File: category_request.php
 * Layer: Frontend Page
 * Module: Category Requests
 * System: University Web Applications System B
 *
 * Description:
 * User page for submitting new category suggestions to the admins
 * and reviewing the status of previously submitted requests.
 *
 * Features:
 * - Form to submit a new category name
 * - List of user's previous requests with status
 * - Delete own pending request option
 * - Inline feedback on success / error
 *
 * Security:
 * - session_start() and requireLogin()
 * - Input validation on category name
 * - htmlspecialchars() for output escaping
 *
 * Used By:
 * - Linked from main dashboard (index.php)
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();

require_once __DIR__ . "/backend/middleware/AuthGuard.php";
requireLogin();

require_once __DIR__ . "/backend/config/database.php";
require_once __DIR__ . "/backend/modules/CategoryModel.php";
require_once __DIR__ . "/backend/modules/NotificationModel.php";

$userId = (int)($_SESSION["user_id"] ?? 0);
$successMessage = "";
$errorMessage = "";

if (isset($_SESSION["category_request_success"])) {
    $successMessage = (string)$_SESSION["category_request_success"];
    unset($_SESSION["category_request_success"]);
}

if (isset($_SESSION["category_request_error"])) {
    $errorMessage = (string)$_SESSION["category_request_error"];
    unset($_SESSION["category_request_error"]);
}

$db = new Database();
$conn = $db->connect();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = (string)($_POST["action"] ?? "submit");

    $redirectTarget = "category_request.php";
    $nextSuccessMessage = "";
    $nextErrorMessage = "";

    if ($action === "delete") {
        $requestId = (int)($_POST["request_id"] ?? 0);

        if ($requestId <= 0) {
            $nextErrorMessage = "Invalid request id.";
        } else {
            $statusStmt = $conn->prepare(
                "SELECT status
                 FROM category_requests
                 WHERE request_id = :request_id
                 AND requested_by = :user_id
                 LIMIT 1"
            );

            $statusStmt->execute([
                ":request_id" => $requestId,
                ":user_id" => $userId
            ]);

            $status = $statusStmt->fetchColumn();

            if ($status === false) {
                $nextErrorMessage = "Request not found or already removed.";
            } elseif ((int)$status === 0) {
                $nextErrorMessage = "Pending requests cannot be deleted.";
            } else {
                $deleteStmt = $conn->prepare(
                    "DELETE FROM category_requests
                     WHERE request_id = :request_id
                     AND requested_by = :user_id"
                );

                $deleteStmt->execute([
                    ":request_id" => $requestId,
                    ":user_id" => $userId
                ]);

                if ($deleteStmt->rowCount() > 0) {
                    $nextSuccessMessage = "Request removed from history.";
                } else {
                    $nextErrorMessage = "Request not found or already removed.";
                }
            }
        }
    } else {
        $name = trim((string)($_POST["category_name"] ?? ""));

        if ($name === "") {
            $nextErrorMessage = "Please enter a category name.";
        } elseif (mb_strlen($name) > 100) {
            $nextErrorMessage = "Category name is too long (max 100 characters).";
        } else {
            try {
                $model = new CategoryModel();
                $model->requestCategory($userId, $name);

                $actorName = trim((string)($_SESSION["username"] ?? "A user"));
                $notificationModel = new NotificationModel();
                $notificationModel->notifyAdmins(
                    "admin_category_request",
                    null,
                    $actorName . " submitted a category request: " . $name
                );

                $nextSuccessMessage = "Your category request was submitted.";
            } catch (Throwable $e) {
                if ($e instanceof PDOException && $e->getCode() === "23000") {
                    $nextErrorMessage = "You have already submitted this category request.";
                } else {
                    $nextErrorMessage = "Could not submit your request right now. Please try again.";
                }
            }
        }
    }

    if ($nextSuccessMessage !== "") {
        $_SESSION["category_request_success"] = $nextSuccessMessage;
    }

    if ($nextErrorMessage !== "") {
        $_SESSION["category_request_error"] = $nextErrorMessage;
    }

    header("Location: " . $redirectTarget);
    exit;
}

$requestsStmt = $conn->prepare(
    "SELECT request_id, suggested_name, status, created_at
     FROM category_requests
     WHERE requested_by = :user_id
     ORDER BY created_at DESC
     LIMIT 8"
);

$requestsStmt->execute([
    ":user_id" => $userId
]);

$recentRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

$categoryRequestCssVersion = filemtime(__DIR__ . '/css/category_request.css');

function statusLabel(int $status): string {
    if ($status === 1) {
        return "Approved";
    }

    if ($status === 2) {
        return "Rejected";
    }

    return "Pending";
}

function statusClass(int $status): string {
    if ($status === 1) {
        return "success";
    }

    if ($status === 2) {
        return "danger";
    }

    return "warning";
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Request Category</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/student/assets/style.css">
<link rel="stylesheet" href="/student/css/category_request.css?v=<?php echo $categoryRequestCssVersion; ?>">
</head>
<body>
<div class="container auth-container">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-3 text-center">Request New Category</h3>
            <p class="text-muted text-center mb-4">Suggest a category and admins will review it.</p>

            <?php if ($successMessage !== ""): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8") ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== ""): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="submit">
                <div class="mb-3">
                    <label for="category_name" class="form-label">Category name</label>
                    <input
                        type="text"
                        id="category_name"
                        name="category_name"
                        class="form-control"
                        maxlength="100"
                        required
                        placeholder="e.g. Data Analytics"
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100">Submit Request</button>
            </form>

            <hr class="my-4">

            <h5 class="mb-3">Your Recent Requests</h5>

            <?php if (empty($recentRequests)): ?>
                <div class="alert alert-light border mb-0">No category requests yet.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recentRequests as $request): ?>
                        <?php $requestId = (int)($request["request_id"] ?? 0); ?>
                        <?php $status = (int)($request["status"] ?? 0); ?>
                        <div class="list-group-item request-history-item d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars((string)($request["suggested_name"] ?? ""), ENT_QUOTES, "UTF-8") ?></div>
                                <small class="text-muted">
                                    <?= htmlspecialchars((string)($request["created_at"] ?? ""), ENT_QUOTES, "UTF-8") ?>
                                </small>
                            </div>
                            <span class="badge text-bg-<?= statusClass($status) ?>">
                                <?= statusLabel($status) ?>
                            </span>

                            <?php if ($status !== 0): ?>
                                <form method="POST" action="" class="m-0">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                    <button type="submit" class="request-history-delete" aria-label="Delete request" title="Delete request">&times;</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="posts.php">Back to feed</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>