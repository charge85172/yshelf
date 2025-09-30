// const searchInput = document.getElementById('searchInput');
// const searchButton = document.getElementById('searchButton');
// const resultsDiv = document.getElementById('results');
//
// searchButton.addEventListener('click', searchUsers);
// searchInput.addEventListener('keyup', function (e) {
//     if (e.key === 'Enter') {
//         searchUsers();
//     } else {
//         searchUsers();
//     }
// });
//
// function searchUsers() {
//     const query = searchInput.value.trim();
//     resultsDiv.innerHTML = '';
//     if (!query) return;
//
//     fetch(`/php/friends.php?q=` + encodeURIComponent(query))
//         .then(response => response.json())
//         .then(users => {
//             resultsDiv.innerHTML = ''; // oude resultaten leegmaken
//             if (users.length === 0) {
//                 resultsDiv.innerHTML = '<p>Geen gebruikers gevonden</p>';
//                 return;
//             }
//
//             users.forEach(user => {
//                 const div = document.createElement('div');
//                 div.className = 'user-result';
//                 div.textContent = user.username;
//
//                 const addBtn = document.createElement('button');
//                 addBtn.textContent = '+ Voeg vriend toe';
//                 addBtn.className = 'friendPageButton';
//                 addBtn.addEventListener('click', (e) => {
//                     e.stopPropagation();
//                     addFriend(user.id);
//                 });
//
//                 div.appendChild(addBtn);
//                 resultsDiv.appendChild(div);
//             });
//         })
//         .catch(err => {
//             console.error(err);
//             resultsDiv.innerHTML = '<p>Er is iets misgegaan</p>';
//         });
//
//     function getFriendStatus(friendId) {
//
//     }
//
//     function addFriend(friendId) {
//         fetch('/php/friends.php', {
//             method: 'POST',
//             headers: {'Content-Type': 'application/json'},
//             body: JSON.stringify({friend_id: friendId})
//         }).then(res => res.json()).then(data => {
//         });
//     }
//
//     function deleteFriend(friendId) {
//         fetch('/php/friends.php', {
//             method: 'POST',
//             headers: {'Content-Type': 'application/json'},
//             body: JSON.stringify({friend_id: friendId})
//         }).then(res => res.json()).then(data => {
//         });
//     }
// }


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
                div.className = 'user-result';
                div.textContent = user.username;

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
    button.textContent = user.friendStatus === 1 ? '- Verwijder vriend' : '+ Voeg vriend toe';
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
            } else {
                alert('Er is iets misgegaan: ' + (data.error || ''));
            }
        })
        .catch(err => console.error(err));
}

