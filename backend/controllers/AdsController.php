<?php
/**
 * File: AdsController.php
 * Layer: Controller
 * Module: Ads
 * System: University Web Applications System B
 *
 * Description:
 * Handles advertisement rewards by tracking ad views and crediting tokens.
 * Updates user token balance upon successful ad completion and records
 * the transaction in the database.
 *
 * Functions:
 * - rewardAdView() → processes ad reward and token credit
 *
 * Security:
 * - requireLogin() middleware enforces authentication
 * - Input validation on ad_id parameter
 * - Database transactions ensure consistency
 *
 * Used By:
 * - frontend/ads_user.php (AJAX calls)
 *
 * Author:Panagiwtis Panagiwtou & Pelagia Koniotaki & Antriani Theofanous 
 * Date: 2026
 */

header("Content-Type: application/json");
require_once __DIR__ . '/BaseController.php';

class AdsController extends BaseController {

    public function rewardAdView(): void {
        $user_id = $this->requireLogin();

        if (!isset($_POST['ad_id'])) {
            $this->jsonResponse(["message" => "Invalid request"], 400);
        }

        $ad_id = (int) $_POST['ad_id'];
        if ($ad_id <= 0) {
            $this->jsonResponse(["message" => "Invalid advertisement id"], 400);
        }

        $db = new Database();
        $pdo = $db->connect();
        $awardedAt = date('Y-m-d H:i:s');

        try {
            $pdo->beginTransaction();

            $adStmt = $pdo->prepare("SELECT advertise_id, cooldown_hours FROM advertisements WHERE advertise_id = ? LIMIT 1");
            $adStmt->execute([$ad_id]);
            $ad = $adStmt->fetch(PDO::FETCH_ASSOC);

            if (!$ad) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->jsonResponse(["message" => "Advertisement not found"], 404);
            }

            $lastViewStmt = $pdo->prepare(
                "SELECT v.viewed_at, a.cooldown_hours
                 FROM ad_views v
                 INNER JOIN advertisements a ON a.advertise_id = v.advertise_id
                 WHERE v.user_id = ?
                 ORDER BY v.viewed_at DESC
                 LIMIT 1"
            );
            $lastViewStmt->execute([$user_id]);
            $lastView = $lastViewStmt->fetch(PDO::FETCH_ASSOC);

            if ($lastView) {
                $lastViewedAtTs = strtotime((string) ($lastView['viewed_at'] ?? ''));
                $lastCooldownHours = (int) ($lastView['cooldown_hours'] ?? 0);
                if ($lastViewedAtTs !== false && $lastCooldownHours > 0) {
                    $remainingSeconds = ($lastViewedAtTs + ($lastCooldownHours * 3600)) - time();
                    if ($remainingSeconds > 0) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $this->jsonResponse([
                            "message" => "Please wait before watching another advertisement",
                            "remaining_seconds" => $remainingSeconds,
                            "cooldown_hours" => $lastCooldownHours
                        ], 429);
                    }
                }
            }

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

        $this->jsonResponse([
            "ok" => true,
            "message" => "Advertisement view rewarded successfully",
            "cooldown_hours" => (int) ($ad['cooldown_hours'] ?? 0),
            "remaining_seconds" => (int) (($ad['cooldown_hours'] ?? 0) * 3600)
        ]);
    }
}

$controller = new AdsController();
$controller->rewardAdView();
