const searchInput = document.getElementById('searchInput');
const searchButton = document.getElementById('searchButton');
const resultsDiv = document.getElementById('results');

searchButton.addEventListener('click', searchUsers);
searchInput.addEventListener('keyup', function (e) {
    if (e.key === 'Enter') {
        searchUsers();
    } else {
        searchUsers();
    }
});

function searchUsers() {
    const query = searchInput.value.trim();
    if (!query) {
        resultsDiv.innerHTML = '';
        return;
    }

    fetch(`/php/friends.php?q=` + encodeURIComponent(query))
        .then(response => response.json())
        .then(users => {
            resultsDiv.innerHTML = ''; // oude resultaten leegmaken
            if (users.length === 0) {
                resultsDiv.innerHTML = '<p>Geen gebruikers gevonden</p>';
                return;
            }

            users.forEach(user => {
                const div = document.createElement('div');
                div.className = 'user-result';
                div.textContent = user.username;
                div.addEventListener('click', () => {
                    alert('Je hebt ' + user.username + ' aangeklikt!');
                });
                resultsDiv.appendChild(div);
            });
        })
        .catch(err => {
            console.error(err);
            resultsDiv.innerHTML = '<p>Er is iets misgegaan</p>';
        });
}
