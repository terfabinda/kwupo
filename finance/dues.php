<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Restrict to finance or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: ../signin.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment_type'])) {
        $type_name = trim($_POST['type_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_recurring = !empty($_POST['is_recurring']);
        
        if ($type_name) {
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO static_payment_types (type_name, description, interval_id, is_active) VALUES (?, ?, ?, TRUE)"
            );
            $interval_id = $is_recurring ? (int)($_POST['interval_id'] ?? 4) : null;
            mysqli_stmt_bind_param($stmt, 'ssii', $type_name, $description, $interval_id, 1);
            mysqli_stmt_execute($stmt);
            $_SESSION['success'] = "Payment type added successfully!";
            header('Location: dues.php');
            exit;
        }
    } elseif (isset($_POST['add_fee'])) {
    $payment_type_id = (int)($_POST['payment_type_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $effective_from = $_POST['effective_from'] ?? '';
    
    if (empty($effective_from)) {
        $_SESSION['error'] = "Effective date is required.";
        header('Location: dues.php');
        exit;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $effective_from)) {
        $_SESSION['error'] = "Invalid date format.";
        header('Location: dues.php');
        exit;
    }
    
    $date_parts = explode('-', $effective_from);
    if (!checkdate((int)$date_parts[1], (int)$date_parts[2], (int)$date_parts[0])) {
        $_SESSION['error'] = "Invalid calendar date.";
        header('Location: dues.php');
        exit;
    }
    
    $safe_date = mysqli_real_escape_string($conn, $effective_from);
    
    mysqli_autocommit($conn, FALSE);
    try {
        // Close current active record
        mysqli_query($conn, "
            UPDATE payment_type_history 
            SET is_active = FALSE 
            WHERE payment_type_id = $payment_type_id AND is_active = TRUE
        ");
        
        // Close previous record's effective_to
        $prev = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT effective_from 
            FROM payment_type_history 
            WHERE payment_type_id = $payment_type_id 
            ORDER BY effective_from DESC LIMIT 1
        "));
        
        if ($prev) {
            $new_date = new DateTime($safe_date);
            $new_date->modify('-1 day');
            $effective_to = $new_date->format('Y-m-d');
            $effective_to = mysqli_real_escape_string($conn, $effective_to);
            
            mysqli_query($conn, "
                UPDATE payment_type_history 
                SET effective_to = '$effective_to' 
                WHERE payment_type_id = $payment_type_id 
                ORDER BY effective_from DESC LIMIT 1
            ");
        }
        
        // INSERT WITH LITERAL DATE STRING (FIXED)
        mysqli_query($conn, "
            INSERT INTO payment_type_history 
            (payment_type_id, amount, effective_from, effective_to, is_active, created_by) 
            VALUES ($payment_type_id, $amount, '$safe_date', NULL, TRUE, {$_SESSION['user_id']})
        ");
        
        mysqli_commit($conn);
        $_SESSION['success'] = "New fee amount added!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    header('Location: dues.php');
    exit;
}
}

// Handle disable/enable
if (isset($_GET['disable'])) {
    $id = (int)$_GET['disable'];
    mysqli_query($conn, "UPDATE static_payment_types SET is_active = 0 WHERE payment_type_id = $id");
    header('Location: dues.php');
    exit;
} elseif (isset($_GET['enable'])) {
    $id = (int)$_GET['enable'];
    mysqli_query($conn, "UPDATE static_payment_types SET is_active = 1 WHERE payment_type_id = $id");
    header('Location: dues.php');
    exit;
}

// Fetch data
$payment_types = mysqli_query($conn, "
    SELECT 
        pt.payment_type_id,
        pt.type_name,
        pt.description,
        pt.interval_id,
        pt.is_active,
        ri.interval_name,
        (SELECT amount FROM payment_type_history 
         WHERE payment_type_id = pt.payment_type_id AND is_active = TRUE
         ORDER BY effective_from DESC LIMIT 1) as current_amount,
        (SELECT effective_from FROM payment_type_history 
         WHERE payment_type_id = pt.payment_type_id AND is_active = TRUE
         ORDER BY effective_from DESC LIMIT 1) as current_effective
    FROM static_payment_types pt
    LEFT JOIN static_recurrence_intervals ri ON pt.interval_id = ri.interval_id
    ORDER BY pt.type_name
");

$intervals = mysqli_query($conn, "SELECT * FROM static_recurrence_intervals ORDER BY interval_id");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Manage Dues & Fees</h1>
      </div>

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <!-- Add New Payment Type -->
      <div class="card" id="add-type">
        <h2>Add New Payment Type</h2>
        <form method="POST" class="signup-form">
          <div class="form-row">
            <div class="form-group">
              <label>Type Name <span>*</span></label>
              <input type="text" name="type_name" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <input type="text" name="description">
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" name="is_recurring" value="1"> Recurring?
              </label>
            </div>
            <div class="form-group" id="interval-field" style="display:none;">
              <label>Recurrence Interval</label>
              <select name="interval_id">
                <?php while ($interval = mysqli_fetch_assoc($intervals)): ?>
                  <option value="<?= $interval['interval_id'] ?>"><?= $interval['interval_name'] ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          <button type="submit" name="add_payment_type" class="btn">Add Payment Type</button>
        </form>
      </div>

      <!-- Add New Fee Amount -->
      <div class="card" style="margin-top: 30px;">
        <h2>Add New Fee Amount</h2>
        <form method="POST" class="signup-form">
          <div class="form-row">
            <div class="form-group">
              <label>Payment Type <span>*</span></label>
              <select name="payment_type_id" required>
                <option value="">Select Payment Type</option>
                <?php 
                // Reset result set for reuse
                mysqli_data_seek($payment_types, 0);
                while ($pt = mysqli_fetch_assoc($payment_types)): 
                ?>
                  <option value="<?= $pt['payment_type_id'] ?>"><?= htmlspecialchars($pt['type_name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Amount (₦) <span>*</span></label>
              <input type="number" step="0.01" name="amount" min="0" required>
            </div>
            <div class="form-group">
              <label>Effective From <span>*</span></label>
              <input type="date" name="effective_from" required>
            </div>
          </div>
          <button type="submit" name="add_fee" class="btn">Add Fee Amount</button>
        </form>
      </div>

      <!-- Payment Types Table -->
      <div class="card" style="margin-top: 30px;">
        <h2>Current Payment Types</h2>
        <?php if (mysqli_num_rows($payment_types) == 0): ?>
          <div class="alert error">No payment types found. Add one above.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="members-table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Recurrence</th>
                  <th>Current Amount</th>
                  <th>Effective From</th>
                  <th>Status</th>
                  <th>Edit</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                mysqli_data_seek($payment_types, 0);
                while ($pt = mysqli_fetch_assoc($payment_types)): 
                ?>
                  <tr>
                    <td><?= htmlspecialchars($pt['type_name']) ?></td>
                    <td><?= htmlspecialchars($pt['description'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($pt['interval_name'] ?? 'One-time') ?></td>
                    <td>₦<?= number_format($pt['current_amount'] ?? 0, 2) ?></td>
                    <td><?= htmlspecialchars($pt['current_effective'] ?? 'N/A') ?></td>
                    <td>
                      <?php if ($pt['is_active']): ?>
                        <span class="status paid">Active</span>
                      <?php else: ?>
                        <span class="status unpaid">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td>
  <!-- ... existing actions ... -->
  
  <!-- Edit Fee (links to current active record) -->
  <?php 
  // Get current active history ID for this payment type
  $current_hist = mysqli_fetch_assoc(mysqli_query($conn, "
      SELECT history_id 
      FROM payment_type_history 
      WHERE payment_type_id = {$pt['payment_type_id']} AND is_active = TRUE
  "));
  if ($current_hist): 
  ?>
    <a href="edit-fee.php?history_id=<?= $current_hist['history_id'] ?>" class="btn-icon" title="Edit Fee">
      <i class="fas fa-coins"></i>
    </a>
  <?php endif; ?>
</td>
                    <td>
                      <a href="edit-payment-type.php?id=<?= $pt['payment_type_id'] ?>" class="btn-icon" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <?php if ($pt['is_active']): ?>
                        <a href="dues.php?disable=<?= $pt['payment_type_id'] ?>" class="btn-icon" title="Disable">
                          <i class="fas fa-toggle-on"></i>
                        </a>
                      <?php else: ?>
                        <a href="dues.php?enable=<?= $pt['payment_type_id'] ?>" class="btn-icon" title="Enable">
                          <i class="fas fa-toggle-off"></i>
                        </a>
                      <?php endif; ?>
                      <a href="payment-history.php?id=<?= $pt['payment_type_id'] ?>" class="btn-icon" title="View History">
                        <i class="fas fa-history"></i>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script>
document.querySelector('input[name="is_recurring"]').addEventListener('change', function() {
  document.getElementById('interval-field').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include '../includes/footer.php'; ?>