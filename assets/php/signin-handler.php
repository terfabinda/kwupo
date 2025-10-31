<?php
// assets/php/signin-handler.php
header('Content-Type: application/json');

// Use init.php for session + DB (2 levels up)
require '../../includes/init.php';

// Initialize response
$response = ['success' => false, 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['errors'][] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($identifier) || empty($password)) {
    $response['errors'][] = 'Please enter both your phone/email and password.';
    echo json_encode($response);
    exit;
}

// Determine if identifier is email or phone
$is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
$sql = $is_email 
    ? "SELECT user_id, firstname, surname, password_hash, role_id, is_active, is_confirmed FROM users WHERE email = ?"
    : "SELECT user_id, firstname, surname, password_hash, role_id, is_active, is_confirmed FROM users WHERE phone = ?";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    // Log DB error (remove in production)
    error_log("Signin DB Prepare Error: " . mysqli_error($conn));
    $response['errors'][] = 'System error. Please try again later.';
    echo json_encode($response);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $identifier);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    if (!password_verify($password, $user['password_hash'])) {
        $response['errors'][] = 'Invalid password.';
    } elseif (!$user['is_confirmed']) {
        $response['errors'][] = 'Your account is pending confirmation. Please contact the administrator.';
    } elseif (!$user['is_active']) {
        $response['errors'][] = 'Your account has been deactivated. Please contact support.';
    } else {
        // Login successful
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['surname'] = $user['surname'];
        $_SESSION['role_id'] = $user['role_id'];

        // Set redirect based on role
        $redirect = 'member/index';
        if ($user['role_id'] == 1) {
            $redirect = 'admin/index';
        } elseif ($user['role_id'] == 2) {
            $redirect = 'finance/index';
        }

        // Force session write
        session_write_close();

        $response = ['success' => true, 'redirect' => $redirect];
    }
} else {
    // Generic message to prevent account enumeration
    $response['errors'][] = 'Invalid phone/email or password.';
}

echo json_encode($response);
exit;