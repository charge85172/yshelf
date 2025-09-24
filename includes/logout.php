<?php

/** @var mysqli $db */
require_once 'include/database.php';
session_start();
session_destroy();
header('location: login.php');
exit;

