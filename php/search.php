<?php

/** @var mysqli $db */
require_once './includes/database.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];

$sql = "SELECT id FROM `users` WHERE username = '$username'";

$result_users = mysqli_query($db, $sql)
or die('Error ' . mysqli_error($db) . ' with query ' . $sql);

$user = mysqli_fetch_assoc($result_users);
$user_id = $user['id'];

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - Jouw Digitale Boekenkast</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="../js/AI.js" defer></script>
</head>
<body>
<header>
    <h1>📚 YShelf</h1>
    <nav>
        <a href="boekenkast.php">Mijn Boekenkast</a>
        <a href="search.php">Zoeken</a>
        <a href="friends.php?id=<?= $user_id ?>">Vrienden</a>
        <a href="../includes/logout.php" class="log-out">
    </nav>
</header>

<main>
    <section id="shelf-section">
        <h2>Mijn Boekenkast</h2>
        <div id="book-shelf" class="book-grid">
        </div>
    </section>

    <div class="bookshelf-search">
        <div class="searchbar">
            <input type="text" id="searchInput" placeholder="Zoek een boek">
            <button id="searchButton" type="button">Zoeken</button>
            <div class="search-box" style="position: absolute; top: 20px; right: 0;">
            </div>
        </div>
        <div class="results-container">
            <div id="results" class="shelf-rows"></div>
        </div>
    </div>

    <div id="bookList"></div>

    <!-- Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content" id="modalContent">
        </div>
    </div>
</main>
<footer>
    <p>&copy; 2024 YShelf. Alle rechten voorbehouden.</p>
</footer>

<!-- JavaScript files -->
<script src="../js/Book.js"></script>
<script src="../js/Shelf.js"></script>
<script src="../js/UI.js"></script>
<script src="../js/app.js"></script>
</body>
</html>