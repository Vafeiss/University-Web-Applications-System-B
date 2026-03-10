<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/config/db.php';
require_once dirname(__DIR__) . '/backend/modules/referral.php';

$ensureResult = null;
$rewardResult = null;
$error = null;
$lookupUser = null;

try {
    $service = new ReferralService($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'ensure_referral_code') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $referralCode = $service->ensureReferralCodeForUser($userId);
            $ensureResult = [
                'user_id' => $userId,
                'referral_code' => $referralCode,
            ];
        }

        if ($action === 'apply_referral_reward') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $referralCode = (string) ($_POST['referral_code'] ?? '');
            $rewardResult = $service->applyReferralReward($userId, $referralCode);
        }
    }

    if (isset($_GET['referral_lookup']) && $_GET['referral_lookup'] !== '') {
        $lookupUser = $service->getUserByReferralCode((string) $_GET['referral_lookup']);
    }
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Reward System</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f6f7fb;
        }

        .container {
            max-width: 980px;
            margin: 40px auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 12px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 6px;
        }

        button {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .card {
            border: 1px solid #e6e6e6;
            border-radius: 12px;
            padding: 18px;
        }

        .success {
            background: #ecfdf3;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 14px;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 14px;
        }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Token Reward Process</h1>
        <p>
            This page is only for the token reward feature. Registration and login can be handled by another part of the system.
            This feature only does two things: ensure a user has a referral code, and apply the referral reward to an already-created user.
        </p>

        <div class="grid">
            <div class="card">
                <h2>Ensure Referral Code</h2>

                <?php if ($ensureResult !== null): ?>
                    <div class="success">
                        Referral code is ready for user
                        <strong>#<?= (int) $ensureResult['user_id'] ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($error !== null): ?>
                    <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="action" value="ensure_referral_code">
                    <label>
                        Existing User ID
                        <input type="number" name="user_id" min="1" required>
                    </label>

                    <button type="submit">Generate / Load Code</button>
                </form>

                <?php if ($ensureResult !== null): ?>
                    <p>Referral code: <code><?= htmlspecialchars($ensureResult['referral_code'], ENT_QUOTES, 'UTF-8') ?></code></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Apply Reward</h2>
                <form method="post">
                    <input type="hidden" name="action" value="apply_referral_reward">
                    <label>
                        New User ID
                        <input type="number" name="user_id" min="1" required>
                    </label>

                    <label>
                        Referral Code
                        <input type="text" name="referral_code" placeholder="Example: REF000001" required>
                    </label>

                    <button type="submit">Apply Reward</button>
                </form>

                <?php if ($rewardResult !== null): ?>
                    <div class="success">
                        Reward applied successfully.
                    </div>
                    <p>New user tokens: <code><?= (int) $rewardResult['new_user']['token_balance'] ?></code></p>
                    <p>Referrer tokens: <code><?= (int) $rewardResult['referrer']['token_balance'] ?></code></p>
                    <p>Reward amount: <code><?= (int) $rewardResult['reward_amount'] ?></code></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Lookup Referral Code</h2>
                <form method="get">
                    <label>
                        Referral Code
                        <input type="text" name="referral_lookup" placeholder="Enter existing code">
                    </label>

                    <button type="submit">Lookup</button>
                </form>

                <?php if (isset($_GET['referral_lookup'])): ?>
                    <?php if ($lookupUser !== null): ?>
                        <p>User: <code><?= htmlspecialchars($lookupUser['username'], ENT_QUOTES, 'UTF-8') ?></code></p>
                        <p>Email: <code><?= htmlspecialchars($lookupUser['email'], ENT_QUOTES, 'UTF-8') ?></code></p>
                        <p>Tokens: <code><?= (int) $lookupUser['token_balance'] ?></code></p>
                        <p>Referral code: <code><?= htmlspecialchars($lookupUser['referral_code'], ENT_QUOTES, 'UTF-8') ?></code></p>
                    <?php else: ?>
                        <p>No user found for that code.</p>
                    <?php endif; ?>
                <?php endif; ?>

                <p>API endpoint: <code>/University-Web-Applications-System-B/backend/token_reward.php</code></p>
            </div>
        </div>
    </div>
</body>
</html>
