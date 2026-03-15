<?php
session_start();    

/* Έλεγχος αν ο χρήστης είναι συνδεδεμένος */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
/* Έλεγχος αν ο χρήστης είναι admin  για εμφανιση anonymous posts */
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

$postCssVersion = filemtime(__DIR__ . '/css/post.css');
$adminCssVersion = filemtime(__DIR__ . '/css/admin_pending_posts.css');
$postsJsVersion = filemtime(__DIR__ . '/js/posts.js');
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">  <!-- Ορίζει το charset σε UTF-8 για σωστή εμφάνιση χαρακτήρων -->
<meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Κάνει τη σελίδα responsive σε κινητές συσκευές -->
<title>Posts Feed</title>   
<link rel="stylesheet" href="css/post.css?v=<?php echo $postCssVersion; ?>">
<link rel="stylesheet" href="css/admin_pending_posts.css?v=<?php echo $adminCssVersion; ?>">
</head>

<body>

<main class="pending-page feed-shell">

    <header class="feed-header">
        <div class="feed-header-row">
            <h1>Posts Feed</h1>

            <details class="feed-menu" id="feedMenu">
                <summary class="feed-menu-trigger" aria-label="Open feed menu" title="Menu">&#8942;</summary>

                <div class="feed-menu-dropdown" role="menu" aria-label="Feed quick actions">
                    <?php if ($isAdmin): ?>
                    <a href="admin_dashboard.php" class="feed-menu-item" role="menuitem">Admin panel</a>
                    <?php endif; ?>

                    <button type="button" class="feed-menu-item" role="menuitem" data-coming-soon="Edit profile setup">Edit profile setup</button>
                    <button type="button" class="feed-menu-item" role="menuitem" data-coming-soon="Edit interests">Edit interests</button>
                    <a href="logout.php" class="feed-menu-item danger" role="menuitem">Logout</a>
                </div>
            </details>
        </div>

        <nav class="feed-tabs" aria-label="Feed navigation">
            <a href="create_post.php" class="feed-tab primary">&#43; Create Post</a>
            <?php if ($isAdmin): ?>
            <a href="admin_dashboard.php" class="feed-tab">&#9881; Admin Panel</a>
            <?php endif; ?>
        </nav>

        <div id="interestsBanner"></div>
    </header>

    <div id="postsList" class="pending-grid" aria-live="polite"></div>

</main>

<script>
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
</script>
<script src="js/posts.js?v=<?php echo $postsJsVersion; ?>"></script>

</body>

</html>