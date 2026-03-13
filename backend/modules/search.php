<?php

declare(strict_types=1);

final class Search
{
    private PDO $conn;

    public function __construct(PDO $connection)
    {
        $this->conn = $connection;
    }

    public function searchPosts(
        string $keyword = '',
        ?int $category = null,
        ?int $status = null,
        ?string $from = null,
        ?string $to = null,
        ?int $followedByUserId = null,
        string $sort = 'newest'
    ): array {
        $sortMap = [
            'newest' => 'p.created_at DESC',
            'oldest' => 'p.created_at ASC',
            'title_asc' => 'p.title ASC',
            'title_desc' => 'p.title DESC',
        ];

        $orderBy = $sortMap[$sort] ?? $sortMap['newest'];

        $sql = '
            SELECT
                p.id,
                p.user_id,
                p.title,
                p.content,
                p.created_at,
                p.status,
                p.category_id,
                c.name AS category_name,
                u.username
            FROM posts p
            LEFT JOIN categories c ON c.category_id = p.category_id
            INNER JOIN users u ON u.user_id = p.user_id
            WHERE 1=1
        ';

        if ($keyword !== '') {
            $sql .= ' AND (p.title LIKE :keyword OR p.content LIKE :keyword)';
        }

        if ($category !== null) {
            $sql .= ' AND p.category_id = :category';
        }

        if ($status !== null) {
            $sql .= ' AND p.status = :status';
        }

        if ($from !== null && $from !== '') {
            $sql .= ' AND p.created_at >= :from';
        }

        if ($to !== null && $to !== '') {
            $sql .= ' AND p.created_at <= :to';
        }

        if ($followedByUserId !== null) {
            $sql .= '
                AND EXISTS (
                    SELECT 1
                    FROM followers f
                    WHERE f.followed_id = p.user_id
                      AND f.follower_id = :followed_by_user_id
                      AND f.status = 1
                )
            ';
        }

        $sql .= ' ORDER BY ' . $orderBy;

        $stmt = $this->conn->prepare($sql);

        if ($keyword !== '') {
            $keywordParam = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $keywordParam, PDO::PARAM_STR);
        }

        if ($category !== null) {
            $stmt->bindParam(':category', $category, PDO::PARAM_INT);
        }

        if ($status !== null) {
            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        }

        if ($from !== null && $from !== '') {
            $fromDate = $from . ' 00:00:00';
            $stmt->bindParam(':from', $fromDate, PDO::PARAM_STR);
        }

        if ($to !== null && $to !== '') {
            $toDate = $to . ' 23:59:59';
            $stmt->bindParam(':to', $toDate, PDO::PARAM_STR);
        }

        if ($followedByUserId !== null) {
            $stmt->bindParam(':followed_by_user_id', $followedByUserId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getCategories(): array
    {
        $stmt = $this->conn->query(
            'SELECT category_id, name
             FROM categories
             ORDER BY name ASC'
        );

        return $stmt->fetchAll();
    }
}
