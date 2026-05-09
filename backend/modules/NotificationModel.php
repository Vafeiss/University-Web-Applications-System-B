<?php
/**
 * File: NotificationModel.php
 * Layer: Model
 * Module: Notifications
 * System: University Web Applications System B
 *
 * Description:
 * Data model for notification lifecycle. Handles notification creation, delivery,
 * read status tracking, and deletion. Supports both plain and localized (i18n) notifications.
 *
 * Functions:
 * - createNotification() → creates notification for user
 * - createLocalizedNotification() → creates i18n notification with key/params/fallback
 * - notifyAdmins() → sends notification to all admins
 * - notifyAdminsLocalized() → sends localized notification to admins
 * - getNotificationsByUser() → retrieves user notifications with status
 * - markAsRead() → marks single notification as read
 * - markAllAsRead() → marks all user notifications as read
 * - deleteByIdForUser() → removes notification
 * - deleteAllReadByUser() → removes all read notifications
 * - buildLocalizedPayload() → creates JSON payload for i18n rendering
 *
 * Security:
 * - User isolation: users only see their own notifications
 * - PDO prepared statements for all queries
 * - Localized payload encoding with JSON
 *
 * Used By:
 * - NotificationController
 * - CommentController
 * - PostController
 * - CategoryController
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . '/../config/database.php';

class NotificationModel {

    private PDO $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    private function getAdminUserIds(?int $excludeUserId = null): array {
        $query = "SELECT user_id FROM users WHERE role = 'admin'";

        if ($excludeUserId && $excludeUserId > 0) {
            $query .= " AND user_id <> :exclude_user_id";
        }

        $stmt = $this->conn->prepare($query);

        if ($excludeUserId && $excludeUserId > 0) {
            $stmt->execute([":exclude_user_id" => $excludeUserId]);
        } else {
            $stmt->execute();
        }

        return array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function notifyAdmins(string $type, ?int $referenceId, string $message, ?int $excludeUserId = null): void {
        $adminUserIds = $this->getAdminUserIds($excludeUserId);

        if (empty($adminUserIds)) {
            return;
        }

        foreach ($adminUserIds as $adminUserId) {
            $this->createNotification(
                $adminUserId,
                $type,
                $referenceId,
                $message
            );
        }
    }

    // φτιαχνει json με key + params + fallback, ωστε το frontend να κανει translate αναλογα με τη γλωσσα
    public static function buildLocalizedPayload(string $key, array $params = [], string $fallback = ""): string {
        $payload = [
            "i18n_key" => $key,
            "params" => (object)$params,
            "fallback" => $fallback
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    // notification με key + params αντι για σκετο string στη βαση
    public function createLocalizedNotification(int $userId, string $type, ?int $referenceId, string $key, array $params = [], string $fallback = "") {
        $payload = self::buildLocalizedPayload($key, $params, $fallback);
        return $this->createNotification($userId, $type, $referenceId, $payload);
    }

    // ιδιο με notifyAdmins αλλα με localized payload
    public function notifyAdminsLocalized(string $type, ?int $referenceId, string $key, array $params = [], string $fallback = "", ?int $excludeUserId = null): void {
        $adminUserIds = $this->getAdminUserIds($excludeUserId);

        if (empty($adminUserIds)) {
            return;
        }

        $payload = self::buildLocalizedPayload($key, $params, $fallback);

        foreach ($adminUserIds as $adminUserId) {
            $this->createNotification($adminUserId, $type, $referenceId, $payload);
        }
    }
    // αποθηκεύει μια νέα ειδοποίηση στη βάση δεδομένων
    public function createNotification($userId, $type, $referenceId, $message) {

        $query = "INSERT INTO notifications
                  (user_id, type, reference_id, message, is_read)
                  VALUES (:user_id, :type, :reference_id, :message, 0)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":user_id" => $userId,
            ":type" => $type,
            ":reference_id" => $referenceId,
            ":message" => $message
        ]);
    }
    // φέρνει dropdown list με τις 20 πιο πρόσφατες ειδοποιήσεις για έναν χρήστη, ταξινομημένες κατά ημερομηνία δημιουργίας
    public function getNotificationsByUser($userId) {

        $query = "SELECT notification_id, user_id, type, reference_id, message, is_read, created_at
                  FROM notifications
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC
                  LIMIT 20";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":user_id" => $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // σημειώνει μια ειδοποίηση ως αναγνωσμένη
    public function markAsRead($notificationId, $userId) {

        $query = "UPDATE notifications
                  SET is_read = 1
                  WHERE notification_id = :notification_id
                  AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":notification_id" => $notificationId,
            ":user_id" => $userId
        ]);
    }
 //καθαρίζει όλες τις ειδοποιήσεις ενός χρήστη ως αναγνωσμένες
    public function markAllAsRead($userId) {

        $query = "UPDATE notifications
                  SET is_read = 1
                  WHERE user_id = :user_id
                  AND is_read = 0";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":user_id" => $userId
        ]);
    }

    public function deleteAllReadByUser($userId): int {
        $query = "DELETE FROM notifications
                  WHERE user_id = :user_id
                  AND is_read = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":user_id" => $userId
        ]);

        return (int)$stmt->rowCount();
    }

    public function deleteByIdForUser($notificationId, $userId): bool {
        $query = "DELETE FROM notifications
                  WHERE notification_id = :notification_id
                  AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":notification_id" => $notificationId,
            ":user_id" => $userId
        ]);

        return $stmt->rowCount() > 0;
    }
    // στελνει ειδοποίηση σε όσους ακολουθούν τον author ενός post όταν δημιουργείται ένα νέο post, με το μήνυμα να περιλαμβάνει τον τίτλο του post και το όνομα του author
    public function notifyFollowersForPost($authorId, $postId, $postTitle) {

        $query = "SELECT f.follower_id, u.username AS author_name
                  FROM followers f
                  JOIN users u ON u.user_id = :author_id
                  WHERE f.followed_id = :author_id
                  AND f.status = 1
                  AND f.follower_id <> :author_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":author_id" => $authorId
        ]);

        $followers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($followers as $follower) {
            $authorName = (string)$follower['author_name'];
            $fallback = $authorName . " created a new post: " . $postTitle;

            $this->createLocalizedNotification(
                (int)$follower['follower_id'],
                "new_post_following",
                $postId,
                "notifications.new_post_following",
                [
                    "author" => $authorName,
                    "title" => $postTitle
                ],
                $fallback
            );
        }
    }
    // στέλνει ειδοποίηση σε users με ίδια category interest.
    public function notifyInterestedUsersForPost($authorId, $postId, $postTitle, $categoryId) {

        if (!$categoryId) {
            return;
        }

        $query = "SELECT DISTINCT ui.user_id, c.name AS category_name
                  FROM user_interest ui
                  JOIN categories c ON c.category_id = ui.category_id
                  WHERE ui.category_id = :category_id
                  AND ui.user_id <> :author_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":category_id" => $categoryId,
            ":author_id" => $authorId
        ]);

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $categoryName = (string)$user['category_name'];
            $fallback = "New post in your interest category (" . $categoryName . "): " . $postTitle;

            $this->createLocalizedNotification(
                (int)$user['user_id'],
                "new_post_interest",
                $postId,
                "notifications.new_post_interest",
                [
                    "category" => $categoryName,
                    "title" => $postTitle
                ],
                $fallback
            );
        }
    }
}