class Book {
    constructor(title, author, coverImage, isRead = false) {
        this.id = Date.now().toString(); // Simple unique ID
        this.title = title;
        this.author = author;
        this.coverImage = coverImage;
        this.isRead = isRead;
    }

    toggleReadStatus() {
        this.isRead = !this.isRead;
    }
}