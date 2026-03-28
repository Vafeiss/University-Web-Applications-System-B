<?php
session_start();
require_once "../backend/middleware/BanGuard.php";
enforceFrontendUserNotBanned();

require_once "../backend/config/database.php";

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

        <label>Category</label>
        <select name="category_id" required>

        <option value="">Select Category</option>
        <?php foreach ($categories as $category): ?>
        <option value="<?= (int)$category['category_id'] ?>"><?= htmlspecialchars((string)$category['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>

        </select>

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
