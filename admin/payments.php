<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

$user_id = $_SESSION['user_id'] ?? null;
$role_id = $_SESSION['role_id'] ?? 0;
$is_finance = ($role_id == 2);
$is_admin = ($role_id == 1);
$is_admin_or_finance = in_array($role_id, [1, 2]);

if (!$user_id || !$is_admin_or_finance) {
    header('Location: ../signin.php');
    exit;
}

// Build base WHERE clause
$where = "1=1";
$params = [];
$types = "";

// Filters (only for finance/admin)
$filters = [
    'type' => FILTER_SANITIZE_NUMBER_INT,
    'status' => FILTER_SANITIZE_NUMBER_INT,
    'start_date' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'end_date' => FILTER_SANITIZE_FULL_SPECIAL_CHARS
];

$input = filter_input_array(INPUT_GET, $filters) ?: [];

if (!empty($input['type'])) {
    $where .= " AND t.payment_type_id = ?";
    $params[] = (int)$input['type'];
    $types .= "i";
}

if (!empty($input['status'])) {
    $where .= " AND p.status_id = ?";
    $params[] = (int)$input['status'];
    $types .= "i";
}

if (!empty($input['start_date']) && strtotime($input['start_date'])) {
    $where .= " AND p.payment_date >= ?";
    $params[] = $input['start_date'];
    $types .= "s";
}

if (!empty($input['end_date']) && strtotime($input['end_date'])) {
    $where .= " AND p.payment_date <= ?";
    $params[] = $input['end_date'];
    $types .= "s";
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Generate filename with date range
    $filename_date = !empty($input['start_date']) || !empty($input['end_date']) 
        ? (isset($input['start_date']) ? $input['start_date'] : 'start') . '_to_' . (isset($input['end_date']) ? $input['end_date'] : 'end')
        : date('Y-m-d');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="kwupo_payments_' . $filename_date . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Member', 'Payment Type', 'Amount', 'Status', 'Date', 'Reference']);
    
    $sql = "
        SELECT p.*, 
               u.firstname, u.surname, u.title_suffix, 
               tp.title_prefix,
               t.type_name, s.status_name
        FROM payments p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN static_user_titles tp ON u.title_prefix_id = tp.title_id
        JOIN payment_type_history h ON p.history_id = h.history_id
        JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
        JOIN static_payment_status s ON p.status_id = s.status_id
        WHERE $where
        ORDER BY p.payment_date DESC, p.payment_id DESC
    ";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $member_name = '';
        if ($row['title_prefix']) $member_name .= $row['title_prefix'] . ' ';
        $member_name .= $row['firstname'] . ' ' . $row['surname'];
        if ($row['title_suffix']) $member_name .= ', ' . $row['title_suffix'];
        
        fputcsv($output, [
            $row['payment_id'],
            $member_name,
            $row['type_name'],
            $row['amount_paid'],
            $row['status_name'],
            $row['payment_date'],
            $row['transaction_ref'] ?? 'N/A'
        ]);
    }
    fclose($output);
    exit;
}

// Fetch reference data for filters
$payment_types = mysqli_query($conn, "SELECT * FROM static_payment_types ORDER BY type_name");
$statuses = mysqli_query($conn, "SELECT * FROM static_payment_status");

// Fetch payments
$sql = "
    SELECT p.*, 
           u.firstname, u.surname, u.title_suffix, 
           tp.title_prefix,
           t.type_name, s.status_name, u.user_id as member_id
    FROM payments p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN static_user_titles tp ON u.title_prefix_id = tp.title_id
    JOIN payment_type_history h ON p.history_id = h.history_id
    JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
    JOIN static_payment_status s ON p.status_id = s.status_id
    WHERE $where
    ORDER BY p.payment_date DESC, p.payment_id DESC
    LIMIT 100
";

$stmt = mysqli_prepare($conn, $sql);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payments = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<style>
.status.paid { color: green; font-weight: bold; }
.status.pending { color: orange; }
.status.failed { color: red; }
.filters { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
.filter-form .form-row { flex-wrap: wrap; gap: 15px; }
.filter-form .form-group { flex: 1; min-width: 150px; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Payment Records</h1>
        <a href="?export=csv<?= http_build_query(array_filter($input)) ?>" class="btn btn-sm">
          <i class="fas fa-file-export"></i> Export CSV
        </a>
      </div>

      <!-- Filters -->
      <div class="filters">
        <form method="GET" class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <label>Payment Type</label>
              <select name="type">
                <option value="">All Types</option>
                <?php while ($pt = mysqli_fetch_assoc($payment_types)): ?>
                  <option value="<?= (int)$pt['payment_type_id'] ?>" <?= (isset($input['type']) && $input['type'] == $pt['payment_type_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pt['type_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status">
                <option value="">All Status</option>
                <?php while ($s = mysqli_fetch_assoc($statuses)): ?>
                  <option value="<?= (int)$s['status_id'] ?>" <?= (isset($input['status']) && $input['status'] == $s['status_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['status_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Start Date</label>
              <input type="date" name="start_date" value="<?= htmlspecialchars($input['start_date'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input type="date" name="end_date" value="<?= htmlspecialchars($input['end_date'] ?? '') ?>">
            </div>
            <div class="form-group" style="align-self: flex-end;">
              <button type="submit" class="btn btn-sm">Filter</button>
              <?php if (!empty(array_filter($input))): ?>
                <a href="payments.php" class="btn btn-sm btn-outline">Clear</a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>

      <!-- Payments Table -->
      <div class="table-responsive">
        <table class="members-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Payment Type</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
              <th>Reference</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr>
                <td colspan="7" class="text-center">No payments found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td><?= (int)$p['payment_id'] ?></td>
                  <td>
                    <a href="../admin/member-view.php?id=<?= (int)$p['member_id'] ?>" target="_blank">
                      <?php
                      $name = '';
                      if ($p['title_prefix']) $name .= $p['title_prefix'] . ' ';
                      $name .= $p['firstname'] . ' ' . $p['surname'];
                      if ($p['title_suffix']) $name .= ', ' . $p['title_suffix'];
                      echo htmlspecialchars($name);
                      ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($p['type_name']) ?></td>
                  <td>₦<?= number_format($p['amount_paid'], 2) ?></td>
                  <td>
                    <span class="status <?= strtolower($p['status_name']) ?>"><?= htmlspecialchars($p['status_name']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($p['payment_date']) ?></td>
                  <td><?= htmlspecialchars($p['transaction_ref'] ?? 'N/A') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>