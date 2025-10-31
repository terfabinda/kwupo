<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Member access only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id, created_at FROM users WHERE user_id = $user_id"));
if (!$user) {
    header('Location: ../signin.php');
    exit;
}

// Parse join date
$join_date = new DateTime($user['created_at']);
$current_date = new DateTime();

// Fetch active payment types with recurrence info (only check effective_from)
$payment_items_query = "
    SELECT 
        pt.payment_type_id,
        pt.type_name,
        pt.description,
        pth.amount,
        pth.history_id,
        pth.effective_from,
        ri.interval_name,
        ri.interval_code
    FROM static_payment_types pt
    JOIN payment_type_history pth ON pt.payment_type_id = pth.payment_type_id
    JOIN static_recurrence_intervals ri ON pt.interval_id = ri.interval_id
    WHERE pt.is_active = 1 
      AND pth.is_active = 1
      AND CURDATE() >= pth.effective_from
    ORDER BY pt.type_name
";

$payment_items_result = mysqli_query($conn, $payment_items_query);
$eligible_items = [];

while ($item = mysqli_fetch_assoc($payment_items_result)) {
    $show_item = true;
    $display_name = $item['type_name'];
    $display_description = $item['description']; // Default description

    // Normalize interval name
    $interval = strtolower($item['interval_name']);
    
    if ($interval === 'single') {
        // Hide if already paid (status = paid/confirmed)
        $paid_check = mysqli_query($conn, "
            SELECT 1 FROM payments 
            WHERE user_id = $user_id 
              AND history_id = {$item['history_id']} 
              AND status_id IN (1, 3)
            LIMIT 1
        ");
        if (mysqli_num_rows($paid_check) > 0) {
            $show_item = false;
        }
    } else {
        // Recurring: determine if this period is due
        $effective_from = new DateTime($item['effective_from']);
        
        // Skip if effective_from is in the future
        if ($effective_from > $current_date) {
            $show_item = false;
        } else {
            // Determine next due period based on join date and interval
            if ($item['interval_code'] === 'MONTHLY') {
                // Monthly: due every month from join month onward
                $join_month = $join_date->format('Y-m');
                $current_month = $current_date->format('Y-m');
                
                // Only show if current month >= join month
                if ($current_month >= $join_month) {
                    $period = $current_date->format('F Y'); // e.g., "October 2025"
                    $display_name .= ' – ' . $period;
                    $display_description .= ' for ' . $period; // 👈 Enhanced description
                    
                    // Check if already paid for this month
                    $paid_check = mysqli_query($conn, "
                        SELECT 1 FROM payments p
                        JOIN payment_type_history pth ON p.history_id = pth.history_id
                        WHERE p.user_id = $user_id 
                          AND pth.payment_type_id = {$item['payment_type_id']}
                          AND DATE_FORMAT(p.payment_date, '%Y-%m') = '$current_month'
                          AND p.status_id IN (1, 3)
                        LIMIT 1
                    ");
                    if (mysqli_num_rows($paid_check) > 0) {
                        $show_item = false; // Already paid this month
                    }
                } else {
                    $show_item = false; // Not due yet
                }
            }
            // Add other intervals (WEEKLY, YEARLY) as needed
        }
    }
    
    if ($show_item) {
        $item['display_name'] = $display_name;
        $item['display_description'] = $display_description; // 👈 Critical: store enhanced description
        $eligible_items[] = $item;
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_type_id = (int)($_POST['payment_type_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $history_id = (int)($_POST['history_id'] ?? 0);
    
    // Validate inputs
    if ($payment_type_id > 0 && $amount > 0 && $history_id > 0) {
        // Verify the amount matches the current active history record
        $verify = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT amount FROM payment_type_history 
            WHERE history_id = $history_id 
              AND is_active = 1
              AND CURDATE() >= effective_from
              AND (effective_to IS NULL OR CURDATE() <= effective_to)
        "));
        
        if ($verify) {
            $expected_amount = (float)$verify['amount'];
            // Allow minor floating point differences
            if (abs($amount - $expected_amount) < 0.01) {
                // Generate local reference
                $local_reference = 'KWUPO_' . $user_id . '_' . time();

                // Create pending payment record WITH reference
                $stmt = mysqli_prepare($conn, "
                    INSERT INTO payments 
                    (user_id, history_id, amount_paid, payment_date, status_id, transaction_ref) 
                    VALUES (?, ?, ?, CURDATE(), 2, ?)
                ");
                mysqli_stmt_bind_param($stmt, 'iids', $user_id, $history_id, $amount, $local_reference);
                mysqli_stmt_execute($stmt);

                $payment_id = mysqli_insert_id($conn);
                $_SESSION['pending_payment_id'] = $payment_id;
                $_SESSION['payment_amount'] = $amount;
                $_SESSION['payment_type_id'] = $payment_type_id;
                $_SESSION['local_reference'] = $local_reference;
                
                // Redirect to Paystack
                header('Location: initiate-payment.php');
                exit;
            }
        }
    }
    
    // If validation fails
    $_SESSION['error'] = "Invalid payment selection. Please try again.";
    header('Location: payments.php');
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Pay Dues & Levies</h1>
        <a href="index.php" class="btn btn-outline">← Back to Dashboard</a>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <?php if (empty($eligible_items)): ?>
        <div class="alert info">
          All dues are up to date. No payments required at this time.
        </div>
      <?php else: ?>
        <div class="payment-instructions">
          <p>Select a payment item below to proceed. All transactions are secured via Paystack.</p>
        </div>

        <form method="POST" class="payment-form">
          <div class="payment-items">
            <?php foreach ($eligible_items as $item): ?>
              <div class="payment-item">
                <div class="payment-header">
                  <h3><?= htmlspecialchars($item['display_name']) ?></h3>
                  <span class="payment-amount">₦<?= number_format($item['amount'], 2) ?></span>
                </div>
                <p class="payment-description">
                  <?= htmlspecialchars($item['display_description']) ?> <!-- 👈 Now uses enhanced description -->
                </p>
                <input type="radio" 
                       name="payment_type_id" 
                       value="<?= (int)$item['payment_type_id'] ?>" 
                       id="pay_<?= (int)$item['payment_type_id'] ?>"
                       data-history="<?= (int)$item['history_id'] ?>"
                       data-amount="<?= (float)$item['amount'] ?>"
                       required>
                <label for="pay_<?= (int)$item['payment_type_id'] ?>">Select this payment</label>
              </div>
            <?php endforeach; ?>
          </div>

          <input type="hidden" name="history_id" id="selected_history">
          <input type="hidden" name="amount" id="selected_amount">

          <button type="submit" class="btn" id="pay-button" disabled>
            Proceed to Payment
          </button>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>

<style>
/* ... your exact CSS unchanged ... */
.payment-instructions {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 30px;
  border-left: 4px solid var(--accent);
}
.payment-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 25px;
  margin-bottom: 30px;
}
.payment-item {
  border: 2px solid #eee;
  border-radius: 8px;
  padding: 20px;
  transition: var(--transition);
}
.payment-item:hover {
  border-color: var(--accent);
}
.payment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.payment-header h3 {
  margin: 0;
  color: var(--black);
}
.payment-amount {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--accent);
}
.payment-description {
  color: #666;
  margin-bottom: 15px;
  line-height: 1.5;
}
.payment-item input[type="radio"] {
  display: none;
}
.payment-item input[type="radio"]:checked + label {
  background: var(--accent);
  color: white;
  padding: 10px 15px;
  border-radius: 4px;
  display: inline-block;
  margin-top: 10px;
}
.payment-item input[type="radio"] + label {
  cursor: pointer;
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 4px;
  display: inline-block;
  margin-top: 10px;
  transition: var(--transition);
}
#pay-button {
  width: 100%;
  padding: 16px;
  font-size: 1.1rem;
}
#pay-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

<script>
/* ... your exact JS unchanged ... */
(function() {
  function initPaymentForm() {
    const radios = document.querySelectorAll('input[name="payment_type_id"]');
    const payButton = document.getElementById('pay-button');
    const historyInput = document.getElementById('selected_history');
    const amountInput = document.getElementById('selected_amount');

    if (!radios.length || !payButton || !historyInput || !amountInput) {
      setTimeout(initPaymentForm, 100);
      return;
    }

    radios.forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.checked) {
          const history = this.dataset.history;
          const amount = this.dataset.amount;
          if (history && amount) {
            historyInput.value = history;
            amountInput.value = amount;
            payButton.disabled = false;
          }
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPaymentForm);
  } else {
    initPaymentForm();
  }
})();
</script>

<?php include '../includes/footer.php'; ?>