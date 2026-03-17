<?php

header("Content-Type: application/json");

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../modules/NotificationModel.php';

class NotificationController extends BaseController {

    private NotificationModel $notificationModel;

    public function __construct() {
        $this->notificationModel = new NotificationModel();
    }

    public function list() {
        $userId = $this->requireLogin();

        $notifications = $this->notificationModel->getNotificationsByUser($userId);

        $this->jsonResponse($notifications);
    }

    public function markRead() {
        $userId = $this->requireLogin();
        $data = $this->getJSONInput();

        $notificationId = (int)($data['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            $this->jsonResponse(["message" => "Notification ID required"], 400);
        }

        $this->notificationModel->markAsRead($notificationId, $userId);

        $this->jsonResponse(["message" => "Notification marked as read"]);
    }

    public function markAllRead() {
        $userId = $this->requireLogin();

        $this->notificationModel->markAllAsRead($userId);

        $this->jsonResponse(["message" => "All notifications marked as read"]);
    }
}

if (isset($_GET['action'])) {

    $controller = new NotificationController();

    switch ($_GET['action']) {
        case 'list':
            $controller->list();
            break;

        case 'markRead':
            $controller->markRead();
            break;

        case 'markAllRead':
            $controller->markAllRead();
            break;
    }
}