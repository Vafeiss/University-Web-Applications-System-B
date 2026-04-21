<?php
/**
 * File: CategoryController.php
 * Layer: Controller
 * Module: Categories
 * System: University Web Applications System B
 *
 * Description:
 * Handles user category requests and admin approval workflow.
 * Users can request new categories; admins review and approve/reject them.
 * Sends localized notifications to admins on new requests.
 *
 * Functions:
 * - request() → users submit new category suggestions
 * - list() → admins retrieve pending category requests
 * - summary() → admins view pending and existing categories
 * - approve() → admins approve category request and create category
 *
 * Security:
 * - requireLogin() for user requests
 * - requireAdmin() for all admin operations
 * - Input validation on category name
 * - CSRF protection via JSON payload validation
 *
 * Used By:
 * - frontend/category_request.php
 * - frontend/admin_dashboard.php
 *
 * Author:
 * Date: 2026
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . "/../modules/CategoryModel.php";
require_once __DIR__ . "/../modules/NotificationModel.php";

header("Content-Type: application/json");

class CategoryController extends BaseController {

    private CategoryModel $model;

    public function __construct() {
        $this->model = new CategoryModel();
    }

    public function request(): void {
        $userId = $this->requireLogin();
        $data = $this->getJSONInput();
        $name = trim($data["name"] ?? "");

        if ($name === "") {
            $this->jsonResponse(["message" => "Category name required"], 400);
        }

        $this->model->requestCategory($userId, $name);

        $actorName = trim((string)($_SESSION['username'] ?? 'A user'));
        $notificationModel = new NotificationModel();
        $notificationModel->notifyAdminsLocalized(
            'admin_category_request',
            null,
            'notifications.admin_category_request',
            [
                "actor" => $actorName,
                "name" => $name
            ],
            $actorName . ' submitted a category request: ' . $name
        );

        $this->jsonResponse(["message" => "Category request submitted"]);
    }

    public function list(): void {
        $this->requireAdmin();
        $requests = $this->model->getPendingRequests();
        $this->jsonResponse($requests);
    }

    public function summary(): void {
        $this->requireAdmin();

        $this->jsonResponse([
            "pending_requests" => $this->model->getPendingRequests(),
            "existing_categories" => $this->model->getExistingCategories()
        ]);
    }

    public function approve(): void {
        $this->requireAdmin();
        $data = $this->getJSONInput();

        if (!is_array($data)) {
            $this->jsonResponse(["message" => "Invalid request body"], 400);
        }

        $requestId = (int)($data["request_id"] ?? 0);
        $name = trim($data["name"] ?? "");

        if ($requestId <= 0 || $name === "") {
            $this->jsonResponse(["message" => "request_id and name are required"], 400);
        }

        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            $this->jsonResponse(["message" => "Request not found"], 404);
        }

        $wasApprovedNow = $this->model->updateRequestStatusIfPending($requestId, 1);
        if (!$wasApprovedNow) {
            $this->jsonResponse(["message" => "Request already processed"]);
        }

        $created = $this->model->createCategory($name);

        $notificationModel = new NotificationModel();
        $requestedName = (string)($request["suggested_name"] ?? $name);
        $notificationModel->createLocalizedNotification(
            (int)$request["requested_by"],
            "category_request_approved",
            $requestId,
            "notifications.category_request_approved",
            ["name" => $requestedName],
            "Your category request \"" . $requestedName . "\" was approved"
        );

        if ($created) {
            $this->jsonResponse(["message" => "Category created"]);
        }

        $this->jsonResponse(["message" => "Category already exists. Request marked as approved."]);
    }

    public function deleteCategory(): void {
        $this->requireAdmin();
        $data = $this->getJSONInput();

        if (!is_array($data)) {
            $this->jsonResponse(["message" => "Invalid request body"], 400);
        }

        $categoryId = (int)($data["category_id"] ?? 0);
        if ($categoryId <= 0) {
            $this->jsonResponse(["message" => "Valid category_id is required"], 400);
        }

        $result = $this->model->deleteCategoryAndPosts($categoryId);
        if (empty($result["ok"])) {
            $this->jsonResponse(["message" => $result["message"] ?? "Could not delete category"], 404);
        }

        $categoryName = (string)($result["category_name"] ?? "Category");
        $deletedPosts = (int)($result["deleted_posts"] ?? 0);

        $this->jsonResponse([
            "message" => "Deleted category '{$categoryName}' and {$deletedPosts} related posts.",
            "deleted_posts" => $deletedPosts
        ]);
    }

    public function reject(): void {
        $this->requireAdmin();
        $data = $this->getJSONInput();

        if (!is_array($data)) {
            $this->jsonResponse(["message" => "Invalid request body"], 400);
        }

        $requestId = (int)($data["request_id"] ?? 0);
        if ($requestId <= 0) {
            $this->jsonResponse(["message" => "Valid request_id is required"], 400);
        }

        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            $this->jsonResponse(["message" => "Request not found"], 404);
        }

        $wasRejectedNow = $this->model->updateRequestStatusIfPending($requestId, 2);
        if (!$wasRejectedNow) {
            $this->jsonResponse(["message" => "Request already processed"]);
        }

        $notificationModel = new NotificationModel();
        $requestedName = (string)($request["suggested_name"] ?? "Unnamed category");
        $notificationModel->createLocalizedNotification(
            (int)$request["requested_by"],
            "category_request_rejected",
            $requestId,
            "notifications.category_request_rejected",
            ["name" => $requestedName],
            "Your category request \"" . $requestedName . "\" was rejected"
        );

        $this->jsonResponse(["message" => "Request rejected"]);
    }

    public function userInterests(): void {
        $userId = $this->requireLogin();
        $interests = $this->model->getUserInterests($userId);
        $this->jsonResponse($interests);
    }

    public function route(string $action): void {
        switch ($action) {
            case "request":
                $this->request();
                break;
            case "list":
                $this->list();
                break;
            case "approve":
                $this->approve();
                break;
            case "reject":
                $this->reject();
                break;
            case "deleteCategory":
                $this->deleteCategory();
                break;
            case "summary":
                $this->summary();
                break;
            case "userInterests":
                $this->userInterests();
                break;
            default:
                $this->jsonResponse(["message" => "Invalid action"], 400);
        }
    }
}

if (isset($_GET["action"])) {
    $controller = new CategoryController();
    $controller->route($_GET["action"]);
}
