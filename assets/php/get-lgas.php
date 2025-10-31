<?php
// assets/php/get-lgas.php
require '../../includes/init.php';
header('Content-Type: application/json');

$state_id = (int)($_GET['state_id'] ?? 0);
if ($state_id <= 0) {
    echo json_encode([]);
    exit;
}

// Query your LGA table
$result = mysqli_query($conn, "
    SELECT lga_id, state_lga 
    FROM static_states_lga 
    WHERE state_id = $state_id 
    ORDER BY state_lga
");

if (!$result) {
    error_log("LGA Query Error: " . mysqli_error($conn));
    echo json_encode([]);
    exit;
}

$lgas = [];
while ($row = mysqli_fetch_assoc($result)) {
    $lgas[] = [
        'lga_id' => (int)$row['lga_id'],
        'state_lga' => htmlspecialchars($row['state_lga'], ENT_QUOTES, 'UTF-8')
    ];
}
echo json_encode($lgas);