// Initialiseer de boekenkast
const myShelf = new Shelf();

// Event: Pagina geladen
document.addEventListener('DOMContentLoaded', () => {
    UI.displayBooks(myShelf);
});

// Event: Boek toevoegen
document.getElementById('add-book-form').addEventListener('submit', (e) => {
    e.preventDefault();

    // Haal formulierwaarden op
    const title = document.getElementById('book-title').value;
    const author = document.getElementById('book-author').value;
    const cover = document.getElementById('book-cover').value;

    // Maak een nieuw boek object
    const newBook = new Book(title, author, cover);

    // Voeg boek toe aan de plank
    myShelf.addBook(newBook);

    // Update de UI
    UI.displayBooks(myShelf);

    // Maak het formulier leeg
    UI.clearForm();
});

// Toekomstige events (bijv. boek verwijderen of als gelezen markeren)
// document.getElementById('book-shelf').addEventListener('click', (e) => {
//     // Logica om een boek te verwijderen of status te wijzigen
// });