<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/search.php';

class SearchController extends BaseController {

    public function search(): void {
        $keyword = trim($_GET['keyword'] ?? '');
        $category = (isset($_GET['category']) && $_GET['category'] !== '') ? (int) $_GET['category'] : null;
        $status = (isset($_GET['status']) && $_GET['status'] !== '') ? (int) $_GET['status'] : 1;
        $from = (isset($_GET['from']) && $_GET['from'] !== '') ? $_GET['from'] : null;
        $to = (isset($_GET['to']) && $_GET['to'] !== '') ? $_GET['to'] : null;
        $sort = trim($_GET['sort'] ?? 'newest');
        $followedOnly = isset($_GET['followed_only']) && $_GET['followed_only'] === '1';
        $currentUserId = $this->getCurrentUserId();
        $isAdmin = $this->isAdmin();
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
                $authorIds,
                $isAdmin
            );

            $this->jsonResponse([
                'ok' => true,
                'count' => count($results),
                'data' => $results
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'ok' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

$controller = new SearchController();
$controller->search();
