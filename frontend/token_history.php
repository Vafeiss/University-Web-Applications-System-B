<?php
/**
 * File: token_history.php
 * Layer: Frontend Page
 * Module: Token History
 * System: University Web Applications System B
 *
 * Description:
 * Page showing the logged-in user's current token balance and
 * the history of their token transactions (rewards, spending).
 *
 * Features:
 * - Current balance summary card
 * - Filter by transaction type / date
 * - Transaction list rows with amount and reason
 * - Running total display
 *
 * Security:
 * - session_start() and requireLogin()
 * - BanGuard check
 * - PDO prepared statements (database.php)
 * - htmlspecialchars() for output escaping
 *
 * Used By:
 * - Linked from main dashboard (index.php)
 *
 * Author: Pelagia Koniotaki 
 * Date: 2026
 */

require_once "../backend/config/database.php";
require_once "../backend/middleware/BanGuard.php";
session_start();
enforceFrontendUserNotBanned();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['role'] ?? '') === 'admin') {
    header("Location: posts.php");
    exit();
}

function describeTransaction(int $tokenCharge, ?string $source = null): string {
    if ($source === 'advertisement_reward') {
        return "Advertisement reward";
    }

    if ($tokenCharge === 10) {
        return "Referral reward";
    }

    if ($tokenCharge === 1) {
        return "Approved upload reward";
    }

    if ($tokenCharge === 0) {
        return "Free daily download";
    }

    if ($tokenCharge === -1) {
        return "Download charge";
    }

    return $tokenCharge > 0 ? "Token gain" : "Token usage";
}

$db = new Database();
$conn = $db->connect();

$balanceStmt = $conn->prepare(
    "SELECT token_balance FROM users WHERE user_id = :id LIMIT 1"
);
$balanceStmt->execute([":id" => $_SESSION['user_id']]);
$tokenBalance = (int) ($balanceStmt->fetchColumn() ?: 0);

$transactionsStmt = $conn->prepare(
    "SELECT *
     FROM (
        SELECT CONCAT('tx-', t.transaction_id) AS history_id,
               t.token_charge,
               t.timestamp,
               CASE
                   WHEN EXISTS (
                       SELECT 1
                       FROM ad_views av
                       WHERE av.user_id = t.user_id
                         AND av.viewed_at = t.timestamp
                   ) THEN 'advertisement_reward'
                   ELSE NULL
               END AS transaction_source
        FROM transactions t
        WHERE t.user_id = :id

        UNION ALL

        SELECT CONCAT('ad-', av.view_id) AS history_id,
               1 AS token_charge,
               av.viewed_at AS timestamp,
               'advertisement_reward' AS transaction_source
        FROM ad_views av
        WHERE av.user_id = :id
          AND NOT EXISTS (
              SELECT 1
              FROM transactions t
              WHERE t.user_id = av.user_id
                AND t.timestamp = av.viewed_at
                AND t.token_charge = 1
          )
     ) history_rows
     ORDER BY timestamp DESC, history_id DESC"
);
$transactionsStmt->execute([":id" => $_SESSION['user_id']]);
$transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

$tokenHistoryCssVersion = filemtime(__DIR__ . '/css/token_history.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token History</title>
    <link rel="stylesheet" href="css/token_history.css?v=<?php echo $tokenHistoryCssVersion; ?>">
</head>
<body>
    <main class="page-shell">
        <div class="page-top">
            <a class="back-link" href="posts.php">&larr; Back to posts</a>
        </div>

        <section class="balance-card">
            <span class="balance-label">Current token balance</span>
            <div class="balance-value"><?= $tokenBalance ?></div>
        </section>

        <section class="history-card">
            <div class="history-head">
                <h2>Token History</h2>
                <p>See where you earned tokens and where you spent them.</p>
            </div>

            <?php if (!$transactions): ?>
                <div class="empty-state">No token transactions found yet.</div>
            <?php else: ?>
                <div class="history-filters">
                    <button type="button" class="history-filter-btn is-active" data-filter="all">All</button>
                    <button type="button" class="history-filter-btn" data-filter="earned">Earned</button>
                    <button type="button" class="history-filter-btn" data-filter="spent">Spent</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $tokenCharge = (int) $transaction['token_charge'];
                            $amountClass = $tokenCharge > 0 ? 'amount-gain' : ($tokenCharge < 0 ? 'amount-loss' : 'amount-free');
                            $amountText = $tokenCharge > 0 ? '+' . $tokenCharge : (string) $tokenCharge;
                            $filterGroup = $tokenCharge > 0 ? 'earned' : ($tokenCharge < 0 ? 'spent' : 'spent');
                            ?>
                            <tr data-filter-group="<?= htmlspecialchars($filterGroup) ?>">
                                <td><?= htmlspecialchars(describeTransaction($tokenCharge, $transaction['transaction_source'] ?? null)) ?></td>
                                <td class="<?= $amountClass ?>"><?= htmlspecialchars($amountText) ?></td>
                                <td><?= htmlspecialchars($transaction['timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="historyEmptyFilter" class="history-empty-filter">No transactions in this category yet.</div>
            <?php endif; ?>
        </section>
    </main>
    <?php if ($transactions): ?>
    <script>
        const filterButtons = document.querySelectorAll(".history-filter-btn");
        const rows = document.querySelectorAll("tbody tr[data-filter-group]");
        const emptyState = document.getElementById("historyEmptyFilter");

        filterButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const filter = button.dataset.filter || "all";
                let visibleCount = 0;

                filterButtons.forEach((item) => item.classList.toggle("is-active", item === button));

                rows.forEach((row) => {
                    const matches = filter === "all" || row.dataset.filterGroup === filter;
                    row.hidden = !matches;
                    if (matches) {
                        visibleCount += 1;
                    }
                });

                emptyState.classList.toggle("is-visible", visibleCount === 0);
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
