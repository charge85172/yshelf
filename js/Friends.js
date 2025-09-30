document.addEventListener("DOMContentLoaded", () => {
    loadFriends();
});

const searchInput = document.getElementById('searchInput');
const searchButton = document.getElementById('searchButton');
const resultsDiv = document.getElementById('results');

searchButton.addEventListener('click', searchUsers);
searchInput.addEventListener('keyup', function (e) {
    if (e.key === 'Enter') searchUsers();
});

function searchUsers() {
    const query = searchInput.value.trim();
    resultsDiv.innerHTML = '';
    if (!query) return;

    fetch(`/php/friends.php?q=` + encodeURIComponent(query))
        .then(res => res.json())
        .then(users => {
            if (users.length === 0) {
                resultsDiv.innerHTML = '<p>Geen gebruikers gevonden</p>';
                return;
            }

            users.forEach(user => {
                const div = document.createElement('div');
                div.className = 'friend-item';
                div.textContent = user.username;

                div.addEventListener('click', () => {
                    window.location.href = `/php/profile.php?id=${user.id}`;
                });

                const btn = document.createElement('button');
                btn.className = 'friendPageButton';
                updateButton(user, btn);

                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleFriend(user, btn);
                });

                div.appendChild(btn);
                resultsDiv.appendChild(div);
            });
        })
        .catch(err => {
            console.error(err);
            resultsDiv.innerHTML = '<p>Er is iets misgegaan</p>';
        });
}

function updateButton(user, button) {
    if (user.friendStatus === 1) {
        button.textContent = '- Verwijder vriend';
        button.classList.remove('add');
        button.classList.add('delete');
    } else {
        button.textContent = '+ Voeg vriend toe';
        button.classList.remove('delete');
        button.classList.add('add');
    }
}

function toggleFriend(user, button) {
    const action = user.friendStatus === 1 ? 'delete' : 'add';

    fetch('/php/friends.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({friend_id: user.id, action})
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                user.friendStatus = user.friendStatus === 1 ? 0 : 1;
                updateButton(user, button);
                loadFriends();
            } else {
                alert('Er is iets misgegaan: ' + (data.error || ''));
            }
        })
        .catch(err => console.error(err));
}


function loadFriends() {
    fetch("friends.php?friends=1")
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById("friendList");
            container.innerHTML = "";
            if (data.length === 0) {
                container.innerHTML = "<p>Je hebt nog geen vrienden toegevoegd.</p>";
                return;
            }
            data.forEach(friend => {
                const div = document.createElement("div");
                div.classList.add("friend-item");

                const span = document.createElement("span");
                span.textContent = friend.username;

                div.addEventListener('click', () => {
                    window.location.href = `/php/profile.php?id=${friend.id}`;
                });

                const btn = document.createElement("button");
                btn.className = "friendPageButton";
                updateButton({friendStatus: 1}, btn);
                btn.textContent = "- Verwijder vriend";
                btn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    fetch('/php/friends.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({friend_id: friend.id, action: 'delete'})
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                loadFriends();
                            } else {
                                alert('Er is iets misgegaan: ' + (data.error || ''));
                            }
                        })
                        .catch(err => console.error(err));
                });

                div.appendChild(span);
                div.appendChild(btn);
                container.appendChild(div);
            });
        })
        .catch(err => console.error("Error loading friends:", err));
}

