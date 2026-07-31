<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT");

include "config/database.php";

$raw = file_get_contents("php://input");
if (!empty($raw)) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        $_POST = array_merge($_POST, $parsed);
    }
}

$id          = intval($_POST['id']          ?? 0);
$name        = trim($_POST['name']          ?? '');
$weight      = trim($_POST['weight']        ?? '');
$type        = trim($_POST['type']          ?? '');
$description = trim($_POST['description']   ?? '');

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID tidak valid"]);
    exit;
}

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
        "message" => "Bobot harus antara 0.0001 dan 1.0",
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

// Check exists
$check = $conn->prepare("SELECT id FROM criteria WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Kriteria tidak ditemukan"]);
    exit;
}

// Check duplicate name (exclude current)
$dup = $conn->prepare("SELECT id FROM criteria WHERE name = ? AND id <> ?");
$dup->bind_param("si", $name, $id);
$dup->execute();
$dup->store_result();

if ($dup->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Nama '$name' sudah digunakan oleh kriteria lain",
    ]);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE criteria SET name = ?, weight = ?, type = ?, description = ? WHERE id = ?"
);
$stmt->bind_param("sdssi", $name, $weightFloat, $type, $description, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Kriteria berhasil diperbarui",
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal memperbarui kriteria: " . $conn->error,
    ]);
}
