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

        if(!isset($_GET['post_id'])){
            echo json_encode([]);
            return;
        }

        $comments = $this->commentModel->getCommentsByPost($_GET['post_id']);

        echo json_encode($comments);
    }

}

if(isset($_GET['action'])){

    $controller = new CommentController();

    switch($_GET['action']){

        case 'create':
            $controller->create();
            break;

        case 'list':
            $controller->list();
            break;

    }

}