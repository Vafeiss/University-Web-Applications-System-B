<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "system_b_support"; 

// Σύνδεση με τη βάση
$conn = new mysqli($servername, $username, $password, $dbname);

// Έλεγχος σύνδεσης
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


?>