<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/config/db.php';
require_once dirname(__DIR__) . '/backend/modules/search.php';

$categories = [];
$loadError = null;

try {
    $search = new Search($pdo);
    $categories = $search->getCategories();
} catch (Throwable $exception) {
    $loadError = 'Unable to load search filters from the database.';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Posts</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/search.css">
</head>
<body>
  <div class="container">
    <h2>Search Posts</h2>

    <?php if ($loadError !== null): ?>
      <p><?= htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="filters">
      <input type="text" id="keyword" placeholder="Search by keyword">

      <select id="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= (int) $category['category_id'] ?>">
            <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select id="sort">
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
        <option value="title_asc">Title A-Z</option>
        <option value="title_desc">Title Z-A</option>
      </select>

      <input type="number" id="followedByUserId" min="1" placeholder="Current User ID for follows">
      <label>
        <input type="checkbox" id="followedOnly">
        Followed Users Only
      </label>

      <input type="date" id="from">
      <input type="date" id="to">

      <button id="searchBtn">Search</button>
    </div>

    <div id="results"></div>
  </div>

  <script src="js/search.js"></script>
</body>
</html>
