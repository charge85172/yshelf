function showTab(tabName) {
    // Hide all booklist sections
    var allTabs = document.querySelectorAll('.booklist-container > div');
    for (var i = 0; i < allTabs.length; i++) {
        allTabs[i].style.display = 'none';
    }

    // Show the selected tab
    var selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var navButtons = document.querySelectorAll('.booklist-nav button');
    for (var i = 0; i < navButtons.length; i++) {
        navButtons[i].addEventListener('click', function () {
            // Remove active class from all buttons
            for (var j = 0; j < navButtons.length; j++) {
                navButtons[j].classList.remove('active');
            }

            this.classList.add('active');

            var buttonText = this.textContent;
            if (buttonText === 'Te lezen') {
                showTab('booklist-unread');
            } else if (buttonText === 'Bezig') {
                showTab('booklist-reading');
            } else if (buttonText === 'Gelezen') {
                showTab('booklist-read');
            } else if (buttonText === 'Gestopt') {
                showTab('booklist-stopped');
            } else if (buttonText === 'Favorieten') {
                showTab('booklist-favorites');
            }
        });
    }

    showTab('booklist-unread');
    navButtons[0].classList.add('active');
});

// Search functionality for booklist with live results
var searchTimeout; // Variable to store the timeout

// This function handles the search input while typing
function handleBooklistSearchInput() {
    // Clear any existing timeout
    clearTimeout(searchTimeout);

    // Get the search term
    var searchTerm = document.getElementById('bookListSearchInput').value;

    // If there's text, search after a short delay
    if (searchTerm.length > 2) {
        searchTimeout = setTimeout(function () {
            searchBooksAPI(searchTerm);
        }, 300); // Wait 300ms after user stops typing
    } else {
        // Hide results if search term is too short
        hideSearchResults();
    }
}

// This function shows the search results container
function showSearchResults() {
    var resultsContainer = document.querySelector('.booklist-search-results');
    resultsContainer.classList.add('show');
}

// This function hides the search results container
function hideSearchResults() {
    var resultsContainer = document.querySelector('.booklist-search-results');
    resultsContainer.classList.remove('show');
}

// This function calls the Google Books API
function searchBooksAPI(query) {
    var apiUrl = 'https://www.googleapis.com/books/v1/volumes?q=' + encodeURIComponent(query) + '&maxResults=20';

    fetch(apiUrl)
        .then(function (response) {
            if (!response.ok) {
                throw new Error(response.statusText);
            }
            return response.json();
        })
        .then(function (data) {
            showBooklistResults(data);
            showSearchResults(); // Show the results container
        })
        .catch(function (error) {
            console.error('Error:', error);
            document.getElementById('booklistResults').innerHTML = 'Er is een fout opgetreden.';
            showSearchResults();
        });
}

// This function displays the search results
function showBooklistResults(data) {
    var resultsDiv = document.getElementById('booklistResults');
    resultsDiv.innerHTML = ''; // Clear previous results

    // Check if we found any books
    if (data && data.items && data.items.length > 0) {
        // Loop through each book result
        data.items.forEach(function (book) {
            // Get book information
            var title = book.volumeInfo.title || 'Geen titel';
            var authors = book.volumeInfo.authors;
            var imageLinks = book.volumeInfo.imageLinks;
            var bookId = book.id;

            // Create a div for this book
            var bookDiv = document.createElement('div');
            bookDiv.className = 'booklist-search-item';
            bookDiv.dataset.id = bookId;

            // Add book title
            var titleElement = document.createElement('h3');
            titleElement.textContent = title;
            titleElement.className = 'booklist-search-title';
            bookDiv.appendChild(titleElement);

            // Add authors if available
            if (authors) {
                var authorsElement = document.createElement('p');
                authorsElement.textContent = 'Auteur(s): ' + authors.join(', ');
                authorsElement.className = 'booklist-search-authors';
                bookDiv.appendChild(authorsElement);
            }

            // Add book cover if available
            if (imageLinks && imageLinks.thumbnail) {
                var img = document.createElement('img');
                img.src = imageLinks.thumbnail;
                img.alt = title;
                img.className = 'booklist-search-cover';
                bookDiv.appendChild(img);
            }

            // Add click event to show book details
            bookDiv.addEventListener('click', function () {
                showBooklistBookDetails(book);
                hideSearchResults(); // Hide results after clicking
            });

            // Add the book to results
            resultsDiv.appendChild(bookDiv);
        });
    } else {
        // No results found
        resultsDiv.innerHTML = 'Geen resultaten gevonden.';
    }
}

// This function shows book details in a modal
function showBooklistBookDetails(book) {
    var title = book.volumeInfo.title || 'Geen titel';
    var authors = book.volumeInfo.authors;
    var description = book.volumeInfo.description;
    var imageLinks = book.volumeInfo.imageLinks;
    var pageCount = book.volumeInfo.pageCount;
    var categories = book.volumeInfo.categories;
    var language = book.volumeInfo.language;
    var publishedDate = book.volumeInfo.publishedDate;
    var bookLink = book.selfLink;
    console.log(bookLink);


    var modal = document.getElementById('myModal');
    var modalContent = document.getElementById('modalContent');

    // Create the modal content HTML
    modalContent.innerHTML =
        '<span class="close">&times;</span>' +
        '<h2>' + title + '</h2>' +
        (authors ? '<p class="detailsAuthor"><strong>Auteur(s):</strong> ' + authors.join(', ') + '</p>' : '') +
        '<div class="detailsContainer">' +
        (imageLinks && imageLinks.thumbnail ? '<img class="detailsImg" src="' + imageLinks.thumbnail + '" alt="' + title + '">' : '') +
        '<div class="detailsDescription"><strong class="detailsDescriptionTitle">Samenvatting:</strong>' +
        (description || 'Geen beschrijving beschikbaar.') +
        '</div></div>' +
        '<p><strong>Genre(s):</strong> ' + (categories ? categories.join(', ') : 'Niet beschikbaar') + '</p>' +
        '<p><strong>Pagina\'s:</strong> ' + (pageCount || 'Informatie niet beschikbaar') + '</p>' +
        '<p><strong>Taal:</strong> ' + (language || 'Niet beschikbaar') + '</p>' +
        '<p><strong>Release datum:</strong> ' + (publishedDate || 'Niet beschikbaar') + '</p>' +
        '<button id="addToShelfBtn" class="detailPageButton">+ Voeg toe aan leeslijst</button>';

    // Show the modal
    modal.style.display = 'block';

    // Close button functionality
    modalContent.querySelector('.close').onclick = function () {
        modal.style.display = 'none';
    };

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };

    // Add to shelf button functionality (placeholder for now)
    modalContent.querySelector('#addToShelfBtn').addEventListener('click', function () {
        addBookToCollection(book);
    });
}

// Function to add book to user's collection
function addBookToCollection(book) {
    // Prepare book data
    var bookData = {
        action: 'addBook',
        apiLink: book.selfLink || ''
    };

    console.log('Sending data:', bookData); // Debug log

    // Send data to PHP
    fetch('booklist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(bookData)
    })
        .then(function (response) {
            console.log('Response status:', response.status); // Debug log
            console.log('Response headers:', response.headers); // Debug log
            return response.text(); // Change from .json() to .text() first
        })
        .then(function (text) {
            console.log('Raw response:', text); // Debug log
            try {
                var data = JSON.parse(text);
                if (data.success) {
                    console.log('Boek toegevoegd aan je collectie!');
                } else {
                    console.log('Fout: ' + data.message);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.log('Server response error: ' + text);
            }
            document.getElementById('myModal').style.display = 'none';
        })
        .catch(function (error) {
            console.error('Error:', error);
            console.log('Er is een fout opgetreden bij het toevoegen van het boek.');
        });
}

// Set up event listeners when page loads
document.addEventListener('DOMContentLoaded', function () {
    // Get the search input and button
    var searchInput = document.getElementById('bookListSearchInput');
    var searchButton = document.getElementById('bookListSearchButton');

    // Add event listener for typing in search input
    if (searchInput) {
        searchInput.addEventListener('input', handleBooklistSearchInput);
    }

    // Add event listener for search button click
    if (searchButton) {
        searchButton.addEventListener('click', function () {
            var searchTerm = searchInput.value;
            if (searchTerm) {
                searchBooksAPI(searchTerm);
                showSearchResults();
            }
        });
    }

    // Hide results when clicking outside
    document.addEventListener('click', function (event) {
        var searchContainer = document.querySelector('.book-search');
        if (!searchContainer.contains(event.target)) {
            hideSearchResults();
        }
    });
});

// Function to fetch book details from API and display them
function displayUserBooks(userBooks) {
    // Clear all existing book containers
    document.querySelectorAll('.booklist-unread-container, .booklist-reading-container, .booklist-read-container, .booklist-stopped-container, .booklist-favorites-container').forEach(container => {
        container.innerHTML = '';
    });

    // Process each book
    userBooks.forEach(function (bookData) {
        // Fetch book details from Google Books API
        fetch(bookData.book_link)
            .then(function (response) {
                return response.json();
            })
            .then(function (book) {
                // Create book element
                var bookElement = createBookElement(book);

                // Add to appropriate tab based on status
                if (bookData.is_unread == 1) {
                    document.querySelector('.booklist-unread-container').appendChild(bookElement);
                } else if (bookData.is_reading == 1) {
                    document.querySelector('.booklist-reading-container').appendChild(bookElement);
                } else if (bookData.is_read == 1) {
                    document.querySelector('.booklist-read-container').appendChild(bookElement);
                } else if (bookData.is_discarded == 1) {
                    document.querySelector('.booklist-stopped-container').appendChild(bookElement);
                } else if (bookData.is_favorite == 1) {
                    document.querySelector('.booklist-favorites-container').appendChild(bookElement);
                }
            })
            .catch(function (error) {
                console.error('Error fetching book details:', error);
            });
    });
}

// Function to create a book element
function createBookElement(book) {
    var bookDiv = document.createElement('div');
    bookDiv.className = 'user-book-item';

    var title = book.volumeInfo.title || 'Geen titel';
    var authors = book.volumeInfo.authors;
    var imageLinks = book.volumeInfo.imageLinks;

    // Create book HTML
    var bookHTML = '<div class="book-cover">';

    if (imageLinks && imageLinks.thumbnail) {
        bookHTML += '<img src="' + imageLinks.thumbnail + '" alt="' + title + '" class="book-thumbnail">';
    }

    bookHTML += '<h3 class="book-title">' + title + '</h3>';

    if (authors) {
        bookHTML += '<p class="book-authors">' + authors.join(', ') + '</p>';
    }

    bookHTML += '</div>';

    bookDiv.innerHTML = bookHTML;

    // Add click event to show details
    bookDiv.addEventListener('click', function () {
        showBooklistBookDetails(book);
    });

    return bookDiv;
}
