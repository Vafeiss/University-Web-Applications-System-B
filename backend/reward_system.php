<?php
session_start();

// 1. ΣΥΝΔΕΣΗ ΜΕ ΤΗ ΒΑΣΗ 
$db_path = 'C:\xampp\htdocs\University-Web-Applications-System-B\database\db_connect.php';

if (file_exists($db_path)) {
    include($db_path);
} else {
  
    include('../database/db_connect.php');
}


if (!isset($conn)) {
    if (isset($db)) { $conn = $db; }
    elseif (isset($link)) { $conn = $link; }
}


if (isset($_POST['ad_id'])) {
    
    $user_id = 1; 
    $ad_id = intval($_POST['ad_id']);
    $tokens_to_add = 1; 

    // 1. Ενημέρωση των tokens του χρήστη
    $update_query = "UPDATE users SET token_balance = token_balance + $tokens_to_add WHERE user_id = $user_id";
    
    // 2. Καταγραφή της προβολής 
    $log_query = "INSERT INTO ad_views (user_id, advertise_id, viewed_at) VALUES ($user_id, $ad_id, NOW())";

   
    if ($conn->query($update_query) && $conn->query($log_query)) {
        echo "Success";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Invalid Request: No ad_id received";
}
?>