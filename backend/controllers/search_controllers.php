<?php
/**
 * File: search_controllers.php
 * Layer: Controller
 * Module: Search
 * System: University Web Applications System B
 *
 * Description:
 * Provides advanced post search with multiple filters and sorting options.
 * Supports keyword search, category filtering, date ranges, followed-only posts,
 * and author filtering. Admin access shows rejected/deleted posts.
 *
 * Functions:
 * - search() → executes post search with full filter/sort support
 *
 * Security:
 * - Input validation on all query parameters
 * - PDO prepared statements for keyword search
 * - Respects user privacy: hides admin-only posts from non-admins
 * - Admin-only visibility of rejected/deleted posts
 * - User role-based result filtering
 *
 * Used By:
 * - frontend/search.php (AJAX search requests)
 * - frontend/posts.php (filtered post loading)
 *
 * Author:Antriani Theofanous 
 * Date: 2026
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/search.php';

class SearchController extends BaseController {

    public function search(): void {
        $keyword = trim((string) ($_GET['keyword'] ?? ''));

        if ($keyword !== '') {
            $keyword = function_exists('mb_strtolower')
                ? mb_strtolower($keyword, 'UTF-8')
                : strtolower($keyword);
        }

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
