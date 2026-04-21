<?php
/**
 * File: CommentController.php
 * Layer: Controller
 * Module: Comments
 * System: University Web Applications System B
 *
 * Description:
 * Manages comment lifecycle: creation, listing, deletion requests, and
 * admin approvals. Automatically notifies post owners of new comments.
 *
 * Functions:
 * - create() → users submit comments on posts with notifications
 * - list() → retrieve comments for a post
 * - requestDelete() → users request comment deletion
 * - listDeleteRequests() → admins view pending deletions
 * - approveDelete() → admins approve and remove comments
 * - rejectDelete() → admins reject deletion requests
 *
 * Security:
 * - requireLogin() enforces authentication
 * - Input validation on post_id and content
 * - PDO prepared statements for all queries
 * - Comment masking: removed comments show '[Removed by moderation]'
 *
 * Used By:
 * - frontend/post.php (comment submission via AJAX)
 * - frontend/admin_comment_delete_requests.php
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

header("Content-Type: application/json");
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../modules/CommentModel.php';
require_once __DIR__ . '/../modules/NotificationModel.php';
// Controller responsible for handling Comment operations such as creating, listing and requesting deletion of comments
class CommentController extends BaseController {

    private CommentModel $commentModel;

    public function __construct(){
        $this->commentModel = new CommentModel();
    }

    // Create comment
    public function create(){
        //χρηση requireLogin απο το BaseController για να διασφαλίσουμε ότι ο χρήστης είναι συνδεδεμένος πριν επιτρέψουμε τη δημιουργία σχολίου
        $user_id = $this->requireLogin();
        // Λήψη δεδομένων από το αίτημα
        $data = $this->getJSONInput();
        // Έλεγχος για την ύπαρξη των απαιτούμενων πεδίων post_id και content
        if(!$data || !isset($data['post_id']) || !isset($data['content'])){
            $this->jsonResponse(["message" => "Invalid data"], 400);
        }
        // Δημιουργία σχολίου χρησιμοποιώντας το CommentModel
        $this->commentModel->createComment(
            $user_id,
            $data['post_id'],
            $data['content']
        );
        
        // Notification: notify post owner
        require_once __DIR__ . '/../modules/PostModel.php';
        require_once __DIR__ . '/../modules/NotificationModel.php';

        $postModel = new PostModel();
        $notificationModel = new NotificationModel();

        // βρες owner του post
        $post = $postModel->getPostById($data['post_id']);
        // να μην ειδοποιείται ο χρήστης αν σχολιάζει το δικό του post
        if ($post && $post['user_id'] != $user_id) {
            // φέρνουμε το username του commenter για να κάνουμε το message πιο ζωντανό
            require_once __DIR__ . '/../config/database.php';
            $db = (new Database())->connect();
            $stmt = $db->prepare("SELECT username FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $commenter_username = $stmt->fetchColumn();
            
            // δημιουργούμε δυναμικό μήνυμα με το όνομα του commenter
            $fallbackMessage = $commenter_username . " commented on your post";

            // δημιουργεί εγγραφή ειδοποίησης στο database για τον ιδιοκτήτη του post
            $notificationModel->createLocalizedNotification(
                (int)$post['user_id'], // receiver (owner)
                'comment',             // type
                (int)$data['post_id'], // post reference
                'notifications.comment',
                ["user" => (string)$commenter_username],
                $fallbackMessage
            );
        }
        
        // Επιστροφή επιτυχούς απάντησης
        $this->jsonResponse(["message" => "Comment added"]);
    }

    // List comments
    public function list(){
        if(!isset($_GET['post_id'])){
            $this->jsonResponse([]);
        }

        $userId = $this->getCurrentUserId();

        $comments = $this->commentModel->getCommentsByPost($_GET['post_id'], $userId);

        foreach ($comments as &$comment) {
            $comment['has_requested_delete'] = (bool)($comment['has_requested_delete'] ?? false);
        }

        $this->jsonResponse($comments);
    }

    // Request comment deletion
    public function requestDelete(){
        $user_id = $this->requireLogin();
        $data = $this->getJSONInput();

        if(!$data || !isset($data['comment_id']) || !isset($data['reason'])){
            $this->jsonResponse(["message" => "Invalid request"], 400);
        }

        $comment_id = $data['comment_id'];
        $reason = trim($data['reason']);

        if ($reason === "") {
            $this->jsonResponse(["message" => "Reason is required"], 400);
        }

        // Έλεγχος αν υπάρχει ήδη request
        $exists = $this->commentModel->deleteRequestExists($comment_id, $user_id);
        if($exists){
            $this->jsonResponse(["message" => "Request already exists"], 409);
        }

        $this->commentModel->requestDelete(
            $comment_id,
            $user_id,
            $reason
        );

        $actorName = trim((string)($_SESSION['username'] ?? 'A user'));
        $notificationModel = new NotificationModel();
        $notificationModel->notifyAdminsLocalized(
            'admin_comment_delete_request',
            (int)$comment_id,
            'notifications.admin_comment_delete_request',
            ["actor" => $actorName],
            $actorName . ' submitted a comment delete request.'
        );

        $this->jsonResponse(["message" => "Request submitted"]);
    }

    public function adminDelete(){
        $this->requireAdmin();

        $data = $this->getJSONInput();
        $comment_id = $data['comment_id'] ?? ($_GET['id'] ?? null);

        if (!$comment_id) {
            $this->jsonResponse(["message" => "Comment ID required"], 400);
        }

        $deleted = $this->commentModel->adminDeleteComment($comment_id);

        if (!$deleted) {
            $this->jsonResponse(["message" => "Comment not found"], 404);
        }

        $this->jsonResponse(["message" => "Comment deleted"]);
    }
}

// Βασικός router για το CommentController
if(isset($_GET['action'])){

    $controller = new CommentController();

    switch($_GET['action']){

        case 'create':  // Create comment
            $controller->create();
            break;

        case 'list':    // List comments for a post
            $controller->list();
            break;
        case 'requestDelete':   // Request comment deletion
            $controller->requestDelete();
            break;
        case 'adminDelete':
            $controller->adminDelete();
            break;

    }

}