<?php
session_start();
// Έλεγχος αν ο χρήστης είναι admin
if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    header("Location: posts.php");
    exit();
}

header("Location: admin_dashboard.php?section=reports");
exit();
