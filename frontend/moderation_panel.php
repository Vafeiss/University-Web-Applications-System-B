<?php
/**
 * Feature 4: Moderation Control Panel 
 */

session_start();

require_once "../backend/middleware/AuthGuard.php";
require_once "../backend/config/database.php";

requireLogin();

$db = new Database();
$conn = $db->connect();

$moderationPanelCssVersion = filemtime(__DIR__ . '/css/moderation_panel.css');


// 1. ΔΙΑΧΕΙΡΙΣΗ ΜΑΖΙΚΩΝ ΕΝΕΡΓΕΙΩΝ (BULK)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    if (!empty($_POST['post_ids'])) {
        $id_list = implode(',', array_map('intval', $_POST['post_ids']));
        $new_status = ($_POST['bulk_action'] == 'bulk_approve') ? 2 : 0;
        $conn->exec("UPDATE posts SET status = $new_status WHERE post_id IN ($id_list)");
    }
}

// 2. ΔΙΑΧΕΙΡΙΣΗ ΜΕΜΟΝΩΜΕΝΩΝ ΕΝΕΡΓΕΙΩΝ (SINGLE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['single_action'])) {
    $p_id = (int)$_POST['post_id'];
    $new_status = ($_POST['single_action'] == 'approve') ? 2 : 0;
    
    $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE post_id = ?");
    $stmt->execute([$new_status, $p_id]);
}

// Ανάκτηση δεδομένων
$sql_pending = "SELECT p.post_id, p.title, p.content, u.username FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.status = 1 ORDER BY p.timestamp DESC";
$pendingStmt = $conn->query($sql_pending);
$pending_result = $pendingStmt ? $pendingStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$sql_history = "SELECT p.title, u.username, p.status, p.timestamp FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.status IN (0, 2) ORDER BY p.timestamp DESC LIMIT 10";
$historyStmt = $conn->query($sql_history);
$history_result = $historyStmt ? $historyStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Moderation Control Panel</title>
    <link rel="stylesheet" href="/University-Web-Applications-System-B/frontend/css/moderation_panel.css?v=<?php echo $moderationPanelCssVersion; ?>">
</head>
<body>

<div class="container">
    <div class="header-row">
      <div class="header-title">Moderation Control Center</div>
      <div class="tabs">
        <button class="btn">Profile settings</button>
        <!-- Μπορείς να προσθέσεις και άλλα tabs εδώ -->
      </div>
    </div>

    <div class="section">
        <h2> Δημοσιεύσεις προς Έλεγχο</h2>
        
        <?php if (!empty($pending_result)): ?>
            <form method="POST">
                <div class="bulk-toolbar">
                    <input type="checkbox" id="selectAll" class="bulk-checkbox"> 
                    <label for="selectAll" class="bulk-checkbox-label">Επιλογή όλων</label>
                    <div class="bulk-actions-wrap">
                        <button type="submit" name="bulk_action" value="bulk_approve" class="btn btn-approve btn-bulk">Έγκριση Επιλεγμένων</button>
                        <button type="submit" name="bulk_action" value="bulk_reject" class="btn btn-reject btn-bulk">Απόρριψη Επιλεγμένων</button>
                    </div>
                </div>

                <?php foreach ($pending_result as $row): ?>
                    <div class="post-card">
                        <input type="checkbox" name="post_ids[]" value="<?php echo $row['post_id']; ?>" class="post-checkbox">
                        
                        <div class="post-content">
                            <div class="post-card-top">
                                <div class="username">@<?php echo htmlspecialchars($row['username']); ?></div>
                                <span class="status-badge-pending">ΕΚΚΡΕΜΕΙ</span>
                            </div>
                            
                            <h3 class="post-card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                            
                            <div class="actions">
                                <input type="hidden" name="post_id_val_<?php echo $row['post_id']; ?>" value="<?php echo $row['post_id']; ?>">
                                <button type="submit" name="single_action" value="approve" 
                                        onclick="this.form.append(Object.assign(document.createElement('input'), {type: 'hidden', name: 'post_id', value: '<?php echo $row['post_id']; ?>'}));" 
                                        class="btn btn-approve btn-compact">ΕΓΚΡΙΣΗ</button>
                                
                                <button type="submit" name="single_action" value="reject" 
                                        onclick="this.form.append(Object.assign(document.createElement('input'), {type: 'hidden', name: 'post_id', value: '<?php echo $row['post_id']; ?>'}));" 
                                        class="btn btn-reject btn-compact">ΑΠΟΡΡΙΨΗ</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        <?php else: ?>
            <div class="empty-msg">Δεν υπάρχουν εκκρεμείς δημοσιεύσεις.</div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2> Ιστορικό Δημοσιεύσεων </h2>
        <?php if (!empty($history_result)): ?>
            <table>
                <thead>
                    <tr><th>Τίτλος</th><th>Χρήστης</th><th>Κατάσταση</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($history_result as $h): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['title']); ?></td>
                            <td>@<?php echo htmlspecialchars($h['username']); ?></td>
                            <td>
                                <span class="badge <?php echo ($h['status'] == 2) ? 'badge-approved' : 'badge-rejected'; ?>">
                                    <?php echo ($h['status'] == 2) ? 'Approved' : 'Rejected'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-msg">Δεν υπάρχει ιστορικό δημοσιεύσεων.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.getElementById('selectAll').onclick = function() {
        var checkboxes = document.getElementsByName('post_ids[]');
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    }
</script>

</body>
</html>
