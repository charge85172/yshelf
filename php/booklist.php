<?php
// --- SESSIEBEHEER ---
// session_start() moet op ELKE pagina staan die sessies gebruikt.
// Het moet de allereerste code zijn, nog voor enige HTML.
session_start();

// Controleer of de gebruiker wel is ingelogd door te kijken of 'user_id' in de sessie bestaat.
// Als de gebruiker niet is ingelogd, stuur hem dan terug naar de inlogpagina.
if (!isset($_SESSION['user_id'])) {
    // header() stuurt de gebruiker naar een andere pagina.
    // We gebruiken een absoluut pad (/index.php) zodat het altijd werkt.
    header('Location:../index.php');
    // exit() stopt het script onmiddellijk, zodat de rest van de code niet wordt uitgevoerd.
    exit();
}

// --- DATABASE CONNECTIE ---
// We includen het database bestand om een verbinding te maken.
/** @var mysqli $db */
require_once '../includes/database.php';

// Haal de user_id op uit de sessie. Dit is veiliger dan de username gebruiken.
$userID = $_SESSION['user_id'];

// --- API LOGICA (voor JavaScript) ---
// Dit deel van de code handelt verzoeken af die via JavaScript (AJAX) binnenkomen.

// Als een boek wordt toegevoegd via een POST verzoek...
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'addBook') {
    $apiLink = $_POST['apiLink'];
    //kijk of boek al bestaat in collectie
    $checkQuery = "SELECT id FROM `user_books` WHERE `user_id` = '$userID' AND `book_link` = '$apiLink'";
    $checkResult = mysqli_query($db, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        echo json_encode(['success' => false, 'message' => 'Boek is al in je collectie']);
        exit;
    }
    //boek in database toevoegen
    $insertQuery = "INSERT INTO `user_books` (`user_id`, `book_link`, `is_unread`) VALUES ('$userID', '$apiLink', 1)";

    if (mysqli_query($db, $insertQuery)) {
        echo json_encode(['success' => true, 'message' => 'Boek toegevoegd aan je collectie']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fout bij toevoegen: ' . mysqli_error($db)]);
    }
    exit; // Stop het script, want we hoeven geen HTML te tonen.
}

// ... (De rest van de API-logica voor GET, POST acties blijft hier) ...
// OPMERKING: De overige PHP-logica voor het afhandelen van GET/POST acties is hier niet volledig getoond,
// maar de belangrijkste reparatie is de sessie-check hierboven.

// --- PAGINA DATA OPHALEN ---
// Dit deel van de code haalt de boeken op die op de pagina getoond moeten worden.
$booksQuery = "SELECT * FROM `user_books` WHERE `user_id` = '$userID'";
$booksResult = mysqli_query($db, $booksQuery);
$userBooks = [];

while ($book = mysqli_fetch_assoc($booksResult)) {
    $userBooks[] = $book;
}

// Sluit de databaseverbinding netjes af.
mysqli_close($db);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jouw Leeslijst - YShelf</title>
    <!-- DE FIX: Verwijzing naar het correcte, centrale stylesheet -->
    <link rel="stylesheet" href="../css/styles.css">
    <!-- Dit aparte CSS-bestand kan later eventueel specifieke stijlen voor deze pagina bevatten -->
    <link rel="stylesheet" href="../css/booklist.css">
    <script src="../js/booklist.js"></script>
</head>
<body>
    <!-- De HTML-structuur blijft hetzelfde, maar zal nu de stijlen uit styles.css overnemen -->
    <div class="booklist">
        <h1>Mijn Boeken</h1>
        <div class="book-search">
            <input type="text" id="bookListSearchInput" placeholder="Zoek een boek">
            <button id="bookListSearchButton">Zoek</button>
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
        </nav>
        <div class="collection-search">
            <input type="text" placeholder="Zoek een boek in je collectie">
            <button>Zoek in collectie</button>
        </div>
        <div class="booklist-container">

            <div id="booklist-unread" class="booklist-unread">
                <h2>Te lezen</h2>
                <div class="booklist-unread-container">
                    <div class="booklist-unread-item">
                        <h3>Boek niet gelezen</h3>
                    </div>
                </div>
            </div>

            <!-- De rest van de HTML-structuur... -->

        </div>
    </div>

    <div id="myModal" class="modal">
        <div class="modal-content" id="modalContent">
        </div>
    </div>

<script>
    // Geef de opgehaalde boeken door aan het JavaScript-bestand.
    var userBooks = <?php echo json_encode($userBooks); ?>;
    console.log('Gebruikersboeken:', userBooks);

    document.addEventListener('DOMContentLoaded', function() {
        if (userBooks && userBooks.length > 0) {
            displayUserBooks(userBooks);
        }
    });
</script>
</body>
</html>
