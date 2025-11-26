<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Exit early on invalid method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed']]);
    exit;
}

// Load dependencies
require '../../includes/init.php';

$errors = [];
$success = false;

// === 1. SANITIZE & EXTRACT INPUTS (defensive) ===
$firstname    = trim($_POST['firstname']    ?? '');
$middlename   = trim($_POST['middlename']   ?? '');
$surname      = trim($_POST['surname']      ?? '');
$title_prefix_id = filter_var($_POST['title_prefix_id'] ?? '', FILTER_VALIDATE_INT) ?: null;
$title_suffix = trim($_POST['title_suffix'] ?? '');
$lga_id       = filter_var($_POST['lga_id'] ?? '', FILTER_VALIDATE_INT) ?: 0;
$ward_id      = filter_var($_POST['ward_id'] ?? '', FILTER_VALIDATE_INT) ?: 0;
$email        = trim($_POST['email'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$password     = $_POST['password']     ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$terms        = !empty($_POST['terms']);
$privacy      = !empty($_POST['privacy']);

// === 2. VALIDATION: REQUIRED FIELDS ===
if (empty($firstname))      $errors[] = 'First name is required.';
if (empty($surname))        $errors[] = 'Surname is required.';
if ($lga_id <= 0)           $errors[] = 'Please select your Local Government Area (LGA).';
if ($ward_id <= 0)          $errors[] = 'Please select your Council Ward.';
if (empty($phone))          $errors[] = 'Phone number is required.';

// === 3. VALIDATION: PHONE (Nigerian format) ===
$phone_clean = preg_replace('/[^0-9]/', '', $phone);
if (!preg_match('/^(?:234|0)[789][01]\d{8}$/', $phone_clean)) {
    $errors[] = 'Invalid Nigerian phone number. Format: 08012345678 or 2348012345678.';
} else {
    // Normalize to 0-prefixed (e.g., 08012345678)
    if (substr($phone_clean, 0, 3) === '234') {
        $phone_clean = '0' . substr($phone_clean, 3);
    }
    $phone = $phone_clean;
}

// === 4. VALIDATION: EMAIL ===
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
} elseif ($email) {
    // Normalize
    $email = strtolower($email);
}

// === 5. ✅ PASSWORD VALIDATION (STRICT) ===
if (empty($password)) {
    $errors[] = 'Password is required.';
} else {
    // 5.1 Length
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    // 5.2 Complexity: at least 3 of: uppercase, lowercase, number, symbol
    $has_upper = preg_match('/[A-Z]/', $password);
    $has_lower = preg_match('/[a-z]/', $password);
    $has_digit = preg_match('/[0-9]/', $password);
    $has_symbol = preg_match('/[^a-zA-Z0-9]/', $password);
    
    $complexity = $has_upper + $has_lower + $has_digit + $has_symbol;
    if ($complexity < 3) {
        $errors[] = 'Password must include at least 3 of: uppercase letter, lowercase letter, number, symbol (e.g., !@#).';
    }
    
    // 5.3 No whitespace (common issue)
    if (preg_match('/\s/', $password)) {
        $errors[] = 'Password must not contain spaces.';
    }
    
    // 5.4 Confirm match
    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }
}

// === 6. VALIDATION: TITLE PREFIX (if provided) ===
if ($title_prefix_id !== null) {
    $check_title = mysqli_prepare($conn, "SELECT 1 FROM static_user_titles WHERE title_id = ?");
    mysqli_stmt_bind_param($check_title, 'i', $title_prefix_id);
    mysqli_stmt_execute($check_title);
    $title_exists = mysqli_stmt_get_result($check_title);
    if (mysqli_num_rows($title_exists) === 0) {
        $errors[] = 'Invalid title selection.';
    }
    mysqli_stmt_close($check_title);
}

// === 7. VALIDATION: TITLE SUFFIX ===
if ($title_suffix) {
    // Allow: letters, numbers, dots, commas, spaces, hyphens, slashes (e.g., "PhD, MON")
    if (!preg_match('/^[a-zA-Z0-9.,\/\-\s]+$/', $title_suffix)) {
        $errors[] = 'Suffix can only contain letters, numbers, dots, commas, hyphens, slashes, and spaces.';
    }
    // Truncate to DB limit (100 chars)
    $title_suffix = substr($title_suffix, 0, 100);
}

// === 8. VALIDATION: TERMS & PRIVACY ===
if (!$terms)   $errors[] = 'You must agree to the Terms and Conditions.';
if (!$privacy) $errors[] = 'You must agree to the Privacy Policy.';

// === 9. CHECK FOR DUPLICATES (phone/email) ===
if (empty($errors)) {
    $check_user = mysqli_prepare($conn, "SELECT user_id FROM users WHERE phone = ? OR (email = ? AND email != '')");
    mysqli_stmt_bind_param($check_user, 'ss', $phone, $email);
    mysqli_stmt_execute($check_user);
    $dup_result = mysqli_stmt_get_result($check_user);
    
    if (mysqli_num_rows($dup_result) > 0) {
        $row = mysqli_fetch_assoc($dup_result);
        // Determine which field matched
        $dup_phone = mysqli_query($conn, "SELECT 1 FROM users WHERE phone = '$phone'");
        if (mysqli_num_rows($dup_phone)) {
            $errors[] = 'This phone number is already registered.';
        } elseif ($email) {
            $errors[] = 'This email address is already registered.';
        } else {
            $errors[] = 'A member with this contact information already exists.';
        }
    }
    mysqli_stmt_close($check_user);
}

// === 10. INSERT USER (IF VALID) ===
if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Use prepared statement for ALL values (including title_prefix_id as nullable)
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO users (
            firstname, middlename, surname, title_prefix_id, title_suffix, 
            ward_id, email, phone, password_hash, role_id, is_confirmed, is_active, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 3, 0, 0, NOW())"
    );
    
    // Bind title_prefix_id as nullable integer
    $title_id_bind = $title_prefix_id ?? null;
    mysqli_stmt_bind_param($stmt, 'sssisssss', 
        $firstname, $middlename, $surname, $title_id_bind, $title_suffix,
        $ward_id, $email, $phone, $hashed_password
    );

    if (mysqli_stmt_execute($stmt)) {
        $success = true;

        // ✅ Optional: Log registration (remove in production or log to file)
        // error_log("New member registered: $phone | $email | " . mysqli_insert_id($conn));

    } else {
        // Log actual DB error (for debugging only)
        error_log("Signup DB Error: " . mysqli_error($conn));
        $errors[] = 'Registration failed due to a system error. Please try again or contact support.';
    }
    
    mysqli_stmt_close($stmt);
}

// === 11. RESPONSE ===
if ($success) {
    http_response_code(201); // Created
    echo json_encode(['success' => true]);
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'errors' => $errors]);
}