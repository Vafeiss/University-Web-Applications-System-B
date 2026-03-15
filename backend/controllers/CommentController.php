<?php

header("Content-Type: application/json");
// import necessary files
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../modules/CommentModel.php';
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