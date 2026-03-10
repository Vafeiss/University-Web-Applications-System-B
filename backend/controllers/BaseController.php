<?php

class BaseController {

    protected function ensureSessionStarted() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    protected function requireLogin() {
        $this->ensureSessionStarted();

        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(["message" => "User not logged in"], 401);
        }

        return $_SESSION['user_id'];
    }

    protected function getCurrentUserId() {
        $this->ensureSessionStarted();
        return $_SESSION['user_id'] ?? null;
    }

    protected function isAdmin() {
        $this->ensureSessionStarted();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    protected function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            $this->jsonResponse(["message" => "Admin access required"], 403);
        }
    }

    protected function getJSONInput() {
        return json_decode(file_get_contents("php://input"), true);
    }

    protected function jsonResponse($data, int $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
