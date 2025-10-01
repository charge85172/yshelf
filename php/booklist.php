<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();

// Check if this is an AJAX request
$isAjax = isset($_GET['action']) || isset($_POST['action']);

if (!isset($_SESSION['username'])) {
    // For AJAX requests, return JSON error instead of redirecting
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit();
    }
    // For page requests, redirect to login
    header('Location: ../index.php');
    exit();
}
require_once '../includes/database.php';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'addBook') {
    //userID ophalen
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result);
    $userID = $row['id'];

    $apiLink = $_POST['apiLink'];
    //kijk of boek al bestaat in collectie
    $checkQuery = "SELECT id FROM `user_books` WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
    $checkResult = mysqli_query($db, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        echo json_encode(['success' => false, 'message' => 'Book already in collection']);
        exit;
    }
    //boek in database rammen
    $insertQuery = "INSERT INTO `user_books` (`user_id`, `book_link`, `is_unread`) VALUES ('$userID', '$apiLink', 1)";

    if (mysqli_query($db, $insertQuery)) {
        echo json_encode(['success' => true, 'message' => 'Book added to collection']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding book: ' . mysqli_error($db)]);
    }
    exit;
}

if ($_GET && isset($_GET['action']) && $_GET['action'] === 'getBooks') {
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result);
    $userID = $row['id'];
    //boeken ophalen van gebruiker
    $booksQuery = "SELECT * FROM `user_books` WHERE `user_id` = '$userID'";
    $booksResult = mysqli_query($db, $booksQuery);
    $userBooks = [];

    while ($book = mysqli_fetch_assoc($booksResult)) {
        $userBooks[] = $book;
    }

    header('Content-Type: application/json');
    echo json_encode($userBooks);
    exit;
}
// checkt nog een keer of boek al bestaat
if ($_GET && isset($_GET['action']) && $_GET['action'] === 'checkBook') {
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result);
    $userID = $row['id'];

    $apiLink = $_GET['apiLink'];
    //checkt of boek al bestaat in collectie
    $checkQuery = "SELECT * FROM `user_books` WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
    $checkResult = mysqli_query($db, $checkQuery);

    $exists = mysqli_num_rows($checkResult) > 0;
    $status = 'unread'; // default status

    if ($exists) {
        $bookData = mysqli_fetch_assoc($checkResult);
        $isRecommended = $bookData['is_recommended'] == 1;

        //checkt of boek al gelezen is enz (recommended is independent)
        if ($bookData['is_read'] == 1) {
            $status = 'read';
        } else if ($bookData['is_reading'] == 1) {
            $status = 'reading';
        } else if ($bookData['is_discarded'] == 1) {
            $status = 'discarded';
        } else if ($bookData['is_favorite'] == 1) {
            $status = 'favorite';
        } else if ($bookData['is_unread'] == 1) {
            $status = 'unread';
        } else {
            $status = 'unread';
        }
    } else {
        $isRecommended = false;
    }

    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists, 'status' => $status, 'isRecommended' => $isRecommended]);
    exit;
}

// Get recommended books count
if ($_GET && isset($_GET['action']) && $_GET['action'] === 'getRecommendedCount') {
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result);
    $userID = $row['id'];

    $countQuery = "SELECT COUNT(*) as count FROM `user_books` WHERE `user_id` = '$userID' AND `is_recommended` = 1";
    $countResult = mysqli_query($db, $countQuery);
    $countRow = mysqli_fetch_assoc($countResult);
    $recommendedCount = $countRow['count'];

    header('Content-Type: application/json');
    echo json_encode(['count' => $recommendedCount, 'max' => 6]);
    exit;
}
// veranderd de status van het boek
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'changeStatus') {
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result);
    $userID = $row['id'];

    $apiLink = $_POST['apiLink'];
    $status = $_POST['status'];

    // Special handling for recommended status - it should be independent/additive
    if ($status === 'recommended') {
        // Toggle recommended status without affecting other statuses
        $checkRecommendedQuery = "SELECT is_recommended FROM `user_books` WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
        $checkRecommendedResult = mysqli_query($db, $checkRecommendedQuery);
        $checkRecommendedRow = mysqli_fetch_assoc($checkRecommendedResult);
        $isCurrentlyRecommended = $checkRecommendedRow['is_recommended'];

        // If trying to add recommendation, check if user already has 6 recommended books
        if (!$isCurrentlyRecommended) {
            $countRecommendedQuery = "SELECT COUNT(*) as count FROM `user_books` WHERE `user_id` = '$userID' AND `is_recommended` = 1";
            $countResult = mysqli_query($db, $countRecommendedQuery);
            $countRow = mysqli_fetch_assoc($countResult);
            $recommendedCount = $countRow['count'];

            if ($recommendedCount >= 6) {
                echo json_encode(['success' => false, 'message' => 'Je kunt maximaal 6 boeken aanbevelen. Verwijder eerst een andere aanbeveling.']);
                exit;
            }
        }

        $newRecommendedValue = $isCurrentlyRecommended ? 0 : 1;
        $updateQuery = "UPDATE `user_books` SET `is_recommended` = '$newRecommendedValue' WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
    } else {
        // For all other statuses, reset the exclusive statuses but keep recommended
        $resetQuery = "UPDATE `user_books` SET `is_read` = 0, `is_reading` = 0, `is_discarded` = 0, `is_favorite` = 0, `is_unread` = 0 WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
        mysqli_query($db, $resetQuery);

        // dan die bende updaten
        $updateQuery = "";
        switch ($status) {
            case 'unread':
                $updateQuery = "UPDATE `user_books` SET `is_unread` = 1 WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
                break;
            case 'read':
                $updateQuery = "UPDATE `user_books` SET `is_read` = 1 WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
                break;
            case 'reading':
                $updateQuery = "UPDATE `user_books` SET `is_reading` = 1 WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
                break;
            case 'discarded':
                $updateQuery = "UPDATE `user_books` SET `is_discarded` = 1 WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
                break;
            case 'favorite':
                $updateQuery = "UPDATE `user_books` SET `is_favorite` = 1 WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
        }
    }

    if (mysqli_query($db, $updateQuery)) {
        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating status: ' . mysqli_error($db)]);
    }
    exit;
}
//boek verwijderen
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'removeBook') {
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result);
    $userID = $row['id'];

    $apiLink = $_POST['apiLink'];

    $deleteQuery = "DELETE FROM `user_books` WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";

    if (mysqli_query($db, $deleteQuery)) {
        echo json_encode(['success' => true, 'message' => 'Book removed from collection']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error removing book: ' . mysqli_error($db)]);
    }
    exit;
}
//laat boeken zien als er nog niks gebeurd is.
$query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";
$result = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($result);
$userID = $row['id'];

$booksQuery = "SELECT * FROM `user_books` WHERE `user_id` = '$userID'";
$booksResult = mysqli_query($db, $booksQuery);
$userBooks = [];

while ($book = mysqli_fetch_assoc($booksResult)) {
    $userBooks[] = $book;
}

mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - Jouw Digitale Boekenkast</title>
    <link rel="stylesheet" href="../css/booklist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="../js/booklist.js"></script>
</head>
<body>

<div class="page-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="menu-header">
            <h2>Menu</h2>
        </div>
        <nav>
            <a href="boekenkast.php">
                <i class="fa-solid fa-book-bookmark"></i>
                <span>Boekenkast</span>
            </a>
            <a href="booklist.php" class="active">
                <i class="fa-solid fa-list-check"></i>
                <span>Leeslijsten</span>
            </a>
            <a href="friends.php?id=<?= $userID ?>">
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="booklist">
            <header>
                <h1>Mijn Boeken</h1>
                <div class="book-search">
                    <input type="text" id="bookListSearchInput" placeholder="Zoek een boek">
                    <div class="booklist-search-results">
                        <div id="booklistResults" class="results-container">
                        </div>
                    </div>
                </div>

                <nav class="booklist-nav">
                    <button>Te lezen</button>
                    <button>Bezig</button>
                    <button>Gelezen</button>
                    <button>Gestopt</button>
                    <button>Favorieten</button>
                    <button>Aanbevolen</button>
                </nav>
                <!-- <div class="collection-search">
                    <input type="text" placeholder="Zoek een boek in je collectie">
                    <button>Zoek in collectie</button>
                </div> -->
                <!-- Misschie later nog toevoegen -->
                <div class="booklist-container">

                    <div id="booklist-unread" class="booklist-unread">
                        <h2>Te lezen</h2>
                        <div class="booklist-unread-container">
                            <div class="booklist-unread-item">
                                <h3>Boek niet gelezen</h3>
                            </div>
                        </div>
                    </div>

                    <div id="booklist-reading" class="booklist-reading">
                        <h2>Bezig</h2>
                        <div class="booklist-reading-container">
                            <div class="booklist-reading-item">
                                <h3>Boek bezig</h3>
                            </div>
                        </div>
                    </div>

                    <div id="booklist-read" class="booklist-read">
                        <h2>Gelezen</h2>
                        <div class="booklist-read-container">
                            <div class="booklist-read-item">
                                <h3>Boek gelezen</h3>
                            </div>
                        </div>
                    </div>

                    <div id="booklist-stopped" class="booklist-stopped">
                        <h2>Gestopt</h2>
                        <div class="booklist-stopped-container">
                            <div class="booklist-stopped-item">
                                <h3>Boek gestopt</h3>
                            </div>
                        </div>
                    </div>

                    <div id="booklist-favorites" class="booklist-favorites">
                        <h2>Favorieten</h2>
                        <div class="booklist-favorites-container">
                            <div class="booklist-favorites-item">
                                <h3>Boek favoriet</h3>
                            </div>
                        </div>
                    </div>

                    <div id="booklist-recommended" class="booklist-recommended">
                        <h2>Aanbevolen</h2>
                        <div class="booklist-recommended-container">
                            <div class="booklist-recommended-item">
                                <h3>Boek aanbevolen</h3>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </main>
</div>

<div id="myModal" class="modal">
    <div class="modal-content" id="modalContent">
    </div>
</div>

<script>
    // pass de userbooks naar js
    var userBooks = <?php echo json_encode($userBooks); ?>;
    console.log('User books:', userBooks);

    document.addEventListener('DOMContentLoaded', function () {
        if (userBooks && userBooks.length > 0) {
            displayUserBooks(userBooks);
        }

        // Handle search parameter from URL
        var urlParams = new URLSearchParams(window.location.search);
        var searchTerm = urlParams.get('search');

        if (searchTerm) {
            var searchInput = document.getElementById('bookListSearchInput');
            if (searchInput) {
                searchInput.value = searchTerm;
                // Automatically trigger search
                searchBooksAPI(searchTerm);
                showSearchResults();
            }
        }
    });
</script>
</body>
</html>
