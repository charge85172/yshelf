<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();
$username = $_SESSION['username'];

$query = "SELECT `username`, `image`, `taste`, `description`, `genres` FROM `users` WHERE username = '$username'";
$result = mysqli_query($db, $query)
or die('Error: ' . mysqli_error($db) . 'with query ' . $query);
$row = mysqli_fetch_assoc($result);

$name = $row['username'];
$taste = $row['taste'];
$description = $row['description'];
$genres = $row['genres'];
$image = $row['image'];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - <?php echo $name ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <script src="/js/AI.js" defer></script>
</head>
<style>
    .btn {
        display: inline-block;
        padding: 8px 14px;
        margin-top: 5px;
        background-color: #ffb6c1;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        transition: 0.2s ease;
    }

    .btn:hover {
        background-color: #ff8fab;
    }

</style>
<body>
<header>
    <h1>📚 YShelf</h1>
    <nav>
        <a href="#">Mijn Boekenkast</a>
        <a href="#">Zoeken</a>
        <a href="php/auth.php">Login</a>
    </nav>
</header>

<main>
    <section id="shelf-section">
        <section class="profile">
            <h2><?php echo htmlspecialchars($name); ?></h2>

            <div class="profile-photo">
                <img src="<?php echo $row['image']; ?>"
                     alt="Profielfoto van <?php echo htmlspecialchars($name); ?>">
            </div>

            <section class="about-me">
                <h3>Over mij</h3>
                <?php if (!empty($description)): ?>
                    <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
                <?php else: ?>
                    <p>Je hebt nog niets ingevuld over jezelf.</p>
                    <a href="edit_profile.php" class="btn">➕ Voeg iets toe</a>
                <?php endif; ?>
            </section>

            <section class="my-taste">
                <h3>Mijn smaak</h3>
                <?php if (!empty($taste)): ?>
                    <p><?php echo nl2br(htmlspecialchars($taste)); ?></p>
                <?php else: ?>
                    <p>Je hebt nog geen smaakprofiel toegevoegd.</p>
                    <a href="edit_profile.php" class="btn">➕ Voeg iets toe</a>
                <?php endif; ?>
            </section>

            <section class="genres">
                <h3>Favoriete genres</h3>
                <?php if (!empty($genres)): ?>
                    <p><?php echo htmlspecialchars($genres); ?></p>
                <?php else: ?>
                    <p>Je hebt nog geen favoriete genres gekozen.</p>
                    <a href="edit_profile.php" class="btn">➕ Kies genres</a>
                <?php endif; ?>
            </section>
        </section>


    </section>
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
        <span id="chat-close" style="">×</span>
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
<script src="js/Book.js"></script>
<script src="js/Shelf.js"></script>
<script src="js/UI.js"></script>
<script src="js/app.js"></script>
</body>
</html>