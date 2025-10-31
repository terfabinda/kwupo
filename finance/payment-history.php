<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    header('Location: ../signin.php');
    exit;
}

$payment_type_id = (int)($_GET['id'] ?? 0);
if (!$payment_type_id) exit('Invalid ID');

// Fetch payment type details
$pt = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT type_name, description 
    FROM static_payment_types 
    WHERE payment_type_id = $payment_type_id
"));

if (!$pt) exit('Payment type not found');

// Fetch full history (ordered newest first)
$history = mysqli_query($conn, "
    SELECT * FROM payment_type_history 
    WHERE payment_type_id = $payment_type_id 
    ORDER BY effective_from DESC
");

// Fetch corrections for audit trail
$corrections = [];
$result = mysqli_query($conn, "
    SELECT c.*, u.firstname, u.surname
    FROM payment_history_corrections c
    JOIN users u ON c.corrected_by = u.user_id
    WHERE c.history_id IN (
        SELECT history_id FROM payment_type_history WHERE payment_type_id = $payment_type_id
    )
    ORDER BY c.corrected_at DESC
");
while ($row = mysqli_fetch_assoc($result)) {
    $corrections[$row['history_id']][] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Payment History: <?= htmlspecialchars($pt['type_name']) ?></h1>
        <a href="<?= base_url('dues.php') ?>" class="btn btn-outline">← Back to Dues</a>
      </div>

      <?php if (mysqli_num_rows($history) === 0): ?>
        <div class="alert error">No history found for this payment type.</div>
      <?php else: ?>
        <div class="timeline-container">
          <?php while ($record = mysqli_fetch_assoc($history)): ?>
            <div class="timeline-item <?= $record['is_active'] ? 'active' : '' ?>">
              <div class="timeline-badge">
                <?php if ($record['is_active']): ?>
                  <i class="fas fa-circle" style="color: #4caf50;"></i>
                <?php else: ?>
                  <i class="fas fa-circle" style="color: #9e9e9e;"></i>
                <?php endif; ?>
              </div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <h3>₦<?= number_format($record['amount'], 2) ?></h3>
                  <span class="timeline-date">
                    <?= $record['effective_from'] ?>
                    <?php if ($record['effective_to']): ?>
                      → <?= $record['effective_to'] ?>
                    <?php else: ?>
                      → Present
                    <?php endif; ?>
                  </span>
                </div>
                
                <div class="timeline-meta">
                  <small>
                    Created: <?= $record['created_at'] ?> 
                    by User ID: <?= $record['created_by'] ?>
                  </small>
                </div>

                <!-- Audit Trail -->
                <?php if (!empty($corrections[$record['history_id']])): ?>
                  <div class="audit-trail">
                    <h4>Corrections</h4>
                    <?php foreach ($corrections[$record['history_id']] as $corr): ?>
                      <div class="correction-item">
                        <strong>₦<?= number_format($corr['old_amount'], 2) ?> → ₦<?= number_format($corr['new_amount'], 2) ?></strong>
                        <br>
                        <small>
                          <?= htmlspecialchars($corr['reason']) ?> 
                          by <?= htmlspecialchars($corr['firstname'] . ' ' . $corr['surname']) ?>
                          on <?= $corr['corrected_at'] ?>
                        </small>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <!-- Edit Button (only for non-current records) -->
                <?php if (!$record['is_active']): ?>
                  <a href="edit-fee.php?history_id=<?= $record['history_id'] ?>" class="btn btn-sm" style="margin-top: 10px;">
                    <i class="fas fa-edit"></i> Correct Amount
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<style>
/* Timeline Styles */
.timeline-container {
  position: relative;
  margin: 30px 0;
}

.timeline-container:before {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e0e0e0;
  left: 32px;
}

.timeline-item {
  display: flex;
  margin-bottom: 30px;
  position: relative;
}

.timeline-badge {
  width: 64px;
  text-align: center;
  z-index: 1;
}

.timeline-content {
  flex: 1;
  padding: 15px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  margin-left: 20px;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.timeline-header h3 {
  color: var(--accent);
  margin: 0;
}

.timeline-date {
  background: #f5f5f5;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.9rem;
}

.timeline-item.active .timeline-content {
  border-left: 4px solid #4caf50;
}

.audit-trail {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px dashed #eee;
}

.correction-item {
  background: #f8f9fa;
  padding: 10px;
  border-radius: 6px;
  margin-top: 8px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.85rem;
}
</style>

<?php include '../includes/footer.php'; ?>