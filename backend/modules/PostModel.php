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
    public function postDeleteRequestExists($post_id,$user_id){

    $query = "SELECT request_id
              FROM post_delete_requests
              WHERE post_id = :post_id
              AND requested_by = :user_id";

    $stmt = $this->conn->prepare($query);

    $stmt->execute([
        ":post_id"=>$post_id,
        ":user_id"=>$user_id
    ]);

    return $stmt->fetch() ? true : false;
}
// Create post delete request
public function createPostDeleteRequest($post_id,$user_id,$reason){

    $query = "INSERT INTO post_delete_requests
              (post_id, requested_by, reason)
              VALUES (:post_id,:user_id,:reason)";

    $stmt = $this->conn->prepare($query);

    return $stmt->execute([
        ":post_id"=>$post_id,
        ":user_id"=>$user_id,
        ":reason"=>$reason
    ]);
}

public function postReportExists($post_id, $user_id){

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

public function createPostReport($post_id, $user_id, $reason){

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
}