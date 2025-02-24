<?php

// CMS_USER=user CMS_PASS=pass php -S localhost:9123 ./index.php

// SQLite database file
$dbFile = __DIR__ . '/cms.db';

// Initialize database (create tables on the first run)
initDatabase($dbFile);

// Connect to SQLite database
$db = new SQLite3($dbFile);

// Routing based on `action` parameter
$action = $_GET['action'] ?? 'view';

switch ($action) {
    case 'view':
        showPage($db);
        break;
    case 'add':
        authenticateUser();
        handleAddPage($db);
        break;
    default:
        showNotFound();
        break;
}

// Close database connection
$db->close();

/**
 * Initializes the SQLite database.
 * Creates the `pages` table if it does not exist.
 */
function initDatabase($dbFile)
{
    if (!file_exists($dbFile)) {
        $db = new SQLite3($dbFile);
        $db->exec('CREATE TABLE pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            content TEXT NOT NULL
        )');

        // Insert initial pages
        $db->exec("INSERT INTO pages (slug, title, content) VALUES 
            ('home', 'Home', '<h1>Welcome to the CMS!</h1><p>This is a simple SQLite-based CMS.</p>'),
            ('about', 'About', '<h1>About This Site</h1><p>This is a demo for a basic CMS.</p>')");

        $db->close();
    }
}

/**
 * Displays a page based on the `slug` parameter.
 */
function showPage($db)
{
    $slug = $_GET['slug'] ?? 'home';

    $stmt = $db->prepare('SELECT title, content FROM pages WHERE slug = :slug');
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        header('Content-Type: text/html; charset=UTF-8');
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</title>
</head>
<body>
    " . $row['content'] . "
    <nav>";
        foreach(listPages($db) as $page) {
            echo "<a href='?action=view&slug=" . urlencode($page["slug"]) . "'>" . htmlspecialchars($page["title"], ENT_QUOTES, 'UTF-8') . "</a> | ";
        }
        echo "<a href='?action=add'>Add Page</a>
    </nav>
</body>
</html>";
    } else {
        showNotFound();
    }
}

function listPages($db)
{
    $stmt = $db->prepare('SELECT title, slug FROM pages');
    $result = $stmt->execute();
    $rows = [];
     while ($row = $result->fetchArray()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Handles adding a new page (only accessible after authentication).
 */
function handleAddPage($db)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $slug = $_POST['slug'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        // Validate input (empty check and HTML escape)
        if (empty($slug) || empty($title) || empty($content)) {
            echo "<p>Please fill in all fields.</p>";
        } else {
            $stmt = $db->prepare('INSERT INTO pages (slug, title, content) VALUES (:slug, :title, :content)');
            $stmt->bindValue(':slug', htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'), SQLITE3_TEXT);
            $stmt->bindValue(':title', htmlspecialchars($title, ENT_QUOTES, 'UTF-8'), SQLITE3_TEXT);
            $stmt->bindValue(':content', htmlspecialchars($content, ENT_QUOTES, 'UTF-8'), SQLITE3_TEXT);

            if ($stmt->execute()) {
                echo "<p>Page \"" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "\" has been added.</p>";
                echo "<p><a href='?action=view&slug=" . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . "'>View Page</a></p>";
            } else {
                echo "<p>Failed to add the page.</p>";
            }
        }
    }

    // Display input form
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Add Page</title>
</head>
<body>
    <h1>Add a New Page</h1>
    <form method='POST'>
        <label>Slug (URL identifier): <input type='text' name='slug' required></label><br>
        <label>Title: <input type='text' name='title' required></label><br>
        <label>Content: <textarea name='content' required></textarea></label><br>
        <button type='submit'>Add Page</button>
    </form>
    <p><a href='?action=view&slug=home'>Back to Home</a></p>
</body>
</html>";
}

/**
 * Implements BASIC authentication for adding new pages.
 */
function authenticateUser()
{
    $validUser = getenv('CMS_USER');
    $validPass = getenv('CMS_PASS');

    if (!$validUser || !$validPass) {
        die("Please set the 'CMS_USER' and 'CMS_PASS' environment variables.");
    }

    if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_USER'] !== $validUser ||
        $_SERVER['PHP_AUTH_PW'] !== $validPass) {
        
        header('WWW-Authenticate: Basic realm="Restricted Area"');
        header('HTTP/1.0 401 Unauthorized');
        die("Authentication required.");
    }
}

/**
 * Displays a 404 Not Found page.
 */
function showNotFound()
{
    header("HTTP/1.1 404 Not Found");
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Page Not Found</title>
</head>
<body>
    <h1>404 Not Found</h1>
    <p>The requested page does not exist.</p>
    <p><a href='?action=view&slug=home'>Back to Home</a></p>
</body>
</html>";
}
