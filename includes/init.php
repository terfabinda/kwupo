<?php
// includes/init.php

// Prevent multiple inclusions
if (defined('DB_HOST')) {
    return; // or exit;
}

if (session_status() === PHP_SESSION_NONE) {
    // Session config for localhost + production
    $cookie_params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookie_params['path'],
        'domain' => $cookie_params['domain'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Define project root (adjust if needed)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR); // Points to /kwupo/
}

// Database config
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dbkwupo');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("DB Connection failed");
}
mysqli_set_charset($conn, "utf8mb4");
?>