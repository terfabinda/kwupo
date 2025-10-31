<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';


// Restrict to admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1)) {
    header('Location: ../signin.php');
    exit;
}

// Fetch stats
$total_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role_id = 3"))['total'];
$paid_members = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as paid FROM payments WHERE status_id = 3"))['paid'];
$total_incidents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM incident_reports"))['total'];
$pending_incidents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as pending FROM incident_reports WHERE is_verified = 0"))['pending'];
?>

<?php include '../includes/header.php'; ?>

<!-- Dashboard Layout -->
<div class="dashboard-layout">
  <!-- Sidebar Navigation -->
  <?php include '../includes/sidebar.php'; ?>
  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="container">
      <h1>Admin Dashboard</h1>
      
      <div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <h3>Total Members</h3>
    <div class="stat-value"><?= $total_members ?></div>
    <a href="members.php">View All <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-coins"></i></div>
    <h3>Paid Members</h3>
    <div class="stat-value"><?= $paid_members ?></div>
    <a href="dues.php">Manage Dues <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <h3>Incident Reports</h3>
    <div class="stat-value"><?= $total_incidents ?> <small>(<?= $pending_incidents ?> pending)</small></div>
    <a href="incidents.php">Review <i class="fas fa-arrow-right"></i></a>
  </div>
</div>

      <div class="quick-links">
        <h2>Quick Actions</h2>
        <div class="link-grid">
          <a href="members.php" class="btn">Manage Members</a>
          <a href="dues.php" class="btn">Manage Dues</a>
          <a href="incidents.php" class="btn">Incident Reports</a>
          <a href="press.php" class="btn">Press Releases</a>
          <a href="../signout.php" class="btn btn-outline">Sign Out</a>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>