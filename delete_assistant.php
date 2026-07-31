<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");

include "config/database.php";

// Support POST body or raw DELETE JSON
$raw = file_get_contents("php://input");
if (!empty($raw)) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        $_POST = array_merge($_POST, $parsed);
    }
}

$id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID tidak valid"]);
    exit;
}

// Check record exists
$check = $conn->prepare("SELECT id, name FROM ai_assistants WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "AI Assistant tidak ditemukan"]);
    exit;
}

$row  = $result->fetch_assoc();
$name = $row['name'];

// Delete (assessments cascade)
$stmt = $conn->prepare("DELETE FROM ai_assistants WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "AI Assistant '$name' berhasil dihapus",
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menghapus AI Assistant: " . $conn->error,
    ]);
}
