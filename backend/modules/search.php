<?php

class Search {

    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function searchPosts($keyword = '', $category = null, $status = null) {

        $sql = "SELECT * FROM posts WHERE 1=1";

        if (!empty($keyword)) {
            $sql .= " AND (title LIKE :keyword OR content LIKE :keyword)";
        }

        if (!empty($category)) {
            $sql .= " AND category_id = :category";
        }

        if ($status !== null && $status !== '') {
            $sql .= " AND status = :status";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);

        if (!empty($keyword)) {
            $keywordParam = "%" . $keyword . "%";
            $stmt->bindParam(':keyword', $keywordParam);
        }

        if (!empty($category)) {
            $stmt->bindParam(':category', $category);
        }

        if ($status !== null && $status !== '') {
            $stmt->bindParam(':status', $status);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}