<?php
/** @var mysqli $db */

require_once '../includes/database.php';

if (isset($_POST['submit'])) {
    print_r($_POST['submit']);
}


mysqli_close($db);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>The random thingy</title>
    <script defer type="text/javascript" src=../js/auth.js></script>
    <style>
        .hidden {
            display: none;
        }

    </style>
</head>
<body>
<main>

    <section id="choice">
        <button id="showRegister">registreer</button>
        <button id="showLogin">inloggen</button>
    </section>


    <div class="hidden" id="registring">
        <form action="" method="post">

            <label for="firstName">Voornaam</label>
            <input id="firstName" type="text" name="firstName">

            <label for="lastName">Achternaam</label>
            <input id="lastName" type="text" name="lastName">

            <label for="userName">Gebruikers-naam</label>
            <input id="userName" type="text" name="userName">

            <label for="email">Email</label>
            <input id="email" type="text" name="email">

            <label for="password">Wachtwoord</label>
            <input id="password" type="text" name="passWord">

            <label for="checkPassword">Herhaal wachtwoord</label>
            <input id="checkPassword" type="text" name="checkPassword">

            <button name="submit" type="submit">Registreer</button>
        </form>
    </div>

    <div class="hidden" id="log-in">
        <form action="" method="post">


            <label>email</label>
            <input>

            <label>Wachtwoord</label>
            <input>

            <button name="submit">Inloggen</button>
        </form>
    </div>
</main>
</body>
</html>