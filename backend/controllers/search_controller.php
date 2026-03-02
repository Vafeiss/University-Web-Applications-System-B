<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/Search.php';

header('Content-Type: application/json');

$keyword  = $_GET['keyword'] ?? '';
$category = $_GET['category'] ?? null;
$status   = $_GET['status'] ?? null;

$search = new Search($pdo);

$results = $search->searchPosts($keyword, $category, $status);

echo json_encode($results);