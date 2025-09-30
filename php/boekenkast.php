<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();
// /** @var $response */
// require_once 'chatbot.php';
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

    // Function to get book details from Google Books API (moved outside to be reusable)
    function getBookDetailsFromAPI($bookLink) {
        // Handle both volume links and search links
        if (strpos($bookLink, '/volumes/') !== false) {
            // Direct volume link
            $apiUrl = $bookLink;
        } else if (strpos($bookLink, 'volumes?q=') !== false) {
            // Search link - get the first result
            $apiUrl = $bookLink;
        } else {
            return null;
        }

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Reduced to 5 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // 3 second connection timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            return null; // Return null on any cURL error
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        
        if (isset($data['volumeInfo'])) {
            // Direct volume response
            $volumeInfo = $data['volumeInfo'];
        } else if (isset($data['items'][0]['volumeInfo'])) {
            // Search response - get first item
            $volumeInfo = $data['items'][0]['volumeInfo'];
        } else {
            return null;
        }
        
        return [
            'title' => $volumeInfo['title'] ?? 'Unknown Title',
            'author' => $volumeInfo['authors'][0] ?? 'Unknown Author',
            'genre' => isset($volumeInfo['categories']) ? $volumeInfo['categories'][0] : 'Unknown Genre',
            'cover_url' => $volumeInfo['imageLinks']['thumbnail'] ?? 'https://placehold.co/150x220/5F6F52/fff?text=No+Cover',
            'description' => $volumeInfo['description'] ?? '',
            'preview_link' => $volumeInfo['previewLink'] ?? $bookLink,
            'link' => $bookLink
        ];
    }

    // Function to get AI recommendations
function getAIRecommendations($user_id, $db) {
    // Get user preferences (same logic as chatbot.php)
    $query = "SELECT `id`, `username`, `taste`, `description`, `genres` 
              FROM `users` WHERE id = $user_id";
    $result = mysqli_query($db, $query)
    or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
    $row = mysqli_fetch_assoc($result);

    $name = $row['username'];
    $genres = $row['genres'];

    // Function to fetch books from database and get their details
    function fetchBooks($db, $userId, $column) {
        $books = [];
        $query = "SELECT `book_link` FROM `user_books` WHERE user_id = $userId AND $column = 1";
        $result = mysqli_query($db, $query)
        or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $bookLink = $row['book_link'];
            if (!empty($bookLink)) {
                $bookDetails = getBookDetailsFromAPI($bookLink);
                if ($bookDetails) {
                    $books[] = $bookDetails;
                }
            }
        }
        return $books;
    }


    $readBooks = fetchBooks($db, $user_id, "is_read");
    $unreadBooks = fetchBooks($db, $user_id, "is_unread");
    $readingBooks = fetchBooks($db, $user_id, "is_reading");
    $favoriteBooks = fetchBooks($db, $user_id, "is_favorite");
    $discardedBooks = fetchBooks($db, $user_id, "is_discarded");
    $recommendedBooks = fetchBooks($db, $user_id, "is_recommended");

    // Helper function to format book details for AI
    function formatBooksForAI($books) {
        if (empty($books)) {
            return "None";
        }
        $formatted = [];
        foreach ($books as $book) {
            $formatted[] = "\"" . $book['title'] . "\" by " . $book['author'] . " (" . $book['genre'] . ")";
        }
        return implode(", ", $formatted);
    }

    // System prompt with detailed book information
    $systemPrompt = "The user has the following reading preferences: " .
        "Favorite genres: " . $genres . ". " .
        "Books read: " . formatBooksForAI($readBooks) . ". " .
        "Books to read: " . formatBooksForAI($unreadBooks) . ". " .
        "Books currently reading: " . formatBooksForAI($readingBooks) . ". " .
        "Favorite books: " . formatBooksForAI($favoriteBooks) . ". " .
        "Discarded books: " . formatBooksForAI($discardedBooks) . ". " .
        "Previously recommended books: " . formatBooksForAI($recommendedBooks) . ". " .
        "Based on this detailed reading history, provide 6 personalized book recommendations. " .
        "Make sure to only recommend books that are NOT already in the user's library. " .
        "Consider the genres, authors, and themes from their reading history. " .
        "Dont give me the same books twice or different editions of the same book, recommend me new books. " .
        "Provide the book title and author separated by a pipe character (|). " .
        "Do NOT include numbers, bullets, or any prefixes before the book titles. " .
        "Format: 
        Book Title|Author Name
        Another Book Title|Another Author Name
        Yet Another Book|Another Author
        Fourth Book Title|Fourth Author
        Fifth Book Title|Fifth Author
        Sixth Book Title|Sixth Author";

    // Get API key
    $env = parse_ini_file(__DIR__ . "/.env");
    $apiKey = $env["OPENAI_API_KEY"] ?? "";

    if (empty($apiKey)) {
        return []; // Return empty array if no API key
    }

    // Call OpenAI API
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
            ["role" => "user", "content" => "Can you give me 6 personalized book recommendations based on my reading history?"]
        ]
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        return [];
    }

    $data = json_decode($response, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        return [];
    }

    $content = $data['choices'][0]['message']['content'];
    
    // Extract book titles and authors from response
    $bookData = [];
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '|') !== false) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2) {
                $title = trim($parts[0]);
                $author = trim($parts[1]);
                
                // Remove any numbers, bullets, or prefixes from the title
                $title = preg_replace('/^[\d\.\-\*\•\s]+/', '', $title);
                $title = trim($title);
                
                // Remove any numbers, bullets, or prefixes from the author
                $author = preg_replace('/^[\d\.\-\*\•\s]+/', '', $author);
                $author = trim($author);
                
                if (!empty($title) && !empty($author)) {
                    $bookData[] = ['title' => $title, 'author' => $author];
                }
            }
        }
    }

    // Convert to book objects with placeholder covers
    $recommendations = [];
    foreach (array_slice($bookData, 0, 6) as $book) {
        $recommendations[] = [
            'title' => $book['title'],
            'author' => $book['author'],
            'cover_url' => 'https://placehold.co/150x220/5F6F52/fff?text=' . urlencode(substr($book['title'], 0, 15)),
            'description' => 'AI Recommended Book',
            'preview_link' => '#'
        ];
    }

    return $recommendations;
}


// Enhanced cache system with 5-minute intervals
function getCachedRecommendations($user_id, $db) {
    $cacheFile = __DIR__ . "/cache/recommendations_$user_id.json";
    $cacheTime = 300; // 5 minutes cache (300 seconds)
    
    // Create cache directory if it doesn't exist
    if (!is_dir(__DIR__ . "/cache")) {
        mkdir(__DIR__ . "/cache", 0755, true);
    }
    
    // Check if cache file exists and is still valid
    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        
        if ($cacheAge < $cacheTime) {
            // Cache is still valid
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && !empty($cached)) {
                // Handle both old and new cache formats
                if (isset($cached['recommendations'])) {
                    // New format with metadata
                    error_log("CACHE HIT: Using cached recommendations for user $user_id (cache age: {$cacheAge}s)");
                    return $cached['recommendations'];
                } else if (is_array($cached) && count($cached) > 0) {
                    // Old format (direct array)
                    error_log("CACHE HIT: Using cached recommendations for user $user_id (cache age: {$cacheAge}s)");
                    return $cached;
                }
            }
        } else {
            // Cache is expired, log it
            error_log("CACHE EXPIRED: Cache for user $user_id is {$cacheAge}s old (limit: {$cacheTime}s)");
        }
    } else {
        error_log("CACHE MISS: No cache file found for user $user_id");
    }
    
    // Cache miss or expired - get new recommendations
    error_log("CACHE MISS: Calling AI for user $user_id");
    $recommendations = getAIRecommendations($user_id, $db);
    
    // Save new recommendations to cache
    $cacheData = [
        'timestamp' => time(),
        'user_id' => $user_id,
        'recommendations' => $recommendations,
        'cache_duration' => $cacheTime
    ];
    
    $success = file_put_contents($cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    
    if ($success) {
        error_log("CACHE SAVED: Recommendations cached for user $user_id");
    } else {
        error_log("CACHE ERROR: Failed to save recommendations for user $user_id");
    }
    
    return $recommendations;
}

// Function to load only cached recommendations (no API calls)
function getCachedRecommendationsOnly($user_id, $db) {
    $cacheFile = __DIR__ . "/cache/recommendations_$user_id.json";
    
    // Create cache directory if it doesn't exist
    if (!is_dir(__DIR__ . "/cache")) {
        mkdir(__DIR__ . "/cache", 0755, true);
    }
    
    // Check if cache file exists
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && !empty($cached)) {
            // Handle both old and new cache formats
            if (isset($cached['recommendations'])) {
                error_log("LOADING CACHED: Using cached recommendations for user $user_id");
                return $cached['recommendations'];
            } else if (is_array($cached) && count($cached) > 0) {
                error_log("LOADING CACHED: Using cached recommendations for user $user_id");
                return $cached;
            }
        }
    }
    
    // Return empty array if no cache (will be populated by AJAX)
    error_log("NO CACHE: Returning empty recommendations for user $user_id");
    return [];
}

// Load cached recommendations immediately (for fast page load)
$aiRecommendations = getCachedRecommendationsOnly($user_id, $db);

// Function to get books for specific shelf with cached details
function getBooksForShelf($db, $user_id, $column) {
    $books = [];
    $query = "SELECT `book_link` FROM `user_books` WHERE user_id = $user_id AND $column = 1";
    $result = mysqli_query($db, $query)
    or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bookLink = $row['book_link'];
        if (!empty($bookLink)) {
            // Try to get cached book details first
            $bookDetails = getCachedBookDetails($bookLink);
            if ($bookDetails) {
                // Add the API link to the book details
                $bookDetails['api_link'] = $bookLink;
                $books[] = $bookDetails;
            }
        }
    }
    return $books;
}

// Function to get cached book details (with 24-hour cache)
function getCachedBookDetails($bookLink) {
    // Create a safe filename from the book link
    $cacheKey = md5($bookLink);
    $cacheFile = __DIR__ . "/cache/book_details_{$cacheKey}.json";
    $cacheTime = 86400; // 24 hours cache
    
    // Create cache directory if it doesn't exist
    if (!is_dir(__DIR__ . "/cache")) {
        mkdir(__DIR__ . "/cache", 0755, true);
    }
    
    // Check if cache exists and is still valid
    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        if ($cacheAge < $cacheTime) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && !empty($cached)) {
                return $cached;
            }
        }
    }
    
    // Cache miss or expired - get fresh data
    $bookDetails = getBookDetailsFromAPI($bookLink);
    if ($bookDetails) {
        // Save to cache
        file_put_contents($cacheFile, json_encode($bookDetails, JSON_PRETTY_PRINT));
    }
    
    return $bookDetails;
}

// Fast loading mode - set to true to skip API calls completely
$fastLoadingMode = isset($_GET['fast']) && $_GET['fast'] === '1';

if ($fastLoadingMode) {
    // Fast mode: Load only book links without API details
    $readingBooks = getBooksForShelfFast($db, $user_id, "is_reading");
    $unreadBooks = getBooksForShelfFast($db, $user_id, "is_unread");
    $readBooks = getBooksForShelfFast($db, $user_id, "is_read");
    $discardedBooks = getBooksForShelfFast($db, $user_id, "is_discarded");
    $favoriteBooks = getBooksForShelfFast($db, $user_id, "is_favorite");
} else {
    // Normal mode: Load with API details but with timeout protection
    $startTime = microtime(true);
    $maxLoadTime = 10; // Maximum 10 seconds for loading book details

    $readingBooks = getBooksForShelfWithTimeout($db, $user_id, "is_reading", $startTime, $maxLoadTime);
    $unreadBooks = getBooksForShelfWithTimeout($db, $user_id, "is_unread", $startTime, $maxLoadTime);
    $readBooks = getBooksForShelfWithTimeout($db, $user_id, "is_read", $startTime, $maxLoadTime);
    $discardedBooks = getBooksForShelfWithTimeout($db, $user_id, "is_discarded", $startTime, $maxLoadTime);
    $favoriteBooks = getBooksForShelfWithTimeout($db, $user_id, "is_favorite", $startTime, $maxLoadTime);
}

// Fast loading function (no API calls)
function getBooksForShelfFast($db, $user_id, $column) {
    $books = [];
    $query = "SELECT `book_link` FROM `user_books` WHERE user_id = $user_id AND $column = 1";
    $result = mysqli_query($db, $query)
    or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bookLink = $row['book_link'];
        if (!empty($bookLink)) {
            // Create basic book entry without API call
            $books[] = [
                'title' => 'Book from Collection',
                'author' => 'Loading details...',
                'genre' => 'Unknown',
                'cover_url' => 'https://placehold.co/150x220/5F6F52/fff?text=Book',
                'description' => 'Book details will load in background',
                'preview_link' => '#',
                'api_link' => $bookLink
            ];
        }
    }
    return $books;
}

// Function with timeout protection
function getBooksForShelfWithTimeout($db, $user_id, $column, $startTime, $maxLoadTime) {
    $books = [];
    $query = "SELECT `book_link` FROM `user_books` WHERE user_id = $user_id AND $column = 1";
    $result = mysqli_query($db, $query)
    or die('Error: ' . mysqli_error($db) . ' with query ' . $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if we're taking too long
        if ((microtime(true) - $startTime) > $maxLoadTime) {
            error_log("TIMEOUT: Stopping book loading after {$maxLoadTime} seconds");
            break;
        }
        
        $bookLink = $row['book_link'];
        if (!empty($bookLink)) {
            $bookDetails = getCachedBookDetails($bookLink);
            if ($bookDetails) {
                $bookDetails['api_link'] = $bookLink;
                $books[] = $bookDetails;
            } else {
                // If API call fails, create a basic book entry
                $books[] = [
                    'title' => 'Loading...',
                    'author' => 'Please wait',
                    'genre' => 'Unknown',
                    'cover_url' => 'https://placehold.co/150x220/5F6F52/fff?text=Loading',
                    'description' => 'Book details are loading',
                    'preview_link' => '#',
                    'api_link' => $bookLink
                ];
            }
        }
    }
    return $books;
}

// --- DATA ---
//php array om database te simuleren, dit kan straks vervangen worden door database logic.

$shelves = [
        [
                'title' => 'Plank 1: Boeken die je aan het lezen bent',
        'books' => $readingBooks
        ],
        [
                'title' => 'Plank 2: Boeken die je wil lezen',
        'books' => $unreadBooks
    ],
    [
        'title' => 'Plank 3: Boeken die je hebt gelezen',
        'books' => $readBooks
    ],
    [
        'title' => 'Plank 4: Boeken die je niet meer wilt lezen',
        'books' => $discardedBooks
    ],
    [
        'title' => 'Plank 5: Je favoriete boeken',
        'books' => $favoriteBooks
    ],
    [
        'title' => 'Plank 6: Aanbevolen voor jou (AI)',
        'books' => $aiRecommendations
        ]
];
?>

<?php
// AJAX endpoint for fetching fresh recommendations
if (isset($_GET['action']) && $_GET['action'] === 'getFreshRecommendations') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['username'])) {
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    
    $username = $_SESSION['username'];
    $sql = "SELECT id FROM `users` WHERE username = '$username'";
    $result_users = mysqli_query($db, $sql)
    or die('Error ' . mysqli_error($db) . ' with query ' . $sql);
    $user = mysqli_fetch_assoc($result_users);
    $user_id = $user['id'];
    
    // Get fresh recommendations (this will make API calls if needed)
    $freshRecommendations = getCachedRecommendations($user_id, $db);
    
    echo json_encode([
        'success' => true,
        'recommendations' => $freshRecommendations
    ]);
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
    <!-- <script src="../js/AI.js" defer></script> -->
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
            width: 140px;
            height: 200px;
            background: linear-gradient(135deg, var(--cover-bg) 0%, #9A937A 100%);
            border-radius: 8px;
            flex-shrink: 0;
            overflow: hidden;
            transition: transform 0.2s ease-in-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .book-cover:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .book-cover-text {
            text-align: center;
            color: var(--text-color);
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 8px;
        }

        .book-title {
            font-weight: bold;
            font-size: 0.85em;
            line-height: 1.2;
            margin-bottom: 6px;
            word-wrap: break-word;
            hyphens: auto;
            max-width: 100%;
            overflow-wrap: break-word;
        }

        .book-author {
            font-size: 0.7em;
            color: #5c5542;
            line-height: 1.1;
            word-wrap: break-word;
            hyphens: auto;
            max-width: 100%;
            overflow-wrap: break-word;
            font-style: italic;
        }

        .book-cover.has-image .book-cover-text {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 12px 8px 8px 8px;
            border-radius: 0 0 6px 6px;
        }

        .book-cover.has-image .book-author {
            color: rgba(255, 255, 255, 0.9);
        }

        .book-cover.clickable {
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .book-cover.clickable:hover {
            transform: scale(1.08);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .book-cover.clickable:hover .book-title {
            color: #2c5530;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .book-cover.clickable:hover .book-author {
            color: #5c5542;
        }

        /* AI Loading Indicator */
        .ai-loading-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 0.9em;
            font-style: italic;
            opacity: 0.8;
        }

        .ai-loading-indicator i {
            color: #4CAF50;
            font-size: 1.1em;
        }

        .ai-loading-indicator.show {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        /* --- Custom Scrollbar Styling --- */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-sidebar);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--bg-container) 0%, #5A5440 100%);
            border-radius: 6px;
            border: 2px solid var(--bg-sidebar);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--bg-sidebar-active) 0%, var(--bg-container) 100%);
        }

        ::-webkit-scrollbar-corner {
            background: var(--bg-sidebar);
        }

        /* Firefox scrollbar styling */
        html {
            scrollbar-width: thin;
            scrollbar-color: var(--bg-container) var(--bg-sidebar);
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
            <a href="../includes/logout.php" class="log-out">
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
                        <?php if ($shelf['title'] === 'Plank 6: Aanbevolen voor jou (AI)'): ?>
                            <div class="ai-loading-indicator" id="aiLoadingIndicator" style="display: none;">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                <span>AI is fetching fresh recommendations...</span>
                            </div>
                        <?php endif; ?>
                        <a href="#">&gt;</a>
                    </div>
                    <div class="book-list">
                        <?php foreach ($shelf['books'] as $book): ?>
                            <?php 
                                $hasImage = !empty($book['cover_url']) && strpos($book['cover_url'], 'placehold.co') === false;
                                $coverClass = $hasImage ? 'book-cover has-image' : 'book-cover';
                                
                                // Create search URL for AI recommendations shelf only
                                $isRecommendationShelf = $shelf['title'] === 'Plank 6: Aanbevolen voor jou (AI)';
                                if ($isRecommendationShelf) {
                                    $searchUrl = 'booklist.php?search=' . urlencode($book['title']);
                                    $clickable = true;
                                } else {
                                    $searchUrl = '#';
                                    $clickable = false;
                                }
                            ?>
                            <?php if ($clickable): ?>
                                <a href="<?= $searchUrl ?>" style="text-decoration: none;">
                            <?php endif; ?>
                            <div class="<?= $coverClass ?><?= $clickable ? ' clickable' : '' ?>">
                                <?php if ($hasImage): ?>
                                    <img src="<?= htmlspecialchars($book['cover_url']) ?>" 
                                         alt="<?= htmlspecialchars($book['title'] ?? 'Book cover') ?>">
                                <?php endif; ?>
                                <div class="book-cover-text">
                                    <div class="book-title"><?= htmlspecialchars($book['title'] ?? 'Unknown Title') ?></div>
                                    <div class="book-author">by <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?></div>
                                </div>
                            </div>
                            <?php if ($clickable): ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <!-- <div id="chat-widget" class="collapsed">
            <div id="chat-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-chat-dots"
                     viewBox="0 0 16 16">
                    <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                    <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125m.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6-3.004 6-7 6a8 8 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a11 11 0 0 0 .398-2"/>
                </svg>
                <span id="chat-title" style="display:none;">Luna</span>
            </div>
            <div id="chat-box"></div>
            <div id="chat-input">
            </div>
        </div> -->
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if recommendations need refreshing and fetch fresh ones
    setTimeout(function() {
        fetchFreshRecommendations();
    }, 1000); // Wait 1 second after page load
});

async function fetchFreshRecommendations() {
    const loadingIndicator = document.getElementById('aiLoadingIndicator');
    const aiShelf = document.querySelector('.shelf:nth-child(6) .book-list');
    
    try {
        // Show loading indicator
        if (loadingIndicator) {
            loadingIndicator.style.display = 'flex';
            loadingIndicator.classList.add('show');
        }
        
        // Dim the shelf slightly
        if (aiShelf) {
            aiShelf.style.opacity = '0.8';
            aiShelf.style.transition = 'opacity 0.3s ease';
        }
        
        const response = await fetch('boekenkast.php?action=getFreshRecommendations');
        const data = await response.json();
        
        if (data.success && data.recommendations && data.recommendations.length > 0) {
            updateRecommendationsDisplay(data.recommendations);
        }
        
        // Hide loading indicator
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
            loadingIndicator.classList.remove('show');
        }
        
        // Restore shelf opacity
        if (aiShelf) {
            aiShelf.style.opacity = '1';
        }
        
    } catch (error) {
        console.error('Error fetching fresh recommendations:', error);
        
        // Hide loading indicator even on error
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
            loadingIndicator.classList.remove('show');
        }
        
        // Restore shelf opacity
        if (aiShelf) {
            aiShelf.style.opacity = '1';
        }
    }
}

function updateRecommendationsDisplay(recommendations) {
    const aiShelf = document.querySelector('.shelf:nth-child(6) .book-list');
    if (!aiShelf) return;
    
    // Clear existing books
    aiShelf.innerHTML = '';
    
    // Add new recommendations
    recommendations.forEach(function(book) {
        const bookElement = createBookElement(book);
        aiShelf.appendChild(bookElement);
    });
    
    // Add smooth animation
    aiShelf.style.opacity = '0';
    setTimeout(() => {
        aiShelf.style.opacity = '1';
    }, 100);
}

function createBookElement(book) {
    const hasImage = book.cover_url && !book.cover_url.includes('placehold.co');
    const coverClass = hasImage ? 'book-cover has-image' : 'book-cover';
    const isRecommendationShelf = true; // This is always true for AI recommendations
    
    let html = '';
    if (isRecommendationShelf) {
        const searchUrl = 'booklist.php?search=' + encodeURIComponent(book.title);
        html += '<a href="' + searchUrl + '" style="text-decoration: none;">';
    }
    
    html += '<div class="' + coverClass + (isRecommendationShelf ? ' clickable' : '') + '">';
    
    if (hasImage) {
        html += '<img src="' + book.cover_url + '" alt="' + (book.title || 'Book cover') + '">';
    }
    
    html += '<div class="book-cover-text">';
    html += '<div class="book-title">' + (book.title || 'Unknown Title') + '</div>';
    html += '<div class="book-author">by ' + (book.author || 'Unknown Author') + '</div>';
    html += '</div>';
    html += '</div>';
    
    if (isRecommendationShelf) {
        html += '</a>';
    }
    
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.firstChild;
}
</script>

</body>
</html>