<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System B Content Search</title>
    <style>
        :root {
            --bg: #f6f1e8;
            --panel: #fffaf2;
            --ink: #1d1b18;
            --muted: #6b655c;
            --line: #d7cabb;
            --accent: #1d6b57;
            --accent-soft: #d8efe7;
            --danger: #8f2d2d;
            --danger-soft: #f9dede;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, #efe3cf 0, transparent 28%),
                linear-gradient(135deg, #f7f0e3 0%, #efe2cf 100%);
            min-height: 100vh;
        }

        .wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        .hero {
            margin-bottom: 24px;
        }

        h1, h2 {
            margin: 0 0 12px;
            font-weight: 600;
        }

        p {
            line-height: 1.5;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: rgba(255, 250, 242, 0.95);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 18px 40px rgba(73, 54, 28, 0.08);
        }

        label {
            display: block;
            margin-bottom: 14px;
            font-size: 0.95rem;
        }

        input {
            width: 100%;
            margin-top: 6px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fffdf9;
            color: var(--ink);
            font: inherit;
        }

        button {
            border: 0;
            border-radius: 999px;
            padding: 11px 18px;
            background: var(--accent);
            color: #fff;
            font: inherit;
            cursor: pointer;
        }

        .message {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
        }

        .message.success {
            background: var(--accent-soft);
            color: #123e33;
        }

        .message.error {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .list {
            margin: 12px 0 0;
            padding-left: 18px;
        }

        .muted {
            color: var(--muted);
        }

        code {
            background: #f1e6d6;
            border-radius: 6px;
            padding: 2px 6px;
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="hero">
            <h1>Content And Search Filtering</h1>
            <p>
                This branch is scoped only to the System B content search/filtering feature.
                Use the search page to filter posts by keyword, category, date, sorting,
                and followed users.
            </p>
        </section>

        <section class="grid">
            <article class="card">
                <h2>Open Search</h2>
                <p>
                    Go to the dedicated search page to query and filter content from the existing
                    <code>posts</code> data in <code>university_web</code>.
                </p>
                <p>
                    <a href="search.php">Open Search Page</a>
                </p>
            </article>

            <article class="card">
                <h2>Supported Filters</h2>
                <ul class="list">
                    <li>Keyword in title or content</li>
                    <li>Category</li>
                    <li>Date range</li>
                    <li>Sorting</li>
                    <li>Followed users only</li>
                </ul>
                <p class="muted">
                    Database connection is configured through <code>backend/config/db.php</code>.
                </p>
            </article>
        </section>
    </main>
</body>
</html>
