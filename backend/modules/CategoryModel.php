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

    // ===============================
    // USER: get own interests
    // ===============================
    public function getUserInterests($userId) {
    // Επιστρέφει τα ενδιαφέροντα του χρήστη με βάση τον πίνακα user_interest
        $stmt = $this->db->prepare("
            SELECT c.category_id, c.name
            FROM user_interest ui
            JOIN categories c ON ui.category_id = c.category_id
            WHERE ui.user_id = ?
            ORDER BY c.name ASC
        ");
    // Εκτελεί το ερώτημα με το userId ως παράμετρο για να πάρει τα ενδιαφέροντα του χρήστη
        $stmt->execute([$userId]);
    // Επιστρέφει έναν πίνακα με τα ενδιαφέροντα του χρήστη, όπου κάθε στοιχείο είναι ένας πίνακας με τα πεδία category_id και name
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}