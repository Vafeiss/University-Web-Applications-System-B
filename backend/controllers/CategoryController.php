<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . "/../modules/CategoryModel.php";

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
        $this->jsonResponse(["message" => "Category request submitted"]);
    }

    public function list(): void {
        $this->requireAdmin();
        $requests = $this->model->getPendingRequests();
        $this->jsonResponse($requests);
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

        $this->model->createCategory($name);
        $this->model->updateRequestStatus($requestId, 1);
        $this->jsonResponse(["message" => "Category created"]);
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