<?php
session_start();
require_once '../includes/database.php';

$username = $_SESSION['username'];
$query = "SELECT `id`, `username`, `taste`, `description`, `genres` 
          FROM `users` WHERE username = '$username'";
$result = mysqli_query($db, $query)
or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
$row = mysqli_fetch_assoc($result);

$id_user = $row['id'];
$name = $row['username'];
$genres = $row['genres'];

// Functie om alle book_links van een categorie op te halen
function fetchBooks($db, $userId, $column)
{
    $books = [];
    $query = "SELECT `book_link` FROM `user_books` WHERE user_id = $userId AND $column = 1";
    $result = mysqli_query($db, $query)
    or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $books[] = $row['book_link'];
    }
    return $books;
}

$readBooks = fetchBooks($db, $id_user, "is_read");
$unreadBooks = fetchBooks($db, $id_user, "is_unread");
$readingBooks = fetchBooks($db, $id_user, "is_reading");
$favoriteBooks = fetchBooks($db, $id_user, "is_favorite");
$discardedBooks = fetchBooks($db, $id_user, "is_discarded");
$recommendedBooks = fetchBooks($db, $id_user, "is_recommended");

header("Content-Type: application/json");
$userProfile = [
    "user_name" => $name,
    "favorite_genres" => [$genres],
];

// System prompt
$systemPrompt =
    "The user has the following preferences: " .
    "Favorite genres: " . implode(", ", $userProfile["favorite_genres"]) .
    ", Books read: " . implode(", ", $readBooks) .
    ", Books to read: " . implode(", ", $unreadBooks) .
    ", Books currently reading: " . implode(", ", $readingBooks) .
    ", Favorite books: " . implode(", ", $favoriteBooks) .
    ", Discarded books: " . implode(", ", $discardedBooks) .
    ", Recommended books: " . implode(", ", $recommendedBooks) .
    ". Please use this information to provide personalized book recommendations. " .
    "Only answer with https://www.googleapis.com/books links. No other text. " .
    "Use this format: 
    'books' => [
        ['api_link' => 'https://www.googleapis.com/books/v1/volumes/35rHBAAAQBAJ'],
        ['api_link' => 'https://www.googleapis.com/books/v1/volumes/u9BwDwAAQBAJ'],
        ['api_link' => 'https://www.googleapis.com/books/v1/volumes/1vZyDwAAQBAJ'],
        ['api_link' => 'https://www.googleapis.com/books/v1/volumes/Os3ODwAAQBAJ'],
        ['api_link' => 'https://www.googleapis.com/books/v1/volumes/DPAAEQAAQBAJ'],
    ]";

// API key uit .env
$env = parse_ini_file(__DIR__ . "/.env");
$apiKey = $env["OPENAI_API_KEY"] ?? "";

// Vast bericht (geen input nodig)
$message = "Can you give me 6 recommendations";

// Call naar OpenAI API
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $message]
    ]
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
