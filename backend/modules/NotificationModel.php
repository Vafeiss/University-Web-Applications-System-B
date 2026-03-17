<?php

require_once __DIR__ . '/../config/database.php';

class NotificationModel {

    private PDO $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
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
            $message = $follower['author_name'] . " created a new post: " . $postTitle;

            $this->createNotification(
                (int)$follower['follower_id'],
                "new_post_following",
                $postId,
                $message
            );
        }
    }
    // στέλνει ειδοποίηση σε users με ίδια category interest.
    // Χρησιμοποιεί ενιαίο μήνυμα για anonymous και non-anonymous post.
    public function notifyInterestedUsersForPost($authorId, $postId, $postTitle, $categoryId, $isAnonymous = false) {

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
            $message = "New post in your interest category (" . $user['category_name'] . "): " . $postTitle;

            $this->createNotification(
                (int)$user['user_id'],
                "new_post_interest",
                $postId,
                $message
            );
        }
    }
}