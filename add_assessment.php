<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include "config/database.php";

$assistant_id = intval($_POST['assistant_id'] ?? 0);
$criteria_id  = intval($_POST['criteria_id']  ?? 0);
$value        = trim($_POST['value']           ?? '');

if ($assistant_id <= 0 || $criteria_id <= 0 || $value === '') {
    echo json_encode([
        "success" => false,
        "message" => "assistant_id, criteria_id, dan value wajib diisi",
    ]);
    exit;
}

$valueFloat = (float) $value;
if ($valueFloat < 0) {
    echo json_encode([
        "success" => false,
        "message" => "Nilai penilaian tidak boleh negatif",
    ]);
    exit;
}

// Verify assistant exists
$chkA = $conn->prepare("SELECT id FROM ai_assistants WHERE id = ?");
$chkA->bind_param("i", $assistant_id);
$chkA->execute();
$chkA->store_result();
if ($chkA->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "AI Assistant tidak ditemukan"]);
    exit;
}

// Verify criteria exists
$chkC = $conn->prepare("SELECT id FROM criteria WHERE id = ?");
$chkC->bind_param("i", $criteria_id);
$chkC->execute();
$chkC->store_result();
if ($chkC->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Kriteria tidak ditemukan"]);
    exit;
}

// Check duplicate (assistant + criteria pair must be unique)
$dup = $conn->prepare(
    "SELECT id FROM assessments WHERE assistant_id = ? AND criteria_id = ?"
);
$dup->bind_param("ii", $assistant_id, $criteria_id);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Penilaian untuk kombinasi AI Assistant dan Kriteria ini sudah ada. Gunakan update.",
    ]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO assessments (assistant_id, criteria_id, value) VALUES (?, ?, ?)"
);
$stmt->bind_param("iid", $assistant_id, $criteria_id, $valueFloat);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Penilaian berhasil ditambahkan",
        "id"      => $conn->insert_id,
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menambahkan penilaian: " . $conn->error,
    ]);
}
