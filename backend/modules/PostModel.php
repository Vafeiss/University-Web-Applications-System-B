<?php

require_once __DIR__ . '/../config/database.php';

class PostModel {

    private PDO $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
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

        $query = "SELECT request_id
                  FROM post_delete_requests
                  WHERE post_id = :post_id
                  AND requested_by = :user_id";

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

    public function adminDeletePost($post_id) {

        $this->conn->beginTransaction();

        try {
            $query = "UPDATE posts
                      SET deleted = 1
                      WHERE post_id = :post_id AND deleted = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id
            ]);

            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            $query = "UPDATE post_delete_requests
                      SET status = 1
                      WHERE post_id = :post_id AND status = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id
            ]);

            $query = "UPDATE content_reports
                      SET status = 1
                      WHERE content_type = 'post' AND content_id = :post_id AND status = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":post_id" => $post_id
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

    // approve post by admin
    public function approvePost($post_id) {
        // Ενημέρωση της κατάστασης του post σε "approved" (status = 1)
        $query = "UPDATE posts
                  SET status = 1
                  WHERE post_id = :post_id";
        // Εκτέλεση του query
        $stmt = $this->conn->prepare($query);
        // Επιστροφή του αποτελέσματος του query
        return $stmt->execute([
            ":post_id" => $post_id
        ]);
    }

    // reject post by admin
    public function rejectPost($post_id) {

        $query = "UPDATE posts
                  SET status = 2, deleted = 1
                  WHERE post_id = :post_id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":post_id" => $post_id
        ]);
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
        // Ενημέρωση του post ως διαγραμμένο (deleted = 1) και της κατάστασης του αιτήματος σε "approved"
        $query = "UPDATE posts
                  SET deleted = 1
                  WHERE post_id = (
                        SELECT post_id
                        FROM post_delete_requests
                        WHERE request_id = :request_id
                  )";
        // Εκτέλεση του query
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":request_id" => $request_id
        ]);
        // Ενημέρωση της κατάστασης του αιτήματος σε "approved"
        $query = "UPDATE post_delete_requests
                  SET status = 1
                  WHERE request_id = :request_id";
        
        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":request_id" => $request_id
        ]);
    }
    // Admin rejects delete request
    public function rejectDeleteRequest($request_id) {
        // Ενημέρωση της κατάστασης του αιτήματος σε "rejected"
        $query = "UPDATE post_delete_requests
                  SET status = 2
                  WHERE request_id = :request_id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":request_id" => $request_id
        ]);
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
    // ενημερωνει την βαση για να εγκριθεί ένα report και να διαγραφεί το σχετικό post ή comment
    public function approveReport($report_id) {

        // βρίσκουμε τι report είναι
        $query = "SELECT content_type, content_id
                  FROM content_reports
                  WHERE report_id = :report_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([":report_id"=>$report_id]);

        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$report){
            return false;
        }

        if($report['content_type'] === 'post'){

            $query = "UPDATE posts
                      SET deleted = 1
                      WHERE post_id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":id"=>$report['content_id']
            ]);
        }

        if($report['content_type'] === 'comment'){

            $query = "DELETE FROM comments
                      WHERE comment_id = :id";

           $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":id"=>$report['content_id']
            ]);
        }
  
        $query = "UPDATE content_reports
                  SET status = 1
                  WHERE report_id = :report_id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":report_id"=>$report_id
        ]);
    }
    // ενημερωνει την βαση για να απορριφθεί ένα report και να παραμείνει το σχετικό post 
    public function rejectReport($report_id){

        $query = "UPDATE content_reports
                  SET status = 2
                  WHERE report_id = :report_id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":report_id"=>$report_id
        ]);
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

    // delete comment
    $query = "DELETE FROM comments
              WHERE comment_id = :comment_id";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ":comment_id"=>$request['comment_id']
    ]);

    // mark request approved
    $query = "UPDATE comment_delete_requests
              SET status = 1
              WHERE request_id = :request_id";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        ":request_id"=>$request_id
    ]);
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
}