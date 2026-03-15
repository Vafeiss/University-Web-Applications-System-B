<?php

require_once __DIR__ . '/../config/database.php';

class FollowModel {

    private PDO $conn;
    // Ο constructor δημιουργεί μια σύνδεση με τη βάση δεδομένων χρησιμοποιώντας την κλάση Database
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    // Η μέθοδος followUser εισάγει ή ενημερώνει μια εγγραφή στον πίνακα followers για να δείξει ότι ο χρήστης followerId ακολουθεί τον χρήστη followedId
    public function followUser($followerId, $followedId) {

        $query = "INSERT INTO followers (follower_id, followed_id, status)
                  VALUES (:follower, :followed, 1)
                  ON DUPLICATE KEY UPDATE status = 1";
    // Χρησιμοποιούμε ON DUPLICATE KEY UPDATE για να ενημερώσουμε την εγγραφή αν υπάρχει ήδη, ώστε να μην έχουμε διπλές εγγραφές για το ίδιο ζευγάρι follower-followed
        $stmt = $this->conn->prepare($query);
    // Εκτελούμε το ερώτημα με τα αντίστοιχα παραμέτρους για τον follower και τον followed
        return $stmt->execute([
            ":follower" => $followerId,
            ":followed" => $followedId
        ]);
    }

    // Η μέθοδος unfollowUser ενημερώνει την εγγραφή στον πίνακα followers για να δείξει ότι ο χρήστης followerId δεν ακολουθεί πλέον τον χρήστη followedId
    public function unfollowUser($followerId, $followedId) {

        $query = "UPDATE followers
                  SET status = 0
                  WHERE follower_id = :follower
                  AND followed_id = :followed";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":follower" => $followerId,
            ":followed" => $followedId
        ]);
    }
    // Η μέθοδος isFollowing ελέγχει αν υπάρχει μια εγγραφή στον πίνακα followers με status 1 για το ζευγάρι followerId-followedId, που σημαίνει ότι ο followerId ακολουθεί τον followedId
    public function isFollowing($followerId, $followedId) {

        $query = "SELECT status
                  FROM followers
                  WHERE follower_id = :follower
                  AND followed_id = :followed
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        // Εκτελούμε το ερώτημα με τα αντίστοιχα παραμέτρους για τον follower και τον followed
        $stmt->execute([
            ":follower" => $followerId,
            ":followed" => $followedId
        ]);
        // Παίρνουμε το αποτέλεσμα και ελέγχουμε αν υπάρχει και αν το status είναι 1, που σημαίνει ότι ο followerId ακολουθεί τον followedId
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // Επιστρέφουμε true αν υπάρχει η εγγραφή και το status είναι 1, αλλιώς false
        return $row && $row["status"] == 1;
    }

    public function getFollowingUsers($followerId) {

        $query = "SELECT u.user_id, u.username
                  FROM followers f
                  JOIN users u ON f.followed_id = u.user_id
                  WHERE f.follower_id = :follower
                  AND f.status = 1
                  ORDER BY u.username ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":follower" => $followerId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFollowersUsers($followedId) {

        $query = "SELECT u.user_id, u.username
                  FROM followers f
                  JOIN users u ON f.follower_id = u.user_id
                  WHERE f.followed_id = :followed
                  AND f.status = 1
                  ORDER BY u.username ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":followed" => $followedId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}