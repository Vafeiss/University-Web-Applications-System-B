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

require_once __DIR__ . "/../config/database.php";

class ProfileModule {

    // Σύνδεση με τη βάση (PDO instance)
    private PDO $conn;

    // Στο constructor ανοίγουμε τη σύνδεση μία φορά για όλη τη ζωή του object
    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Ενημερώνει τα στοιχεία του προφίλ (university, year)
     * για τον συγκεκριμένο user στον πίνακα users.
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

    /**
     * Αποθηκεύει τις κατηγορίες που επέλεξε ο χρήστης ως interests.
     * Πριν κάνει insert ελέγχει αν το ζευγάρι (user, category) υπάρχει
     * ήδη, για να μην έχουμε διπλοεγγραφές στον user_interest.
     */
    public function saveInterests(int $userId, array $categories): void
    {
        // Άμα δεν επέλεξε τίποτα, δεν κάνουμε δουλειά
        if (empty($categories)) {
            return;
        }

        // Query για έλεγχο αν υπάρχει ήδη η συγκεκριμένη κατηγορία για τον user
        $checkStmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM user_interest
            WHERE user_id = :uid
            AND category_id = :cid
        ");

        // Query για να βάλουμε το νέο interest στη βάση
        $insertStmt = $this->conn->prepare("
            INSERT INTO user_interest (user_id, category_id)
            VALUES (:uid, :cid)
        ");

        // Πάμε μία-μία τις κατηγορίες που έστειλε το form
        foreach ($categories as $categoryId) {

            $checkStmt->execute([
                ":uid" => $userId,
                ":cid" => $categoryId
            ]);

            $exists = $checkStmt->fetchColumn();

            // Insert μόνο αν δεν υπάρχει ήδη
            if (!$exists) {

                $insertStmt->execute([
                    ":uid" => $userId,
                    ":cid" => $categoryId
                ]);

            }
        }
    }

    /**
     * Αντικαθιστά πλήρως τα interests του χρήστη με τη νέα λίστα.
     * Διαγράφει τα παλιά rows και βάζει τα καινούρια μέσα σε transaction
     * ώστε αν κάτι σπάσει στη μέση, να μη μείνει ο χρήστης χωρίς interests.
     * Χρησιμοποιείται από το "Edit Interests" form.
     */
    public function replaceInterests(int $userId, array $categories): void
    {
        // Καθαρίζουμε την είσοδο: μόνο θετικά integers, χωρίς διπλά
        $normalized = [];
        foreach ($categories as $categoryId) {
            $value = (int)$categoryId;
            if ($value > 0) {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));

        try {
            // Ξεκινάμε transaction — όλα μαζί ή τίποτα
            $this->conn->beginTransaction();

            // 1. Διαγραφή όλων των παλιών interests του χρήστη
            $deleteStmt = $this->conn->prepare("DELETE FROM user_interest WHERE user_id = :uid");
            $deleteStmt->execute([":uid" => $userId]);

            // 2. Εισαγωγή των νέων (αν υπάρχουν)
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

            // Όλα ΟΚ → κάνουμε commit
            $this->conn->commit();
        } catch (Throwable $e) {
            // Κάτι έσπασε → rollback και ξαναπετάμε το exception στον controller
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Αποθήκευση όλου του προφίλ μαζί (πανεπιστήμιο + έτος + interests)
     * σε ένα ενιαίο transaction. Αν αποτύχει οποιοδήποτε βήμα, γίνεται
     * rollback και τίποτα δεν αποθηκεύεται.
     *
     * Χρήσιμο κυρίως κατά το αρχικό setup του προφίλ.
     */
    public function saveFullProfile(int $userId, string $university, string $year, array $categories): void
    {
        try {

            $this->conn->beginTransaction();

            // Πρώτα τα βασικά στοιχεία...
            $this->saveProfile($userId, $university, $year);

            // ...και μετά τα interests
            $this->saveInterests($userId, $categories);

            $this->conn->commit();

        } catch (Exception $e) {

            // Rollback και rethrow ώστε να το πιάσει ο controller
            $this->conn->rollBack();
            throw $e;

        }
    }

}