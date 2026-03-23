<?php

class Search {

    private PDO $conn;

    public function __construct(PDO $connection) {
        $this->conn = $connection;
    }

    public function searchPosts(
        string $keyword = '',
        ?int $category = null,
        ?int $status = 1,
        ?string $from = null,
        ?string $to = null,
        string $sort = 'newest',
        bool $followedOnly = false,
        ?int $currentUserId = null,
        array $authorIds = []
    ): array {

        $sql = "SELECT p.*, u.username, c.name AS category
                FROM posts p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.deleted = 0";

        $params = [];

        if ($keyword !== '') {
            $sql .= " AND (p.title LIKE :keyword OR p.content LIKE :keyword)";
            $params[':keyword'] = $keyword . '%';
        }

        if ($category !== null) {
            $sql .= " AND p.category_id = :category";
            $params[':category'] = $category;
        }

        if ($status !== null) {
            $sql .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        if ($from !== null && $from !== '') {
            $sql .= " AND p.timestamp >= :from";
            $params[':from'] = $from . ' 00:00:00';
        }

        if ($to !== null && $to !== '') {
            $sql .= " AND p.timestamp <= :to";
            $params[':to'] = $to . ' 23:59:59';
        }

        if ($followedOnly && $currentUserId !== null) {
            $sql .= " AND EXISTS (
                        SELECT 1
                        FROM followers f
                        WHERE f.follower_id = :current_user_id
                        AND f.followed_id = p.user_id
                        AND f.status = 1
                    )";
            $params[':current_user_id'] = $currentUserId;
        }

        if (!empty($authorIds)) {
            $placeholders = [];

            foreach ($authorIds as $index => $authorId) {
                $placeholder = ':author_id_' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $authorId;
            }

            $sql .= " AND p.user_id IN (" . implode(', ', $placeholders) . ")";
        }

        if (!empty($authorIds)) {
            $sql .= " AND p.is_anonymous = 0";
        }

        $orderBy = match ($sort) {
            'oldest' => 'p.timestamp ASC',
            'title_asc' => 'p.title ASC',
            'title_desc' => 'p.title DESC',
            default => 'p.timestamp DESC',
        };

        $sql .= " ORDER BY {$orderBy}";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
