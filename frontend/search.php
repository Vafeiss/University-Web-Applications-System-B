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

    <div class="filters">
      <input type="text" id="keyword" placeholder="Search...">

      <select id="category">
        <option value="">All Categories</option>
        <option value="1">Programming</option>
        <option value="2">Database</option>
      </select>

      <input type="date" id="from">
      <input type="date" id="to">

      <button id="searchBtn">Search</button>
    </div>

    <div id="results"></div>
  </div>

  <script src="js/search.js"></script>
</body>
</html>