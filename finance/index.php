<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Restrict to finance or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: ../signin.php');
    exit;
}

// ✅ FIXED: Total Revenue = SUM of all confirmed payments (status_id = 3)
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(amount_paid) as total 
    FROM payments 
    WHERE status_id = 3
"))['total'] ?? 0;

$pending_payments = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM payments 
    WHERE status_id = 2
"))['total'] ?? 0;

$members_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role_id = 3"))['total'];
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php include '../includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="container">
      <h1>Finance Dashboard</h1>
      
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-coins"></i></div>
          <h3>Total Revenue Collected</h3> <!-- ✅ Updated label -->
          <div class="stat-value">₦<?= number_format($total_revenue, 2) ?></div> <!-- ✅ Full revenue -->
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <h3>Active Members</h3>
          <div class="stat-value"><?= $members_count ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-clock"></i></div>
          <h3>Pending Payments</h3>
          <div class="stat-value"><?= $pending_payments ?></div>
        </div>
      </div>

      <div class="quick-links">
        <h2>Quick Actions</h2>
        <div class="link-grid">
          <a href="dues.php" class="btn">Manage Dues</a>
          <a href="payments.php" class="btn">View Payments</a>
          <a href="reports.php" class="btn">Generate Report</a>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>