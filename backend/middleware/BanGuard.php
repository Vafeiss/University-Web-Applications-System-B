<?php
require_once __DIR__ . '/../config/app.php';
/**
 * File: BanGuard.php
 * Layer: Middleware
 * Module: Ban Enforcement
 * System: University Web Applications System B
 *
 * Description:
 * Middleware for user ban enforcement across frontend and backend.
 * Checks ban status, caches ban data, and provides ban messages.
 * Prevents banned users from accessing protected pages/APIs.
 *
 * Functions:
 * - hasBanColumns() → checks if ban columns exist in users table
 * - getBannedAccountMessage() → returns standard ban message
 * - getUserBanState() → retrieves cached ban status for user
 * - isUserBanned() → checks if user has active ban
 * - getUserBanMessage() → returns user-specific ban reason
 * - clearAuthenticatedSession() → invalidates ban user session
 * - enforceFrontendUserNotBanned() → redirects banned users from frontend
 *
 * Security:
 * - Caches ban status to reduce database queries
 * - Session clearing on ban detection
 * - Protection against report spam (automatic banning)
 *
 * Used By:
 * - AuthGuard.php
 * - BanController.php
 * - All protected frontend pages
 * - BaseController
 *
 * Author: Antriani Theofanous
 * Date: 2026
 */

require_once __DIR__ . '/../config/database.php';

const BANNED_ACCOUNT_MESSAGE = "Your account has been banned because it exceeded the allowed number of reports.";

function hasBanColumns(): bool
{
    static $checked = false;
    static $hasColumns = false;

    if ($checked) {
        return $hasColumns;
    }

    $checked = true;

    try {
        $database = new Database();
        $conn = $database->connect();

        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'is_banned'");
        $hasColumns = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        $hasColumns = false;
    }

    return $hasColumns;
}

function getBannedAccountMessage(): string
{
    return BANNED_ACCOUNT_MESSAGE;
}

function getUserBanState(int $userId): ?array
{
    static $cache = [];

    if ($userId <= 0) {
        return null;
    }

    if (!hasBanColumns()) {
        return null;
    }

    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    $database = new Database();
    $conn = $database->connect();

    $stmt = $conn->prepare(
        "SELECT is_banned, ban_reason
         FROM users
         WHERE user_id = :id
         LIMIT 1"
    );

    $stmt->execute([
        ':id' => $userId
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $cache[$userId] = $row;

    return $row;
}

function isUserBanned(int $userId): bool
{
    $state = getUserBanState($userId);
    return $state !== null && (int) ($state['is_banned'] ?? 0) === 1;
}

function getUserBanMessage(int $userId): string
{
    $state = getUserBanState($userId);
    $reason = trim((string) ($state['ban_reason'] ?? ''));

    return $reason !== '' ? $reason : getBannedAccountMessage();
}

function clearAuthenticatedSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function redirectBannedUserToLogin(): void
{
    $message = rawurlencode(getBannedAccountMessage());
    header("Location: " . app_frontend_url("login.php?ban_message={$message}"));
    exit;
}

function enforceFrontendUserNotBanned(): void
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $userId = (int) $_SESSION['user_id'];

    if (!isUserBanned($userId)) {
        return;
    }

    clearAuthenticatedSession();
    redirectBannedUserToLogin();
}
