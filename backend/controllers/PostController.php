<?php

// Return JSON responses
header("Content-Type: application/json");

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../modules/PostModel.php';

/*
 Controller responsible for handling Post operations
 such as creating, listing and deleting posts
*/
class PostController extends BaseController {

    private PostModel $postModel;

    public function __construct() {
        $this->postModel = new PostModel();
    }

    // Create_Post()
    public function create() {
        $user_id = $this->requireLogin();

        if (!isset($_POST['title']) || !isset($_POST['content'])) {
            $this->jsonResponse([
                "message" => "Invalid post data"
            ], 400);
        }

        $title = $_POST['title'];
        $content = $_POST['content'];
        $categoryId = $_POST['category_id'] ?? null;
        $isAnonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1' ? 1 : 0;

        if ($categoryId === "") {
            $categoryId = null;
        }

        try {

            // Δημιουργία post
            $post_id = $this->postModel->createPost(
                $user_id,
                $title,
                $content,
                $categoryId,
                $isAnonymous
            );

            // Upload attachments
            if (!empty($_FILES['attachments']['name'][0])) {

                $uploadDir = __DIR__ . '/../../frontend/uploads/';
                $totalFiles = count($_FILES['attachments']['name']);

                if ($totalFiles > 5) {
                    $this->jsonResponse(["message" => "Maximum 5 files allowed"], 400);
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

                    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip'];

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

            $this->jsonResponse([
                "message" => "Post submitted for review"
            ]);

        } catch (Throwable $e) {
            $this->jsonResponse([
                "message" => "Could not create post"
            ], 500);
        }
    }
    // Show_Post()
    public function list() {

    $isAdmin = $this->isAdmin();

    // παίρνουμε τον τρέχοντα user
    $currentUserId = $this->getCurrentUserId();

    // αν υπάρχει user → personalized feed
    if ($currentUserId) {

        $posts = $this->postModel->getPostsForUser($currentUserId);

    } else {

        // αν δεν είναι logged in → όλα τα posts
        $posts = $this->postModel->getApprovedPosts();
    }


    // διαχείριση anonymous posts
    if (!$isAdmin) {
        foreach ($posts as &$post) {

            if (!empty($post['is_anonymous'])) {
                $post['username'] = 'Anonymous';
            }

        }
        unset($post);
    }

    $this->jsonResponse($posts);
}

    public function adminList() {
        $this->requireAdmin();

        $posts = $this->postModel->getAdminPosts();

        $this->jsonResponse($posts);
    }

    // Delete_Post()
    public function delete($post_id) {
        $user_id = $this->requireLogin();

        $this->postModel->deletePost(
            $post_id,
            $user_id
        );

        $this->jsonResponse([
            "message" => "Post deleted"
        ]);
    }

    public function adminDelete() {
        $this->requireAdmin();

        $input = $this->getJSONInput();
        $post_id = $input['post_id'] ?? ($_GET['id'] ?? null);

        if (!$post_id) {
            $this->jsonResponse(["message" => "Post ID required"], 400);
        }

        $deleted = $this->postModel->adminDeletePost($post_id);

        if (!$deleted) {
            $this->jsonResponse(["message" => "Post not found or already deleted"], 404);
        }

        $this->jsonResponse([
            "message" => "Post deleted"
        ]);
    }

    // Get_Post()
    public function get() {
        // Επιστρέφει ένα post με βάση το ID, μαζί με τα attachments του
        if (!isset($_GET['id'])) {
            $this->jsonResponse(["message" => "Post not found"], 404);
        }
        // φορτώνει ένα post μαζί με το username του δημιουργού και την κατηγορία
        $post_id = $_GET['id'];
        // φέρνουμε το post
        $post = $this->postModel->getPostById($post_id);

        if (!$post) {
            $this->jsonResponse(["message" => "Post not found"], 404);
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

        $currentUserId = $this->getCurrentUserId();
        $isAdmin = $this->isAdmin();

        if (!$isAdmin && !empty($post['is_anonymous'])) {
            $post['username'] = 'Anonymous';
        }

        $post['has_requested_delete'] = $currentUserId
            ? $this->postModel->postDeleteRequestExists($post_id, $currentUserId)
            : false;
        $post['has_reported'] = $currentUserId
            ? $this->postModel->postReportExists($post_id, $currentUserId)
            : false;

        // προσθέτουμε τα attachments στο post object
        $post['attachments'] = $attachments;
        // επιστρέφουμε το post με τα attachments
        $this->jsonResponse($post);
    }

    // Request post deletion
    public function requestDelete() {
        $user_id = $this->requireLogin();
        $data = $this->getJSONInput();

        if (!$data || !isset($data['post_id']) || !isset($data['reason'])) {
            $this->jsonResponse(["message" => "Invalid request"], 400);
        }

        $post_id = $data['post_id'];
        $reason = trim($data['reason']);

        if ($reason === '') {
            $this->jsonResponse(["message" => "Reason is required"], 400);
        }

        // Έλεγχος αν το post ανήκει στον user
        $post = $this->postModel->getPostById($post_id);

        if (!$post || $post['user_id'] != $user_id) {
            $this->jsonResponse(["message" => "You can only request deletion for your own post"], 403);
        }

        // Έλεγχος αν υπάρχει ήδη request
        if ($this->postModel->postDeleteRequestExists($post_id, $user_id)) {
            $this->jsonResponse(["message" => "You already requested deletion for this post"], 409);
        }

        $this->postModel->createPostDeleteRequest($post_id, $user_id, $reason);

        $this->jsonResponse(["message" => "Post delete request submitted"]);
    }

    public function requestReport() {
        $user_id = $this->requireLogin();
        $data = $this->getJSONInput();

        if (!$data || !isset($data['post_id']) || !isset($data['reason'])) {
            $this->jsonResponse(["message" => "Invalid request"], 400);
        }

        $post_id = $data['post_id'];
        $reason = trim($data['reason']);

        if ($reason === '') {
            $this->jsonResponse(["message" => "Reason is required"], 400);
        }

        $post = $this->postModel->getPostById($post_id);
        if (!$post) {
            $this->jsonResponse(["message" => "Post not found"], 404);
        }

        if ($post['user_id'] == $user_id) {
            $this->jsonResponse(["message" => "You cannot report your own post"], 403);
        }

        if ($this->postModel->postReportExists($post_id, $user_id)) {
            $this->jsonResponse(["message" => "You already reported this post"], 409);
        }

        $this->postModel->createPostReport($post_id, $user_id, $reason);

        $this->jsonResponse(["message" => "Post report submitted"]);
    }
    public function pending() {
        // χρηση base controller για να ελέγξει αν ο χρήστης είναι admin
        $this->requireAdmin();

        $posts = $this->postModel->getPendingPosts();

        $this->jsonResponse($posts);
    }
    // approve post by admin
    public function approve() {
        // Έλεγχος αν ο χρήστης είναι admin
        $this->requireAdmin();
        // Έλεγχος αν υπάρχει id
        if (!isset($_GET['id'])) {
            $this->jsonResponse(["message" => "Post ID required"], 400);
        }
        // Έλεγχος αν υπάρχει το post
        $post_id = $_GET['id'] ?? null;
        
        if (!$post_id) {
            $this->jsonResponse(["message" => "Post ID required"], 400);
    }

        $this->postModel->approvePost($post_id);

        $this->jsonResponse([
            "message" => "Post approved"
        ]);
    }

    // reject post by admin
    public function reject() {

        $this->requireAdmin();

        if (!isset($_GET['id'])) {
            $this->jsonResponse(["message" => "Post ID required"], 400);
        }

        $post_id = $_GET['id'] ?? null;

        if (!$post_id) {
            $this->jsonResponse(["message" => "Post ID required"], 400);
        }

        $this->postModel->rejectPost($post_id);

        $this->jsonResponse([
            "message" => "Post rejected"
        ]);
    }
    // get delete requests for admin
    public function deleteRequests() {

        $this->requireAdmin();

        $requests = $this->postModel->getDeleteRequests();

        $this->jsonResponse($requests);
    }   // επιστρεφει json με ολα τα pending delete requests για τα posts
    
    // approve delete request,  καλείται οταν admin πατησει approve σε ένα delete request, και διαγράφει το post
    public function approveDelete() {

        $this->requireAdmin();
        // Έλεγχος αν υπάρχει id
        $request_id = $_GET['id'] ?? null;

        if (!$request_id) {
            $this->jsonResponse(["message" => "Request ID required"], 400);
        }
        // Έλεγχος αν υπάρχει το request
        $this->postModel->approveDeleteRequest($request_id);

        $this->jsonResponse([
            "message" => "Delete request approved"
        ]);
    }
    public function rejectDelete() {

        $this->requireAdmin();

        $request_id = $_GET['id'] ?? null;

        if (!$request_id) {
            $this->jsonResponse(["message" => "Request ID required"], 400);
        }

        $this->postModel->rejectDeleteRequest($request_id);

        $this->jsonResponse([
            "message" => "Delete request rejected"
        ]);
    }
    // επιστρέφει όλα τα reports που έχουν γίνει για posts, μαζί με τα στοιχεία του post και του χρήστη που έκανε το report
    public function reports(){

        $this->requireAdmin();

        $reports = $this->postModel->getReportedContent();

        $this->jsonResponse($reports);
    }
    // approve report, καλείται όταν admin πατήσει approve σε ένα report, και διαγράφει το post
    public function approveReport(){

        $this->requireAdmin();

        $report_id = $_GET['id'] ?? null;

        if(!$report_id){
        $this->jsonResponse(["message"=>"Report ID required"],400);
        }

        $this->postModel->approveReport($report_id);

        $this->jsonResponse([
            "message"=>"Post removed"
        ]);
    }
        public function rejectReport(){

        $this->requireAdmin();

        $report_id = $_GET['id'] ?? null;

        if(!$report_id){
            $this->jsonResponse(["message"=>"Report ID required"],400);
        }

        $this->postModel->rejectReport($report_id);

        $this->jsonResponse([
            "message"=>"Report rejected"
        ]);
    }
    // επιστρέφει όλα τα comment delete requests που έχουν γίνει, μαζί με τα στοιχεία του comment και του χρήστη που έκανε το request
    public function commentDeleteRequests(){

    $this->requireAdmin();

    $requests = $this->postModel->getCommentDeleteRequests();

    $this->jsonResponse($requests);
    }
    // approve comment delete, καλείται όταν admin πατήσει approve σε ένα comment delete request, και διαγράφει το comment
    public function approveCommentDelete(){

    $this->requireAdmin();

    $request_id = $_GET['id'] ?? null;

    if(!$request_id){
        $this->jsonResponse(["message"=>"Request ID required"],400);
    }

    $this->postModel->approveCommentDelete($request_id);

    $this->jsonResponse([
        "message"=>"Comment deleted"
    ]);
}
// reject comment delete, καλείται όταν admin πατήσει reject σε ένα comment delete request, και απορρίπτει το request χωρίς να διαγράψει το comment
public function rejectCommentDelete(){

    $this->requireAdmin();

    $request_id = $_GET['id'] ?? null;

    if(!$request_id){
        $this->jsonResponse(["message"=>"Request ID required"],400);
    }

    $this->postModel->rejectCommentDelete($request_id);

    $this->jsonResponse([
        "message"=>"Delete request rejected"
    ]);
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

        case 'adminList':
            $controller->adminList();
            break;

        case 'delete':
            $controller->delete($_GET['id'] ?? null);
            break;
        case 'adminDelete':
            $controller->adminDelete();
            break;
        case 'get':  // Get_Post()
            $controller->get();
            break;
        case 'requestDelete':
            $controller->requestDelete();
            break;
        case 'requestReport':
            $controller->requestReport();
            break;
        case 'pending':
        $controller->pending();
        break;
        case 'approve':
            $controller->approve();
            break;
        case 'reject':
            $controller->reject();
            break;
        case 'deleteRequests':  // Get delete requests for admin
            $controller->deleteRequests();
            break;

        case 'approveDelete':   // Approve delete request
            $controller->approveDelete();
            break;

        case 'rejectDelete': // Reject delete request
            $controller->rejectDelete();
            break;
        case 'reports':
            $controller->reports();
            break;

        case 'approveReport':
            $controller->approveReport();
            break;

        case 'rejectReport':
            $controller->rejectReport();
            break;
        
        case 'commentDeleteRequests':
            $controller->commentDeleteRequests();
            break;

        case 'approveCommentDelete':
            $controller->approveCommentDelete();
            break;

        case 'rejectCommentDelete':
            $controller->rejectCommentDelete();
            break;
    }
}