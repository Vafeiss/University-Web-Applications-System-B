<?php
/**
 * File: BaseController.php
 * Layer: Controller
 * Module: Base Controller
 * System: University Web Applications System B
 *
 * Description:
 * Abstract base controller providing shared authentication, session, and
 * response handling methods for all API controllers.
 *
 * Functions:
 * - ensureSessionStarted() → initializes session if not already active
 * - requireLogin() → enforces authentication and returns user_id
 * - getCurrentUserId() → safely retrieves current user_id or null
 * - isAdmin() → checks if current user has admin role
 * - requireAdmin() → enforces admin-only access
 * - getJSONInput() → parses JSON POST body
 * - jsonResponse() → sends JSON response with HTTP status code
 *
 * Security:
 * - Session validation on every protected call
 * - Ban status checking integrated with requireLogin()
 * - Admin role verification
 * - JSON output with proper content-type headers
 *
 * Used By:
 * - All controller classes (AdsController, PostController, etc.)
 *
 * Author:Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . '/../middleware/BanGuard.php';

class BaseController {

    protected function ensureSessionStarted(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    protected function requireLogin(): int {
        $this->ensureSessionStarted();

        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(["message" => "User not logged in"], 401);
        }

        $userId = (int) $_SESSION['user_id'];

        if (isUserBanned($userId)) {
            clearAuthenticatedSession();
            $this->jsonResponse(["message" => getUserBanMessage($userId)], 403);
        }

        return $userId;
    }

    protected function getCurrentUserId(): ?int {
        $this->ensureSessionStarted();

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $userId = (int) $_SESSION['user_id'];

        if (isUserBanned($userId)) {
            clearAuthenticatedSession();
            $this->jsonResponse(["message" => getUserBanMessage($userId)], 403);
        }

        return $userId;
    }

    protected function isAdmin(): bool {
        $this->ensureSessionStarted();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    protected function requireAdmin(): void {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            $this->jsonResponse(["message" => "Admin access required"], 403);
        }
    }

    protected function getJSONInput(): ?array {
        $raw = file_get_contents("php://input");
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function jsonResponse($data, int $statusCode = 200): void {
        header("Content-Type: application/json; charset=utf-8");
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
