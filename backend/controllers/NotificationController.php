<?php
/**
 * File: NotificationController.php
 * Layer: Controller
 * Module: Notifications
 * System: University Web Applications System B
 *
 * Description:
 * Provides notification management endpoints for users. Supports retrieving
 * notifications, marking individual/all notifications as read, and deleting
 * read notifications.
 *
 * Functions:
 * - list() → retrieves all notifications for current user
 * - markRead() → marks single notification as read
 * - markAllRead() → marks all notifications as read
 * - deleteRead() → removes all read notifications
 * - deleteOne() → removes single notification
 *
 * Security:
 * - requireLogin() enforces authentication
 * - User isolation: users only see their own notifications
 * - Input validation on notification_id
 *
 * Used By:
 * - frontend notifications panel (AJAX calls)
 *
 * Author:Pelagia Koniotaki
 * Date: 2026
 */

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

    public function deleteRead() {
        $userId = $this->requireLogin();

        $deletedCount = $this->notificationModel->deleteAllReadByUser($userId);

        $this->jsonResponse([
            "message" => "Read notifications deleted",
            "deleted" => $deletedCount
        ]);
    }

    public function deleteOne() {
        $userId = $this->requireLogin();
        $data = $this->getJSONInput();

        $notificationId = (int)($data['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            $this->jsonResponse(["message" => "Notification ID required"], 400);
        }

        $deleted = $this->notificationModel->deleteByIdForUser($notificationId, $userId);
        if (!$deleted) {
            $this->jsonResponse(["message" => "Notification not found"], 404);
        }

        $this->jsonResponse(["message" => "Notification deleted"]);
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

        case 'deleteRead':
            $controller->deleteRead();
            break;

        case 'deleteOne':
            $controller->deleteOne();
            break;
    }
}