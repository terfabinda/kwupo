<?php
// finance/edit-fee.php
require '../includes/init.php';
require '../includes/helpers.php';

// Access control
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: ../signin.php');
    exit;
}

// Validate input
$history_id = (int)($_GET['history_id'] ?? 0);
if (!$history_id) {
    exit('Invalid ID');
}

// Fetch record
$history = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT pth.*, pt.type_name 
    FROM payment_type_history pth
    JOIN static_payment_types pt ON pth.payment_type_id = pt.payment_type_id
    WHERE pth.history_id = $history_id
"));

if (!$history) {
    exit('Record not found');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_amount = (float)($_POST['amount'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($new_amount <= 0 || empty($reason)) {
        $_SESSION['error'] = "Amount and reason are required.";
    } else {
        // Log correction
        $stmt = mysqli_prepare($conn, "
            INSERT INTO payment_history_corrections 
            (history_id, old_amount, new_amount, reason, corrected_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, 'iddis', 
            $history_id, 
            $history['amount'], 
            $new_amount, 
            $reason, 
            $_SESSION['user_id']
        );
        mysqli_stmt_execute($stmt);
        
        // Update amount
        $new_amount_safe = mysqli_real_escape_string($conn, $new_amount);
        mysqli_query($conn, "
            UPDATE payment_type_history 
            SET amount = $new_amount_safe 
            WHERE history_id = $history_id
        ");
        
        $_SESSION['success'] = "Fee amount corrected!";
        header("Location: payment-history.php?id={$history['payment_type_id']}");
        exit;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Correct Fee Amount</h1>
        <a href="<?= base_url("payment-history.php?id={$history['payment_type_id']}") ?>" class="btn btn-outline">← Back to History</a>
      </div>

      <div class="card">
        <h3>Payment Type: <?= htmlspecialchars($history['type_name']) ?></h3>
        <p><strong>Effective Date:</strong> <?= htmlspecialchars($history['effective_from']) ?> 
          <?php if ($history['effective_to']): ?>
            to <?= htmlspecialchars($history['effective_to']) ?>
          <?php else: ?>
            (Current)
          <?php endif; ?>
        </p>
        <p><strong>Current Amount:</strong> ₦<?= number_format($history['amount'], 2) ?></p>

        <form method="POST" class="signup-form" style="margin-top: 20px;">
          <div class="form-group">
            <label>New Amount (₦) <span>*</span></label>
            <input type="number" step="0.01" name="amount" 
                   value="<?= $history['amount'] ?>" min="0.01" required>
          </div>
          <div class="form-group">
            <label>Reason for Correction <span>*</span></label>
            <textarea name="reason" rows="3" required 
                      placeholder="e.g., Data entry error, typo in original amount"></textarea>
          </div>
          <button type="submit" class="btn">Save Correction</button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>