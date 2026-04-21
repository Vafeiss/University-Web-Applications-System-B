<?php
/**
 * File: admin_ads.php
 * Layer: Frontend Page
 * Module: Admin Ads Management
 * System: University Web Applications System B
 *
 * Description:
 * Standalone admin page for adding and deleting advertisements while
 * preserving the original Moderation-Control page layout and flow.
 *
 * Security:
 * - Admin session validation
 * - Ban check before access
 * - Prepared statements for database writes
 *
 * Used By:
 * - frontend/admin_dashboard.php
 *
 * Author: Adapted for current branch
 * Date: 2026
 */

declare(strict_types=1);

session_start();

require_once "../backend/middleware/BanGuard.php";
require_once "../backend/config/database.php";

enforceFrontendUserNotBanned();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: posts.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM advertisements WHERE advertise_id = :id");
        $stmt->execute([':id' => $id]);
    }

    header("Location: admin_ads.php");
    exit();
}

if (isset($_POST['add_ad'])) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $url = trim((string) ($_POST['url'] ?? ''));
    $duration = (float) ($_POST['duration'] ?? 0);
    $cooldown = (int) ($_POST['cooldown'] ?? 0);

    if ($title !== '' && $url !== '' && $duration > 0 && $cooldown >= 0) {
        $stmt = $conn->prepare(
            "INSERT INTO advertisements (title, image_url, time_duration, cooldown_hours)
             VALUES (:title, :url, :duration, :cooldown)"
        );
        $stmt->execute([
            ':title' => $title,
            ':url' => $url,
            ':duration' => $duration,
            ':cooldown' => $cooldown,
        ]);
    }

    header("Location: admin_ads.php");
    exit();
}

$adsStmt = $conn->query("SELECT * FROM advertisements ORDER BY advertise_id DESC");
$ads = $adsStmt ? $adsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Διαχείριση Διαφημίσεων</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f6; padding: 20px; }
        .admin-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table img, .table video { border-radius: 5px; object-fit: cover; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="mb-0">📢 Διαχείριση Διαφημίσεων (Admin)</h2>
        <a href="admin_dashboard.php" class="btn btn-outline-secondary">Επιστροφή στο Admin</a>
    </div>

    <div class="admin-card mb-5">
        <h5>Προσθήκη Νέας Διαφήμισης </h5>
        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="title" class="form-control" placeholder="Τίτλος" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="url" class="form-control" placeholder="URL Φωτογραφίας ή Video" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="duration" class="form-control" placeholder="Διάρκεια (sec)" step="0.1" min="0.1" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="cooldown" class="form-control" placeholder="Cooldown (ώρες)" min="0" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="add_ad" class="btn btn-success w-100">Προσθήκη</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5>Υπάρχουσες Διαφημίσεις</h5>
        <table class="table table-hover mt-3">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Προεπισκόπηση</th>
                    <th>Τίτλος</th>
                    <th>Διάρκεια</th>
                    <th>Cooldown</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $row): ?>
                <tr>
                    <td><?php echo (int) $row['advertise_id']; ?></td>
                    <td>
                        <?php
                        $url = (string) ($row['image_url'] ?? '');
                        $ext = strtolower((string) pathinfo($url, PATHINFO_EXTENSION));
                        if (in_array($ext, ['mp4', 'webm'], true)):
                        ?>
                            <video width="80" height="50" muted><source src="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"></video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" width="80" height="50" alt="Ad preview">
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['time_duration'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>s</td>
                    <td><?php echo (int) ($row['cooldown_hours'] ?? 0); ?>h</td>
                    <td>
                        <a href="?delete=<?php echo (int) $row['advertise_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Διαγραφή;')">🗑 Διαγραφή</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
