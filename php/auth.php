<?php
/** @var mysqli $db */

require_once '../includes/database.php';

if (isset($_POST['submit-login'])) {
    print "HELp";

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

            <label for="userName">Gebruikersnaam</label>
            <input id="userName" type="text" name="username">
            <p>  <?= $errors['username'] ?? '' ?> </p>
            <p>  <?= $errors['dubbleName'] ?? '' ?> </p>

            <label for="password">Wachtwoord</label>
            <input id="password" type="password" name="password">
            <p>  <?= $errors['password'] ?? '' ?> </p>
            <p>  <?= $errors['claimedPassword'] ?? '' ?> </p>

            <label for="checkPassword">Herhaal wachtwoord</label>
            <input id="checkPassword" type="password" name="passwordCheck">
            <p>  <?= $errors['dubblePassword'] ?? '' ?> </p>
            <p>  <?= $errors['claimedPassword'] ?? '' ?> </p>

            <button id="registerButton" name="submit-register" type="submit">Registreer</button>
        </form>
    </div>

    <div class="hidden" id="log-in">
        <form action="" method="post">


            <label for="loginUsername">Gebruikersnaam</label>
            <input id="loginUsername" type="text" name="loginUsername">

            <label for="loginPassword">Wachtwoord</label>
            <input id="loginPassword" type="password" name="loginPassword">

            <button name="submit-login" type="submit">Inloggen</button>
        </form>
    </div>
</main>
</body>
</html>