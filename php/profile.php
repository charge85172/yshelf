<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();

$current_user_id = $_SESSION['id'] ?? 0;

$profile_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($profile_id > 0) {
    $query = "SELECT `id`, `username`, `image`, `taste`, `description`, `genres` FROM `users` WHERE id = $profile_id";
} else {
    $username = $_SESSION['username'] ?? '';
    $query = "SELECT `id`, `username`, `image`, `taste`, `description`, `genres` FROM `users` WHERE username = '$username'";
}

$result = mysqli_query($db, $query)
or die('Error: ' . mysqli_error($db) . ' with query ' . $query);

$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("Profiel niet gevonden.");
}

$name = $row['username'];
$taste = $row['taste'];
$description = $row['description'];
$genres = $row['genres'];
$image = $row['image'];
$userID = $row['id'];
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - <?php echo $name ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

    /* --- STYLING (CSS) --- */
    :root {
        --bg-main: #EFEBE0;
        --bg-container: #6B654F;
        --bg-sidebar: #A29A82;
        --bg-sidebar-active: #8B836B;
        --text-color: #333;
        --text-light: #FFFFFF;
        --search-bg: #D4CCB4;
        --cover-bg: #B1A990;
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
        max-width: 95%;
        min-height: 90vh;
        margin: 5vh auto;
        background-color: var(--bg-container);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        overflow: hidden;

    }

    /* --- Main Content Area --- */
    .main-content {
        flex-grow: 1;
        padding: 30px 40px;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow-y: auto;
        margin: 10px;
    }

    .cover-background {
        background: var(--cover-bg);
        border-radius: 15px;
        padding: 10px;
        margin: 10px;
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

    img {
        margin: 10px;
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
        width: 30px;
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
        margin-top: auto;
    }

</style>
<body>

<div class="page-container">
    <aside class="sidebar">
        <div class="menu-header">
            <h2>Menu</h2>
        </div>
        <nav>
            <a href="boekenkast.php">
                <i class="fa-solid fa-book-bookmark"></i>
                <span>Boekenkast</span>
            </a>
            <a href="booklist.php">
                <i class="fa-solid fa-list-check"></i>
                <span>Leeslijsten</span>
            </a>
            <a href="friends.php?id=<?= $userID ?>">
                <i class="fa-solid fa-users"></i>
                <span>Vrienden</span>
            </a>
            <a href="profile.php" class="active">
                <i class="fa-solid fa-user"></i>
                <span>Profiel</span>
            </a>
            <a href="../includes/logout.php" class="log-out">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Log uit</span>
            </a>
        </nav>
    </aside>
    <main class="main-content">
        <section id="shelf-section">
            <section class="profile">
                <div class="cover-background">
                    <h2>Profiel van: <?php echo htmlspecialchars($name); ?></h2>
                </div>
                <div class="profile-photo">
                    <img src="<?php echo $row['image']; ?>"
                         alt="Profielfoto van <?php echo htmlspecialchars($name); ?>">
                </div>
                <div class="cover-background">
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
                </div>
            </section>
        </section>
    </main>
</div>


<!-- JavaScript files -->
<script src="/js/Book.js"></script>
<script src="/js/Shelf.js"></script>
<script src="/js/UI.js"></script>
<script src="/js/app.js"></script>
</body>
</html>