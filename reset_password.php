<?php
/**
 * File: reset_password.php
 * Layer: Frontend Page
 * Module: Password Reset
 * System: University Web Applications System B
 *
 * Description:
 * Public page that lets a user set a new password using the reset
 * token received by email. Validates the token through AuthController
 * and updates the password on success.
 *
 * Features:
 * - New password and confirm fields
 * - Token validation via AuthController
 * - Inline success / error feedback
 * - Redirect to login on success
 *
 * Security:
 * - Token validation with expiry check
 * - Password strength validation
 * - Password hashed with password_hash()
 *
 * Used By:
 * - Linked from the password-reset email
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . "/backend/controllers/AuthController.php";

$token = $_GET["token"] ?? "";
$message = "";
$is_ok = false;
$i18nJsVersion = filemtime(__DIR__ . '/js/i18n.js');
$messageKey = "";

$messageKeyMap = [
    "Invalid request" => "reset_password.message_invalid_request",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth = new AuthController();

    $res = $auth->resetPassword(
        $_POST["token"] ?? "",
        $_POST["password"] ?? ""
    );

    $message = $res["message"] ?? "";
    $is_ok = !empty($res["ok"]);
    $messageKey = $messageKeyMap[$message] ?? "";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reset-language-switcher .language-switcher-btn {
            min-width: 42px;
            border: 0;
            background: transparent;
            color: #3f5a85;
            font-weight: 700;
        }

        .reset-language-switcher .language-switcher-btn.is-active {
            background: linear-gradient(180deg, #214f95 0%, #173665 100%);
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(23, 54, 101, 0.18);
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="reset-language-switcher position-fixed top-0 start-50 translate-middle-x mt-4 d-inline-flex align-items-center gap-1 p-1 rounded-pill border bg-white shadow-sm" data-language-switcher aria-label="Language switcher" data-i18n-aria-label="common.language_switcher" style="z-index: 10;">
            <button type="button" class="language-switcher-btn btn btn-sm rounded-pill px-3 is-active" data-language="en" aria-pressed="true">EN</button>
            <button type="button" class="language-switcher-btn btn btn-sm rounded-pill px-3" data-language="el" aria-pressed="false">EL</button>
        </div>

        <div class="card shadow-sm" style="width: 480px;">
            <div class="card-body p-4">
                <h2 class="mb-4 text-center" data-i18n="reset_password.title">Reset Password</h2>

                <?php if (!empty($message)): ?>
                    <div class="alert <?= $is_ok ? "alert-success" : "alert-danger" ?>"<?php echo $messageKey !== "" ? ' data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- hidden token -->
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <!-- new password -->
                    <div class="mb-3">
                        <label class="form-label" data-i18n="reset_password.new_password">New Password</label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="New Password"
                            data-i18n-placeholder="reset_password.new_password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100" data-i18n="reset_password.submit">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/i18n.js?v=<?php echo $i18nJsVersion; ?>"></script>
</body>
</html>
