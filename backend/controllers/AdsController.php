
<?php
header("Content-Type: application/json");
require_once __DIR__ . '/BaseController.php';

class AdsController extends BaseController {

    // Καλείται όταν ο χρήστης τελειώσει τη διαφήμιση
    public function rewardAdView(): void {
        $user_id = $this->requireLogin(); // παίρνει το πραγματικό user από session

        if (!isset($_POST['ad_id'])) {
            $this->jsonResponse(["message" => "Invalid request"], 400);
        }

        $ad_id = (int) $_POST['ad_id'];

        $db = new Database();
        $pdo = $db->connect();

        // Ενημέρωση tokens χρήστη
        $stmt = $pdo->prepare("UPDATE users SET token_balance = token_balance + 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Καταγραφή προβολής
        $stmt2 = $pdo->prepare("INSERT INTO ad_views (user_id, advertise_id, viewed_at) VALUES (?, ?, NOW())");
        $stmt2->execute([$user_id, $ad_id]);

        echo "Success";
        exit;
    }
}

// Εκτέλεση
$controller = new AdsController();
$controller->rewardAdView();