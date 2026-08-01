<?php

$host = getenv("MYSQLHOST") ?: "localhost";
$user = getenv("MYSQLUSER") ?: "root";
$pass = getenv("MYSQLPASSWORD") ?: "";
$db   = getenv("MYSQLDATABASE") ?: "ai_choice";
$port = getenv("MYSQLPORT") ?: 3306;

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => $conn->connect_error
    ]));
}

$conn->set_charset("utf8");