<?php

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
        $notificationModel->notifyAdmins(
            'admin_category_request',
            null,
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

        $created = $this->model->createCategory($name);
        $this->model->updateRequestStatus($requestId, 1);

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

        $this->model->updateRequestStatus($requestId, 2);
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