<?php
session_start();
require '../includes/init.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 3)) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user + ward + LGA
$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT u.*, w.ward_name, l.lga_name 
    FROM users u
    LEFT JOIN static_users_council_wards w ON u.ward_id = w.ward_id
    LEFT JOIN static_users_lga l ON w.lga_id = l.lga_id
    WHERE u.user_id = $user_id
"));

// Check if REGISTRATION FEE is paid (critical for membership status)
$registration_paid = false;
$reg_query = mysqli_query($conn, "
    SELECT 1 
    FROM payments p
    JOIN payment_type_history h ON p.history_id = h.history_id
    JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
    WHERE p.user_id = $user_id 
      AND t.type_name = 'Registration Fee'
      AND p.status_id IN (1, 3)
    LIMIT 1
");
$registration_paid = (mysqli_num_rows($reg_query) > 0);

// ✅ FIXED: Check ONLY current month's Monthly Dues status
$dues_status = 'Unpaid';
$current_month = date('Y-m'); // e.g., '2025-10'

$dues_query = mysqli_query($conn, "
    SELECT 1
    FROM payments p
    JOIN payment_type_history h ON p.history_id = h.history_id
    JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
    WHERE p.user_id = $user_id 
      AND t.type_name = 'Monthly Dues'
      AND DATE_FORMAT(p.payment_date, '%Y-%m') = '$current_month'
      AND p.status_id IN (1, 3)
    LIMIT 1
");

if (mysqli_num_rows($dues_query) > 0) {
    $dues_status = 'Paid';
}

// Determine primary membership status
$membership_status = $registration_paid ? 'Active' : 'Pending Registration';
?>

<?php include '../includes/header.php'; ?>

<style>
.status.active { color: green; font-weight: bold; }
.status.pending { color: orange; }
.status.unpaid { color: red; }
</style>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-header">
      <i class="fas fa-user-circle"></i>
      <h3>Member Portal</h3>
    </div>
    <nav class="sidebar-nav">
      <ul>
        <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="payments.php"><i class="fas fa-file-invoice-dollar"></i> Pay Dues</a></li>
        <li><a href="report.php"><i class="fas fa-exclamation-triangle"></i> Report Incident</a></li>
        <li><a href="settings.php"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
        <li><hr></li>
        <li><a href="../signout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
      </ul>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="container">
      <h1>Member Dashboard</h1>

      <!-- Member Overview Card -->
      <div class="member-overview">
        <div class="member-avatar">
          <?php if ($user['profile_image']): ?>
            <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
          <?php else: ?>
            <i class="fas fa-user fa-4x"></i>
          <?php endif; ?>
        </div>
        <div class="member-info">
          <h2>Welcome, <?= htmlspecialchars($user['firstname']) ?>!</h2>
          <p><strong>Ward:</strong> <?= htmlspecialchars($user['ward_name'] ?? 'Not set') ?></p>
          <p><strong>LGA:</strong> <?= htmlspecialchars($user['lga_name'] ?? 'Benue') ?></p>
          <p><strong>Membership Status:</strong> 
            <span class="status <?= strtolower($membership_status) ?>"><?= htmlspecialchars($membership_status) ?></span>
          </p>
          <!-- <?php if ($registration_paid): ?>
            <p><strong>Monthly Dues (<?= date('F Y') ?>):</strong> 
              <span class="status <?= strtolower($dues_status) ?>"><?= htmlspecialchars($dues_status) ?></span>
            </p>
          <?php endif; ?> -->
        </div>
      </div>

      <!-- Conditional Call-to-Action -->
      <?php if (!$registration_paid): ?>
        <div class="alert warning">
          <p><strong>⚠️ Complete Registration:</strong> Pay your one-time registration fee to activate full membership benefits.</p>
          <a href="payments.php" class="btn">Pay Registration Fee Now</a>
        </div>
      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
          <a href="payments.php" class="action-card">
            <i class="fas fa-coins"></i>
            <span>Pay Dues</span>
          </a>
          <a href="report.php" class="action-card">
            <i class="fas fa-bell"></i>
            <span>Report Incident</span>
          </a>
          <a href="settings.php" class="action-card">
            <i class="fas fa-camera"></i>
            <span>Update Photo</span>
          </a>
          <a href="https://chat.whatsapp.com/YOUR_KWUPO_GROUP" target="_blank" class="action-card">
            <i class="fab fa-whatsapp"></i>
            <span>WhatsApp Group</span>
          </a>
        </div>
      </div>

      <!-- Next Steps -->
      <div class="next-steps">
        <h2>Next Steps</h2>
        <ul>
          <li>✅ Complete your profile</li>
          <li>💳 Pay your registration fee</li>
          <li>📅 Pay monthly dues</li>
          <li>📱 Join our WhatsApp group</li>
          <li>📣 Report community issues</li>
        </ul>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>