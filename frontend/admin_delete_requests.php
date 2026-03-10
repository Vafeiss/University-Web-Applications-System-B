<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: posts.php");
    exit();
}

$cssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_delete_requests.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Requests</title>
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $cssVersion; ?>">
</head>

<body>

<main class="pending-page">
    <header class="pending-page-header">
        <h1>Post Delete Requests</h1>
        <p>Review user deletion requests and decide whether the related posts should be removed.</p>
    </header>

    <div id="deleteRequestsFeedback" class="pending-feedback" hidden></div>

    <section id="deleteRequests" class="pending-grid" aria-live="polite"></section>
</main>

<script src="js/admin_delete_requests.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>