class UI {
    static displayBooks(shelf) {
        const bookShelf = document.getElementById('book-shelf');
        bookShelf.innerHTML = ''; // Maak de plank eerst leeg

        shelf.books.forEach(book => {
            const bookCard = document.createElement('div');
            bookCard.classList.add('book-card');
            bookCard.dataset.id = book.id;

            bookCard.innerHTML = `
                <img src="${book.coverImage || 'https://via.placeholder.com/150x200.png?text=No+Cover'}" alt="Cover van ${book.title}">
                <h3>${book.title}</h3>
                <p>${book.author}</p>
                ${book.isRead ? '<span class="read-indicator">Gelezen</span>' : ''}
            `;
            bookShelf.appendChild(bookCard);
        });
    }

    static clearForm() {
        document.getElementById('add-book-form').reset();
    }
}