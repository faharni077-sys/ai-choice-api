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

$id    = intval($_POST['id']    ?? 0);
$value = trim($_POST['value']   ?? '');

// Also support update by assistant_id + criteria_id pair
$assistant_id = intval($_POST['assistant_id'] ?? 0);
$criteria_id  = intval($_POST['criteria_id']  ?? 0);

if ($value === '') {
    echo json_encode(["success" => false, "message" => "Nilai penilaian wajib diisi"]);
    exit;
}

$valueFloat = (float) $value;
if ($valueFloat < 0) {
    echo json_encode(["success" => false, "message" => "Nilai penilaian tidak boleh negatif"]);
    exit;
}

if ($id > 0) {
    // Update by primary key
    $check = $conn->prepare("SELECT id FROM assessments WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Penilaian tidak ditemukan"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE assessments SET value = ? WHERE id = ?");
    $stmt->bind_param("di", $valueFloat, $id);

} elseif ($assistant_id > 0 && $criteria_id > 0) {
    // Update by assistant+criteria pair (upsert)
    $check = $conn->prepare(
        "SELECT id FROM assessments WHERE assistant_id = ? AND criteria_id = ?"
    );
    $check->bind_param("ii", $assistant_id, $criteria_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        // Insert instead
        $ins = $conn->prepare(
            "INSERT INTO assessments (assistant_id, criteria_id, value) VALUES (?, ?, ?)"
        );
        $ins->bind_param("iid", $assistant_id, $criteria_id, $valueFloat);
        if ($ins->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Penilaian berhasil ditambahkan (upsert)",
                "id"      => $conn->insert_id,
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal: " . $conn->error]);
        }
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE assessments SET value = ? WHERE assistant_id = ? AND criteria_id = ?"
    );
    $stmt->bind_param("dii", $valueFloat, $assistant_id, $criteria_id);

} else {
    echo json_encode([
        "success" => false,
        "message" => "Harap kirim id, atau kombinasi assistant_id dan criteria_id",
    ]);
    exit;
}

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Penilaian berhasil diperbarui",
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal memperbarui penilaian: " . $conn->error,
    ]);
}
