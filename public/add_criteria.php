<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include __DIR__ . "/../config/database.php";

$name        = trim($_POST['name']        ?? '');
$weight      = trim($_POST['weight']      ?? '');
$type        = trim($_POST['type']        ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '' || $weight === '' || $type === '') {
    echo json_encode([
        "success" => false,
        "message" => "Nama, bobot, dan jenis wajib diisi",
    ]);
    exit;
}

$weightFloat = (float) $weight;
if ($weightFloat <= 0 || $weightFloat > 1) {
    echo json_encode([
        "success" => false,
        "message" => "Bobot harus antara 0.0001 dan 1.0 (contoh: 0.25 untuk 25%)",
    ]);
    exit;
}

if (!in_array($type, ['Benefit', 'Cost'])) {
    echo json_encode([
        "success" => false,
        "message" => "Jenis harus 'Benefit' atau 'Cost'",
    ]);
    exit;
}

// Check duplicate name
$check = $conn->prepare("SELECT id FROM criteria WHERE name = ?");
$check->bind_param("s", $name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Kriteria '$name' sudah ada",
    ]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO criteria (name, weight, type, description) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param("sdss", $name, $weightFloat, $type, $description);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Kriteria berhasil ditambahkan",
        "id"      => $conn->insert_id,
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menambahkan kriteria: " . $conn->error,
    ]);
}
