<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

include __DIR__ . "/../config/database.php";

// ================================================================
// SAW Algorithm Implementation (Simple Additive Weighting)
// Reference: Fuzzy MADM, equations 3.3 and 3.4
// ================================================================

// Step 1: Fetch all assistants
$assistants = [];
$resAssist = $conn->query("SELECT id, name, company, model FROM ai_assistants ORDER BY id ASC");
while ($row = $resAssist->fetch_assoc()) {
    $assistants[$row['id']] = [
        'id'      => (int) $row['id'],
        'name'    => $row['name'],
        'company' => $row['company'],
        'model'   => $row['model'],
    ];
}

if (count($assistants) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Tidak ada data AI Assistant",
    ]);
    exit;
}

// Step 2: Fetch all criteria
$criteria = [];
$resCrit = $conn->query("SELECT id, name, weight, type FROM criteria ORDER BY id ASC");
while ($row = $resCrit->fetch_assoc()) {
    $criteria[$row['id']] = [
        'id'     => (int) $row['id'],
        'name'   => $row['name'],
        'weight' => (float) $row['weight'],
        'type'   => $row['type'], // "Benefit" or "Cost"
    ];
}

if (count($criteria) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Tidak ada data Kriteria",
    ]);
    exit;
}

// Step 3: Fetch all assessments (raw values)
$rawMatrix = [];
$resAssess = $conn->query(
    "SELECT assistant_id, criteria_id, value FROM assessments ORDER BY assistant_id, criteria_id"
);
while ($row = $resAssess->fetch_assoc()) {
    $aid = (int) $row['assistant_id'];
    $cid = (int) $row['criteria_id'];
    $val = (float) $row['value'];

    if (!isset($rawMatrix[$aid])) {
        $rawMatrix[$aid] = [];
    }
    $rawMatrix[$aid][$cid] = $val;
}

if (count($rawMatrix) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Tidak ada data Penilaian (assessments)",
    ]);
    exit;
}

// Step 4: Compute max and min per criterion (for normalization)
$maxValues = [];
$minValues = [];

foreach ($criteria as $cid => $crit) {
    $values = [];
    foreach ($rawMatrix as $aid => $scores) {
        if (isset($scores[$cid])) {
            $values[] = $scores[$cid];
        }
    }
    if (count($values) > 0) {
        $maxValues[$cid] = max($values);
        $minValues[$cid] = min($values);
    } else {
        $maxValues[$cid] = 1;
        $minValues[$cid] = 1;
    }
}

// Step 5: Normalize using SAW formula (eq. 3.3)
// r_ij = x_ij / max(x_ij)    if Benefit
// r_ij = min(x_ij) / x_ij    if Cost
$normalizedMatrix = [];

foreach ($rawMatrix as $aid => $scores) {
    $normalizedMatrix[$aid] = [];
    foreach ($criteria as $cid => $crit) {
        $xij = isset($scores[$cid]) ? $scores[$cid] : 0;

        if ($xij == 0) {
            $rij = 0;
        } elseif ($crit['type'] === 'Benefit') {
            $rij = $xij / $maxValues[$cid];
        } else {
            // Cost
            $rij = $minValues[$cid] / $xij;
        }

        $normalizedMatrix[$aid][$cid] = $rij;
    }
}

// Step 6: Calculate preference value Vi (eq. 3.4)
// Vi = Σ (w_j * r_ij)
$preferences = [];

foreach ($assistants as $aid => $assist) {
    $vi = 0;

    if (isset($normalizedMatrix[$aid])) {
        foreach ($criteria as $cid => $crit) {
            $rij = isset($normalizedMatrix[$aid][$cid]) ? $normalizedMatrix[$aid][$cid] : 0;
            $wj  = $crit['weight'];
            $vi += $wj * $rij;
        }
    }

    $preferences[$aid] = [
        'assistant_id'   => $aid,
        'name'           => $assist['name'],
        'company'        => $assist['company'],
        'model'          => $assist['model'],
        'preference_value' => round($vi, 6),
    ];
}

// Step 7: Sort by preference value descending (highest is best)
usort($preferences, function($a, $b) {
    return $b['preference_value'] <=> $a['preference_value'];
});

// Step 8: Assign ranks
$ranking = [];
$rank = 1;
foreach ($preferences as $pref) {
    $ranking[] = array_merge($pref, ['rank' => $rank]);
    $rank++;
}

// Build detailed response
$response = [
    "success"            => true,
    "total_alternatives" => count($assistants),
    "total_criteria"     => count($criteria),
    "ranking"            => $ranking,
    "normalization"      => [],
    "raw_matrix"         => [],
    "criteria"           => array_values($criteria),
];

// Include normalized matrix for transparency
foreach ($normalizedMatrix as $aid => $normScores) {
    $response["normalization"][] = [
        'assistant_id'   => $aid,
        'assistant_name' => $assistants[$aid]['name'],
        'normalized_scores' => $normScores,
    ];
}

// Include raw matrix
foreach ($rawMatrix as $aid => $rawScores) {
    $response["raw_matrix"][] = [
        'assistant_id'   => $aid,
        'assistant_name' => $assistants[$aid]['name'],
        'raw_scores'     => $rawScores,
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
