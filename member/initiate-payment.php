<?php
// initiate-payment.php
require '../includes/init.php';
require '../includes/helpers.php';

// Member access only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: ../signin.php');
    exit;
}

// Verify pending payment exists
if (!isset($_SESSION['pending_payment_id'])) {
    $_SESSION['error'] = "No pending payment found.";
    header('Location: payments.php');
    exit;
}

// Fetch user data
$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, phone FROM users WHERE user_id = $user_id"));
if (!$user) {
    $_SESSION['error'] = "User account not found.";
    header('Location: payments.php');
    exit;
}

// Payment data
$payment_id = (int)$_SESSION['pending_payment_id'];
$amount_ngn = (float)$_SESSION['payment_amount'];
$amount_kobo = $amount_ngn * 100;
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$reference = $_SESSION['local_reference'] ?? 'KWUPO_' . $payment_id . '_' . time();

// Paystack test public key (replace with your real key from Paystack dashboard)
$paystack_public_key = 'pk_test_19b372a2e2f99b7905009651949b792fa46a2c0e';
?>

<?php include '../includes/header.php'; ?>

<style>
.payment-confirmation-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  padding: 30px;
  max-width: 600px;
  margin: 0 auto;
  border: 1px solid #eee;
}

.payment-confirmation-card h2 {
  margin-top: 0;
  color: var(--accent);
  font-size: 1.5rem;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f5f5f5;
}

.payment-detail {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
  font-size: 1.1rem;
}

.payment-amount {
  color: var(--accent);
  font-weight: 700;
  font-size: 1.3rem;
}

.paystack-button {
  width: 100%;
  padding: 16px;
  font-size: 1.1rem;
  background: #000;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.3s;
  font-weight: 600;
  margin-top: 20px;
}

.paystack-button:hover {
  background: #333;
}

.payment-note {
  background: #fff8e1;
  padding: 15px;
  border-radius: 8px;
  margin-top: 25px;
  font-size: 0.95rem;
  color: #5d4037;
}
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Complete Payment</h1>
        <a href="payments.php" class="btn btn-outline">← Back to Payments</a>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <div class="payment-confirmation-card">
        <h2>Payment Confirmation</h2>
        
        <div class="payment-detail">
          <span>Amount:</span>
          <span class="payment-amount">₦<?= number_format($amount_ngn, 2) ?></span>
        </div>
        
        <div class="payment-detail">
          <span>Reference:</span>
          <span><?= htmlspecialchars($reference) ?></span>
        </div>
        
        <div class="payment-detail">
          <span>Email:</span>
          <span><?= htmlspecialchars($email) ?></span>
        </div>

        <button id="pay-button" class="paystack-button">
          Pay Now with Paystack
        </button>

        <div class="payment-note">
          <p><strong>Secure Payment:</strong> You will be redirected to Paystack's encrypted payment page. KWUPO does not store your card details.</p>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Paystack SDK (no extra spaces!) -->
<script src="https://js.paystack.co/v1/inline.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('pay-button').addEventListener('click', function() {
    const button = this;
    button.disabled = true;
    button.textContent = 'Redirecting...';

    PaystackPop.setup({
      key: '<?= $paystack_public_key ?>',
      email: '<?= addslashes($email) ?>',
      amount: <?= $amount_kobo ?>,
      ref: '<?= addslashes($reference) ?>',
      phone: '<?= addslashes($phone) ?>',
      callback: function(response) {
        window.location.href = 'verify-payment.php?reference=' + encodeURIComponent(response.reference);
      },
      onClose: function() {
        button.disabled = false;
        button.textContent = 'Pay Now with Paystack';
        alert('Payment was cancelled.');
      }
    }).openIframe();
  });
});
</script>

<?php include '../includes/footer.php'; ?>