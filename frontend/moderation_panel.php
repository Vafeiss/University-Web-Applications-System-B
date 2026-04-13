<?php
/**
 * Feature 4: Moderation Control Panel 
 */

$host = "localhost";
$user = "root";
$pass = "";


if ($conn->connect_error) {
    die("Σφάλμα σύνδεσης: " . $conn->connect_error);
}

// 1. ΔΙΑΧΕΙΡΙΣΗ ΜΑΖΙΚΩΝ ΕΝΕΡΓΕΙΩΝ (BULK)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    if (!empty($_POST['post_ids'])) {
        $id_list = implode(',', array_map('intval', $_POST['post_ids']));
        $new_status = ($_POST['bulk_action'] == 'bulk_approve') ? 2 : 0;
        $conn->query("UPDATE posts SET status = $new_status WHERE post_id IN ($id_list)");
    }
}

// 2. ΔΙΑΧΕΙΡΙΣΗ ΜΕΜΟΝΩΜΕΝΩΝ ΕΝΕΡΓΕΙΩΝ (SINGLE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['single_action'])) {
    $p_id = (int)$_POST['post_id'];
    $new_status = ($_POST['single_action'] == 'approve') ? 2 : 0;
    
    $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE post_id = ?");
    $stmt->bind_param("ii", $new_status, $p_id);
    $stmt->execute();
    $stmt->close();
}

// Ανάκτηση δεδομένων
$sql_pending = "SELECT p.post_id, p.title, p.content, u.username FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.status = 1 ORDER BY p.timestamp DESC";
$pending_result = $conn->query($sql_pending);

$sql_history = "SELECT p.title, u.username, p.status, p.timestamp FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.status IN (0, 2) ORDER BY p.timestamp DESC LIMIT 10";
$history_result = $conn->query($sql_history);
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Moderation Control Panel</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f0f2f5; padding: 20px; color: #1c1e21; }
        .container { max-width: 900px; margin: auto; }
        .section { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; }
        h1 { text-align: center; color: #1877f2; margin-bottom: 30px; }
        h2 { border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; margin-bottom: 20px; }
        
        .bulk-toolbar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; border: 1px solid #ddd; }
        
        .post-card { border: 1px solid #dddfe2; border-radius: 8px; padding: 20px; margin-bottom: 15px; border-left: 6px solid #1877f2; display: flex; gap: 15px; align-items: flex-start; position: relative; }
        .post-content { flex-grow: 1; }
        .post-checkbox { transform: scale(1.5); margin-top: 5px; cursor: pointer; }
        
        .username { font-weight: bold; color: #1877f2; }
        .status-badge-pending { 
            background-color: #fff3e0; color: #ef6c00; padding: 4px 12px; border-radius: 20px; 
            font-weight: bold; font-size: 0.75em; border: 1px solid #ffe0b2; 
        }

        .actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-approve { background-color: #42b72a; color: white; }
        .btn-reject { background-color: #f02849; color: white; }
        .btn-bulk { padding: 10px 20px; font-size: 0.9em; }
        .btn:hover { opacity: 0.8; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8em; color: white; font-weight: bold; }
        .badge-approved { background-color: #42b72a; }
        .badge-rejected { background-color: #f02849; }
        .empty-msg { text-align: center; color: #8d949e; padding: 20px; font-style: italic; }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 48px;
            margin-bottom: 24px;
        }
        .header-title {
            font-size: 2rem;
            font-weight: bold;
        }
        .tabs {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        /* Responsive: όταν το body έχει κλάση minimized, ο τίτλος πάει από κάτω */
        body.minimized .header-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        body.minimized .header-title {
            margin-left: 0;
        }
    </style>
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
        
        <?php if ($pending_result && $pending_result->num_rows > 0): ?>
            <form method="POST">
                <div class="bulk-toolbar">
                    <input type="checkbox" id="selectAll" style="transform: scale(1.2); cursor:pointer;"> 
                    <label for="selectAll" style="font-weight: bold; cursor:pointer;">Επιλογή όλων</label>
                    <div style="margin-left: auto; display: flex; gap: 10px;">
                        <button type="submit" name="bulk_action" value="bulk_approve" class="btn btn-approve btn-bulk">Έγκριση Επιλεγμένων</button>
                        <button type="submit" name="bulk_action" value="bulk_reject" class="btn btn-reject btn-bulk">Απόρριψη Επιλεγμένων</button>
                    </div>
                </div>

                <?php while($row = $pending_result->fetch_assoc()): ?>
                    <div class="post-card">
                        <input type="checkbox" name="post_ids[]" value="<?php echo $row['post_id']; ?>" class="post-checkbox">
                        
                        <div class="post-content">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div class="username">@<?php echo htmlspecialchars($row['username']); ?></div>
                                <span class="status-badge-pending">ΕΚΚΡΕΜΕΙ</span>
                            </div>
                            
                            <h3 style="margin: 8px 0;"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></div>
                            
                            <div class="actions">
                                <input type="hidden" name="post_id_val_<?php echo $row['post_id']; ?>" value="<?php echo $row['post_id']; ?>">
                                <button type="submit" name="single_action" value="approve" 
                                        onclick="this.form.append(Object.assign(document.createElement('input'), {type: 'hidden', name: 'post_id', value: '<?php echo $row['post_id']; ?>'}));" 
                                        class="btn btn-approve" style="font-size: 0.8em;">ΕΓΚΡΙΣΗ</button>
                                
                                <button type="submit" name="single_action" value="reject" 
                                        onclick="this.form.append(Object.assign(document.createElement('input'), {type: 'hidden', name: 'post_id', value: '<?php echo $row['post_id']; ?>'}));" 
                                        class="btn btn-reject" style="font-size: 0.8em;">ΑΠΟΡΡΙΨΗ</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </form>
        <?php else: ?>
            <div class="empty-msg">Δεν υπάρχουν εκκρεμείς δημοσιεύσεις.</div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2> Ιστορικό Δημοσιεύσεων </h2>
        <?php if ($history_result && $history_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr><th>Τίτλος</th><th>Χρήστης</th><th>Κατάσταση</th></tr>
                </thead>
                <tbody>
                    <?php while($h = $history_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['title']); ?></td>
                            <td>@<?php echo htmlspecialchars($h['username']); ?></td>
                            <td>
                                <span class="badge <?php echo ($h['status'] == 2) ? 'badge-approved' : 'badge-rejected'; ?>">
                                    <?php echo ($h['status'] == 2) ? 'Approved' : 'Rejected'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
