<?php
// assets/php/forgot-password-handler
header('Content-Type: application/json');

// In a production system, you'd:
// 1. Generate a secure token
// 2. Store it in a `password_resets` table
// 3. Send link via email/SMS
// For now, we simulate success for valid accounts

require '../../includes/config.php';


// Generate secure token
$token = bin2hex(random_bytes(64));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Insert into DB
$stmt = mysqli_prepare($conn, 
  "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'iss', $user_id, $token, $expires);
mysqli_stmt_execute($stmt);

// Send SMS (example with generic gateway)
// file_get_contents("https://sms-api.com/send?to=$phone&msg=Reset: yoursite.com/reset?token=$token");

// Or send email
// mail($email, "Password Reset", "Click: yoursite.com/reset?token=$token");


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');

if (empty($identifier)) {
    echo json_encode(['errors' => ['Please enter your phone number or email address.']]);
    exit;
}

// Check if user exists
$is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
if ($is_email) {
    $sql = "SELECT user_id FROM users WHERE email = ?";
} else {
    $sql = "SELECT user_id FROM users WHERE phone = ?";
}

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $identifier);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // ✅ In real app: generate token, send SMS/email
    // For now: simulate success
    echo json_encode(['success' => true]);
} else {
    // ❌ Don't reveal if account exists (security best practice)
    // Always show same message to prevent enumeration
    echo json_encode(['success' => true]);
}