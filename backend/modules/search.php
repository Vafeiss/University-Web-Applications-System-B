<?php

class Search {

    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function searchPosts(
        $keyword = '',
        $category = null,
        $status = null,
        $from = null,
        $to = null
    ) {

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

    
        if (!empty($from)) {
            $sql .= " AND created_at >= :from";
        }

        
        if (!empty($to)) {
            $sql .= " AND created_at <= :to";
        }

        
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);

        

        if (!empty($keyword)) {
            $keywordParam = "%" . $keyword . "%";
            $stmt->bindParam(':keyword', $keywordParam, PDO::PARAM_STR);
        }

        if (!empty($category)) {
            $stmt->bindParam(':category', $category, PDO::PARAM_INT);
        }

        if ($status !== null && $status !== '') {
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        }

        if (!empty($from)) {
            $fromDate = $from . " 00:00:00";
            $stmt->bindParam(':from', $fromDate, PDO::PARAM_STR);
        }

        if (!empty($to)) {
            $toDate = $to . " 23:59:59";
            $stmt->bindParam(':to', $toDate, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}