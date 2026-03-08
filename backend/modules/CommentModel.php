<?php

require_once __DIR__ . '/../config/database.php';

class CommentModel {

    private PDO $conn;

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create comment
    public function createComment($user_id, $post_id, $content){

        $query = "INSERT INTO comments
                  (user_id, post_id, comment_content)
                  VALUES (:user_id, :post_id, :content)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':user_id' => $user_id,
            ':post_id' => $post_id,
            ':content' => $content
        ]);
    }

    // Get comments for post
    public function getCommentsByPost($post_id){

        $query = "SELECT c.*, u.username
                  FROM comments c
                  JOIN users u ON c.user_id = u.user_id
                  WHERE c.post_id = :post_id
                  ORDER BY c.timestamp ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':post_id' => $post_id
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}