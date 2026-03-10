<?php
session_start();
// Έλεγχος αν ο χρήστης είναι admin
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    header("Location: posts.php");
    exit();
}

$cssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_reports.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reported Posts</title>
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $cssVersion; ?>">
</head>

<body>

<main class="pending-page">
    <header class="pending-page-header">
        <h1>Reported Posts</h1>
        <p>Review reported posts and decide whether the post should be removed.</p>
    </header>

    <div id="reportsFeedback" class="pending-feedback" hidden></div>

    <!-- Εδώ θα εμφανιστούν τα αναφερόμενα posts και σχόλια -->
    <section id="reports" class="pending-grid" aria-live="polite"></section>
</main>

<!-- Φόρτωση JS για διαχείριση των αναφορών -->
<script src="js/admin_reports.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>