// Initialiseer de boekenkast
const myShelf = new Shelf();

// Event: Pagina geladen
document.addEventListener('DOMContentLoaded', () => {
    UI.displayBooks(myShelf);

    let searchButton = document.getElementById('searchButton')
    searchButton.addEventListener('click', actionSearch)
});

function actionSearch() {
    //kijken of er iets in de zoekbalk staat.
    // zo ja, haal de data uit de api op
    // zo nee, geef een warning dat er niks is ingevuld
    const query = document.getElementById('searchInput').value;
    if (query) {
        fetchData(query);
    } else {
        alert('Zoekveld is nog leeg');
    }
}

function fetchData(query) {
    const apiUrl = `https://www.googleapis.com/books/v1/volumes?q=${encodeURIComponent(query)}&maxResults=40`;

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(response.statusText)
            }
            return response.json()
        })
        .then(getResults)
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('results').innerHTML = 'Er is een fout opgetreden.';
        })

}

function getResults(data) {
    //zodra data is opgehaald data laten zien
    console.log("API data:", data);
    displayResults(data);

}

function displayResults(data) {
    const resultsDiv = document.getElementById("results");
    const resultsContainer = document.querySelector(".results-container");
    resultsDiv.innerHTML = '';

    const oldPrev = document.getElementById("prevBooks");
    const oldNext = document.getElementById("nextBooks");
    if (oldPrev) oldPrev.remove();
    if (oldNext) oldNext.remove();

    if (data && data.items && data.items.length > 0) {

        let prevButton = document.createElement('button');
        prevButton.id = "prevBooks";
        prevButton.classList.add("scroll-btn");
        prevButton.textContent = "<";
        prevButton.addEventListener('click', scrollLeft);

        let nextButton = document.createElement('button');
        nextButton.id = "nextBooks";
        nextButton.classList.add("scroll-btn");
        nextButton.textContent = ">";
        nextButton.addEventListener('click', scrollRight);

        resultsContainer.prepend(prevButton);
        resultsContainer.appendChild(nextButton);

        data.items.forEach(book => {

            const {title, authors, imageLinks} = book.volumeInfo;
            const bookId = book.id;

            // main container
            let bookDiv = document.createElement("div");
            bookDiv.className = "book-cover";
            bookDiv.dataset.id = bookId;

            // Titel
            let bookTitle = document.createElement("h3");
            bookTitle.textContent = title || "Geen titel";
            bookTitle.classList.add("book-title");
            bookDiv.appendChild(bookTitle);

            // Auteurs
            if (authors) {
                let authorsElement = document.createElement("p");
                authorsElement.textContent = "Auteur(s): " + authors.join(', ');
                authorsElement.classList.add("authors");
                bookDiv.appendChild(authorsElement);
            }

            // Thumbnail / cover foto
            if (imageLinks?.thumbnail) {
                let img = document.createElement("img");
                img.src = imageLinks.thumbnail;
                img.alt = title || '';
                bookDiv.appendChild(img);
            }

            // Voeg click listener toe voor modal
            bookDiv.addEventListener('click', () => {
                displayBookDetails(book); // Modal openen
            });

            resultsDiv.appendChild(bookDiv);
        });
    } else {
        resultsDiv.innerHTML = 'Geen resultaten gevonden.';
    }
}

const modal = document.getElementById("myModal");

function displayBookDetails(book) {
    const {title, authors, description, imageLinks, pageCount, categories, language, publishedDate} = book.volumeInfo;
    const modalContent = document.querySelector("#modalContent");

    const isRead = getBookReadStatus(book.id);

    modalContent.innerHTML = `
        <span class="close">&times;</span>
        <h2>${title || "Geen titel"}</h2>
        ${authors ? `<p><strong>Auteur(s):</strong> ${authors.join(", ")}</p>` : ""}
        ${imageLinks?.thumbnail ? `<img src="${imageLinks.thumbnail}" alt="${title}">` : ""}
        <p>${description || "Geen beschrijving beschikbaar."}</p>
        <p><strong>Genre(s)</strong> ${categories} </p>
        <p><strong>Pagina's:</strong> ${pageCount || "Informatie niet beschikbaar"}</p>
         <p><strong>Taal:</strong> ${language} </p>
          <p><strong>Release datum:</strong> ${publishedDate} </p>
        <button id="addToShelfBtn" class="detailPageButton">+ Voeg toe aan leeslijst</button>
        <button id="BookReadBtn" class="detailPageButton">${isRead ? "Markeer als ongelezen" : "Markeer als gelezen"}</button>
          <button id="DeleteBookBtn" class="detailPageButton"> &#xf2ed Verwijder uit leeslijst</button>
    `;

    // Open modal
    modal.style.display = "block";

    // Sluitknop
    modalContent.querySelector(".close").onclick = () => modal.style.display = "none";

    // Klik buiten modal sluiten
    window.onclick = (event) => {
        if (event.target === modal) modal.style.display = "none";
    };

    // Voeg toe knop
    modalContent.querySelector("#addToShelfBtn").addEventListener("click", () => addToBookshelf(book.id));
    modalContent.querySelector("#BookReadBtn").addEventListener("click", (e) => bookRead(book.id, e.target));

}


function scrollLeft() {
    document.getElementById('results').scrollBy({left: -300, behavior: 'smooth'})
}

function scrollRight() {
    document.getElementById('results').scrollBy({left: 300, behavior: 'smooth'})
}

function addToBookshelf(book) {
    console.log(`Boek toegevoegd`)

}

function setBookReadStatus(bookId, isRead) {
    const readBooks = JSON.parse(localStorage.getItem("readBooks")) || {};
    readBooks[bookId] = isRead;
    localStorage.setItem("readBooks", JSON.stringify(readBooks));
}

function getBookReadStatus(bookId) {
    const readBooks = JSON.parse(localStorage.getItem("readBooks")) || {};
    return readBooks[bookId] || false;
    //altijd eerst op niet gelezen
}


function bookRead(bookId, button) {
    console.log("book is gelezen")

    console.log(`Boek ${bookId} status veranderd`);

    const isCurrentlyRead = getBookReadStatus(bookId);
    const newStatus = !isCurrentlyRead;

    setBookReadStatus(bookId, newStatus);

    button.textContent = newStatus ? "Markeer als ongelezen" : "Markeer als gelezen";

    console.log(`Boek ${bookId} is nu ${newStatus ? "gelezen" : "ongelezen"}`);


}


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

});
