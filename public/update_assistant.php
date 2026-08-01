<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT");

include __DIR__ . "/../config/database.php";

// Support both POST body and raw PUT/POST JSON
$raw = file_get_contents("php://input");
if (!empty($raw)) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        $_POST = array_merge($_POST, $parsed);
    }
}

$id          = intval($_POST['id']          ?? 0);
$name        = trim($_POST['name']          ?? '');
$company     = trim($_POST['company']       ?? '');
$model       = trim($_POST['model']         ?? '');
$description = trim($_POST['description']   ?? '');

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID tidak valid"]);
    exit;
}

if ($name === '' || $company === '' || $model === '') {
    echo json_encode([
        "success" => false,
        "message" => "Nama, perusahaan, dan model wajib diisi",
    ]);
    exit;
}

// Check record exists
$check = $conn->prepare("SELECT id FROM ai_assistants WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();

if ($check->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "AI Assistant tidak ditemukan"]);
    exit;
}

// Check duplicate name (exclude current id)
$dup = $conn->prepare("SELECT id FROM ai_assistants WHERE name = ? AND id <> ?");
$dup->bind_param("si", $name, $id);
$dup->execute();
$dup->store_result();

if ($dup->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Nama '$name' sudah digunakan oleh AI Assistant lain",
    ]);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE ai_assistants
     SET name = ?, company = ?, model = ?, description = ?
     WHERE id = ?"
);
$stmt->bind_param("ssssi", $name, $company, $model, $description, $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "AI Assistant berhasil diperbarui",
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal memperbarui AI Assistant: " . $conn->error,
    ]);
}
