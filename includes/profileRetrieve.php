<?php

session_start();
if ($_SESSION['login'] !== true) {
    header('location: index.php');
}
$username = $_SESSION['username'];