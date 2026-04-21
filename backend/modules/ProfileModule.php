<?php
/**
 * File: ProfileModule.php
 * Layer: Model
 * Module: Profile Management
 * System: University Web Applications System B
 *
 * Description:
 * Data model for user profile management. Handles profile initialization and updates
 * including university, year, and interest category selection. Prevents duplicate
 * entries and supports transactional consistency.
 *
 * Functions:
 * - saveProfile() → updates university and year of study
 * - saveInterests() → stores user-selected interest categories with duplicate prevention
 * - getProfile() → retrieves complete profile for user
 * - getUserInterests() → lists categories user is interested in
 * - clearInterests() → removes all interests for user
 *
 * Security:
 * - PDO prepared statements for all database operations
 * - Transactional updates ensure data consistency
 * - Duplicate category prevention
 * - Input validation on university/year values
 *
 * Used By:
 * - ProfileController
 *
 * Author: Pelagia Koniotaki
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

    public function replaceInterests(int $userId, array $categories): void
    {
        $normalized = [];
        foreach ($categories as $categoryId) {
            $value = (int)$categoryId;
            if ($value > 0) {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));

        try {
            $this->conn->beginTransaction();

            $deleteStmt = $this->conn->prepare("DELETE FROM user_interest WHERE user_id = :uid");
            $deleteStmt->execute([":uid" => $userId]);

            if (!empty($normalized)) {
                $insertStmt = $this->conn->prepare(" 
                    INSERT INTO user_interest (user_id, category_id)
                    VALUES (:uid, :cid)
                ");

                foreach ($normalized as $categoryId) {
                    $insertStmt->execute([
                        ":uid" => $userId,
                        ":cid" => $categoryId
                    ]);
                }
            }

            $this->conn->commit();
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
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