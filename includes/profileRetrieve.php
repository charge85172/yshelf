<?php

session_start();
if ($_SESSION['login'] !== true) {
    header('location: auth.php');
}
$username = $_SESSION['username'];