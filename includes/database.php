<?php
// Local hosting
$host = "127.0.0.1";
$database = "cle2";
$user = "root";
$password = "";

//Online server hosting
//$host = "127.0.0.1";
//$database = "prj_2024_2025_cle2_t17";
//$user = "prj_2024_2025_cle2_t17";
//$password = "ainoosho";


$db = mysqli_connect($host, $user, $password, $database)
or die("Error: " . mysqli_connect_error());