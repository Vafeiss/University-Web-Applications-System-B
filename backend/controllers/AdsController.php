<?php

header("Content-Type: application/json");
require_once __DIR__ . '/BaseController.php';

class AdsController extends BaseController {

    public function rewardAdView(): void {
        $user_id = $this->requireLogin();

        if (!isset($_POST['ad_id'])) {
            $this->jsonResponse(["message" => "Invalid request"], 400);
        }

        $ad_id = (int) $_POST['ad_id'];

        $db = new Database();
        $pdo = $db->connect();
        $awardedAt = date('Y-m-d H:i:s');

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE users SET token_balance = token_balance + 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt2 = $pdo->prepare(
                "INSERT INTO ad_views (user_id, advertise_id, viewed_at) VALUES (?, ?, ?)"
            );
            $stmt2->execute([$user_id, $ad_id, $awardedAt]);

            $stmt3 = $pdo->prepare(
                "INSERT INTO transactions (user_id, token_charge, timestamp) VALUES (?, 1, ?)"
            );
            $stmt3->execute([$user_id, $awardedAt]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->jsonResponse(["message" => "Could not reward advertisement view"], 500);
        }

        echo "Success";
        exit;
    }
}

$controller = new AdsController();
$controller->rewardAdView();
