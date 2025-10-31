<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: ../signin.php');
    exit;
}

// Get date range (default: current year)
$start_date = $_GET['start'] ?? date('Y-01-01');
$end_date = $_GET['end'] ?? date('Y-m-d');

// Total Revenue by Payment Type
$revenue_by_type = mysqli_query($conn, "
    SELECT t.type_name, SUM(p.amount_paid) as total, COUNT(p.payment_id) as count
    FROM payments p
    JOIN payment_type_history h ON p.history_id = h.history_id
    JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
    WHERE p.payment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY t.type_name
    ORDER BY total DESC
");

// Payment Status Summary
$status_summary = mysqli_query($conn, "
    SELECT s.status_name, COUNT(p.payment_id) as count, SUM(p.amount_paid) as total
    FROM payments p
    JOIN static_payment_status s ON p.status_id = s.status_id
    WHERE p.payment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY s.status_name
");

// Monthly Trends
$monthly_trends = mysqli_query($conn, "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, 
           SUM(amount_paid) as total,
           COUNT(payment_id) as count
    FROM payments
    WHERE payment_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY month
    ORDER BY month
");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Financial Reports</h1>
        <a href="?export=csv&start=<?= $start_date ?>&end=<?= $end_date ?>" class="btn btn-sm">
          <i class="fas fa-file-export"></i> Export CSV
        </a>
      </div>

      

      <!-- Date Range Filter -->
      <div class="filters" style="margin-bottom: 30px;">
        <form method="GET" class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <label>Start Date</label>
              <input type="date" name="start" value="<?= $start_date ?>" required>
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input type="date" name="end" value="<?= $end_date ?>" required>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
              <button type="submit" class="btn btn-sm">Apply</button>
            </div>
          </div>
        </form>
      </div>

      <!-- Revenue by Type -->
      <div class="card">
        <h2>Revenue by Payment Type</h2>
        <div class="table-responsive">
          <table class="members-table">
            <thead>
              <tr>
                <th>Payment Type</th>
                <th>Transactions</th>
                <th>Total Revenue</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($revenue_by_type)): ?>
                <tr>
                  <td><?= htmlspecialchars($row['type_name']) ?></td>
                  <td><?= $row['count'] ?></td>
                  <td>₦<?= number_format($row['total'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Status Summary -->
      <div class="card" style="margin-top: 30px;">
        <h2>Payment Status Summary</h2>
        <div class="table-responsive">
          <table class="members-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Transactions</th>
                <th>Total Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($status_summary)): ?>
                <tr>
                  <td>
                    <span class="status <?= strtolower($row['status_name']) ?>">
                      <?= $row['status_name'] ?>
                    </span>
                  </td>
                  <td><?= $row['count'] ?></td>
                  <td>₦<?= number_format($row['total'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Monthly Trends -->
      <div class="card" style="margin-top: 30px;">
        <h2>Monthly Trends</h2>
        <div class="table-responsive">
          <table class="members-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Transactions</th>
                <th>Total Revenue</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($monthly_trends)): ?>
                <tr>
                  <td><?= $row['month'] ?></td>
                  <td><?= $row['count'] ?></td>
                  <td>₦<?= number_format($row['total'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>