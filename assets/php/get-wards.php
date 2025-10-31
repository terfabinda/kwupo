<?php
// assets/php/get-wards.php
header('Content-Type: application/json');

// Use init.php for DB connection (2 levels up)
require '../../includes/init.php';

// Get LGA ID from query string
$lga_id = (int)($_GET['lga_id'] ?? 0);

// Validate input
if ($lga_id <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch wards (only from valid LGAs)
$result = mysqli_query($conn, "
    SELECT ward_id, ward_name 
    FROM static_users_council_wards 
    WHERE lga_id = $lga_id 
    ORDER BY ward_name
");

if (!$result) {
    // Log error (remove in production)
    error_log("Wards Query Error: " . mysqli_error($conn));
    echo json_encode([]);
    exit;
}

$wards = [];
while ($row = mysqli_fetch_assoc($result)) {
    $wards[] = $row;
}

echo json_encode($wards);