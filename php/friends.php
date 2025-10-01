<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();

// M
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
// E
if (isset($_GET['q'])) {
    $search = $db->real_escape_string($_GET['q']);
    $sql = "
        SELECT u.id, u.username,
               IFNULL(
                   (SELECT status 
                    FROM friendships f 
                    WHERE f.user_id = $user_id AND f.friend_id = u.id
                    LIMIT 1),
               0) AS friendStatus
        FROM users u
        WHERE u.username LIKE '%$search%'
          AND u.id != $user_id
        LIMIT 10
    ";

    $result = mysqli_query($db, $sql);
    if (!$result) die(json_encode(['success' => false, 'error' => mysqli_error($db)]));

    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['friendStatus'] = $row['friendStatus'] ? 1 : 0;
        $users[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($users);
    exit;
}

// M
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $friend_id = (int)($input['friend_id'] ?? 0);
    $action = $input['action'] ?? '';

    if ($friend_id <= 0 || !in_array($action, ['add', 'delete'])) {
        echo json_encode(['success' => false, 'error' => 'Ongeldige parameters']);
        exit;
    }

    if ($action === 'add') {
        $sql = "INSERT INTO friendships (user_id, friend_id, status) 
                VALUES ($user_id, $friend_id, 1)
                ON DUPLICATE KEY UPDATE status = 1";
    } else {
        $sql = "UPDATE friendships SET status = 0 WHERE user_id = $user_id AND friend_id = $friend_id";
    }

    header('Content-Type: application/json');
    if (mysqli_query($db, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    }

    mysqli_close($db);
    exit;
}

//E
if (isset($_GET['friends']) && $_GET['friends'] == 1) {
    $friends = [];

    $sql = "SELECT friend_id 
            FROM friendships 
            WHERE user_id = $user_id AND status = 1";

    $result = mysqli_query($db, $sql);
    if (!$result) {
        die(json_encode(['success' => false, 'error' => mysqli_error($db)]));
    }

    $friendIds = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $friendIds[] = (int)$row['friend_id'];
    }

    if (!empty($friendIds)) {
        $idList = implode(',', $friendIds);
        $sqlUsers = "SELECT id, username FROM users WHERE id IN ($idList)";
        $resultUsers = mysqli_query($db, $sqlUsers);

        if (!$resultUsers) {
            die(json_encode(['success' => false, 'error' => mysqli_error($db)]));
        }

        while ($row = mysqli_fetch_assoc($resultUsers)) {
            $friends[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($friends);
    exit;
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jouw Yshelf</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="../js/AI.js" defer></script>
    <style>
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
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            background-color: var(--cover-bg);
            border-radius: 15px;
        }

        header h1 {
            font-size: 2.5em;
            color: var(--text-color);
            font-weight: bold;
        }

        .search-bar {
            position: relative;
            margin-bottom: 35px;
        }

        .search-bar input {
            width: 100%;
            padding: 15px 50px 15px 25px;
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

        .friend-section {
            display: flex;
            gap: 20px;
            flex: 1;
        }

        .friend-search,
        .friend-list {
            flex: 1;
            background: var(--bg-sidebar);
            border-radius: 10px;
            padding: 20px;
            overflow-y: auto;
        }

        .friend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            margin-bottom: 10px;
            background-color: #D4CCB4;
            border: 1px solid #B1A990;
            border-radius: 8px;
            transition: background-color 0.2s, transform 0.2s;
        }

        .friend-item:last-child {
            border-bottom: none;
        }

        .friend-item:hover {
            background-color: #C0B58E;
            transform: translateY(-2px);
        }
    </style>
</head>
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
            <a href="friends.php?id=<?= $user_id ?>" class="active">
                <i class="fa-solid fa-users"></i>
                <span>Vrienden</span>
            </a>
            <a href="profile.php">
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
        <header>
            <h1>Jouw Yshelf</h1>
        </header>
        <div class="friend-section">
            <div class="friend-search">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Zoek een vriend">
                    <button id="searchButton" type="button">Zoeken</button>
                    <div class="search-box" style="position: absolute; top: 20px; right: 0;">
                    </div>
                </div>
                <div class="results-container" id="friend-item">
                    <div id="results" class="shelf-rows"></div>
                </div>
            </div>
            <div class="friend-list">
                <h2>Jouw vrienden</h2>
                <div class="results-container" id="friendList">
                    <div id="results" class="shelf-rows"></div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="/js/Friends.js"></script>
</body>
</html>