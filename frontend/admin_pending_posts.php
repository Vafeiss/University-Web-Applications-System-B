<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: posts.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
<title>Pending Posts</title>
</head>

<body>

<h2>Pending Posts</h2>
<!-- Εδώ θα εμφανιστούν τα pending posts(status=0) -->
<div id="pendingPosts"></div>
<!-- js για φόρτωση των pending posts από controller & προσθηκη κουμπιων-->
<script src="js/admin_pending_posts.js"></script>

</body>

</html>