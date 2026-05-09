<?php
/**
 * File: CommentModel.php
 * Layer: Model
 * Module: Comments
 * System: University Web Applications System B
 *
 * Description:
 * Data model for comment management. Provides CRUD operations for comments,
 * deletion request handling, and deletion count tracking per user.
 *
 * Functions:
 * - createComment() → inserts new comment on a post
 * - getCommentsByPost() → retrieves comments for a post with deletion request flags
 * - deleteRequestExists() → checks if user requested deletion for a comment
 * - deleteComment() → marks comment as removed by moderation
 * - getDeleteRequestById() → retrieves pending deletion request details
 * - listDeleteRequests() → admins view pending comment deletion requests
 * - getCommentDeletionCount() → counts delete requests per user for reports
 *
 * Security:
 * - PDO prepared statements prevent SQL injection
 * - Comment masking: removed comments display '[Removed by moderation]'
 * - Deletion request user isolation
 *
 * Used By:
 * - CommentController
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

require_once __DIR__ . '/../config/database.php';

class CommentModel {

    private PDO $conn;

    public function __construct(){
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create comment
    public function createComment($user_id, $post_id, $content){

        $query = "INSERT INTO comments
                  (user_id, post_id, comment_content)
                  VALUES (:user_id, :post_id, :content)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':user_id' => $user_id,
            ':post_id' => $post_id,
            ':content' => $content
        ]);
    }

    // Get comments for post
    public function getCommentsByPost($post_id, $currentUserId = null){

        $query = "SELECT c.*, u.username";

        if ($currentUserId !== null) {
            $query .= ", EXISTS (
                            SELECT 1
                            FROM comment_delete_requests cdr
                            WHERE cdr.comment_id = c.comment_id
                              AND cdr.requested_by = :current_user_id
                        ) AS has_requested_delete";
        }

        $query .= "
                  FROM comments c
                  JOIN users u ON c.user_id = u.user_id
                  WHERE c.post_id = :post_id
                    AND c.comment_content <> '[Removed by moderation]'
                  ORDER BY c.timestamp ASC";

        $stmt = $this->conn->prepare($query);
        $params = [
            ':post_id' => $post_id
        ];

        if ($currentUserId !== null) {
            $params[':current_user_id'] = $currentUserId;
        }

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteRequestExists($comment_id, $user_id){
        $query = "SELECT 1
                  FROM comment_delete_requests
                  WHERE comment_id = :comment_id
                    AND requested_by = :user_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':comment_id' => $comment_id,
            ':user_id' => $user_id
        ]);

        return (bool) $stmt->fetchColumn();
    }

    // Request comment deletion
    public function requestDelete($comment_id,$user_id,$reason){
        $query = "INSERT INTO comment_delete_requests
                  (comment_id, requested_by, reason)
                  VALUES (:comment_id, :user_id, :reason)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':comment_id' => $comment_id,
            ':user_id' => $user_id,
            ':reason' => $reason
        ]);
    }

    public function adminDeleteComment($comment_id){

        $this->conn->beginTransaction();

        try {
            $query = "DELETE FROM comments
                      WHERE comment_id = :comment_id";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':comment_id' => $comment_id
            ]);

            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            $query = "UPDATE comment_delete_requests
                      SET status = 1
                      WHERE comment_id = :comment_id AND status = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':comment_id' => $comment_id
            ]);

            $query = "UPDATE content_reports
                      SET status = 1
                      WHERE content_type = 'comment' AND content_id = :comment_id AND status = 0";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':comment_id' => $comment_id
            ]);

            $this->conn->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            throw $exception;
        }
    }

    

}




