<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();

if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'];

$sql = "SELECT id FROM `users` WHERE username = '$username'";

$result_users = mysqli_query($db, $sql)
or die('Error ' . mysqli_error($db) . ' with query ' . $sql);

$user = mysqli_fetch_assoc($result_users);
$user_id = $user['id'];

// --- DATA ---
//php array om database te simuleren, dit kan straks vervangen worden door database logic.

$shelves = [
        [
                'title' => 'Plank 1: Boeken die je aan het lezen bent',
                'books' => [] // No books on this shelf yet, as in the mockup
        ],
        [
                'title' => 'Plank 2: Boeken die je wil lezen',
                'books' => [] // No books on this shelf yet
        ],
        [
                'title' => 'Plank 3: Aanbevolen voor jou',
                'books' => [
                        ['cover_url' => 'https://placehold.co/150x220/5F6F52/fff?text=Book+A'],
                        ['cover_url' => 'https://placehold.co/150x220/5F6F52/fff?text=Book+B'],
                ]
        ],
        [
                'title' => 'Plank 4: Lees opnieuw',
                'books' => [
                        ['cover_url' => 'https://placehold.co/150x220/333/fff?text=Book+1'],
                        ['cover_url' => 'https://placehold.co/150x220/333/fff?text=Book+2'],
                        ['cover_url' => 'https://placehold.co/150x220/333/fff?text=Book+3'],
                        ['cover_url' => 'https://placehold.co/150x220/333/fff?text=Book+4'],
                        ['cover_url' => 'https://placehold.co/150x220/333/fff?text=Book+5'],
                        ['cover_url' => 'https://placehold.co/150x220/333/fff?text=Book+6'],
                ]
        ]
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jouw Yshelf</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* --- STYLING (CSS) --- */
        :root {
            --bg-main: #EFEBE0;
            --bg-container: #6B654F; /* Brownish container color */
            --bg-sidebar: #A29A82; /* Lighter sidebar color */
            --bg-sidebar-active: #8B836B;
            --text-color: #333;
            --text-light: #FFFFFF;
            --search-bg: #D4CCB4;
            --cover-bg: #B1A990; /* The requested background for covers */
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-color);
            line-height: 1.6;
        }

        .page-container {
            display: flex;
            width: 1200px;
            max-width: 95%; /* Use percentage for better responsiveness */
            min-height: 90vh; /* Use min-height instead of fixed height */
            margin: 5vh auto; /* Center the container with margin */
            background-color: var(--bg-container);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* --- Main Content Area --- */
        .main-content {
            flex-grow: 1;
            padding: 30px 40px; /* Increased padding for better spacing */
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto; /* Allow this entire section to scroll */
        }

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        header h1 {
            font-size: 2.5em;
            color: var(--text-light);
            font-weight: bold;
        }

        .search-bar {
            position: relative;
            margin-bottom: 35px;
        }

        .search-bar input {
            width: 100%;
            padding: 15px 50px 15px 25px; /* Adjust padding for icon */
            border-radius: 25px;
            border: none;
            background-color: var(--search-bg);
            font-size: 1em;
            color: var(--text-color);
        }

        .search-bar input::placeholder {
            color: #5c5542;
        }

        .search-bar button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            color: var(--text-color);
            padding: 10px;
        }

        /* Bookshelves */
        .bookshelves {
            display: flex;
            flex-direction: column;
            gap: 30px; /* Space between shelves */
        }

        .shelf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .shelf-header h2 {
            color: var(--text-light);
            font-size: 1.4em;
        }

        .shelf-header a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 1.5em;
            transition: transform 0.2s;
        }

        .shelf-header a:hover {
            transform: translateX(5px);
        }

        .book-list {
            background-color: var(--bg-sidebar);
            padding: 20px;
            border-radius: 10px;
            display: flex;
            gap: 20px;
            overflow-x: auto; /* Allows horizontal scrolling for books */
            min-height: 225px; /* Minimum height for empty shelves */
            align-items: center; /* Vertically center content */
        }

        /* Style for empty shelves */
        .book-list:empty::after {
            content: "Deze plank is nog leeg.";
            color: var(--bg-container);
            font-style: italic;
            width: 100%;
            text-align: center;
        }

        .book-cover {
            width: 120px;
            height: 180px;
            background-color: var(--cover-bg);
            border-radius: 5px;
            flex-shrink: 0; /* Prevent books from shrinking */
            overflow: hidden;
            transition: transform 0.2s ease-in-out;
        }

        .book-cover:hover {
            transform: scale(1.05);
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- Sidebar --- */
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background-color: var(--bg-sidebar);
            padding: 25px;
            display: flex;
            flex-direction: column;
        }

        .sidebar .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .sidebar .menu-header h2 {
            font-size: 1.5em;
            color: var(--text-color);
        }

        .sidebar .menu-header .menu-icon {
            font-size: 24px;
            cursor: pointer;
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-color);
            font-weight: bold;
            transition: background-color 0.2s;
        }

        .sidebar nav a i {
            font-size: 22px;
            margin-right: 20px;
            width: 30px; /* To align text */
            text-align: center;
        }

        .sidebar nav a:hover {
            background-color: var(--bg-sidebar-active);
        }

        .sidebar nav a.active {
            background-color: var(--bg-sidebar-active);
            color: var(--text-light);
        }

        .sidebar .log-out {
            margin-top: auto; /* Pushes logout to the bottom */
        }

        /* --- Help Pop-up Widget --- */
        .help-widget {
            position: absolute;
            bottom: 25px;
            right: 40px;
            cursor: pointer;
        }

        .help-widget .icon {
            width: 60px;
            height: 60px;
            background-color: var(--bg-sidebar);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 28px;
            color: var(--text-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.2s;
        }

        .help-widget:hover .icon {
            background-color: var(--bg-sidebar-active);
        }

    </style>
</head>
<body>

<div class="page-container">

    <!-- sidebar staat als eerst, dus naar rechts.. enige oplossing lol -->
    <aside class="sidebar">
        <div class="menu-header">
            <h2>Menu</h2>
            <div class="menu-icon">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
        <nav>
            <a href="boekenkast.php" class="active">
                <i class="fa-solid fa-book-bookmark"></i>
                <span>Boekenkast</span>
            </a>
            <a href="booklist.php">
                <i class="fa-solid fa-list-check"></i>
                <span>Leeslijsten</span>
            </a>
            <a href="friends.php?id=<?= $user_id ?>">
                <i class="fa-solid fa-users"></i>
                <span>Vrienden</span>
            </a>
            <a href="profile.php">
                <i class="fa-solid fa-user"></i>
                <span>Profiel</span>
            </a>
            <a href="logout.php" class="log-out">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Log uit</span>
            </a>
        </nav>
    </aside>

    <!-- main content is 2e, dus de sidebar wordt rechts gedaan ofzo -->
    <main class="main-content">
        <header>
            <h1>Jouw Yshelf</h1>
        </header>

        <!-- search bar is fake, is gewoon een link die doorstuurt naar de zoekpagina -->
        <a href="booklist.php" style="text-decoration: none;">
            <div class="search-bar">
                <!-- 'pointer-events: none' makes the input non-interactive, so the click goes to the link -->
                <input type="text" placeholder="Zoek naar titel, auteur of genre..." style="pointer-events: none;">
                <button type="submit" style="pointer-events: none;"><i class="fa-solid fa-search"></i></button>
            </div>
        </a>

        <section class="bookshelves">
            <?php foreach ($shelves as $shelf): ?>
                <article class="shelf">
                    <div class="shelf-header">
                        <h2><?= htmlspecialchars($shelf['title']) ?></h2>
                        <a href="#">&gt;</a>
                    </div>
                    <div class="book-list">
                        <?php foreach ($shelf['books'] as $book): ?>
                            <div class="book-cover">
                                <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Book cover">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="help-widget">
            <div class="icon"><i class="fa-solid fa-comment-dots"></i></div>
        </div>
    </main>

</div>

</body>
</html>