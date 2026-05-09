<?php
/**
 * File: create_post.php
 * Layer: Frontend Page
 * Module: Create Post
 * System: University Web Applications System B
 *
 * Description:
 * Post creation interface with title, content, optional category, and file attachments.
 * Supports anonymous posting, up to 5 file attachments, and post policy acknowledgment.
 *
 * Features:
 * - Post title and content input (rich text)
 * - Category selection dropdown
 * - Anonymous post checkbox
 * - File attachment management (max 5 files)
 * - File preview before submission
 * - Post policy dialog/acknowledgment
 * - Form validation and error display
 * - Post submission with AJAX
 *
 * Security:
 * - requireLogin() enforces authentication
 * - requireCompleteProfile() enforces profile setup
 * - Ban check via BanGuard middleware
 * - CSRF protection via form tokens
 * - File upload validation on backend
 *
 * Used By:
 * - Navigation link from posts.php
 * - Users creating new posts
 *
 * Author: Pelagia Koniotaki
 * Date: 2026
 */

session_start();
require_once __DIR__ . "/backend/middleware/BanGuard.php";
enforceFrontendUserNotBanned();

require_once __DIR__ . "/backend/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$categoriesStmt = $conn->query(
    "SELECT MIN(category_id) AS category_id, name
     FROM categories
     GROUP BY name
     ORDER BY name ASC"
);
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
$createPostJsVersion = filemtime(__DIR__ . "/js/createPost.js");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Post</title>
    <link rel="stylesheet" href="css/post.css">
</head>

<body>

<div class="post-container">

    <a href="posts.php" class="back-link">&larr; Back to posts</a>

    <h2>Create New Post</h2>

        <!-- input για attachments -->
        <form id="postForm" enctype="multipart/form-data">

        <input 
        type="text" 
        name="title" 
        placeholder="Post title"
        required
        >

        <textarea 
        name="content"
        placeholder="Write your content..."
        required
        ></textarea>

        <label for="categoryTrigger">Category</label>
        <div class="post-category-field">
            <div class="post-category-dropdown" id="categoryDropdown">
                <button type="button" class="post-category-trigger" id="categoryTrigger" aria-haspopup="true" aria-expanded="false">
                    <span class="post-category-label" id="categoryLabel">Select Category</span>
                </button>
                <div class="post-category-menu" id="categoryMenu">
                    <div class="post-category-options">
                        <?php foreach ($categories as $category): ?>
                            <?php $catId = (int)$category['category_id']; ?>
                            <label class="post-category-option" for="postCat<?= $catId ?>">
                                <input type="radio" class="post-category-radio" name="category_id" value="<?= $catId ?>" id="postCat<?= $catId ?>" required>
                                <span><?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="anonymous-setting">
            <div class="anonymous-setting-text">
                <span class="anonymous-setting-title">Publish anonymously</span>
                <small class="anonymous-setting-hint">Your name will be hidden for users. Admins can still view the post owner.</small>
            </div>

            <label class="anonymous-switch" for="anonymousToggle">
                <input type="checkbox" id="anonymousToggle" name="is_anonymous" value="1">
                <span class="anonymous-slider" aria-hidden="true"></span>
            </label>
        </div>

        <!-- Attachments -->
        <div class="attachments-upload">
            <div class="attachments-head">
                <div class="attachments-head-text">
                    <span class="attachments-title">Attachments</span>
                    <span class="attachments-hint">At least 1 file required, up to 5 files (jpg, png, pdf, doc, docx, txt, zip)</span>
                </div>

                <label for="attachmentsInput" class="attachments-choose-btn">Choose Files</label>
            </div>

            <input 
            type="file"
            id="attachmentsInput"
            class="attachments-native-input"
            name="attachments[]"
            multiple
            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip"
            >

            <small id="selectedFiles" class="selected-files"></small>
        </div>

        <button type="submit">
        Publish
        </button>
        </form>

        <p id="response" class="response-message" aria-live="polite"></p>

        <div id="postPolicyDialog" class="comment-policy-dialog" hidden>
            <div class="comment-policy-card" role="dialog" aria-modal="true" aria-labelledby="postPolicyTitle">
                <h4 id="postPolicyTitle">Confirm Publication</h4>
                <p>After publishing, this post cannot be deleted directly and requires a delete request.</p>
                <div class="comment-policy-actions">
                    <button type="button" id="postPolicyCancel" class="policy-link cancel">Cancel</button>
                    <button type="button" id="postPolicyAccept" class="policy-link accept">Accept</button>
                </div>
            </div>
        </div>

        </div>

        <script src="js/createPost.js?v=<?php echo $createPostJsVersion; ?>"></script>

</body>
</html>
