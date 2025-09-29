class Shelf {
    constructor() {
        // Laad boeken uit localStorage of start met een lege lijst
        this.books = JSON.parse(localStorage.getItem('yshelf_books')) || [];
    }

    addBook(book) {
        this.books.push(book);
        this.saveToLocalStorage();
    }

    removeBook(bookId) {
        this.books = this.books.filter(book => book.id !== bookId);
        this.saveToLocalStorage();
    }

    findBook(bookId) {
        return this.books.find(book => book.id === bookId);
    }

    saveToLocalStorage() {
        localStorage.setItem('yshelf_books', JSON.stringify(this.books));
    }
}