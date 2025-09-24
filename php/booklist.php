<?php
require_once '../includes/profileRetrieve.php';
require_once '../includes/database.php';

// Handle adding book to collection FIRST (before any output)
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'addBook') {
    // Get the user ID
    $query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";	
    $result = mysqli_query($db, $query);
    $row = mysqli_fetch_assoc($result); 
    $userID = $row['id'];
    
    $apiLink = $_POST['apiLink'];
    
    // Check if book already exists for this user
    $checkQuery = "SELECT id FROM `user_books` WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
    $checkResult = mysqli_query($db, $checkQuery);
    
    if (mysqli_num_rows($checkResult) > 0) {
        echo json_encode(['success' => false, 'message' => 'Book already in collection']);
        exit;
    }
    
    // Insert book into database using your table structure
    $insertQuery = "INSERT INTO `user_books` (`user_id`, `book_link`, `is_unread`) VALUES ('$userID', '$apiLink', 1)";
    
    if (mysqli_query($db, $insertQuery)) {
        echo json_encode(['success' => true, 'message' => 'Book added to collection']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding book: ' . mysqli_error($db)]);
    }
    exit; // Stop execution after handling the POST request
}

// Regular page display (only runs when NOT a POST request)
// Get the user ID for regular page display
$query = "SELECT id FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'";	
$result = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($result); 
$userID = $row['id'];

// Fetch user's books from database
$booksQuery = "SELECT * FROM `user_books` WHERE `user_id` = '$userID'";
$booksResult = mysqli_query($db, $booksQuery);
$userBooks = [];

while ($book = mysqli_fetch_assoc($booksResult)) {
    $userBooks[] = $book;
}

mysqli_close($db); // Move this to the very end!
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - Jouw Digitale Boekenkast</title>
    <link rel="stylesheet" href="../css/booklist.css">
    <script src="../js/booklist.js"></script>
</head> 
<body>
    <div class="booklist">
        <h1>Mijn Boeken</h1>
        <div class="book-search">
            <input type="text" id="bookListSearchInput" placeholder="Zoek een boek">
            <button id="bookListSearchButton">Zoek</button>
            <div class="booklist-search-results">
                <div id="booklistResults" class="results-container">
                    <!-- Zoekresultaten worden hier dynamisch ingeladen door JavaScript -->
                </div>
            </div>
        </div>  

        <nav class="booklist-nav">
            <button>Te lezen</button>
            <button>Bezig</button>
            <button>Gelezen</button>
            <button>Gestopt</button>
            <button>Favorieten</button>
        </nav>
        <div class="collection-search">
            <input type="text" placeholder="Zoek een boek in je collectie">
            <button>Zoek in collectie</button>
        </div>
        <div class="booklist-container">

            <!-- Te lezen tab -->
            <div id="booklist-unread" class="booklist-unread">
                <h2>Te lezen</h2>
                <div class="booklist-unread-container">
                    <div class="booklist-unread-item">
                        <h3>Boek niet gelezen</h3>
                    </div>
                </div>
            </div>
            
            <!-- Bezig tab -->
            <div id="booklist-reading" class="booklist-reading">
                <h2>Bezig</h2>
                <div class="booklist-reading-container">
                    <div class="booklist-reading-item">
                        <h3>Boek bezig</h3>
                    </div>
                </div>
            </div>
            
            <!-- Gelezen tab -->
            <div id="booklist-read" class="booklist-read">
                <h2>Gelezen</h2>
                <div class="booklist-read-container">
                    <div class="booklist-read-item">
                        <h3>Boek gelezen</h3>
                    </div>
                </div>
            </div>
            
            <!-- Gestopt tab -->
            <div id="booklist-stopped" class="booklist-stopped">
                <h2>Gestopt</h2>
                <div class="booklist-stopped-container">
                    <div class="booklist-stopped-item">
                        <h3>Boek gestopt</h3>
                    </div>
                </div>
            </div>
            
            <!-- Favorieten tab -->
            <div id="booklist-favorites" class="booklist-favorites">
                <h2>Favorieten</h2>
                <div class="booklist-favorites-container">
                    <div class="booklist-favorites-item">
                        <h3>Boek favoriet</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for book details -->
    <div id="myModal" class="modal">
        <div class="modal-content" id="modalContent">
        </div>
    </div>

<script>
// Pass user books data to JavaScript
var userBooks = <?php echo json_encode($userBooks); ?>;
console.log('User books:', userBooks);

// Display books when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (userBooks && userBooks.length > 0) {
        displayUserBooks(userBooks);
    }
});
</script>
</body>
</html>
