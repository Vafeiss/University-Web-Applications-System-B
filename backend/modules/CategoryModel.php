<?php
/**
 * File: CategoryModel.php
 * Layer: Model
 * Module: Categories
 * System: University Web Applications System B
 *
 * Description:
 * Data model for categories and category requests. Handles database operations
 * for category management including user requests, admin approvals, and deletion
 * with cascading post removal.
 *
 * Functions:
 * - requestCategory() → inserts new category suggestion from user
 * - getPendingRequests() → retrieves awaiting admin approval requests
 * - getExistingCategories() → lists all approved categories
 * - approveCategoryRequest() → admin approves and creates category
 * - rejectCategoryRequest() → admin rejects category request
 * - deleteCategoryAndPosts() → removes category and related posts
 * - getCategoryById() → retrieves category details
 *
 * Security:
 * - PDO prepared statements for all queries
 * - Input validation on category names
 * - Admin-only operations for approvals/rejections
 *
 * Used By:
 * - CategoryController
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . "/../config/database.php";

class CategoryModel {

    private $db;

    public function __construct(){
        $database = new Database();
        $this->db = $database->connect();
    }

    // ===============================
    // USER: request new category
    // ===============================
    public function requestCategory($userId, $name){

        $stmt = $this->db->prepare("
            INSERT INTO category_requests
            (requested_by, suggested_name)
            VALUES (?, ?)
        ");

        return $stmt->execute([$userId, $name]);
    }

    // ===============================
    // ADMIN: get pending requests
    // ===============================
    public function getPendingRequests(){

        $stmt = $this->db->query("
            SELECT
                cr.request_id,
                cr.suggested_name,
                cr.created_at,
                u.username
            FROM category_requests cr
            JOIN users u
            ON u.user_id = cr.requested_by
            WHERE cr.status = 0
            ORDER BY cr.created_at DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===============================
    // ADMIN: get existing categories
    // ===============================
    public function getExistingCategories(){

        $stmt = $this->db->query(
            "SELECT MIN(category_id) AS category_id, name
             FROM categories
             GROUP BY name
             ORDER BY name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===============================
    // ADMIN: delete category and all related posts
    // ===============================
    public function deleteCategoryAndPosts(int $categoryId): array {

        if ($categoryId <= 0) {
            return [
                "ok" => false,
                "message" => "Invalid category id"
            ];
        }

        $this->db->beginTransaction();

        try {
            $categoryStmt = $this->db->prepare(
                "SELECT category_id, name
                 FROM categories
                 WHERE category_id = ?
                 LIMIT 1"
            );
            $categoryStmt->execute([$categoryId]);
            $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                $this->db->rollBack();
                return [
                    "ok" => false,
                    "message" => "Category not found"
                ];
            }

            $categoryName = trim((string)($category["name"] ?? ""));

            $postCountStmt = $this->db->prepare(
                "SELECT COUNT(*)
                 FROM posts p
                 INNER JOIN categories c ON p.category_id = c.category_id
                 WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))"
            );
            $postCountStmt->execute([$categoryName]);
            $postCount = (int)$postCountStmt->fetchColumn();

            $deleteCommentRequestsStmt = $this->db->prepare(
                "DELETE cdr
                 FROM comment_delete_requests cdr
                 INNER JOIN comments c ON cdr.comment_id = c.comment_id
                 INNER JOIN posts p ON c.post_id = p.post_id
                 INNER JOIN categories cat ON p.category_id = cat.category_id
                 WHERE LOWER(TRIM(cat.name)) = LOWER(TRIM(?))"
            );
            $deleteCommentRequestsStmt->execute([$categoryName]);

            $deleteCommentReportsStmt = $this->db->prepare(
                "DELETE cr
                 FROM content_reports cr
                 INNER JOIN comments c ON cr.content_type = 'comment' AND cr.content_id = c.comment_id
                 INNER JOIN posts p ON c.post_id = p.post_id
                 INNER JOIN categories cat ON p.category_id = cat.category_id
                 WHERE LOWER(TRIM(cat.name)) = LOWER(TRIM(?))"
            );
            $deleteCommentReportsStmt->execute([$categoryName]);

            $deletePostReportsStmt = $this->db->prepare(
                "DELETE FROM content_reports
                 WHERE content_type = 'post'
                 AND content_id IN (
                     SELECT p.post_id
                     FROM posts p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))
                 )"
            );
            $deletePostReportsStmt->execute([$categoryName]);

            $deletePostDeleteRequestsStmt = $this->db->prepare(
                "DELETE FROM post_delete_requests
                 WHERE post_id IN (
                     SELECT p.post_id
                     FROM posts p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))
                 )"
            );
            $deletePostDeleteRequestsStmt->execute([$categoryName]);

            $deleteNotificationsStmt = $this->db->prepare(
                "DELETE FROM notifications
                 WHERE type IN ('new_post_following', 'new_post_interest')
                 AND reference_id IN (
                     SELECT p.post_id
                     FROM posts p
                     INNER JOIN categories c ON p.category_id = c.category_id
                     WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))
                 )"
            );
            $deleteNotificationsStmt->execute([$categoryName]);

            $deletePostsStmt = $this->db->prepare(
                "DELETE p
                 FROM posts p
                 INNER JOIN categories c ON p.category_id = c.category_id
                 WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))"
            );
            $deletePostsStmt->execute([$categoryName]);

            $deleteCategoryStmt = $this->db->prepare(
                "DELETE FROM categories
                 WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))"
            );
            $deleteCategoryStmt->execute([$categoryName]);

            $this->db->commit();

            return [
                "ok" => true,
                "category_name" => $categoryName,
                "deleted_posts" => $postCount
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    // ===============================
    // ADMIN: create category
    // ===============================
    public function createCategory($name){

        $normalizedName = trim((string)$name);

        if ($normalizedName === "") {
            return false;
        }

        $existsStmt = $this->db->prepare(
            "SELECT category_id
             FROM categories
             WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
             LIMIT 1"
        );

        $existsStmt->execute([$normalizedName]);

        if ($existsStmt->fetchColumn()) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO categories (name)
            VALUES (?)
        ");

        return $stmt->execute([$normalizedName]);
    }

    // ===============================
    // ADMIN: get request by id
    // ===============================
    public function getRequestById($requestId) {

        $stmt = $this->db->prepare("
            SELECT request_id, requested_by, suggested_name, status
            FROM category_requests
            WHERE request_id = ?
            LIMIT 1
        ");

        $stmt->execute([$requestId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ===============================
    // ADMIN: update request status
    // ===============================
    public function updateRequestStatus($requestId, $status){

        $stmt = $this->db->prepare("
            UPDATE category_requests
            SET status = ?
            WHERE request_id = ?
        ");

        return $stmt->execute([$status, $requestId]);
    }

    // ===============================
    // ADMIN: update request status only if pending
    // ===============================
    public function updateRequestStatusIfPending($requestId, $status): bool {

        $stmt = $this->db->prepare("
            UPDATE category_requests
            SET status = ?
            WHERE request_id = ?
            AND status = 0
        ");

        $stmt->execute([$status, $requestId]);

        return $stmt->rowCount() > 0;
    }

    // ===============================
    // USER: get own interests
    // ===============================
    public function getUserInterests($userId) {
    // Επιστρέφει τα ενδιαφέροντα του χρήστη με βάση τον πίνακα user_interest
        $stmt = $this->db->prepare("
            SELECT MIN(c.category_id) AS category_id, c.name
            FROM user_interest ui
            JOIN categories c ON ui.category_id = c.category_id
            WHERE ui.user_id = ?
            GROUP BY c.name
            ORDER BY c.name ASC
        ");
    // Εκτελεί το ερώτημα με το userId ως παράμετρο για να πάρει τα ενδιαφέροντα του χρήστη
        $stmt->execute([$userId]);
    // Επιστρέφει έναν πίνακα με τα ενδιαφέροντα του χρήστη, όπου κάθε στοιχείο είναι ένας πίνακας με τα πεδία category_id και name
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
