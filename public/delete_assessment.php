<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, DELETE");

include __DIR__ . "/../config/database.php";

$raw = file_get_contents("php://input");
if (!empty($raw)) {
    $parsed = json_decode($raw, true);
    if (is_array($parsed)) {
        $_POST = array_merge($_POST, $parsed);
    }
}

$id           = intval($_POST['id']           ?? $_GET['id']           ?? 0);
$assistant_id = intval($_POST['assistant_id'] ?? $_GET['assistant_id'] ?? 0);
$criteria_id  = intval($_POST['criteria_id']  ?? $_GET['criteria_id']  ?? 0);

if ($id > 0) {
    $check = $conn->prepare("SELECT id FROM assessments WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Penilaian tidak ditemukan"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM assessments WHERE id = ?");
    $stmt->bind_param("i", $id);

} elseif ($assistant_id > 0 && $criteria_id > 0) {
    $check = $conn->prepare(
        "SELECT id FROM assessments WHERE assistant_id = ? AND criteria_id = ?"
    );
    $check->bind_param("ii", $assistant_id, $criteria_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Penilaian tidak ditemukan"]);
        exit;
    }

    $stmt = $conn->prepare(
        "DELETE FROM assessments WHERE assistant_id = ? AND criteria_id = ?"
    );
    $stmt->bind_param("ii", $assistant_id, $criteria_id);

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
        "message" => "Penilaian berhasil dihapus",
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menghapus penilaian: " . $conn->error,
    ]);
}
