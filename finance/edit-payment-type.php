<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Restrict to finance/admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: ../signin.php');
    exit;
}

$payment_type_id = (int)($_GET['id'] ?? 0);
if (!$payment_type_id) exit('Invalid ID');

// Fetch current payment type
$pt = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM static_payment_types WHERE payment_type_id = $payment_type_id
"));

if (!$pt) exit('Payment type not found');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_name = trim($_POST['type_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_recurring = !empty($_POST['is_recurring']);
    $interval_id = $is_recurring ? (int)($_POST['interval_id'] ?? 4) : null;
    
    if ($type_name) {
        $stmt = mysqli_prepare($conn, 
            "UPDATE static_payment_types 
             SET type_name = ?, description = ?, interval_id = ? 
             WHERE payment_type_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ssii', $type_name, $description, $interval_id, $payment_type_id);
        mysqli_stmt_execute($stmt);
        header("Location: dues.php");
        exit;
    }
}

// Fetch intervals for form
$intervals = mysqli_query($conn, "SELECT * FROM static_recurrence_intervals ORDER BY interval_id");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Edit Payment Type</h1>
        <a href="<?= base_url('dues.php') ?>" class="btn btn-outline">← Back to Dues</a>
      </div>

      <div class="card">
        <form method="POST" class="signup-form">
          <div class="form-row">
            <div class="form-group">
              <label>Type Name <span>*</span></label>
              <input type="text" name="type_name" value="<?= htmlspecialchars($pt['type_name']) ?>" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <input type="text" name="description" value="<?= htmlspecialchars($pt['description'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" name="is_recurring" value="1" <?= $pt['interval_id'] ? 'checked' : '' ?>> 
                Recurring?
              </label>
            </div>
            <div class="form-group" id="interval-field" style="<?= $pt['interval_id'] ? 'display:block;' : 'display:none;' ?>">
              <label>Recurrence Interval</label>
              <select name="interval_id">
                <?php while ($interval = mysqli_fetch_assoc($intervals)): ?>
                  <option value="<?= $interval['interval_id'] ?>" <?= ($interval['interval_id'] == $pt['interval_id']) ? 'selected' : '' ?>>
                    <?= $interval['interval_name'] ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="btn">Save Changes</button>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
// Toggle interval field
document.querySelector('input[name="is_recurring"]').addEventListener('change', function() {
  document.getElementById('interval-field').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include '../includes/footer.php'; ?>