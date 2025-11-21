<?php
header('Content-Type: application/json');
require '../../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Method not allowed']]);
    exit;
}

// Validate inputs
$errors = [];
$required = ['name', 'email', 'subject', 'message', 'consent'];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst($field) . ' is required';
    }
}

// Email validation
if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Sanitize
$name = mysqli_real_escape_string($conn, trim($_POST['name']));
$email = mysqli_real_escape_string($conn, trim($_POST['email']));
$phone = !empty($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
$subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
$message = mysqli_real_escape_string($conn, trim($_POST['message']));

// Insert into DB (create table if needed)
$query = "
    INSERT INTO contact_messages (name, email, phone, subject, message, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $phone, $subject, $message);

if (mysqli_stmt_execute($stmt)) {
    // Optional: Send email to secretariat
    // mail('secretariat@kwupo.org.ng', "KWUPO Contact: $subject", $message, "From: $email");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you! Your message has been received. We will respond within 2 business days.'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'errors' => ['Failed to send message. Please try again later.']
    ]);
}