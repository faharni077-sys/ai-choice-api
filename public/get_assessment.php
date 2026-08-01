<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include __DIR__ . "/../config/database.php";

$assistant_id = $_GET['assistant_id'] ?? null;
$criteria_id  = $_GET['criteria_id']  ?? null;
$id           = $_GET['id']           ?? null;

if ($id !== null) {
    // Single assessment by id
    $stmt = $conn->prepare(
        "SELECT a.id, a.assistant_id, ai.name AS assistant_name,
                a.criteria_id, c.name AS criteria_name, c.type AS criteria_type,
                c.weight, a.value, a.created_at
         FROM assessments a
         JOIN ai_assistants ai ON ai.id = a.assistant_id
         JOIN criteria c       ON c.id  = a.criteria_id
         WHERE a.id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Penilaian tidak ditemukan"]);
        exit;
    }

    $row = $result->fetch_assoc();
    $row['value']  = (float) $row['value'];
    $row['weight'] = (float) $row['weight'];
    echo json_encode(["success" => true, "data" => $row]);

} elseif ($assistant_id !== null) {
    // All criteria values for one assistant
    $stmt = $conn->prepare(
        "SELECT a.id, a.assistant_id, ai.name AS assistant_name,
                a.criteria_id, c.name AS criteria_name, c.type AS criteria_type,
                c.weight, a.value
         FROM assessments a
         JOIN ai_assistants ai ON ai.id = a.assistant_id
         JOIN criteria c       ON c.id  = a.criteria_id
         WHERE a.assistant_id = ?
         ORDER BY c.id ASC"
    );
    $stmt->bind_param("i", $assistant_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['value']  = (float) $row['value'];
        $row['weight'] = (float) $row['weight'];
        $data[] = $row;
    }

    echo json_encode(["success" => true, "total" => count($data), "data" => $data]);

} else {
    // Full matrix — all assistants × all criteria
    $result = $conn->query(
        "SELECT a.id, a.assistant_id, ai.name AS assistant_name,
                a.criteria_id, c.name AS criteria_name, c.type AS criteria_type,
                c.weight, a.value
         FROM assessments a
         JOIN ai_assistants ai ON ai.id = a.assistant_id
         JOIN criteria c       ON c.id  = a.criteria_id
         ORDER BY a.assistant_id ASC, c.id ASC"
    );

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['value']  = (float) $row['value'];
        $row['weight'] = (float) $row['weight'];
        $data[] = $row;
    }

    // Also build grouped matrix for convenience
    $matrix = [];
    foreach ($data as $row) {
        $aName = $row['assistant_name'];
        if (!isset($matrix[$aName])) {
            $matrix[$aName] = [
                'assistant_id' => (int) $row['assistant_id'],
                'name'         => $aName,
                'scores'       => [],
            ];
        }
        $matrix[$aName]['scores'][] = [
            'criteria_id'   => (int) $row['criteria_id'],
            'criteria_name' => $row['criteria_name'],
            'criteria_type' => $row['criteria_type'],
            'weight'        => $row['weight'],
            'value'         => $row['value'],
        ];
    }

    echo json_encode([
        "success" => true,
        "total"   => count($data),
        "matrix"  => array_values($matrix),
        "data"    => $data,
    ]);
}
