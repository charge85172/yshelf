<?php
//deze code is geschreven door thomas.
/** @var mysqli $db */

require_once '../includes/database.php';
session_start();

$_SESSION['login'] = false;

if (isset($_POST['submit-login'])) {

    $loginUsername = mysqli_escape_string($db, $_POST['loginUsername']);
    $loginPassword = mysqli_escape_string($db, $_POST['loginPassword']);

    $loginErrors = [];

    if ($loginUsername === "") {
        $loginErrors['loginUsernameError'] = 'Uw email is verplicht';
    }
    if ($loginPassword === "") {
        $loginErrors['loginPasswordError'] = 'uw wachtwoord is verplicht';
    }
    if (empty($loginErrors)) {
        $query = "
       SELECT 'username' FROM users WHERE `username` = '$loginUsername'
       ";
        $result = mysqli_query($db, $query)
        or die('Error: ' . mysqli_error($db) . 'with query ' . $query);

        if (mysqli_num_rows($result) == 1) {
            $query = "
        SELECT * FROM `users` WHERE `username` = '$loginUsername'
        ";
            $result = mysqli_query($db, $query)
            or die('Error: ' . mysqli_error($db) . 'with query ' . $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $users = $row;
            }

        } else {
            $loginErrors['loginFailed'] = 'Login failed';
        }
        if (empty($errors)) {
            if (password_verify($loginPassword, $users['password']) == true) {
                $_SESSION['login'] = true;
                $_SESSION['username'] = $loginUsername;
                header('location: index.html');
            } else {
                $loginErrors['loginPasswordError'] = 'uw wachtwoord is incorrect';
            }
        }
    }
}

if (isset($_POST['submit-register'])) {
    $username = mysqli_escape_string($db, $_POST['username']);
    $password = mysqli_escape_string($db, $_POST['password']);
    $dubblePassword = mysqli_escape_string($db, $_POST['passwordCheck']);

    $errors = [];

    if ($username === "") {
        $errors['username'] = 'U moet een gebruikersnaam invoeren.';
    }
    if ($password === "") {
        $errors['password'] = 'U moet een wachtwoord invoeren.';
    }
    if ($dubblePassword === "") {
        $errors['dubblePassword'] = 'U moet uw wachtwoord opnieuw invoeren ';
    }
    if ($password !== $dubblePassword) {
        $errors['claimedPassword'] = 'Uw wachtwoord komt niet overeen';
    }

    $sql = " SELECT `username`  FROM users WHERE `username` = '$username'";
    $result = mysqli_query($db, $sql)
    or die('Error ' . mysqli_error($db) . 'with query ' . $sql);
    if (mysqli_num_rows($result) > 0) {
        $errors['dubbleName'] = 'De gebruikersnaam is al in gebruikt';
    }

    if (empty($errors)) {
        $password = password_hash($password, PASSWORD_DEFAULT);

        $query = "
    INSERT INTO `users`(`username`, `password`)
    VALUES ('$username', '$password' )
    ";
        $result = mysqli_query($db, $query)
        or die('Error ' . mysqli_error($db) . 'with query ' . $query);

        header('location: login.php');
        exit;

    }
}
mysqli_close($db);
/** @var mysqli $db */

//deze code is geschreven door charge, maar niet relevant nog.
//
//// Zet error reporting aan voor debugging
//// error_reporting(E_ALL);
//// ini_set('display_errors', 1);
//
//// Dit pad moet mogelijk aangepast worden afhankelijk van je mapstructuur
//require_once '../includes/database.php';
//session_start();
//
//$_SESSION['login'] = false;
//$loginErrors = [];
//$errors = [];
//$pageState = ''; // Houdt bij welke deur open moet zijn
//
//// --- LOGIN LOGICA ---
//if (isset($_POST['submit-login'])) {
//    $pageState = 'login-active'; // Markeer dat het login formulier actief was
//
//    $loginUsername = mysqli_escape_string($db, $_POST['loginUsername']);
//    $loginPassword = $_POST['loginPassword']; // Niet escapen voor password_verify
//
//    if (empty($loginUsername)) {
//        $loginErrors['loginUsernameError'] = 'Uw gebruikersnaam is verplicht.';
//    }
//    if (empty($loginPassword)) {
//        $loginErrors['loginPasswordError'] = 'Uw wachtwoord is verplicht.';
//    }
//
//    if (empty($loginErrors)) {
//        $query = "SELECT * FROM `users` WHERE `username` = '$loginUsername'";
//        $result = mysqli_query($db, $query);
//
//        if ($result && mysqli_num_rows($result) == 1) {
//            $user = mysqli_fetch_assoc($result);
//            if (password_verify($loginPassword, $user['password'])) {
//                $_SESSION['login'] = true;
//                $_SESSION['user_id'] = $user['id'];
//                $_SESSION['username'] = $user['username'];
//                header('Location: ../index.html'); // Stuur door naar de hoofdpagina
//                exit;
//            } else {
//                $loginErrors['loginFailed'] = 'Gebruikersnaam of wachtwoord is incorrect.';
//            }
//        } else {
//            $loginErrors['loginFailed'] = 'Gebruikersnaam of wachtwoord is incorrect.';
//        }
//    }
//}
//
//// --- REGISTRATIE LOGICA ---
//if (isset($_POST['submit-register'])) {
//    $pageState = 'register-active'; // Markeer dat het registratie formulier actief was
//
//    $username = mysqli_escape_string($db, $_POST['username']);
//    $password = $_POST['password']; // Niet escapen voor validatie
//    $dubblePassword = $_POST['passwordCheck'];
//
//    if (empty($username)) {
//        $errors['username'] = 'U moet een gebruikersnaam invoeren.';
//    } else {
//        $sql = "SELECT `username` FROM users WHERE `username` = '$username'";
//        $result = mysqli_query($db, $sql);
//        if (mysqli_num_rows($result) > 0) {
//            $errors['dubbleName'] = 'Deze gebruikersnaam is al in gebruik.';
//        }
//    }
//
//    if (empty($password)) {
//        $errors['password'] = 'U moet een wachtwoord invoeren.';
//    }
//    if ($password !== $dubblePassword) {
//        $errors['claimedPassword'] = 'Uw wachtwoorden komen niet overeen.';
//    }
//
//    if (empty($errors)) {
//        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
//
//        $query = "INSERT INTO `users`(`username`, `password`) VALUES ('$username', '$hashedPassword')";
//        $result = mysqli_query($db, $query);
//
//        if ($result) {
//            $pageState = 'login-active';
//        } else {
//            $errors['database'] = 'Er is iets misgegaan. Probeer het opnieuw.';
//        }
//    }
//}
//
//mysqli_close($db);
//?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YShelf - Login</title>

    <style>
        /* --- HIER BEGINT DE CSS CODE --- */
        :root {
            --bg-color: #2d2d2d;
            --cabinet-color: #654321;
            --door-color: #8B4513;
            --handle-bg: #8FBC8F; /* Donker zeegroen */
            --handle-hover-bg: #9ACD32; /* Geelgroen */
            --text-color: #f0f0f0;
            --input-bg: rgba(255, 255, 255, 0.1);
            --input-border: #a0a0a0;
            --error-color: #ff8a8a;
            --knob-color-light: #f0e68c;
            --knob-color-dark: #daa520;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
            perspective: 1500px;
        }

        .auth-container {
            text-align: center;
        }

        .welcome-text {
            margin-bottom: 30px;
        }

        .welcome-text h1 {
            font-size: 2.5em;
            font-weight: normal;
        }

        .welcome-text p {
            font-size: 1.1em;
            color: #b0b0b0;
        }

        .cabinet {
            position: relative;
            width: 600px;
            height: 400px;
            background-color: var(--cabinet-color);
            border: 10px solid #503419;
            border-radius: 10px;
        }

        .door {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 1s ease-in-out;
        }

        .door-left {
            left: 0;
            transform-origin: left center;
        }

        .door-right {
            right: 0;
            transform-origin: right center;
        }

        .auth-container.register-active .door-left,
        .door-left.open {
            transform: rotateY(-160deg);
        }

        .auth-container.login-active .door-right,
        .door-right.open {
            transform: rotateY(160deg);
        }

        /* FIX: De .door-panel bevat nu alle visuele stijlen */
        .door-panel {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden; /* Verberg de achterkant van dit paneel */
            background-color: var(--door-color);
            border: 2px solid #503419;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .door-left .door-panel {
            border-radius: 10px 0 0 10px;
        }

        .door-right .door-panel {
            border-radius: 0 10px 10px 0;
        }

        .door-panel.back {
            transform: rotateY(180deg);
            padding: 20px;
        }

        /* Styling for the decorative knob */
        .knob {
            position: absolute;
            top: 50%;
            width: 35px;
            height: 35px;
            background: radial-gradient(circle, var(--knob-color-light) 0%, var(--knob-color-dark) 100%);
            border-radius: 50%;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5), inset 1px 1px 2px #fff7d6;
            /* Gebruik margin om de 3D-ruimte niet te verstoren */
            margin-top: -17.5px; /* De helft van de hoogte */
        }

        .door-left .knob {
            right: 25px;
        }

        .door-right .knob {
            left: 25px;
        }

        /* This is the existing button style, it remains unchanged */
        .door-handle {
            background-color: var(--handle-bg);
            border: 2px solid #547454;
            color: var(--text-color);
            padding: 10px 20px;
            font-size: 1.2em;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .door-handle:hover {
            background-color: var(--handle-hover-bg);
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .auth-form h2 {
            margin-bottom: 10px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        .auth-form input {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 4px;
            padding: 10px;
            color: var(--text-color);
            font-size: 1em;
        }

        .auth-form input::placeholder {
            color: #b0b0b0;
        }

        .auth-form button {
            background-color: var(--handle-bg);
            border: none;
            color: var(--text-color);
            padding: 12px;
            font-size: 1.1em;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }

        .auth-form button:hover {
            background-color: var(--handle-hover-bg);
        }

        .error {
            color: var(--error-color);
            font-size: 0.8em;
            min-height: 1.2em;
            margin-top: 2px;
        }

        .general-error {
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
<main class="auth-container <?= htmlspecialchars($pageState) ?>">
    <div class="welcome-text">
        <h1>Welkom bij YShelf</h1>
        <p>jouw middel om je leeservaring te verbeteren</p>
    </div>

    <div class="cabinet">
        <div class="door door-left">
            <!-- FIX: De inhoud zit nu in een 'door-panel' -->
            <div class="door-panel front">
                <div class="knob"></div>
                <button id="showRegister" class="door-handle">Registreer</button>
            </div>
            <div class="door-panel back">
                <form action="" method="post" class="auth-form" id="registring">
                    <h2>Registreer</h2>
                    <div class="form-group">
                        <input id="userName" type="text" name="username" placeholder="Gebruikersnaam"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        <span class="error"><?= $errors['username'] ?? '' ?></span>
                        <span class="error"><?= $errors['dubbleName'] ?? '' ?></span>
                    </div>
                    <div class="form-group">
                        <input id="password" type="password" name="password" placeholder="Wachtwoord">
                        <span class="error"><?= $errors['password'] ?? '' ?></span>
                    </div>
                    <div class="form-group">
                        <input id="checkPassword" type="password" name="passwordCheck" placeholder="Herhaal wachtwoord">
                        <span class="error"><?= $errors['claimedPassword'] ?? '' ?></span>
                    </div>
                    <button name="submit-register" type="submit">Registreer</button>
                </form>
            </div>
        </div>

        <div class="door door-right">
            <!-- FIX: De inhoud zit nu in een 'door-panel' -->
            <div class="door-panel front">
                <div class="knob"></div>
                <button id="showLogin" class="door-handle">Log In</button>
            </div>
            <div class="door-panel back">
                <form action="" method="post" class="auth-form" id="log-in">
                    <h2>Log In</h2>
                    <div class="form-group">
                        <input id="loginUsername" type="text" name="loginUsername" placeholder="Gebruikersnaam"
                               value="<?= htmlspecialchars($_POST['loginUsername'] ?? '') ?>">
                        <span class="error"><?= $loginErrors['loginUsernameError'] ?? '' ?></span>
                    </div>
                    <div class="form-group">
                        <input id="loginPassword" type="password" name="loginPassword" placeholder="Wachtwoord">
                        <span class="error"><?= $loginErrors['loginPasswordError'] ?? '' ?></span>
                    </div>
                    <span class="error general-error"><?= $loginErrors['loginFailed'] ?? '' ?></span>
                    <button name="submit-login" type="submit">Inloggen</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
    // De JavaScript code hoeft niet aangepast te worden en blijft hetzelfde.
    document.addEventListener('DOMContentLoaded', () => {
        const showRegisterBtn = document.getElementById('showRegister');
        const showLoginBtn = document.getElementById('showLogin');
        const leftDoor = document.querySelector('.door-left');
        const rightDoor = document.querySelector('.door-right');
        const authContainer = document.querySelector('.auth-container');

        if (showRegisterBtn) {
            showRegisterBtn.addEventListener('click', () => {
                leftDoor.classList.add('open');
                rightDoor.classList.remove('open');
                authContainer.classList.remove('login-active', 'register-active');
            });
        }

        if (showLoginBtn) {
            showLoginBtn.addEventListener('click', () => {
                rightDoor.classList.add('open');
                leftDoor.classList.remove('open');
                authContainer.classList.remove('login-active', 'register-active');
            });
        }
    });
</script>
</body>
</html>