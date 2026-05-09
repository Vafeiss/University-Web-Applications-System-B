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

// Φέρνουμε τη σύνδεση με τη βάση
require_once __DIR__ . "/../config/database.php";

class CategoryModel {

    // PDO instance — μένει ζωντανό όσο υπάρχει το object
    private $db;

    // Στο constructor κρατάμε ένα connection, για να μην ανοιγοκλείνουμε
    // σύνδεση σε κάθε query
    public function __construct(){
        $database = new Database();
        $this->db = $database->connect();
    }


    // --- USER: αίτημα για νέα κατηγορία ---
    // Όταν ο χρήστης προτείνει μια νέα κατηγορία (π.χ. "Robotics"),
    // καταχωρείται με status 0 = pending μέχρι να την δει ο admin.
    public function requestCategory($userId, $name){

        $stmt = $this->db->prepare("
            INSERT INTO category_requests
            (requested_by, suggested_name)
            VALUES (?, ?)
        ");

        return $stmt->execute([$userId, $name]);
    }


    // --- ADMIN: λίστα με pending αιτήματα κατηγοριών ---
    // Φέρνει τις προτάσεις που έχουν στείλει οι χρήστες και περιμένουν έγκριση.
    // Κάνουμε JOIN με users για να εμφανίζουμε και ποιος την πρότεινε.
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


    // --- Λίστα με τις ήδη εγκεκριμένες κατηγορίες ---
    // Αν υπάρχουν διπλά names, παίρνουμε το μικρότερο category_id με MIN()
    // για να μην εμφανίζεται η ίδια κατηγορία 2 φορές στο UI.
    public function getExistingCategories(){

        $stmt = $this->db->query(
            "SELECT MIN(category_id) AS category_id, name
             FROM categories
             GROUP BY name
             ORDER BY name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // --- ADMIN: διαγραφή κατηγορίας μαζί με ΟΛΑ τα posts της ---
    // Επικίνδυνη ενέργεια: σβήνει cascading όλα τα σχετικά (comments,
    // delete requests, reports, notifications) ώστε να μη μένουν "ορφανά"
    // δεδομένα. Όλο γίνεται μέσα σε transaction για ασφάλεια.
    public function deleteCategoryAndPosts(int $categoryId): array {

        // Έλεγχος εγκυρότητας του id
        if ($categoryId <= 0) {
            return [
                "ok" => false,
                "message" => "Invalid category id"
            ];
        }

        $this->db->beginTransaction();

        try {
            // 1. Βρίσκουμε την κατηγορία και κρατάμε το όνομα
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

            // 2. Μετράμε πόσα posts θα σβηστούν (χρειάζεται για το report
            //    που εμφανίζεται μετά στον admin)
            $postCountStmt = $this->db->prepare(
                "SELECT COUNT(*)
                 FROM posts p
                 INNER JOIN categories c ON p.category_id = c.category_id
                 WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))"
            );
            $postCountStmt->execute([$categoryName]);
            $postCount = (int)$postCountStmt->fetchColumn();

            // 3. Καθαρίζουμε τα comment_delete_requests των comments
            //    των posts αυτής της κατηγορίας
            $deleteCommentRequestsStmt = $this->db->prepare(
                "DELETE cdr
                 FROM comment_delete_requests cdr
                 INNER JOIN comments c ON cdr.comment_id = c.comment_id
                 INNER JOIN posts p ON c.post_id = p.post_id
                 INNER JOIN categories cat ON p.category_id = cat.category_id
                 WHERE LOWER(TRIM(cat.name)) = LOWER(TRIM(?))"
            );
            $deleteCommentRequestsStmt->execute([$categoryName]);

            // 4. Καθαρίζουμε reports που αφορούν τα comments αυτών των posts
            $deleteCommentReportsStmt = $this->db->prepare(
                "DELETE cr
                 FROM content_reports cr
                 INNER JOIN comments c ON cr.content_type = 'comment' AND cr.content_id = c.comment_id
                 INNER JOIN posts p ON c.post_id = p.post_id
                 INNER JOIN categories cat ON p.category_id = cat.category_id
                 WHERE LOWER(TRIM(cat.name)) = LOWER(TRIM(?))"
            );
            $deleteCommentReportsStmt->execute([$categoryName]);

            // 5. Reports που αφορούν τα ίδια τα posts
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

            // 6. Pending αιτήματα διαγραφής για τα posts
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

            // 7. Notifications που είχαν ενημερώσει followers / interest users
            //    για τα posts αυτής της κατηγορίας
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

            // 8. Διαγραφή των ίδιων των posts
            $deletePostsStmt = $this->db->prepare(
                "DELETE p
                 FROM posts p
                 INNER JOIN categories c ON p.category_id = c.category_id
                 WHERE LOWER(TRIM(c.name)) = LOWER(TRIM(?))"
            );
            $deletePostsStmt->execute([$categoryName]);

            // 9. Τελευταίο: σβήνουμε την ίδια την κατηγορία
            //    (χρησιμοποιούμε case-insensitive match για να σβήσει
            //    και τυχόν διπλοεγγραφές με ίδιο όνομα)
            $deleteCategoryStmt = $this->db->prepare(
                "DELETE FROM categories
                 WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))"
            );
            $deleteCategoryStmt->execute([$categoryName]);

            $this->db->commit();

            // Γυρίζουμε στον admin τι ακριβώς διαγράφηκε
            return [
                "ok" => true,
                "category_name" => $categoryName,
                "deleted_posts" => $postCount
            ];
        } catch (Throwable $exception) {
            // Κάτι πήγε στραβά — rollback και rethrow
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }


    // --- ADMIN: δημιουργία νέας κατηγορίας ---
    // Χρησιμοποιείται είτε όταν ο admin εγκρίνει αίτημα,
    // είτε όταν προσθέτει χειροκίνητα κατηγορία.
    public function createCategory($name){

        $normalizedName = trim((string)$name);

        // Δεν δεχόμαστε κενά ονόματα
        if ($normalizedName === "") {
            return false;
        }

        // Έλεγχος για διπλό όνομα (case-insensitive)
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

        // Insert της νέας κατηγορίας
        $stmt = $this->db->prepare("
            INSERT INTO categories (name)
            VALUES (?)
        ");

        return $stmt->execute([$normalizedName]);
    }


    // --- ADMIN: φέρνει αίτημα κατηγορίας με συγκεκριμένο id ---
    // Χρειάζεται για να εμφανίσει ο admin τις λεπτομέρειες πριν approve/reject.
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


    // --- ADMIN: αλλάζει το status ενός αιτήματος ---
    // Status: 0 = pending, 1 = approved, 2 = rejected
    public function updateRequestStatus($requestId, $status){

        $stmt = $this->db->prepare("
            UPDATE category_requests
            SET status = ?
            WHERE request_id = ?
        ");

        return $stmt->execute([$status, $requestId]);
    }


    // --- Πιο "ασφαλής" εκδοχή: αλλάζει το status ΜΟΝΟ αν είναι ακόμα pending ---
    // Έτσι αποφεύγουμε race conditions όπου 2 admins θα έκαναν approve/reject
    // ταυτόχρονα στο ίδιο αίτημα.
    public function updateRequestStatusIfPending($requestId, $status): bool {

        $stmt = $this->db->prepare("
            UPDATE category_requests
            SET status = ?
            WHERE request_id = ?
            AND status = 0
        ");

        $stmt->execute([$status, $requestId]);

        // Αν δεν επηρεάστηκε καμία γραμμή, σημαίνει ότι το αίτημα
        // είχε ήδη γίνει approved/rejected από κάποιον άλλον
        return $stmt->rowCount() > 0;
    }


    // --- USER: τα δικά του ενδιαφέροντα ---
    // Φέρνει τις κατηγορίες που έχει επιλέξει ο χρήστης (από user_interest)
    // και τις επιστρέφει αλφαβητικά. Με GROUP BY name αποφεύγουμε διπλές
    // εμφανίσεις σε περίπτωση που υπάρχουν 2 categories με ίδιο όνομα.
    public function getUserInterests($userId) {

        $stmt = $this->db->prepare("
            SELECT MIN(c.category_id) AS category_id, c.name
            FROM user_interest ui
            JOIN categories c ON ui.category_id = c.category_id
            WHERE ui.user_id = ?
            GROUP BY c.name
            ORDER BY c.name ASC
        ");

        $stmt->execute([$userId]);

        // Επιστρέφει array με στοιχεία της μορφής ["category_id"=>X, "name"=>"..."]
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
