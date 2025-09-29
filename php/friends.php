<?php

/** @var mysqli $db */
require_once '../includes/database.php';

if (isset($_GET['q'])) {
    $search = $db->real_escape_string($_GET['q']);
    $sql = "SELECT id, username FROM users WHERE username LIKE '%$search%' LIMIT 10";
    $result = mysqli_query($db, $sql);

    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($users);
    exit;
}

$user_id = $_GET["id"];

$sql = "SELECT * FROM `users` WHERE id = '$user_id'";

$result_users = mysqli_query($db, $sql)
or die('Error ' . mysqli_error($db) . ' with query ' . $sql);

$sql_friend = "SELECT * FROM `user_to_friend_id`";

$friends = [];

$result_friend = mysqli_query($db, $sql_friend)
or die('Error ' . mysqli_error($db) . ' with query ' . $sql_friend);

$user = mysqli_fetch_assoc($result_users);
while ($row = mysqli_fetch_assoc($result_friend)) {
    $friends[] = $row;
}
print_r($user);
print_r($friends);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - Jouw Digitale Boekenkast</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<header>
    <h1>📚 YShelf</h1>
    <nav>
        <a href="/php/search.php">Mijn Boekenkast</a>
        <a href="#">Zoeken</a>
        <a href="../index.php">Login</a>


    </nav>
</header>

<main>
    <div class="friend-search">
        <div class="searchbar">
            <input type="text" id="searchInput" placeholder="Zoek een vriend">
            <button id="searchButton" type="button">Zoeken</button>
            <div class="search-box" style="position: absolute; top: 20px; right: 0;">
            </div>
        </div>
        <div class="results-container">
            <div id="results" class="shelf-rows"></div>
        </div>
    </div>
</main>
<!-- Chat widget -->
<div id="chat-widget" class="collapsed">
    <div id="chat-header">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots"
             viewBox="0 0 16 16">
            <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
            <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2"/>
        </svg>
        <span id="chat-title" style="display:none;">Luna</span>
    </div>
    <div id="chat-box"></div>
    <div id="chat-input">
        <input type="text" id="userInput" placeholder="Typ een bericht..."/>
        <button onclick="sendMessage()">▶</button>
    </div>
</div>


<footer>
    <p>&copy; 2024 YShelf. Alle rechten voorbehouden.</p>
</footer>

<!-- JavaScript files -->
<script src="/js/Friends.js"></script>

</body>
</html>