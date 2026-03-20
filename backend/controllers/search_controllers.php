<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/Search.php';

$keyword  = trim($_GET['keyword'] ?? '');
$category = (isset($_GET['category']) && $_GET['category'] !== '') ? (int)$_GET['category'] : null;
$status   = (isset($_GET['status']) && $_GET['status'] !== '') ? (int)$_GET['status'] : null;
$from     = (isset($_GET['from']) && $_GET['from'] !== '') ? $_GET['from'] : null;
$to       = (isset($_GET['to']) && $_GET['to'] !== '') ? $_GET['to'] : null;

try {

    $search = new Search($pdo);

    $results = $search->searchPosts($keyword, $category, $status, $from, $to);

    echo json_encode([
        'ok' => true,
        'count' => count($results),
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}