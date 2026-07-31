<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include "config/database.php";

$id = $_GET['id'] ?? null;

if ($id !== null) {
    $stmt = $conn->prepare(
        "SELECT id, name, weight, type, description, created_at
         FROM criteria WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Kriteria tidak ditemukan"]);
        exit;
    }

    $row = $result->fetch_assoc();
    // Cast weight to float
    $row['weight'] = (float) $row['weight'];
    echo json_encode(["success" => true, "data" => $row]);

} else {
    $search = $_GET['search'] ?? '';

    if ($search !== '') {
        $like = "%" . $search . "%";
        $stmt = $conn->prepare(
            "SELECT id, name, weight, type, description, created_at
             FROM criteria
             WHERE name LIKE ? OR type LIKE ?
             ORDER BY id ASC"
        );
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query(
            "SELECT id, name, weight, type, description, created_at
             FROM criteria ORDER BY id ASC"
        );
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['weight'] = (float) $row['weight'];
        $data[] = $row;
    }

    // Calculate total weight for validation info
    $totalWeight = array_sum(array_column($data, 'weight'));

    echo json_encode([
        "success"      => true,
        "total"        => count($data),
        "total_weight" => round($totalWeight, 4),
        "data"         => $data,
    ]);
}
