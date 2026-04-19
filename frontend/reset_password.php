<?php
// reset password - με token απο το email
// Pela

require_once "../backend/controllers/AuthController.php";

$token = $_GET["token"] ?? "";
$message = "";
$is_ok = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth = new AuthController();

    $res = $auth->resetPassword(
        $_POST["token"] ?? "",
        $_POST["password"] ?? ""
    );

    $message = $res["message"] ?? "";
    $is_ok = !empty($res["ok"]);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow-sm" style="width: 480px;">
            <div class="card-body p-4">
                <h2 class="mb-4 text-center">Reset Password</h2>

                <?php if (!empty($message)): ?>
                    <div class="alert <?= $is_ok ? "alert-success" : "alert-danger" ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- hidden token -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <!-- new password -->
                    <div class="mb-3">
                        <label class="form-label">New Password</label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
