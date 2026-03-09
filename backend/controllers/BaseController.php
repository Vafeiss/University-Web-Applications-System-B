<?php

class BaseController {

    protected function requireLogin() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(["message" => "User not logged in"], 401);
        }

        return $_SESSION['user_id'];
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
