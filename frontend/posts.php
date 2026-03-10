<?php
session_start();    

/* Έλεγχος αν ο χρήστης είναι συνδεδεμένος */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
/* Έλεγχος αν ο χρήστης είναι admin  για εμφανιση anonymous posts */
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>

<!DOCTYPE html>
<html>

<head>

<title>Posts Feed</title>
<!-- Προσθήκη CSS για styling των posts -->
<link rel="stylesheet" href="css/post.css">

</head>

<body>

<div class="post-container">

<h2>Posts Feed</h2>

<!-- εμφανίζονται τα posts -->
<div id="postsList"></div>

</div>
<!-- Προσθήκη JavaScript για φόρτωση των posts σε admin -->
<script>
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
</script>

<!-- Προσθήκη JavaScript για φόρτωση των posts -->
<script src="js/posts.js"></script>

</body>

</html>