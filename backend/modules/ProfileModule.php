<?php
/**
 * File: ProfileModule.php
 * Layer: Backend Module
 * Module: User Profile Management
 * System: University Web Applications System B
 *
 * Description:
 * This module is responsible for handling all database operations
 * related to user profile data.
 *
 * It allows the system to update a user's university and year of study
 * as well as store the user's selected interest categories.
 *
 * The module is used by the ProfileController to persist
 * profile-related data in the database.
 *
 * Core Responsibilities:
 * - Update user profile information (university & year)
 * - Store user interest categories
 * - Prevent duplicate interest entries
 * - Support transactional profile updates
 *
 * Security:
 * - Uses PDO prepared statements to prevent SQL injection
 * - Validates duplicate category entries before inserting
 * - Supports database transactions for atomic operations
 *
 * Database Tables Used:
 * - users
 * - user_interest
 *
 * Used By:
 * - ProfileController
 *
 * Author: Pela Koniotaki
 * Date: 2026
 */

require_once "../config/database.php";

class ProfileModule {

    private PDO $conn;

    /* =========================
       CONSTRUCTOR
       Establish database connection
    ========================= */

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /* =========================
       SAVE PROFILE DATA
       Updates university and year
    ========================= */

    /**
     * Updates the user's university and year of study.
     *
     * @param int $userId
     * @param string $university
     * @param string $year
     */
    public function saveProfile(int $userId, string $university, string $year): void
    {
        $stmt = $this->conn->prepare("
            UPDATE users
            SET university = :u,
                year = :y
            WHERE user_id = :id
        ");

        $stmt->execute([
            ":u"  => $university,
            ":y"  => $year,
            ":id" => $userId
        ]);
    }

    /* =========================
       SAVE USER INTERESTS
       Inserts selected categories
    ========================= */

    /**
     * Stores the user's selected interest categories.
     * Prevents duplicate entries for the same user/category.
     *
     * @param int $userId
     * @param array $categories
     */
    public function saveInterests(int $userId, array $categories): void
    {
        if (empty($categories)) {
            return;
        }

        // Check if interest already exists
        $checkStmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM user_interest
            WHERE user_id = :uid
            AND category_id = :cid
        ");

        // Insert interest
        $insertStmt = $this->conn->prepare("
            INSERT INTO user_interest (user_id, category_id)
            VALUES (:uid, :cid)
        ");

        foreach ($categories as $categoryId) {

            $checkStmt->execute([
                ":uid" => $userId,
                ":cid" => $categoryId
            ]);

            $exists = $checkStmt->fetchColumn();

            if (!$exists) {

                $insertStmt->execute([
                    ":uid" => $userId,
                    ":cid" => $categoryId
                ]);

            }
        }
    }

    /* =========================
       SAVE FULL PROFILE
       Optional transactional method
    ========================= */

    /**
     * Saves profile information and interests
     * within a single database transaction.
     *
     * @param int $userId
     * @param string $university
     * @param string $year
     * @param array $categories
     */
    public function saveFullProfile(int $userId, string $university, string $year, array $categories): void
    {
        try {

            $this->conn->beginTransaction();

            $this->saveProfile($userId, $university, $year);

            $this->saveInterests($userId, $categories);

            $this->conn->commit();

        } catch (Exception $e) {

            $this->conn->rollBack();
            throw $e;

        }
    }

}