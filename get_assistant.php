<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include "config/database.php";

$id = $_GET['id'] ?? null;

if ($id !== null) {
    // Single record
    $stmt = $conn->prepare(
        "SELECT id, name, company, model, description, created_at
         FROM ai_assistants WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "AI Assistant tidak ditemukan"]);
        exit;
    }

    $row = $result->fetch_assoc();
    echo json_encode(["success" => true, "data" => $row]);

} else {
    // All records
    $search = $_GET['search'] ?? '';

    if ($search !== '') {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare(
            "SELECT id, name, company, model, description, created_at
             FROM ai_assistants
             WHERE name LIKE ? OR company LIKE ? OR model LIKE ?
             ORDER BY id ASC"
        );
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query(
            "SELECT id, name, company, model, description, created_at
             FROM ai_assistants
             ORDER BY id ASC"
        );
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "success" => true,
        "total"   => count($data),
        "data"    => $data,
    ]);
}
