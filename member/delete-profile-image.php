<?php
session_start();
require '../includes/init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current image
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_image FROM users WHERE user_id = $user_id"));
if ($user['profile_image']) {
    // Delete file
    if (file_exists("../uploads/profiles/{$user['profile_image']}")) {
        unlink("../uploads/profiles/{$user['profile_image']}");
    }
    
    // Update DB
    mysqli_query($conn, "UPDATE users SET profile_image = NULL WHERE user_id = $user_id");
}

echo json_encode(['success' => true]);
?>