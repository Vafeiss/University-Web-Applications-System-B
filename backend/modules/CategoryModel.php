<?php

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
    // ADMIN: create category
    // ===============================
    public function createCategory($name){

        $stmt = $this->db->prepare("
            INSERT INTO categories (name)
            VALUES (?)
        ");

        return $stmt->execute([$name]);
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

}