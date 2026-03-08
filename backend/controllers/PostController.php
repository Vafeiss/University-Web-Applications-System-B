<?php

// Return JSON responses
header("Content-Type: application/json");

require_once __DIR__ . '/../modules/PostModel.php';

/*
 Controller responsible for handling Post operations
 such as creating, listing and deleting posts
*/
class PostController {

    private PostModel $postModel;

    public function __construct() {
        $this->postModel = new PostModel();
    }

    // Create_Post() from class diagram
    public function create() {

        session_start();

        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                "message" => "User not logged in"
            ]);
            return;
        }

        // Get JSON request body
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate request data
        if (!$data || !isset($data['title']) || !isset($data['content'])) {
            echo json_encode([
                "message" => "Invalid post data"
            ]);
            return;
        }

        $categoryId = $data['category_id'] ?? null;
        if ($categoryId === "") {
            $categoryId = null;
        }

        // Insert post in database (status = pending)
        try {
            $this->postModel->createPost(
                $_SESSION['user_id'],
                $data['title'],
                $data['content'],
                $categoryId
            );

            echo json_encode([
                "message" => "Post submitted for review"
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "message" => "Could not create post"
            ]);
        }
    }

    // Show_Post()
    public function list() {

        $posts = $this->postModel->getApprovedPosts();

        echo json_encode($posts);
    }

    // Delete_Post()
    public function delete($post_id) {

        session_start();

        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                "message" => "User not logged in"
            ]);
            return;
        }

        $this->postModel->deletePost(
            $post_id,
            $_SESSION['user_id']
        );

        echo json_encode([
            "message" => "Post deleted"
        ]);
    }
}

/*
 Simple router
 Determines which controller action to execute
*/
if (isset($_GET['action'])) {

    $controller = new PostController();

    switch ($_GET['action']) {

        case 'create':
            $controller->create();
            break;

        case 'list':
            $controller->list();
            break;

        case 'delete':
            $controller->delete($_GET['id']);
            break;
    }
}