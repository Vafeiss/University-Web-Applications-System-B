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

    // Create_Post()
public function create() {

    session_start();

    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            "message" => "User not logged in"
        ]);
        return;
    }

    if (!isset($_POST['title']) || !isset($_POST['content'])) {
        echo json_encode([
            "message" => "Invalid post data"
        ]);
        return;
    }

    $title = $_POST['title'];
    $content = $_POST['content'];
    $categoryId = $_POST['category_id'] ?? null;

    if ($categoryId === "") {
        $categoryId = null;
    }

    try {

        // Δημιουργία post
        $post_id = $this->postModel->createPost(
            $_SESSION['user_id'],
            $title,
            $content,
            $categoryId
        );

        // Upload attachments
        if (!empty($_FILES['attachments']['name'][0])) {

            $uploadDir = __DIR__ . '/../../frontend/uploads/';
            $totalFiles = count($_FILES['attachments']['name']);

            if ($totalFiles > 5) {
                echo json_encode(["message" => "Maximum 5 files allowed"]);
                return;
            }

            for ($i = 0; $i < $totalFiles; $i++) {

            // Αν δεν υπάρχει πραγματικό αρχείο, το αγνοούμε
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileName = $_FILES['attachments']['name'][$i];
            $fileTmp = $_FILES['attachments']['tmp_name'][$i];
            $fileSize = $_FILES['attachments']['size'][$i];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowed = ['jpg','jpeg','png','pdf','doc','docx','txt','zip'];

            if (!in_array($fileType, $allowed)) {
                continue;
            }

            $newName = time() . "_" . uniqid() . "_" . $fileName;
            $filePath = $uploadDir . $newName;

            if (move_uploaded_file($fileTmp, $filePath)) {

                $this->postModel->saveAttachment(
                    $post_id,
                    $fileName,
                    "uploads/" . $newName,
                    $fileSize,
                    $fileType
                );
                }
            }
        }

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
    // Get_Post()
    public function get() {
        // Επιστρέφει ένα post με βάση το ID, μαζί με τα attachments του
        if (!isset($_GET['id'])) {
            echo json_encode(["message" => "Post not found"]);
            return;
        }
        // φορτώνει ένα post μαζί με το username του δημιουργού και την κατηγορία
        $post_id = $_GET['id'];
        // φέρνουμε το post
        $post = $this->postModel->getPostById($post_id);

        if (!$post) {
            http_response_code(404);
            echo json_encode(["message" => "Post not found"]);
            return;
        }

        // Φέρνουμε attachments
        $attachments = $this->postModel->getAttachmentsByPost($post_id);

        $basePath = '/University-Web-Applications-System-B/frontend/';
        foreach ($attachments as &$attachment) {
            $rawPath = $attachment['file_path'] ?? '';
            if ($rawPath === '') {
                $rawPath = 'uploads/' . ($attachment['file_name'] ?? '');
            }

            $attachment['file_url'] = str_starts_with($rawPath, 'http')
                ? $rawPath
                : $basePath . ltrim($rawPath, '/');
        }
        unset($attachment);

        // προσθέτουμε τα attachments στο post object
        $post['attachments'] = $attachments;
        // επιστρέφουμε το post με τα attachments
        echo json_encode($post);
    }
}

// Βασικός router για το PostController
if (isset($_GET['action'])) {
    // Δημιουργία instance του controller
    $controller = new PostController();

    switch ($_GET['action']) {

        case 'create':  // Create_Post()
            $controller->create();
            break;

        case 'list':    // Show_Post()
            $controller->list();
            break;

        case 'delete':  // Delete_Post()
            $controller->delete($_GET['id']);
            break;
        case 'get':  // Get_Post()
            $controller->get();
            break;
    }
}