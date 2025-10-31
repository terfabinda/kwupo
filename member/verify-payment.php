<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: ../signin.php');
    exit;
}

// Payment statuses (match your static_payment_status table)
define('STATUS_UNPAID', 1);
define('STATUS_PENDING', 2);
define('STATUS_PAID', 3);

$reference = $_GET['reference'] ?? '';

if (empty($reference) || !isset($_SESSION['pending_payment_id'])) {
    $_SESSION['error'] = "Invalid payment reference.";
    header('Location: payments.php');
    exit;
}

$payment_id = (int)$_SESSION['pending_payment_id'];
$paystack_secret_key = 'sk_test_a31fa98e25f35349665732991d37ba203ee535bc'; // ← Replace with live key in production

// ✅ FIXED: No spaces in URL
$url = "https://api.paystack.co/transaction/verify/" . urlencode($reference);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $paystack_secret_key",
    "Content-Type: application/json",
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $result = json_decode($response, true);
    
    if ($result && $result['status'] === true && $result['data']['status'] === 'success') {
        // Fetch payment details to check recurrence
        $payment = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT p.*, pth.payment_type_id
            FROM payments p
            JOIN payment_type_history pth ON p.history_id = pth.history_id
            WHERE p.payment_id = $payment_id
        "));
        
        if (!$payment) {
            $_SESSION['error'] = "Payment record not found.";
            header('Location: payments.php');
            exit;
        }
        
        // Get recurrence type
        $interval = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT ri.interval_name
            FROM static_payment_types pt
            JOIN static_recurrence_intervals ri ON pt.interval_id = ri.interval_id
            WHERE pt.payment_type_id = {$payment['payment_type_id']}
        "));
        
        $is_single = (strtolower($interval['interval_name'] ?? '') === 'single');
        
        if ($is_single) {
            // Prevent duplicate single payments
            $existing = mysqli_query($conn, "
                SELECT 1 FROM payments 
                WHERE user_id = {$payment['user_id']} 
                  AND history_id = {$payment['history_id']} 
                  AND status_id = " . STATUS_PAID . "
                  AND payment_id != $payment_id
            ");
            if (mysqli_num_rows($existing) > 0) {
                // Already paid — reject this one
                mysqli_query($conn, "UPDATE payments SET status_id = " . STATUS_UNPAID . " WHERE payment_id = $payment_id");
                $_SESSION['error'] = "This one-time payment has already been made.";
                header('Location: payments.php');
                exit;
            }
        }
        
        // ✅ All clear — mark as paid
         $paystack_ref = $result['data']['reference'] ?? $reference;
         $amount_paid = $result['data']['amount'] / 100; // kobo to Naira
         $status_paid = STATUS_PAID; // ← FIX: constant → variable
         $pid = $payment_id;         // ← ensure variable

         $stmt = mysqli_prepare($conn, "
            UPDATE payments 
            SET status_id = ?, transaction_ref = ?, amount_paid = ?
            WHERE payment_id = ?
         ");
         mysqli_stmt_bind_param($stmt, 'isdi', $status_paid, $paystack_ref, $amount_paid, $pid);
         mysqli_stmt_execute($stmt);
        
        // Clean session
        unset($_SESSION['pending_payment_id'], $_SESSION['payment_amount'], $_SESSION['payment_type_id']);
        $_SESSION['success'] = "Payment completed successfully!";
        header('Location: index.php');
        exit;
    }
}

// Log failed verifications (optional)
error_log("Paystack verification failed for ref: $reference, HTTP: $http_code, Response: $response");

$_SESSION['error'] = "Payment verification failed. Please contact support.";
header('Location: payments.php');
exit;
?>