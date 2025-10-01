<?php
/** @var mysqli $db */
require_once '../includes/database.php';
session_start();

$current_user_id = $_SESSION['user_id'] ?? 0;

// Handle AJAX profile update requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['action']) && $data['action'] === 'updateProfile') {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
            exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $field = $data['field'] ?? '';
        $value = $data['value'] ?? '';
        
        // Whitelist allowed fields
        $allowed_fields = ['description', 'taste', 'genres'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'error' => 'Ongeldig veld']);
            exit;
        }
        
        // Sanitize and update
        $value = mysqli_real_escape_string($db, $value);
        $query = "UPDATE `users` SET `$field` = '$value' WHERE `id` = $user_id";
        
        if (mysqli_query($db, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
        }
        exit;
    }
}

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

// Function to get cached book details (with 24-hour cache)
function getCachedBookDetails($bookLink)
{
    $cacheKey = md5($bookLink);
    $cacheFile = __DIR__ . "/cache/book_details_{$cacheKey}.json";
    $cacheTime = 86400; // 24 hours cache

    if (!is_dir(__DIR__ . "/cache")) {
        mkdir(__DIR__ . "/cache", 0755, true);
    }

    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        if ($cacheAge < $cacheTime) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && !empty($cached)) {
                return $cached;
            }
        }
    }

    return null; // Return null if no cache (we don't want to make API calls on profile page)
}

// Function to get recommended books for a user
function getRecommendedBooks($db, $user_id)
{
    $books = [];
    $query = "SELECT `book_link` FROM `user_books` WHERE user_id = $user_id AND is_recommended = 1 LIMIT 6";
    $result = mysqli_query($db, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $bookLink = $row['book_link'];
            if (!empty($bookLink)) {
                $bookDetails = getCachedBookDetails($bookLink);
                if ($bookDetails) {
                    $bookDetails['api_link'] = $bookLink;
                    $books[] = $bookDetails;
                } else {
                    // Fallback for books without cache
                    $books[] = [
                        'title' => 'Recommended Book',
                        'author' => 'Loading...',
                        'genre' => 'Unknown',
                        'cover_url' => 'https://placehold.co/150x220/5F6F52/fff?text=Book',
                        'description' => '',
                        'preview_link' => '#',
                        'api_link' => $bookLink
                    ];
                }
            }
        }
    }

    return $books;
}

// Get recommended books for this profile
$recommendedBooks = getRecommendedBooks($db, $userID);
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

    /* Profile editing styles */
    .edit-btn {
        display: inline-block;
        padding: 5px 10px;
        margin-left: 10px;
        background-color: #8B836B;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9em;
        transition: 0.2s ease;
    }

    .edit-btn:hover {
        background-color: #6B654F;
    }

    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: var(--bg-container);
        margin: 10% auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
    }

    .modal-content h2 {
        color: var(--text-light);
        margin-bottom: 20px;
    }

    .modal-content label {
        display: block;
        color: var(--text-light);
        margin-bottom: 8px;
        font-weight: bold;
    }

    .modal-content textarea {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 2px solid var(--bg-sidebar);
        background-color: var(--search-bg);
        color: var(--text-color);
        font-family: inherit;
        font-size: 1em;
        resize: vertical;
        min-height: 100px;
    }

    .modal-content input[type="text"] {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 2px solid var(--bg-sidebar);
        background-color: var(--search-bg);
        color: var(--text-color);
        font-family: inherit;
        font-size: 1em;
    }

    .modal-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: flex-end;
    }

    .modal-buttons button {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .save-btn {
        background-color: #ffb6c1;
        color: white;
    }

    .save-btn:hover {
        background-color: #ff8fab;
    }

    .cancel-btn {
        background-color: var(--bg-sidebar);
        color: var(--text-color);
    }

    .cancel-btn:hover {
        background-color: var(--bg-sidebar-active);
    }

    /* Bookshelf styles */
    .recommended-shelf {
        margin-top: 30px;
    }

    .shelf-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding: 0 10px;
    }

    .shelf-header h3 {
        color: var(--text-light);
        font-size: 1.3em;
    }

    .book-list {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding: 20px 10px;
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .book-cover {
        position: relative;
        width: 150px;
        min-width: 150px;
        height: 220px;
        background: linear-gradient(135deg, #8B7355 0%, #6B5A42 100%);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .book-cover:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
    }

    .book-cover.has-image {
        background: transparent;
        padding: 0;
    }

    .book-cover.has-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }

    .book-cover-text {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 15px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.7) 70%, transparent 100%);
        color: white;
    }

    .book-cover.has-image .book-cover-text {
        background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.8) 70%, transparent 100%);
    }

    .book-title {
        font-weight: bold;
        font-size: 0.9em;
        margin-bottom: 5px;
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .book-author {
        font-size: 0.75em;
        opacity: 0.9;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .book-cover.clickable {
        cursor: pointer;
    }

    .no-books-message {
        padding: 40px;
        text-align: center;
        color: var(--text-light);
        opacity: 0.7;
        font-style: italic;
    }

    /* Custom scrollbar for book list */
    .book-list::-webkit-scrollbar {
        height: 8px;
    }

    .book-list::-webkit-scrollbar-track {
        background: var(--bg-sidebar);
        border-radius: 4px;
    }

    .book-list::-webkit-scrollbar-thumb {
        background: var(--bg-container);
        border-radius: 4px;
    }

    .book-list::-webkit-scrollbar-thumb:hover {
        background: var(--bg-sidebar-active);
    }

    /* Book Details Modal */
    .book-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
    }

    .book-modal-content {
        background-color: var(--bg-container);
        margin: 5% auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 700px;
        max-height: 85vh;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.5);
        position: relative;
        color: var(--text-light);
    }

    .book-modal-content .close {
        color: var(--text-light);
        float: right;
        font-size: 32px;
        font-weight: bold;
        cursor: pointer;
        line-height: 20px;
    }

    .book-modal-content .close:hover {
        color: #ffb6c1;
    }

    .book-modal-content h2 {
        color: var(--text-light);
        margin-bottom: 15px;
        margin-top: 10px;
        font-size: 1.3em;
        display: block;
        clear: both;
    }

    .book-modal-content .detailsAuthor {
        color: var(--text-light);
        margin-bottom: 20px;
        margin-top: 0;
        opacity: 0.9;
        font-size: 0.9em;
        display: block;
    }

    .book-modal-content .detailsContainer {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .book-modal-content .detailsImg {
        width: 150px;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }

    .book-modal-content .detailsDescription {
        flex: 1;
        color: var(--text-light);
        line-height: 1.6;
        max-height: 200px;
        overflow-y: auto;
    }

    .book-modal-content .detailsDescription::-webkit-scrollbar {
        width: 6px;
    }

    .book-modal-content .detailsDescription::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .book-modal-content .detailsDescription::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .book-modal-content .detailsDescription::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .book-modal-content .detailsDescriptionTitle {
        display: block;
        margin-bottom: 10px;
        color: #ffb6c1;
    }

    .book-modal-content p {
        color: var(--text-light);
        margin-bottom: 10px;
    }

    .book-modal-content strong {
        color: #ffb6c1;
    }

    .book-status-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 25px;
    }

    .detailPageButton {
        padding: 10px 20px;
        background-color: var(--bg-sidebar);
        color: var(--text-light);
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        font-size: 0.9em;
        transition: background-color 0.2s;
    }

    .detailPageButton:hover:not(:disabled) {
        background-color: var(--bg-sidebar-active);
    }

    .detailPageButton:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .detailPageButton.remove-btn {
        background-color: #8B4513;
    }

    .detailPageButton.remove-btn:hover {
        background-color: #A0522D;
    }

    #addToShelfBtn {
        background-color: #ffb6c1;
        width: 100%;
    }

    #addToShelfBtn:hover {
        background-color: #ff8fab;
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
                        <h3>Over mij
                            <?php if ($current_user_id == $userID): ?>
                                <button class="edit-btn" onclick="openEditModal('description')">✏️ Bewerken</button>
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($description)): ?>
                            <p id="description-text"><?php echo nl2br(htmlspecialchars($description)); ?></p>
                        <?php else: ?>
                            <p id="description-text">
                                <?php if ($current_user_id == $userID): ?>
                                    Je hebt nog niets ingevuld over jezelf.
                        <?php else: ?>
                                    Nog geen beschrijving toegevoegd.
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </section>

                    <section class="my-taste">
                        <h3>Mijn smaak
                            <?php if ($current_user_id == $userID): ?>
                                <button class="edit-btn" onclick="openEditModal('taste')">✏️ Bewerken</button>
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($taste)): ?>
                            <p id="taste-text"><?php echo nl2br(htmlspecialchars($taste)); ?></p>
                        <?php else: ?>
                            <p id="taste-text">
                                <?php if ($current_user_id == $userID): ?>
                                    Je hebt nog geen smaakprofiel toegevoegd.
                        <?php else: ?>
                                    Nog geen smaakprofiel toegevoegd.
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </section>

                    <section class="genres">
                        <h3>Favoriete genres
                            <?php if ($current_user_id == $userID): ?>
                                <button class="edit-btn" onclick="openEditModal('genres')">✏️ Bewerken</button>
                            <?php endif; ?>
                        </h3>
                        <?php if (!empty($genres)): ?>
                            <p id="genres-text"><?php echo htmlspecialchars($genres); ?></p>
                        <?php else: ?>
                            <p id="genres-text">
                                <?php if ($current_user_id == $userID): ?>
                                    Je hebt nog geen favoriete genres gekozen.
                        <?php else: ?>
                                    Nog geen favoriete genres gekozen.
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </section>
                </div>

                <!-- Recommended Books Shelf -->
                <div class="cover-background recommended-shelf">
                    <div class="shelf-header">
                        <h3><?php echo htmlspecialchars($name); ?> beveelt deze boeken aan.</h3>
                    </div>
                    <?php if (count($recommendedBooks) > 0): ?>
                        <div class="book-list">
                            <?php foreach ($recommendedBooks as $book): ?>
                                <?php
                                $hasImage = !empty($book['cover_url']) && strpos($book['cover_url'], 'placehold.co') === false;
                                $coverClass = $hasImage ? 'book-cover has-image clickable' : 'book-cover clickable';
                                ?>
                                <div class="<?= $coverClass ?>" 
                                     onclick="showBookDetails('<?= htmlspecialchars($book['api_link'], ENT_QUOTES) ?>')">
                                    <?php if ($hasImage): ?>
                                        <img src="<?= htmlspecialchars($book['cover_url']) ?>"
                                             alt="<?= htmlspecialchars($book['title'] ?? 'Book cover') ?>">
                                    <?php endif; ?>
                                    <div class="book-cover-text">
                                        <div class="book-title"><?= htmlspecialchars($book['title'] ?? 'Unknown Title') ?></div>
                                        <div class="book-author">
                                            by <?= htmlspecialchars($book['author'] ?? 'Unknown Author') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-books-message">
                            <?php if ($current_user_id == $userID): ?>
                                Je hebt nog geen boeken aangeraden. Ga naar je <a href="booklist.php" style="color: #ffb6c1;">leeslijst</a> om boeken aan te bevelen!
                            <?php else: ?>
                                <?php echo htmlspecialchars($name); ?> heeft nog geen boeken aangeraden.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </section>
    </main>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Bewerken</h2>
        <label for="editInput" id="editLabel">Waarde:</label>
        <textarea id="editInput"></textarea>
        <div class="modal-buttons">
            <button class="cancel-btn" onclick="closeEditModal()">Annuleren</button>
            <button class="save-btn" onclick="saveEdit()">Opslaan</button>
        </div>
    </div>
</div>

<!-- Book Details Modal -->
<div id="bookModal" class="book-modal">
    <div class="book-modal-content" id="bookModalContent">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<script>
    let currentField = '';
    const fieldData = {
        description: {
            title: 'Over mij bewerken',
            label: 'Beschrijving:',
            placeholder: 'Vertel iets over jezelf...'
        },
        taste: {
            title: 'Mijn smaak bewerken',
            label: 'Smaakprofiel:',
            placeholder: 'Wat voor boeken lees je graag?'
        },
        genres: {
            title: 'Favoriete genres bewerken',
            label: 'Genres:',
            placeholder: 'Bijvoorbeeld: Fantasy, Thriller, Science Fiction'
        }
    };

    function openEditModal(field) {
        currentField = field;
        const modal = document.getElementById('editModal');
        const title = document.getElementById('modalTitle');
        const label = document.getElementById('editLabel');
        const input = document.getElementById('editInput');
        
        // Set modal content based on field
        title.textContent = fieldData[field].title;
        label.textContent = fieldData[field].label;
        input.placeholder = fieldData[field].placeholder;
        
        // Get current value from the page
        const currentText = document.getElementById(field + '-text').textContent.trim();
        // Don't prefill if it's the default empty message
        if (currentText.includes('nog niets') || currentText.includes('nog geen')) {
            input.value = '';
        } else {
            input.value = currentText;
        }
        
        modal.style.display = 'block';
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.style.display = 'none';
        currentField = '';
        document.getElementById('editInput').value = '';
    }

    function saveEdit() {
        const value = document.getElementById('editInput').value;
        
        // Send to backend
        fetch('profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'updateProfile',
                field: currentField,
                value: value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the page without reload
                const textElement = document.getElementById(currentField + '-text');
                if (value.trim() === '') {
                    textElement.textContent = fieldData[currentField].placeholder.replace('Bijvoorbeeld: ', 'Je hebt nog geen ').toLowerCase() + ' toegevoegd.';
                } else {
                    // Preserve line breaks for description and taste
                    if (currentField === 'description' || currentField === 'taste') {
                        textElement.innerHTML = value.replace(/\n/g, '<br>');
                    } else {
                        textElement.textContent = value;
                    }
                }
                closeEditModal();
            } else {
                alert('Er is iets misgegaan: ' + (data.error || 'Onbekende fout'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het opslaan.');
        });
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const editModal = document.getElementById('editModal');
        const bookModal = document.getElementById('bookModal');
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == bookModal) {
            closeBookModal();
        }
    }

    // Book Details Modal Functions
    function showBookDetails(apiLink) {
        fetch(apiLink)
            .then(response => response.json())
            .then(book => {
                displayBookModal(book);
            })
            .catch(error => {
                console.error('Error fetching book details:', error);
                alert('Er is een fout opgetreden bij het ophalen van de boekgegevens.');
            });
    }

    function displayBookModal(book) {
        const title = book.volumeInfo.title || 'Geen titel';
        const authors = book.volumeInfo.authors;
        const description = book.volumeInfo.description;
        const imageLinks = book.volumeInfo.imageLinks;
        const pageCount = book.volumeInfo.pageCount;
        const categories = book.volumeInfo.categories;
        const language = book.volumeInfo.language;
        const publishedDate = book.volumeInfo.publishedDate;
        const bookLink = book.selfLink;

        checkBookInCollection(bookLink, function(bookData) {
            const isInCollection = bookData.exists;
            const currentStatus = bookData.status;
            const isRecommended = bookData.isRecommended;

            getRecommendedCount(function(countData) {
                let buttonHTML = '';
                if (isInCollection) {
                    buttonHTML = '<div class="book-status-buttons">';
                    if (currentStatus !== 'unread') {
                        buttonHTML += '<button class="detailPageButton" onclick="changeBookStatus(\'' + bookLink + '\', \'unread\')">📚 Te lezen</button>';
                    }
                    if (currentStatus !== 'read') {
                        buttonHTML += '<button class="detailPageButton" onclick="changeBookStatus(\'' + bookLink + '\', \'read\')">✓ Gelezen</button>';
                    }
                    if (currentStatus !== 'reading') {
                        buttonHTML += '<button class="detailPageButton" onclick="changeBookStatus(\'' + bookLink + '\', \'reading\')">📖 Bezig</button>';
                    }
                    if (currentStatus !== 'discarded') {
                        buttonHTML += '<button class="detailPageButton" onclick="changeBookStatus(\'' + bookLink + '\', \'discarded\')">❌ Gestopt</button>';
                    }
                    if (currentStatus !== 'favorite') {
                        buttonHTML += '<button class="detailPageButton" onclick="changeBookStatus(\'' + bookLink + '\', \'favorite\')">⭐ Favoriet</button>';
                    }

                    const isAtLimit = countData.count >= countData.max && !isRecommended;
                    let recommendedButtonText;
                    if (isRecommended) {
                        recommendedButtonText = '💡 Niet meer aanbevelen';
                    } else if (isAtLimit) {
                        recommendedButtonText = '💡 Maximaal 6 aanbevelingen (' + countData.count + '/' + countData.max + ')';
                    } else {
                        recommendedButtonText = '💡 Aanbevolen (' + countData.count + '/' + countData.max + ')';
                    }
                    buttonHTML += '<button class="detailPageButton" onclick="changeBookStatus(\'' + bookLink + '\', \'recommended\')" ' + (isAtLimit ? 'disabled' : '') + '>' + recommendedButtonText + '</button>';
                    buttonHTML += '<button class="detailPageButton remove-btn" onclick="removeBook(\'' + bookLink + '\')">🗑️ Verwijder uit collectie</button>';
                    buttonHTML += '</div>';
                } else {
                    buttonHTML = '<button id="addToShelfBtn" class="detailPageButton" onclick="addBookToCollection(\'' + bookLink + '\')">+ Voeg toe aan leeslijst</button>';
                }

                const modalContent = document.getElementById('bookModalContent');
                modalContent.innerHTML =
                    '<span class="close" onclick="closeBookModal()">&times;</span>' +
                    '<h2>' + title + '</h2>' +
                    (authors ? '<p class="detailsAuthor"><strong>Auteur(s):</strong> ' + authors.join(', ') + '</p>' : '') +
                    '<div class="detailsContainer">' +
                    (imageLinks && imageLinks.thumbnail ? '<img class="detailsImg" src="' + imageLinks.thumbnail + '" alt="' + title + '">' : '') +
                    '<div class="detailsDescription"><strong class="detailsDescriptionTitle">Samenvatting:</strong>' +
                    (description || 'Geen beschrijving beschikbaar.') +
                    '</div></div>' +
                    '<p><strong>Genre(s):</strong> ' + (categories ? categories.slice(0, 2).join(', ') : 'Niet beschikbaar') + '</p>' +
                    '<p><strong>Pagina\'s:</strong> ' + (pageCount || 'Informatie niet beschikbaar') + '</p>' +
                    '<p><strong>Taal:</strong> ' + (language || 'Niet beschikbaar') + '</p>' +
                    '<p><strong>Release datum:</strong> ' + (publishedDate || 'Niet beschikbaar') + '</p>' +
                    buttonHTML;

                document.getElementById('bookModal').style.display = 'block';
            });
        });
    }

    function closeBookModal() {
        document.getElementById('bookModal').style.display = 'none';
    }

    function checkBookInCollection(apiLink, callback) {
        fetch('/php/booklist.php?action=checkBook&apiLink=' + encodeURIComponent(apiLink))
            .then(response => response.json())
            .then(data => callback(data))
            .catch(error => {
                console.error('Error checking book:', error);
                callback({ exists: false, status: null, isRecommended: false });
            });
    }

    function getRecommendedCount(callback) {
        fetch('/php/booklist.php?action=getRecommendedCount')
            .then(response => response.json())
            .then(data => callback(data))
            .catch(error => {
                console.error('Error getting recommended count:', error);
                callback({ count: 0, max: 6 });
            });
    }

    function addBookToCollection(apiLink) {
        const bookData = new URLSearchParams({
            action: 'addBook',
            apiLink: apiLink
        });

        fetch('/php/booklist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bookData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Boek succesvol toegevoegd aan je collectie!');
                    closeBookModal();
                } else {
                    alert(data.message || 'Er is iets misgegaan bij het toevoegen van het boek.');
                }
            } catch (e) {
                console.error('Parse error:', e, text);
                alert('Er is een fout opgetreden bij het toevoegen van het boek.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het toevoegen van het boek.');
        });
    }

    function changeBookStatus(apiLink, status) {
        const bookData = new URLSearchParams({
            action: 'changeStatus',
            apiLink: apiLink,
            status: status
        });

        fetch('/php/booklist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bookData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Boekstatus bijgewerkt!');
                    // Refresh the modal to show updated buttons
                    showBookDetails(apiLink);
                } else {
                    alert(data.message || 'Er is iets misgegaan bij het bijwerken van de status.');
                }
            } catch (e) {
                console.error('Parse error:', e, text);
                alert('Er is een fout opgetreden bij het bijwerken van de status.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het bijwerken van de status.');
        });
    }

    function removeBook(apiLink) {
        if (!confirm('Weet je zeker dat je dit boek uit je collectie wilt verwijderen?')) {
            return;
        }

        const bookData = new URLSearchParams({
            action: 'removeBook',
            apiLink: apiLink
        });

        fetch('/php/booklist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: bookData
        })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Boek verwijderd uit je collectie!');
                    closeBookModal();
                } else {
                    alert(data.message || 'Er is iets misgegaan bij het verwijderen van het boek.');
                }
            } catch (e) {
                console.error('Parse error:', e, text);
                alert('Er is een fout opgetreden bij het verwijderen van het boek.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het verwijderen van het boek.');
        });
    }
</script>

</body>
</html>