<?php
$conn = new mysqli("localhost", "root", "", "university_web");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = ($_GET['action'] == 'approve') ? 'approved' : 'rejected';
    $conn->query("UPDATE posts SET status = '$status' WHERE id = $id");
    header("Location: moderation_panel.php");
}

$pending = $conn->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = 'pending'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Moderation Panel</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #eee; }
        .card { background: white; padding: 15px; margin-bottom: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn { padding: 5px 10px; color: white; text-decoration: none; border-radius: 3px; margin-right: 5px; }
        .approve { background: green; } .reject { background: red; }
    </style>
</head>
<body>
    <h2>Εκκρεμείς Δημοσιεύσεις</h2>
    <?php while($row = $pending->fetch_assoc()): ?>
        <div class="card">
            <p><strong><?php echo $row['username']; ?>:</strong> <?php echo $row['content']; ?></p>
            <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn approve">Έγκριση</a>
            <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn reject">Απόρριψη</a>
        </div>
    <?php endwhile; ?>
</body>
</html>