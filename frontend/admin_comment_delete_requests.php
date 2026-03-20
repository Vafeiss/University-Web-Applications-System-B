<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: posts.php");
	exit();
}

$cssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$jsVersion = filemtime(__DIR__ . '/js/admin_comment_delete_requests.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comment Delete Requests</title>
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $cssVersion; ?>">
</head>

<body>

<main class="pending-page">
	<header class="pending-page-header">
		<h1>Comment Delete Requests</h1>
		<p>Review user requests to remove comments and decide whether they should be deleted.</p>
	</header>

	<div id="commentDeleteFeedback" class="pending-feedback" hidden></div>

	<section id="commentDeleteRequests" class="pending-grid" aria-live="polite"></section>
</main>

<script src="js/admin_comment_delete_requests.js?v=<?php echo $jsVersion; ?>"></script>

</body>

</html>