<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "ai_choice";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]));
}

$conn->set_charset("utf8");