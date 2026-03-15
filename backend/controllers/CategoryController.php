<?php

require_once __DIR__ . "/../modules/CategoryModel.php";

session_start();

header("Content-Type: application/json");

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function requireLogin(): int {
    if (!isset($_SESSION["user_id"])) {
        jsonResponse(["message" => "User not logged in"], 401);
    }

    return (int)$_SESSION["user_id"];
}

function requireAdmin(): void {
    requireLogin();

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        jsonResponse(["message" => "Admin access required"], 403);
    }
}

$model = new CategoryModel();

$action = $_GET["action"] ?? "";

switch($action){

    // =================================
    // USER REQUEST CATEGORY
    // =================================
    case "request":

        $userId = requireLogin();

        $data = json_decode(file_get_contents("php://input"), true);

        $name = trim($data["name"] ?? "");

        if($name === ""){
            jsonResponse([
                "message" => "Category name required"
            ], 400);
        }

        $model->requestCategory($userId, $name);

        jsonResponse([
            "message" => "Category request submitted"
        ]);

        break;


    // =================================
    // ADMIN GET PENDING REQUESTS
    // =================================
    case "list":

        requireAdmin();

        $requests = $model->getPendingRequests();

        jsonResponse($requests);

        break;


    // =================================
    // ADMIN CREATE CATEGORY
    // =================================
    case "approve":

        requireAdmin();

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            jsonResponse(["message" => "Invalid request body"], 400);
        }

        $requestId = (int)($data["request_id"] ?? 0);
        $name = trim($data["name"] ?? "");

        if ($requestId <= 0 || $name === "") {
            jsonResponse(["message" => "request_id and name are required"], 400);
        }

        $model->createCategory($name);

        $model->updateRequestStatus($requestId, 1);

        jsonResponse([
            "message" => "Category created"
        ]);

        break;


    // =================================
    // ADMIN REJECT REQUEST
    // =================================
    case "reject":

        requireAdmin();

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data)) {
            jsonResponse(["message" => "Invalid request body"], 400);
        }

        $requestId = (int)($data["request_id"] ?? 0);

        if ($requestId <= 0) {
            jsonResponse(["message" => "Valid request_id is required"], 400);
        }

        $model->updateRequestStatus($requestId, 2);

        jsonResponse([
            "message" => "Request rejected"
        ]);

        break;

    // =================================
    // USER: get own interests
    // =================================
    case "userInterests":
// This endpoint can be used by the frontend to get the user's interests and show them in the feed filter
        $userId = requireLogin();
// επιστρέφει πίνακα με τα ενδιαφέροντα του χρήστη (π.χ. ["Computer Science", "Business Administration"])
        $interests = $model->getUserInterests($userId);
// επιστρέφει json response με τα ενδιαφέροντα
        jsonResponse($interests);

        break;
// εάν δεν ταιριάζει καμία από τις παραπάνω ενέργειες, επιστρέφει σφάλμα
    default:
        jsonResponse(["message" => "Invalid action"], 400);

}