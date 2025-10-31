<?php
// assets/php/signup-handler.php
header('Content-Type: application/json');

// Use init.php for session + DB (2 levels up)
require '../../includes/init.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Sanitize inputs
$firstname = trim($_POST['firstname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$surname = trim($_POST['surname'] ?? '');
$title_prefix_id = (int)($_POST['title_prefix_id'] ?? 0);
$title_suffix = trim($_POST['title_suffix'] ?? '');
$lga_id = (int)($_POST['lga_id'] ?? 0);
$ward_id = (int)($_POST['ward_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$terms = !empty($_POST['terms']);
$privacy = !empty($_POST['privacy']);

// Validation
if (empty($firstname)) $errors[] = "First name is required.";
if (empty($surname)) $errors[] = "Surname is required.";
if ($lga_id <= 0) $errors[] = "Please select your LGA.";
if ($ward_id <= 0) $errors[] = "Please select your ward.";
if (empty($phone)) $errors[] = "Phone number is required.";
if (!preg_match('/^0[789][01]\d{8}$/', $phone)) $errors[] = "Invalid Nigerian phone number.";
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
if (!$terms) $errors[] = "You must agree to the Terms and Conditions.";
if (!$privacy) $errors[] = "You must agree to the Privacy Policy.";

// Validate title_prefix_id (if provided)
if ($title_prefix_id > 0) {
    $check_title = mysqli_query($conn, "SELECT title_id FROM static_user_titles WHERE title_id = $title_prefix_id");
    if (mysqli_num_rows($check_title) === 0) {
        $errors[] = "Invalid title selection.";
    }
}

// Validate title_suffix (alphanumeric + dots/commas/spaces only)
if (!empty($title_suffix)) {
    if (!preg_match('/^[a-zA-Z0-9.,\s]+$/', $title_suffix)) {
        $errors[] = "Suffix can only contain letters, numbers, dots, commas, and spaces.";
    }
    // Truncate to 100 chars (matches DB)
    $title_suffix = substr($title_suffix, 0, 100);
}

// Check duplicates
if (empty($errors)) {
    $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE phone = ? OR email = ?");
    mysqli_stmt_bind_param($check, 'ss', $phone, $email);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "A member with this phone or email already exists.";
    }
}

// Insert if valid
if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Handle NULL for title_prefix_id
    $prefix_id = ($title_prefix_id > 0) ? $title_prefix_id : 'NULL';
    
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO users (firstname, middlename, surname, title_prefix_id, title_suffix, ward_id, email, phone, password_hash, role_id, is_confirmed, is_active) 
         VALUES (?, ?, ?, $prefix_id, ?, ?, ?, ?, ?, 3, 0, 0)"
    );
    
    // Bind parameters (skip title_prefix_id since it's handled in query)
    mysqli_stmt_bind_param($stmt, 'ssssssss', 
        $firstname, $middlename, $surname, $title_suffix, $ward_id, $email, $phone, $hashed_password
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $success = true;
    } else {
        // Log error for debugging (remove in production)
        error_log("Signup DB Error: " . mysqli_error($conn));
        $errors[] = "Registration failed. Please try again.";
    }
}

// Return JSON
if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['errors' => $errors]);
}