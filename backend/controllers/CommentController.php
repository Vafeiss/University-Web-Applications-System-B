<?php

header("Content-Type: application/json");

require_once __DIR__ . '/../modules/CommentModel.php';

class CommentController {

    private CommentModel $commentModel;

    public function __construct(){
        $this->commentModel = new CommentModel();
    }

    // Create comment
    public function create(){

        session_start();

        if(!isset($_SESSION['user_id'])){
            echo json_encode(["message" => "User not logged in"]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if(!$data || !isset($data['post_id']) || !isset($data['content'])){
            echo json_encode(["message" => "Invalid data"]);
            return;
        }

        $this->commentModel->createComment(
            $_SESSION['user_id'],
            $data['post_id'],
            $data['content']
        );

        echo json_encode(["message" => "Comment added"]);
    }

    // List comments
    public function list(){

        session_start();

        if(!isset($_GET['post_id'])){
            echo json_encode([]);
            return;
        }

        $userId = $_SESSION['user_id'] ?? null;

        $comments = $this->commentModel->getCommentsByPost($_GET['post_id'], $userId);

        foreach ($comments as &$comment) {
            $comment['has_requested_delete'] = (bool)($comment['has_requested_delete'] ?? false);
        }

        echo json_encode($comments);
    }
    // Request comment deletion
    public function requestDelete(){

    session_start();

    if(!isset($_SESSION['user_id'])){
        echo json_encode(["message"=>"Not logged in"]);
        return;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if(!$data || !isset($data['comment_id']) || !isset($data['reason'])){
        http_response_code(400);
        echo json_encode(["message"=>"Invalid request"]);
        return;
    }

    $comment_id = $data['comment_id'];
    $reason = trim($data['reason']);
    $user_id = $_SESSION['user_id'];

    if ($reason === "") {
        http_response_code(400);
        echo json_encode(["message"=>"Reason is required"]);
        return;
    }

    // Έλεγχος αν υπάρχει ήδη request
    $exists = $this->commentModel->deleteRequestExists($comment_id,$user_id);
    if($exists){
        http_response_code(409);
        echo json_encode(["message"=>"Request already exists"]);
        return;
    }

    $this->commentModel->requestDelete(
        $comment_id,
        $user_id,
        $reason
    );

    echo json_encode(["message"=>"Request submitted"]);
}
}


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

    }

}