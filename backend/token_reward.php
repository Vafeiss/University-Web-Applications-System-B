<?php

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/modules/referral.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
    ]);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $jsonInput = json_decode($rawInput ?: '', true);
    $payload = is_array($jsonInput) && $jsonInput !== []
        ? $jsonInput
        : $_POST;

    $action = (string) ($payload['action'] ?? '');
    $service = new ReferralService($pdo);

    if ($action === 'ensure_referral_code') {
        $userId = (int) ($payload['user_id'] ?? 0);
        $referralCode = $service->ensureReferralCodeForUser($userId);

        echo json_encode([
            'success' => true,
            'action' => $action,
            'data' => [
                'user_id' => $userId,
                'referral_code' => $referralCode,
            ],
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'apply_referral_reward') {
        $userId = (int) ($payload['user_id'] ?? 0);
        $referralCode = (string) ($payload['referral_code'] ?? '');

        $result = $service->applyReferralReward($userId, $referralCode);

        echo json_encode([
            'success' => true,
            'action' => $action,
            'data' => $result,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action. Use ensure_referral_code or apply_referral_reward.',
    ], JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Token reward process failed.',
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES);
}
