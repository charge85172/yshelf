<?php

/** @var mysqli $db */
require_once 'database.php';
session_start();
session_destroy();
header('location: ../index.php');
exit;

