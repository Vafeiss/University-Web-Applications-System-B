<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/search.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$keyword = trim($_GET['keyword'] ?? '');
$category = (isset($_GET['category']) && $_GET['category'] !== '') ? (int) $_GET['category'] : null;
$status = (isset($_GET['status']) && $_GET['status'] !== '') ? (int) $_GET['status'] : 1;
$from = (isset($_GET['from']) && $_GET['from'] !== '') ? $_GET['from'] : null;
$to = (isset($_GET['to']) && $_GET['to'] !== '') ? $_GET['to'] : null;
$sort = trim($_GET['sort'] ?? 'newest');
$followedOnly = isset($_GET['followed_only']) && $_GET['followed_only'] === '1';
$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$authorIds = [];

if (isset($_GET['author_ids'])) {
    $rawAuthorIds = is_array($_GET['author_ids'])
        ? $_GET['author_ids']
        : explode(',', (string) $_GET['author_ids']);

    $authorIds = array_values(array_filter(array_map('intval', $rawAuthorIds), static fn ($id) => $id > 0));
}

try {
    $db = new Database();
    $search = new Search($db->connect());

    $results = $search->searchPosts(
        $keyword,
        $category,
        $status,
        $from,
        $to,
        $sort,
        $followedOnly,
        $currentUserId,
        $authorIds
    );

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
