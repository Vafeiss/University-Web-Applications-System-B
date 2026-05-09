<?php
/**
 * File: PostModel.php
 * Layer: Model
 * Module: Posts
 * System: University Web Applications System B
 *
 * Description:
 * Core data model for post management. Handles post creation, querying, deletion,
 * attachments, reports, and admin moderation. Integrates ban checking with post approval.
 *
 * Functions:
 * - createPost() → inserts new post with pending status
 * - saveAttachment() → stores file metadata for post
 * - getPostById() → retrieves single post with full details
 * - getPostsByUser() → lists user's posts with pagination
 * - getAllPosts() → retrieves all posts with optional filters
 * - updatePostStatus() → sets post status (approved/rejected/deleted)
 * - requestDeletePost() → user submits deletion request
 * - reportPost() → user reports post for moderation
 * - approvePost() → admin approves pending post
 * - rejectPost() → admin rejects pending post
 * - deletePost() → marks post as deleted
 * - getPostReportCount() → counts reports on post
 * - getUserReportCount() → counts user's reports for ban detection
 *
 * Security:
 * - PDO prepared statements for all queries
 * - Ban status checking (automatic banning on high reports)
 * - Post status workflow enforcement
 * - Attachment path sanitization
 *
 * Used By:
 * - PostController
 * - SearchController
 *
 * Author:Pelagia Koniotaki & Antriani Theofanous 
 * Date: 2026
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/NotificationModel.php';

class PostModel {

    private PDO $conn;
    private const BAN_MESSAGE = "Your account has been banned because it exceeded the allowed number of reports.";
    private ?bool $banColumnsAvailable = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    private function hasBanColumns(): bool {

        if ($this->banColumnsAvailable !== null) {
            return $this->banColumnsAvailable;
        }

        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM users LIKE 'is_banned'");
            $this->banColumnsAvailable = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            $this->banColumnsAvailable = false;
        }

        return $this->banColumnsAvailable;
    }

    // Create_Post()
    public function createPost($user_id, $title, $content, $category_id, $is_anonymous = 0) {

        $query = "INSERT INTO posts 
                  (user_id, title, content, category_id, status, is_anonymous) 
                  VALUES (:user_id, :title, :content, :category_id, 0, :is_anonymous)";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':content' => $content,
            ':category_id' => $category_id,
            ':is_anonymous' => $is_anonymous
        ]);

        // επιστρέφει το ID του post ,με post_id ως primary key, για να χρησιμοποιηθεί για την αποθήκευση των attachments
        return $this->conn->lastInsertId();
    }

    // Save attachment
    public function saveAttachment($post_id, $file_name, $file_path, $file_size, $file_type) {

        $query = "INSERT INTO attachments 
                  (post_id, file_name, file_path, file_size, file_type)
                  VALUES (:post_id, :file_name, :file_path, :file_size, :file_type)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':post_id' => $post_id,
            ':file_name' => $file_name,
            ':file_path' => $file_path,
            ':file_size' => $file_size,
            ':file_type' => $file_type
        ]);
    }

    public function getAttachmentById($attachment_id) {

        $query = "SELECT a.*, p.user_id AS post_owner_id, p.status, p.deleted
                  FROM attachments a
                  JOIN posts p ON a.post_id = p.post_id
                  WHERE a.attachment_id = :attachment_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':attachment_id' => $attachment_id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTokenBalance(int $user_id): int {

        $query = "SELECT token_balance
                  FROM users
                  WHERE user_id = :user_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id
        ]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function hasUsedFreeDownloadToday(int $user_id): bool {

        $query = "SELECT transaction_id
                  FROM transactions
                  WHERE user_id = :user_id
                  AND token_charge = 0
                  AND DATE(timestamp) = CURDATE()
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id
        ]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function recordTokenTransaction(int $user_id, int $tokenCharge): bool {

        $query = "INSERT INTO transactions (user_id, token_charge, timestamp)
                  VALUES (:user_id, :token_charge, NOW())";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':user_id' => $user_id,
            ':token_charge' => $tokenCharge
        ]);
    }

    public function rewardApprovedPost(int $user_id): void {
        $this->conn->beginTransaction();

        try {
            $query = "UPDATE users
                      SET token_balance = token_balance + 1
                      WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id
            ]);

            $this->recordTokenTransaction($user_id, 1);

            $this->conn->commit();
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $exception;
        }
    }

    public function processDownloadTokenCharge(int $user_id, int $tokenCharge): void {
        $this->conn->beginTransaction();

        try {
            if ($tokenCharge > 0) {
                $query = "UPDATE users
                          SET token_balance = token_balance - :token_charge
                          WHERE user_id = :user_id";

                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':token_charge' => $tokenCharge
                ]);
            }

            $this->recordTokenTransaction($user_id, -$tokenCharge);

            $this->conn->commit();
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $exception;
        }
    }

    // Show_Post()
    public function getApprovedPosts() {

        $query = "SELECT p.*, u.username, c.name AS category
                  FROM posts p
                  JOIN users u ON p.user_id = u.user_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.status = 1 AND p.deleted = 0
                  ORDER BY p.timestamp DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAdminPosts() {

        $query = "SELECT p.*, u.username, c.name AS category
                  FROM posts p
                  JOIN users u ON p.user_id = u.user_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.status = 1 AND p.deleted = 0
                  ORDER BY p.timestamp DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPostById($post_id) {
                // φορτώνει ένα post μαζί με το username του δημιουργού και την κατηγορία
        $query = "SELECT p.*, u.username, c.name AS category
                                    FROM posts p
                                    JOIN users u ON p.user_id = u.user_id
                                    LEFT JOIN categories c ON p.category_id = c.category_id
                                    WHERE p.post_id = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':post_id' => $post_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get attachments for a post
    public function getAttachmentsByPost($post_id) {

        $query = "SELECT * 
                  FROM attachments
                  WHERE post_id = :post_id
                  ORDER BY timestamp ASC";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':post_id' => $post_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete_Post()
    public function postDeleteRequestExists($post_id, $user_id) {

        // Έλεγχος ΜΟΝΟ για pending request (status=0).
        // Παλιά rejected requests (status=2) δεν εμποδίζουν νέο request.
        $query = "SELECT request_id
                  FROM post_delete_requests
                  WHERE post_id = :post_id
                  AND requested_by = :user_id
                  AND status = 0";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ":post_id" => $post_id,
            ":user_id" => $user_id
        ]);

        return $stmt->fetch() ? true : false;
    }

    // Create post delete request
    public function createPostDeleteRequest($post_id, $user_id, $reason) {

        $query = "INSERT INTO post_delete_requests
                  (post_id, requested_by, reason)
                  VALUES (:post_id, :user_id, :reason)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":post_id" => $post_id,
            ":user_id" => $user_id,
            ":reason" => $reason
        ]);
    }

    public function postReportExists($post_id, $user_id) {

        $query = "SELECT report_id
                  FROM content_reports
                  WHERE content_type = 'post'
                  AND content_id = :post_id
                  AND reported_by = :user_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ":post_id" => $post_id,
            ":user_id" => $user_id
        ]);

        return $stmt->fetch() ? true : false;
    }

    public function createPostReport($post_id, $user_id, $reason) {

        $query = "INSERT INTO content_reports
                  (content_type, content_id, reported_by, reason)
                  VALUES ('post', :post_id, :user_id, :reason)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":post_id" => $post_id,
            ":user_id" => $user_id,
            ":reason" => $reason
        ]);
    }

    public function adminDeletePost($post_id, bool $notifyOwner = true) {

        // Φέρνουμε post info & attachments ΠΡΙΝ τη διαγραφή
        $post = $this->getPostById($post_id);
        if (!$post) {
            return false;
        }

        $postOwnerId = (int) ($post['user_id'] ?? 0);
        $postTitle = (string) ($post['title'] ?? '');
        $attachments = $this->getAttachmentsByPost($post_id);

        $this->conn->beginTransaction();

        try {
            // 1. Φέρνουμε comment IDs για cleanup των σχετικών δεδομένων
            $stmt = $this->conn->prepare(
                "SELECT comment_id FROM comments WHERE post_id = :post_id"
            );
            $stmt->execute([":post_id" => $post_id]);
            $commentIds = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));

            // 2. Καθαρισμός δεδομένων που σχετίζονται με τα σχόλια
            if (!empty($commentIds)) {
                $placeholders = [];
                $params = [];
                foreach ($commentIds as $index => $commentId) {
                    $placeholder = ":comment_id_" . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $commentId;
                }

                $stmt = $this->conn->prepare(
                    "DELETE FROM comment_delete_requests
                     WHERE comment_id IN (" . implode(", ", $placeholders) . ")"
                );
                $stmt->execute($params);

                $stmt = $this->conn->prepare(
                    "DELETE FROM content_reports
                     WHERE content_type = 'comment'
                     AND content_id IN (" . implode(", ", $placeholders) . ")"
                );
                $stmt->execute($params);

                $stmt = $this->conn->prepare(
                    "DELETE FROM notifications
                     WHERE type = 'admin_comment_delete_request'
                     AND reference_id IN (" . implode(", ", $placeholders) . ")"
                );
                $stmt->execute($params);
            }

            // 3. Διαγραφή σχολίων του post
            $stmt = $this->conn->prepare(
                "DELETE FROM comments WHERE post_id = :post_id"
            );
            $stmt->execute([":post_id" => $post_id]);

            // 4. Διαγραφή attachments rows
            $stmt = $this->conn->prepare(
                "DELETE FROM attachments WHERE post_id = :post_id"
            );
            $stmt->execute([":post_id" => $post_id]);

            // 5. Διαγραφή post_delete_requests για το post
            $stmt = $this->conn->prepare(
                "DELETE FROM post_delete_requests WHERE post_id = :post_id"
            );
            $stmt->execute([":post_id" => $post_id]);

            // 6. Διαγραφή content_reports για το post
            $stmt = $this->conn->prepare(
                "DELETE FROM content_reports
                 WHERE content_type = 'post' AND content_id = :post_id"
            );
            $stmt->execute([":post_id" => $post_id]);

            // 7. Διαγραφή ΟΛΩΝ των notifications σχετικών με το post
            $stmt = $this->conn->prepare(
                "DELETE FROM notifications
                 WHERE reference_id = :post_id
                 AND type IN (
                     'admin_pending_post',
                     'admin_post_delete_request',
                     'admin_post_report',
                     'post_approved',
                     'post_rejected',
                     'delete_approved',
                     'delete_rejected',
                     'report_approved',
                     'report_rejected',
                     'new_post_following',
                     'new_post_interest',
                     'comment'
                 )"
            );
            $stmt->execute([":post_id" => $post_id]);

            // 8. HARD DELETE του post (όχι soft delete πια)
            $stmt = $this->conn->prepare(
                "DELETE FROM posts WHERE post_id = :post_id"
            );
            $stmt->execute([":post_id" => $post_id]);

            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            // 9. Ειδοποίηση στον owner του post ότι ο admin το διέγραψε
            // (παραλείπεται όταν η διαγραφή γίνεται από έγκριση delete request - ο χρήστης
            // λαμβάνει διαφορετικό μήνυμα "delete_approved" από το approve flow)
            if ($notifyOwner && $postOwnerId > 0) {
                $payload = NotificationModel::buildLocalizedPayload(
                    'notifications.post_deleted_by_admin',
                    ['title' => $postTitle],
                    'Your post "' . $postTitle . '" was deleted by an admin.'
                );

                $stmt = $this->conn->prepare(
                    "INSERT INTO notifications
                     (user_id, type, reference_id, message, is_read)
                     VALUES (:user_id, 'post_deleted_by_admin', NULL, :message, 0)"
                );
                $stmt->execute([
                    ":user_id" => $postOwnerId,
                    ":message" => $payload
                ]);
            }

            if ($postOwnerId > 0) {
                $this->banUserIfThresholdReached($postOwnerId);
            }

            $this->conn->commit();

            // 10. Διαγραφή φυσικών αρχείων από το δίσκο (μετά το commit)
            foreach ($attachments as $attachment) {
                $relativePath = ltrim((string) ($attachment['file_path'] ?? ''), '/');
                if ($relativePath === '') {
                    continue;
                }
                $fullPath = __DIR__ . '/../../' . $relativePath;
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $exception;
        }
    }

    public function hardDeleteRejectedPostByOwner(int $post_id, int $user_id): bool {
        $attachments = $this->getAttachmentsByPost($post_id);

        $this->conn->beginTransaction();

        try {
            $query = "SELECT post_id
                      FROM posts
                      WHERE post_id = :post_id
                      AND user_id = :user_id
                      AND status = 2
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id,
                ":user_id" => $user_id
            ]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->conn->rollBack();
                return false;
            }

            $query = "SELECT comment_id
                      FROM comments
                      WHERE post_id = :post_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id
            ]);
            $commentIds = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($commentIds)) {
                $placeholders = [];
                $params = [];

                foreach ($commentIds as $index => $commentId) {
                    $placeholder = ":comment_id_" . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $commentId;
                }

                $query = "DELETE FROM comment_delete_requests
                          WHERE comment_id IN (" . implode(", ", $placeholders) . ")";
                $stmt = $this->conn->prepare($query);
                $stmt->execute($params);

                $query = "DELETE FROM content_reports
                          WHERE content_type = 'comment'
                          AND content_id IN (" . implode(", ", $placeholders) . ")";
                $stmt = $this->conn->prepare($query);
                $stmt->execute($params);

                $query = "DELETE FROM notifications
                          WHERE type = 'admin_comment_delete_request'
                          AND reference_id IN (" . implode(", ", $placeholders) . ")";
                $stmt = $this->conn->prepare($query);
                $stmt->execute($params);
            }

            $query = "DELETE FROM content_reports
                      WHERE content_type = 'post'
                      AND content_id = :post_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id
            ]);

            $query = "DELETE FROM notifications
                      WHERE reference_id = :post_id
                      AND type IN (
                          'admin_pending_post',
                          'admin_post_delete_request',
                          'admin_post_report',
                          'post_approved',
                          'post_rejected',
                          'delete_approved',
                          'delete_rejected',
                          'report_approved',
                          'report_rejected',
                          'new_post_following',
                          'new_post_interest',
                          'comment'
                      )";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id
            ]);

            $query = "DELETE FROM posts
                      WHERE post_id = :post_id
                      AND user_id = :user_id
                      AND status = 2";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id,
                ":user_id" => $user_id
            ]);

            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->commit();

            foreach ($attachments as $attachment) {
                $relativePath = ltrim((string) ($attachment["file_path"] ?? ""), "/");
                if ($relativePath === "") {
                    continue;
                }

                $fullPath = __DIR__ . "/../../" . $relativePath;
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $exception;
        }
    }

    // approve post by admin
    public function approvePost($post_id) {
        // Ενημέρωση της κατάστασης του post σε "approved" (status = 1)
        $query = "UPDATE posts
                  SET status = 1
                  WHERE post_id = :post_id
                  AND status <> 1";
        // Εκτέλεση του query
        $stmt = $this->conn->prepare($query);
        // Επιστροφή του αποτελέσματος του query
        $stmt->execute([
            ":post_id" => $post_id
        ]);

        return $stmt->rowCount() > 0;
    }

    public function rejectPost($post_id, $reason) {

        $query = "UPDATE posts
                  SET status = 2, deleted = 1, rejection_reason = :reason
                  WHERE post_id = :post_id
                  AND status <> 2";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ":post_id" => $post_id,
            ":reason" => $reason
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getPendingPosts() {

        $query = "SELECT p.*, u.username, c.name AS category
                  FROM posts p
                  JOIN users u ON p.user_id = u.user_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.status = 0 AND p.deleted = 0
                  ORDER BY p.timestamp DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Get delete requests for admin review
    public function getDeleteRequests() {

        $query = "SELECT r.request_id, r.reason, r.created_at AS timestamp,
                         p.post_id, p.title,
                         u.username
                  FROM post_delete_requests r
                  JOIN posts p ON r.post_id = p.post_id
                  JOIN users u ON r.requested_by = u.user_id
                  WHERE r.status = 0
                  ORDER BY r.created_at DESC";
        // Εκτέλεση του query
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        // Επιστροφή των αποτελεσμάτων
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Admin approves delete request
    public function approveDeleteRequest($request_id) {
        // Φέρνουμε το post_id από το pending request (status=0)
        $stmt = $this->conn->prepare(
            "SELECT post_id
             FROM post_delete_requests
             WHERE request_id = :request_id
             AND status = 0
             LIMIT 1"
        );
        $stmt->execute([
            ":request_id" => $request_id
        ]);

        $postId = (int) ($stmt->fetchColumn() ?: 0);
        if ($postId <= 0) {
            return false;
        }

        // HARD delete του post + όλων των σχετικών (notifications, attachments,
        // comments, reports, requests). Δεν στέλνουμε post_deleted_by_admin
        // notification γιατί ο controller στέλνει "delete_approved".
        return $this->adminDeletePost($postId, false);
    }
    // Admin rejects delete request
    public function rejectDeleteRequest($request_id) {
        // Ενημέρωση της κατάστασης του αιτήματος σε "rejected"
        $query = "UPDATE post_delete_requests
                  SET status = 2
                  WHERE request_id = :request_id
                  AND status = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":request_id" => $request_id
        ]);

        return $stmt->rowCount() > 0;
    }
    // φερνει απο την βαση τα posts που εχουν γινει report
    public function getReportedContent() {

        $query = "SELECT r.report_id,
                         r.content_type,
                         r.content_id,
                         r.reason,
                         r.created,
                    u.username,
                    p.title AS post_title
                  FROM content_reports r
                  JOIN users u ON r.reported_by = u.user_id
                LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.post_id
                WHERE r.status = 0
                AND r.content_type = 'post'
                  ORDER BY r.created DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getReportTarget(int $report_id): ?array {

        $query = "SELECT r.report_id,
                         r.content_type,
                         r.content_id,
                         r.status,
                         CASE
                             WHEN r.content_type = 'post' THEN p.user_id
                             WHEN r.content_type = 'comment' THEN c.user_id
                             ELSE NULL
                         END AS reported_user_id
                  FROM content_reports r
                  LEFT JOIN posts p
                    ON r.content_type = 'post'
                   AND r.content_id = p.post_id
                  LEFT JOIN comments c
                    ON r.content_type = 'comment'
                   AND r.content_id = c.comment_id
                  WHERE r.report_id = :report_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':report_id' => $report_id
        ]);

        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        return $report ?: null;
    }

    private function countAcceptedReportsForUser(int $user_id): int {

        $query = "SELECT COUNT(DISTINCT r.report_id)
                  FROM content_reports r
                  LEFT JOIN posts p
                    ON r.content_type = 'post'
                   AND r.content_id = p.post_id
                  LEFT JOIN comments c
                    ON r.content_type = 'comment'
                   AND r.content_id = c.comment_id
                  WHERE r.status = 1
                    AND (
                        (r.content_type = 'post' AND p.user_id = :user_id)
                        OR
                        (r.content_type = 'comment' AND c.user_id = :user_id)
                    )";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id
        ]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function notifyBannedUser(int $user_id): void {

        $query = "SELECT notification_id
                  FROM notifications
                  WHERE user_id = :user_id
                    AND type = 'ban'
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id
        ]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $query = "INSERT INTO notifications (user_id, type, reference_id, message, is_read, created_at)
                  VALUES (:user_id, 'ban', NULL, :message, 0, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':message' => "Your account has been banned due to multiple accepted reports."
        ]);
    }

    private function banUserIfThresholdReached(int $user_id): void {

        if ($user_id <= 0) {
            return;
        }

        if (!$this->hasBanColumns()) {
            return;
        }

        $acceptedReports = $this->countAcceptedReportsForUser($user_id);
        if ($acceptedReports < 3) {
            return;
        }

        $query = "SELECT is_banned
                  FROM users
                  WHERE user_id = :user_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id
        ]);

        $isAlreadyBanned = (int) ($stmt->fetchColumn() ?: 0) === 1;
        if ($isAlreadyBanned) {
            return;
        }

        $query = "UPDATE users
                  SET is_banned = 1,
                      ban_reason = :ban_reason
                  WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':ban_reason' => self::BAN_MESSAGE
        ]);

        $this->notifyBannedUser($user_id);
    }

    // ενημερωνει την βαση για να εγκριθεί ένα report και να διαγραφεί το σχετικό post ή comment
    public function approveReport($report_id) {

        // βρίσκουμε τι report είναι
        $query = "SELECT content_type, content_id, status
                  FROM content_reports
                  WHERE report_id = :report_id";
        $this->conn->beginTransaction();

        try {
            $report = $this->getReportTarget((int) $report_id);

            if (!$report) {
                $this->conn->rollBack();
                return false;
            }

            if ((int) ($report['status'] ?? 0) !== 1) {
                if ($report['content_type'] === 'post') {

                    $query = "UPDATE posts
                              SET deleted = 1
                              WHERE post_id = :id";

                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([
                        ':id' => $report['content_id']
                    ]);
                }

                if ($report['content_type'] === 'comment') {

                    $query = "UPDATE comments
                              SET comment_content = '[Removed by moderation]'
                              WHERE comment_id = :id";

                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([
                        ':id' => $report['content_id']
                    ]);
                }

                $query = "UPDATE content_reports
                          SET status = 1
                          WHERE report_id = :report_id";

                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    ':report_id' => $report_id
                ]);
            }

            $reportedUserId = (int) ($report['reported_user_id'] ?? 0);
            if ($reportedUserId > 0) {
                $this->banUserIfThresholdReached($reportedUserId);
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $exception;
        }
    }
    // ενημερωνει την βαση για να απορριφθεί ένα report και να παραμείνει το σχετικό post 
    public function rejectReport($report_id){

        $query = "UPDATE content_reports
                  SET status = 2
                  WHERE report_id = :report_id
                  AND status = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":report_id"=>$report_id
        ]);

        return $stmt->rowCount() > 0;
    }

public function getDeleteRequestById($request_id) {

    $query = "SELECT r.request_id,
                     r.requested_by,
                     r.post_id,
                     p.title
              FROM post_delete_requests r
              JOIN posts p ON r.post_id = p.post_id
              WHERE r.request_id = :request_id
              LIMIT 1";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":request_id" => $request_id
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

public function getReportById($report_id) {

    $query = "SELECT r.report_id,
                     r.reported_by,
                     r.content_type,
                     r.content_id,
                     p.post_id,
                     p.title AS post_title
              FROM content_reports r
              LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.post_id
              WHERE r.report_id = :report_id
              LIMIT 1";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":report_id" => $report_id
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
    // φερνει όλα τα pending delete requests για admin 
public function getCommentDeleteRequests() {

    $query = "SELECT r.request_id,
                     r.reason,
                     r.created,
                     c.comment_id,
                c.post_id,
                     c.comment_content,
                     u.username
              FROM comment_delete_requests r
              JOIN comments c ON r.comment_id = c.comment_id
              JOIN users u ON r.requested_by = u.user_id
              WHERE r.status = 0
              ORDER BY r.created DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// updates database για ενα approved comment delete request
public function approveCommentDelete($request_id) {

    $query = "SELECT comment_id
              FROM comment_delete_requests
              WHERE request_id = :request_id";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":request_id"=>$request_id
    ]);

    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$request){
        return false;
    }

    $query = "SELECT user_id
              FROM comments
              WHERE comment_id = :comment_id
              LIMIT 1";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ':comment_id' => $request['comment_id']
    ]);

    $commentOwnerId = (int) ($stmt->fetchColumn() ?: 0);

    $this->conn->beginTransaction();

    try {
        $query = "UPDATE content_reports
                  SET status = 1
                  WHERE content_type = 'comment'
                    AND content_id = :comment_id
                    AND status = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':comment_id' => $request['comment_id']
        ]);

        if ($commentOwnerId > 0) {
            $this->banUserIfThresholdReached($commentOwnerId);
        }

        $query = "DELETE FROM comments
                  WHERE comment_id = :comment_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":comment_id"=>$request['comment_id']
        ]);

        $query = "UPDATE comment_delete_requests
                  SET status = 1
                  WHERE request_id = :request_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":request_id"=>$request_id
        ]);

        $this->conn->commit();
        return true;
    } catch (Throwable $exception) {
        if ($this->conn->inTransaction()) {
            $this->conn->rollBack();
        }

        throw $exception;
    }
}
// updates database για ενα rejected comment delete request
public function rejectCommentDelete($request_id){

    $query = "UPDATE comment_delete_requests
              SET status = 2
              WHERE request_id = :request_id";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        ":request_id"=>$request_id
    ]);
}
// ===========================================
// Get posts based on user interests
// ===========================================
public function getPostsForUser($user_id) {

    // Βήμα 1: βρίσκουμε ποιες categories έχει επιλέξει ο user
    $query = "SELECT category_id
              FROM user_interest
              WHERE user_id = :user_id";

    $stmt = $this->conn->prepare($query);

    $stmt->execute([
        ":user_id" => $user_id
    ]);

    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);


    // Βήμα 2: αν δεν έχει επιλέξει categories → δείχνουμε όλα τα posts
    if (empty($categories)) {
        return $this->getApprovedPosts();
    }


    // Βήμα 3: δημιουργούμε placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($categories), '?'));


    // Βήμα 4: φέρνουμε posts μόνο από αυτές τις categories
    $query = "SELECT p.*, u.username, c.name AS category
              FROM posts p
              JOIN users u ON p.user_id = u.user_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE p.status = 1
              AND p.deleted = 0
              AND p.category_id IN ($placeholders)
              ORDER BY p.timestamp DESC";

    $stmt = $this->conn->prepare($query);

    $stmt->execute($categories);
    // αν δεν εχει επιλέξει καμία κατηγορία, θα επιστρέψει όλα τα posts, αλλιώς μόνο αυτά που ανήκουν στις επιλεγμένες κατηγορίες
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPostsFromFollowing($user_id) {

    $query = "SELECT p.*, u.username, c.name AS category
              FROM posts p
              JOIN followers f ON f.followed_id = p.user_id
              JOIN users u ON p.user_id = u.user_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE f.follower_id = :user_id
              AND f.status = 1
              AND p.status = 1
              AND p.deleted = 0
              AND p.is_anonymous = 0
              ORDER BY p.timestamp DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":user_id" => $user_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getPostsByUserWithStatus($user_id) {

    $query = "SELECT p.post_id,
                     p.title,
                     p.status,
                     p.deleted,
                     p.rejection_reason,
                     p.timestamp,
                     c.name AS category
              FROM posts p
              LEFT JOIN categories c ON p.category_id = c.category_id
              WHERE p.user_id = :user_id
              ORDER BY p.timestamp DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":user_id" => $user_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getDeleteRequestsByUser($user_id) {

    $query = "SELECT r.request_id,
                     r.reason,
                     r.status,
                     r.created_at AS timestamp,
                     p.post_id,
                     p.title,
                     p.status AS post_status,
                     p.deleted
              FROM post_delete_requests r
              JOIN posts p ON r.post_id = p.post_id
              WHERE r.requested_by = :user_id
              ORDER BY r.created_at DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":user_id" => $user_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getReportsByUser($user_id) {

    $query = "SELECT r.report_id,
                     r.content_type,
                     r.content_id,
                     r.reason,
                     r.status,
                     r.created,
                     p.post_id,
                     p.title AS post_title,
                     p.deleted
              FROM content_reports r
              LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.post_id
              WHERE r.reported_by = :user_id
              AND r.content_type = 'post'
              ORDER BY r.created DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":user_id" => $user_id
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
