<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "config/database.php";

$name        = trim($_POST['name']        ?? '');
$company     = trim($_POST['company']     ?? '');
$model       = trim($_POST['model']       ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '' || $company === '' || $model === '') {
    echo json_encode([
        "success" => false,
        "message" => "Nama, perusahaan, dan model wajib diisi",
    ]);
    exit;
}

// Check duplicate name
$check = $conn->prepare("SELECT id FROM ai_assistants WHERE name = ?");
$check->bind_param("s", $name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "AI Assistant dengan nama '$name' sudah ada",
    ]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO ai_assistants (name, company, model, description)
     VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("ssss", $name, $company, $model, $description);

if ($stmt->execute()) {
    $newId = $conn->insert_id;
    echo json_encode([
        "success" => true,
        "message" => "AI Assistant berhasil ditambahkan",
        "id"      => $newId,
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menambahkan AI Assistant: " . $conn->error,
    ]);
}
