function showTab(tabName) {
    var allTabs = document.querySelectorAll('.booklist-container > div');
    for (var i = 0; i < allTabs.length; i++) {
        allTabs[i].style.display = 'none';
    }

    var selectedTab = document.getElementById(tabName);
    if (selectedTab) {
        selectedTab.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var navButtons = document.querySelectorAll('.booklist-nav button');
    for (var i = 0; i < navButtons.length; i++) {
        navButtons[i].addEventListener('click', function () {
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
            } else if (buttonText === 'Aanbevolen') {
                showTab('booklist-recommended');
            }
        });
    }

    showTab('booklist-unread');
    navButtons[0].classList.add('active');

    // Update recommended header with count
    updateRecommendedHeader();
});

var searchTimeout;

function handleBooklistSearchInput() {
    clearTimeout(searchTimeout);

    var searchTerm = document.getElementById('bookListSearchInput').value;

    if (searchTerm.length > 2) {
        searchTimeout = setTimeout(function () {
            searchBooksAPI(searchTerm);
        }, 300);
    } else {
        hideSearchResults();
    }
}

function showSearchResults() {
    var resultsContainer = document.querySelector('.booklist-search-results');
    resultsContainer.classList.add('show');
}

function hideSearchResults() {
    var resultsContainer = document.querySelector('.booklist-search-results');
    resultsContainer.classList.remove('show');
}

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
            showSearchResults();
        })
        .catch(function (error) {
            console.error('Error:', error);
            document.getElementById('booklistResults').innerHTML = 'Er is een fout opgetreden.';
            showSearchResults();
        });
}

function showBooklistResults(data) {
    var resultsDiv = document.getElementById('booklistResults');
    resultsDiv.innerHTML = '';

    if (data && data.items && data.items.length > 0) {
        data.items.forEach(function (book) {
            var title = book.volumeInfo.title || 'Geen titel';
            var authors = book.volumeInfo.authors;
            var imageLinks = book.volumeInfo.imageLinks;
            var bookId = book.id;

            var bookDiv = document.createElement('div');
            bookDiv.className = 'booklist-search-item';
            bookDiv.dataset.id = bookId;

            var titleElement = document.createElement('h3');
            titleElement.textContent = title;
            titleElement.className = 'booklist-search-title';
            bookDiv.appendChild(titleElement);

            if (authors) {
                var authorsElement = document.createElement('p');
                authorsElement.textContent = 'Auteur(s): ' + authors.join(', ');
                authorsElement.className = 'booklist-search-authors';
                bookDiv.appendChild(authorsElement);
            }

            if (imageLinks && imageLinks.thumbnail) {
                var img = document.createElement('img');
                img.src = imageLinks.thumbnail;
                img.alt = title;
                img.className = 'booklist-search-cover';
                bookDiv.appendChild(img);
            }

            bookDiv.addEventListener('click', function () {
                showBooklistBookDetails(book);
                hideSearchResults();
            });

            resultsDiv.appendChild(bookDiv);
        });
    } else {
        resultsDiv.innerHTML = 'Geen resultaten gevonden.';
    }
}

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

    checkBookInCollection(bookLink, function (bookData) {
        var isInCollection = bookData.exists;
        var currentStatus = bookData.status;
        var isRecommended = bookData.isRecommended;

        // Get recommended count to check limit
        getRecommendedCount(function (countData) {
            var buttonHTML = '';
            if (isInCollection) {
                buttonHTML = '<div class="book-status-buttons">';

                if (currentStatus !== 'unread') {
                    buttonHTML += '<button id="markAsUnreadBtn" class="detailPageButton">📚 Te lezen</button>';
                }
                if (currentStatus !== 'read') {
                    buttonHTML += '<button id="markAsReadBtn" class="detailPageButton">✓ Gelezen</button>';
                }
                if (currentStatus !== 'reading') {
                    buttonHTML += '<button id="markAsReadingBtn" class="detailPageButton">📖 Bezig</button>';
                }
                if (currentStatus !== 'discarded') {
                    buttonHTML += '<button id="markAsDiscardedBtn" class="detailPageButton">❌ Gestopt</button>';
                }
                if (currentStatus !== 'favorite') {
                    buttonHTML += '<button id="markAsFavoriteBtn" class="detailPageButton">⭐ Favoriet</button>';
                }

                // Recommended button with toggle text and limit check
                var recommendedButtonText;
                var isAtLimit = countData.count >= countData.max && !isRecommended;

                if (isRecommended) {
                    recommendedButtonText = '💡 Niet meer aanbevelen';
                } else if (isAtLimit) {
                    recommendedButtonText = '💡 Maximaal 6 aanbevelingen (' + countData.count + '/' + countData.max + ')';
                } else {
                    recommendedButtonText = '💡 Aanbevolen (' + countData.count + '/' + countData.max + ')';
                }

                buttonHTML += '<button id="markAsRecommendedBtn" class="detailPageButton" ' + (isAtLimit ? 'disabled' : '') + '>' + recommendedButtonText + '</button>';

                buttonHTML += '<button id="removeFromCollectionBtn" class="detailPageButton remove-btn">🗑️ Verwijder uit collectie</button>';
                buttonHTML += '</div>';
            } else {
                buttonHTML = '<button id="addToShelfBtn" class="detailPageButton">+ Voeg toe aan leeslijst</button>';
            }

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
                buttonHTML;

            modal.style.display = 'block';

            modalContent.querySelector('.close').onclick = function () {
                modal.style.display = 'none';
            };

            window.onclick = function (event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            };

            if (isInCollection) {
                var unreadBtn = modalContent.querySelector('#markAsUnreadBtn');
                if (unreadBtn) {
                    unreadBtn.addEventListener('click', function () {
                        changeBookStatus(book, 'unread');
                    });
                }

                var readBtn = modalContent.querySelector('#markAsReadBtn');
                if (readBtn) {
                    readBtn.addEventListener('click', function () {
                        changeBookStatus(book, 'read');
                    });
                }

                var readingBtn = modalContent.querySelector('#markAsReadingBtn');
                if (readingBtn) {
                    readingBtn.addEventListener('click', function () {
                        changeBookStatus(book, 'reading');
                    });
                }

                var discardedBtn = modalContent.querySelector('#markAsDiscardedBtn');
                if (discardedBtn) {
                    discardedBtn.addEventListener('click', function () {
                        changeBookStatus(book, 'discarded');
                    });
                }

                var favoriteBtn = modalContent.querySelector('#markAsFavoriteBtn');
                if (favoriteBtn) {
                    favoriteBtn.addEventListener('click', function () {
                        changeBookStatus(book, 'favorite');
                    });
                }

                var recommendedBtn = modalContent.querySelector('#markAsRecommendedBtn');
                if (recommendedBtn) {
                    recommendedBtn.addEventListener('click', function () {
                        changeBookStatus(book, 'recommended');
                    });
                }

                modalContent.querySelector('#removeFromCollectionBtn').addEventListener('click', function () {
                    removeBookFromCollection(book);
                });
            } else {
                modalContent.querySelector('#addToShelfBtn').addEventListener('click', function () {
                    addBookToCollection(book);
                });
            }
        }); // Close getRecommendedCount callback
    }); // Close checkBookInCollection callback
}

function addBookToCollection(book) {
    var bookData = {
        action: 'addBook',
        apiLink: book.selfLink || ''
    };

    console.log('Sending data:', bookData);

    fetch('booklist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(bookData)
    })
        .then(function (response) {
            return response.text();
        })
        .then(function (text) {
            try {
                var data = JSON.parse(text);
                if (data.success) {


                    refreshBookList();

                } else {
                    alert('Fout: ' + data.message);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                alert('Server response error: ' + text);
            }
            document.getElementById('myModal').style.display = 'none';
        })
        .catch(function (error) {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het toevoegen van het boek.');
        });
}

function checkBookInCollection(apiLink, callback) {
    fetch('booklist.php?action=checkBook&apiLink=' + encodeURIComponent(apiLink))
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            callback(data);
        })
        .catch(function (error) {
            console.error('Error checking book:', error);
            callback({ exists: false, status: null });
        });
}

function getRecommendedCount(callback) {
    fetch('booklist.php?action=getRecommendedCount')
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            callback(data);
        })
        .catch(function (error) {
            console.error('Error getting recommended count:', error);
            callback({ count: 0, max: 6 });
        });
}

function removeBookFromCollection(book) {
    var bookData = {
        action: 'removeBook',
        apiLink: book.selfLink || ''
    };

    console.log('Removing book:', bookData);

    fetch('booklist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(bookData)
    })
        .then(function (response) {
            return response.text();
        })
        .then(function (text) {
            try {
                var data = JSON.parse(text);
                if (data.success) {

                    refreshBookList();

                } else {
                    alert('Fout: ' + data.message);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                alert('Server response error: ' + text);
            }
            document.getElementById('myModal').style.display = 'none';
        })
        .catch(function (error) {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het verwijderen van het boek.');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('bookListSearchInput');
    var searchButton = document.getElementById('bookListSearchButton');

    if (searchInput) {
        searchInput.addEventListener('input', handleBooklistSearchInput);
    }

    if (searchButton) {
        searchButton.addEventListener('click', function () {
            var searchTerm = searchInput.value;
            if (searchTerm) {
                searchBooksAPI(searchTerm);
                showSearchResults();
            }
        });
    }

    document.addEventListener('click', function (event) {
        var searchContainer = document.querySelector('.book-search');
        if (!searchContainer.contains(event.target)) {
            hideSearchResults();
        }
    });
});

function displayUserBooks(userBooks) {
    document.querySelectorAll('.booklist-unread-container, .booklist-reading-container, .booklist-read-container, .booklist-stopped-container, .booklist-favorites-container, .booklist-recommended-container').forEach(container => {
        container.innerHTML = '';
    });

    userBooks.forEach(function (bookData) {
        fetch(bookData.book_link)
            .then(function (response) {
                return response.json();
            })
            .then(function (book) {
                // Add book to primary status category
                if (bookData.is_unread == 1) {
                    var bookElement = createBookElement(book);
                    document.querySelector('.booklist-unread-container').appendChild(bookElement);
                } else if (bookData.is_reading == 1) {
                    var bookElement = createBookElement(book);
                    document.querySelector('.booklist-reading-container').appendChild(bookElement);
                } else if (bookData.is_read == 1) {
                    var bookElement = createBookElement(book);
                    document.querySelector('.booklist-read-container').appendChild(bookElement);
                } else if (bookData.is_discarded == 1) {
                    var bookElement = createBookElement(book);
                    document.querySelector('.booklist-stopped-container').appendChild(bookElement);
                } else if (bookData.is_favorite == 1) {
                    var bookElement = createBookElement(book);
                    document.querySelector('.booklist-favorites-container').appendChild(bookElement);
                }

                // Also add to recommended category if it's recommended (independent of primary status)
                if (bookData.is_recommended == 1) {
                    var bookElement = createBookElement(book);
                    document.querySelector('.booklist-recommended-container').appendChild(bookElement);
                }
            })
            .catch(function (error) {
                console.error('Error fetching book details:', error);
            });
    });
}

function createBookElement(book) {
    var bookDiv = document.createElement('div');
    bookDiv.className = 'user-book-item';

    var title = book.volumeInfo.title || 'Geen titel';
    var authors = book.volumeInfo.authors;
    var imageLinks = book.volumeInfo.imageLinks;

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

    bookDiv.addEventListener('click', function () {
        showBooklistBookDetails(book);
    });

    return bookDiv;
}

function changeBookStatus(book, status) {
    var bookData = {
        action: 'changeStatus',
        apiLink: book.selfLink || '',
        status: status
    };

    console.log('Changing book status:', bookData);

    fetch('booklist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(bookData)
    })
        .then(function (response) {
            return response.text();
        })
        .then(function (text) {
            try {
                var data = JSON.parse(text);
                if (data.success) {
                    var statusMessages = {
                        'unread': 'Boek gemarkeerd als te lezen!',
                        'read': 'Boek gemarkeerd als gelezen!',
                        'reading': 'Boek gemarkeerd als bezig!',
                        'discarded': 'Boek gemarkeerd als gestopt!',
                        'favorite': 'Boek toegevoegd aan favorieten!',
                        'recommended': 'Aanbeveling bijgewerkt!'
                    };
                    console.log(statusMessages[status] || 'Status bijgewerkt!');

                    refreshBookList();

                } else {
                    alert('Fout: ' + data.message);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                alert('Server response error: ' + text);
            }
            document.getElementById('myModal').style.display = 'none';
        })
        .catch(function (error) {
            console.error('Error:', error);
            alert('Er is een fout opgetreden bij het bijwerken van de status.');
        });
}

function refreshBookList() {
    fetch('booklist.php?action=getBooks')
        .then(function (response) {
            return response.json();
        })
        .then(function (userBooks) {
            document.querySelectorAll('.booklist-unread-container, .booklist-reading-container, .booklist-read-container, .booklist-stopped-container, .booklist-favorites-container, .booklist-recommended-container').forEach(container => {
                container.innerHTML = '';
            });

            displayUserBooks(userBooks);

            // Update recommended section header with count
            updateRecommendedHeader();
        })
        .catch(function (error) {
            console.error('Error refreshing book list:', error);
        });
}

function updateRecommendedHeader() {
    getRecommendedCount(function (countData) {
        var header = document.querySelector('#booklist-recommended h2');
        if (header) {
            header.textContent = 'Aanbevolen (' + countData.count + '/' + countData.max + ')';
        }
    });
}
