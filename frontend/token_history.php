<?php
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

function describeTransaction(int $tokenCharge): string {
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
    "SELECT transaction_id, token_charge, timestamp
     FROM transactions
     WHERE user_id = :id
     ORDER BY timestamp DESC, transaction_id DESC"
);
$transactionsStmt->execute([":id" => $_SESSION['user_id']]);
$transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token History</title>
    <style>
        body {
            margin: 0;
            background: #f4f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f3659;
        }

        .page-shell {
            max-width: 960px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .page-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .back-link {
            color: #2c5cc5;
            font-weight: 700;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .balance-card,
        .history-card {
            background: #ffffff;
            border: 1px solid #dbe5f1;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .balance-card {
            padding: 22px 24px;
            margin-bottom: 22px;
        }

        .balance-label {
            display: block;
            color: #60708b;
            font-size: 14px;
            margin-bottom: 6px;
        }

        .balance-value {
            font-size: 36px;
            font-weight: 700;
            color: #183b72;
        }

        .history-card {
            overflow: hidden;
        }

        .history-head {
            padding: 20px 24px 14px;
            border-bottom: 1px solid #e4ebf5;
        }

        .history-head h2 {
            margin: 0 0 6px;
            font-size: 24px;
        }

        .history-head p {
            margin: 0;
            color: #687892;
        }

        .history-filters {
            display: flex;
            gap: 10px;
            padding: 0 24px 18px;
            border-bottom: 1px solid #e4ebf5;
            flex-wrap: wrap;
        }

        .history-filter-btn {
            border: 1px solid #d7e1f0;
            background: #ffffff;
            color: #28405f;
            border-radius: 999px;
            padding: 9px 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .history-filter-btn.is-active {
            background: #214f95;
            border-color: #214f95;
            color: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 24px;
            text-align: left;
            border-bottom: 1px solid #eef3f8;
        }

        th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7b95;
            background: #fbfcfe;
        }

        .amount-gain {
            color: #16824f;
            font-weight: 700;
        }

        .amount-loss {
            color: #c0392b;
            font-weight: 700;
        }

        .amount-free {
            color: #8a6b10;
            font-weight: 700;
        }

        .empty-state {
            padding: 28px 24px;
            color: #687892;
        }

        .history-empty-filter {
            display: none;
            padding: 22px 24px;
            color: #687892;
            border-top: 1px solid #eef3f8;
        }

        .history-empty-filter.is-visible {
            display: block;
        }

        @media (max-width: 720px) {
            .page-top {
                flex-direction: column;
                align-items: flex-start;
            }

            th,
            td {
                padding: 12px 14px;
                font-size: 14px;
            }

            .balance-value {
                font-size: 30px;
            }
        }
    </style>
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
                                <td><?= htmlspecialchars(describeTransaction($tokenCharge)) ?></td>
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
