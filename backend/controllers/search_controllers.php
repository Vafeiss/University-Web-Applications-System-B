<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/search.php';

$keyword  = trim($_GET['keyword'] ?? '');
$category = (isset($_GET['category']) && $_GET['category'] !== '') ? (int)$_GET['category'] : null;
$status   = (isset($_GET['status']) && $_GET['status'] !== '') ? (int)$_GET['status'] : null;
$from     = (isset($_GET['from']) && $_GET['from'] !== '') ? $_GET['from'] : null;
$to       = (isset($_GET['to']) && $_GET['to'] !== '') ? $_GET['to'] : null;
$followedByUserId = (isset($_GET['followed_by_user_id']) && $_GET['followed_by_user_id'] !== '')
    ? (int) $_GET['followed_by_user_id']
    : null;
$sort     = trim($_GET['sort'] ?? 'newest');

try {
    $search = new Search($pdo);
    $results = $search->searchPosts($keyword, $category, $status, $from, $to, $followedByUserId, $sort);

    echo json_encode([
        'ok' => true,
        'count' => count($results),
        'data' => $results
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
