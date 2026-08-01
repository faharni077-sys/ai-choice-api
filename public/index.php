<?php

header('Content-Type: application/json');

echo json_encode([
    "success" => true,
    "message" => "AI Choice API Running",
    "php" => phpversion()
]);