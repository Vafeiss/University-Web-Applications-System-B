<?php

require_once __DIR__ . '/../config/database.php';

class PostModel {

    private PDO $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create_Post()
    public function createPost($user_id, $title, $content, $category_id) {

        $query = "INSERT INTO posts 
                  (user_id, title, content, category_id, status) 
                  VALUES (:user_id, :title, :content, :category_id, 0)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':content' => $content,
            ':category_id' => $category_id
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

        $query = "SELECT * FROM posts WHERE post_id = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':post_id' => $post_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Delete_Post()
    public function deletePost($post_id, $user_id) {

        $query = "UPDATE posts 
                  SET deleted = 1 
                  WHERE post_id = :post_id 
                  AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id
        ]);
    }
}