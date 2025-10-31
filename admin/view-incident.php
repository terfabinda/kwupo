<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Admin access only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$report_id = (int)($_GET['id'] ?? 0);
if (!$report_id) {
    $_SESSION['error'] = "Invalid report ID.";
    header('Location: incidents.php');
    exit;
}

// Fetch full incident report
$report = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        ir.*, 
        u.firstname, u.surname, u.phone, u.email,
        l.lga_name,
        w.ward_name,
        c.category_name,
        a.agency_name,
        s.source_name
    FROM incident_reports ir
    JOIN users u ON ir.user_id = u.user_id
    LEFT JOIN static_users_lga l ON ir.lga_id = l.lga_id
    LEFT JOIN static_users_council_wards w ON ir.ward_id = w.ward_id
    LEFT JOIN static_incident_categories c ON ir.category_id = c.category_id
    LEFT JOIN static_response_agencies a ON ir.agency_id = a.agency_id
    LEFT JOIN static_information_sources s ON ir.source_id = s.source_id
    WHERE ir.report_id = $report_id
"));

if (!$report) {
    $_SESSION['error'] = "Report not found.";
    header('Location: incidents.php');
    exit;
}

// Fetch media files
$media_files = mysqli_query($conn, "SELECT * FROM incident_media WHERE report_id = $report_id");

// Handle verification toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_verify'])) {
    $new_status = $report['is_verified'] ? 0 : 1;
    mysqli_query($conn, "UPDATE incident_reports SET is_verified = $new_status WHERE report_id = $report_id");
    $report['is_verified'] = $new_status;
    $_SESSION['success'] = "Report status updated successfully.";
    header("Location: view-incident.php?id=$report_id");
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<style>
.incident-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  padding: 30px;
  margin-bottom: 30px;
}
.incident-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 25px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
}
.incident-title {
  margin: 0;
  color: var(--accent);
  font-size: 1.8rem;
}
.crisis-code {
  font-size: 1.3rem;
  font-weight: bold;
  color: #e74c3c;
  margin: 10px 0;
}
.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.9rem;
}
.status-badge.verified { background: #d4edda; color: #155724; }
.status-badge.unverified { background: #fff3cd; color: #856404; }

/* Professional table styles */
.incident-table {
  width: 100%;
  border-collapse: collapse;
  margin: 15px 0;
}
.incident-table th,
.incident-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}
.incident-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  width: 30%;
  color: #333;
}
.incident-table td {
  width: 70%;
}
.description-table,
.impact-table {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
}
.description-table td,
.impact-table td {
  padding: 15px;
  border: 1px solid #eee;
  vertical-align: top;
}
.description-table td {
  background-color: #fcfcfc;
}
.impact-table th {
  background-color: #f8f9fa;
  padding: 12px 15px;
  text-align: left;
  font-weight: 600;
}
.property-loss-cell {
  grid-column: 1 / -1;
  background-color: #fcfcfc;
}

.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-top: 10px;
}
.media-item img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 6px;
  cursor: pointer;
}
.media-item video {
  width: 100%;
  height: 120px;
  border-radius: 6px;
}
.print-only { display: none; }
@media print {
  .no-print { display: none !important; }
  .print-only { display: block !important; }
  .incident-card { box-shadow: none; padding: 15px; }
  body { font-size: 11pt; }
}
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header no-print">
        <h1>Incident Report Details</h1>
        <div class="dashboard-actions">
          <button onclick="window.print()" class="btn btn-sm"><i class="fas fa-print"></i> Print Report</button>
          <a href="incidents.php" class="btn btn-sm btn-outline">← Back to Reports</a>
        </div>
      </div>

      <div class="incident-card">
        <!-- Header -->
        <div class="incident-header">
          <div>
            <h2 class="incident-title"><?= htmlspecialchars($report['title']) ?></h2>
            <p class="text-muted">
              Reported by: <?= htmlspecialchars($report['firstname'] . ' ' . $report['surname']) ?> 
              • <?= htmlspecialchars($report['created_at']) ?>
            </p>
            <!-- ✅ Crisis Code -->
            <div class="crisis-code">Incident ID: <?= htmlspecialchars($report['crisis_code'] ?? 'N/A') ?></div>
          </div>
          <span class="status-badge <?= $report['is_verified'] ? 'verified' : 'unverified' ?>">
            <?= $report['is_verified'] ? 'Verified' : 'Unverified' ?>
          </span>
        </div>

        <!-- Verification Control (Admin Only) -->
        <div class="no-print" style="text-align: center; margin: 20px 0;">
          <form method="POST" style="display: inline;">
            <input type="hidden" name="toggle_verify" value="1">
            <button type="submit" class="btn <?= $report['is_verified'] ? 'btn-outline' : 'btn' ?>">
              <?= $report['is_verified'] ? 'Mark as Unverified' : 'Verify Report' ?>
            </button>
          </form>
        </div>

        <!-- ✅ Incident Information: Full-width professional table -->
        <h3>Incident Information</h3>
        <table class="incident-table">
          <tr>
            <th>Date</th>
            <td><?= htmlspecialchars($report['incident_date']) ?></td>
          </tr>
          <tr>
            <th>Time</th>
            <td><?= htmlspecialchars($report['incident_time']) ?></td>
          </tr>
          <tr>
            <th>Community</th>
            <td><?= htmlspecialchars($report['community_name']) ?></td>
          </tr>
          <tr>
            <th>Location</th>
            <td><?= htmlspecialchars($report['ward_name'] ?? 'N/A') ?>, <?= htmlspecialchars($report['lga_name'] ?? 'Benue') ?></td>
          </tr>
          <tr>
            <th>Category</th>
            <td><?= htmlspecialchars($report['category_name']) ?></td>
          </tr>
          <tr>
            <th>Information Source</th>
            <td><?= htmlspecialchars($report['source_name'] ?? 'N/A') ?></td>
          </tr>
          <tr>
            <th>Response Agency</th>
            <td><?= htmlspecialchars($report['agency_name'] ?? 'N/A') ?></td>
          </tr>
        </table>

        <!-- ✅ Description: Single-column full-width table -->
        <h3>Description</h3>
        <table class="description-table">
          <tr>
            <td><?= nl2br(htmlspecialchars($report['description'])) ?></td>
          </tr>
        </table>

        <!-- ✅ Impact Assessment: Structured table with full-width Property Loss -->
        <h3>Impact Assessment</h3>
        <table class="impact-table">
          <tr>
            <th>Affected Population</th>
            <td><?= (int)$report['affected_population'] ?></td>
          </tr>
          <tr>
            <th>Deaths</th>
            <td><?= (int)$report['deaths'] ?></td>
          </tr>
          <tr>
            <th>Injured</th>
            <td><?= (int)$report['injured'] ?></td>
          </tr>
          <tr>
            <th>Missing</th>
            <td><?= (int)$report['missing'] ?></td>
          </tr>
          <tr>
            <th>Displaced</th>
            <td><?= (int)$report['displaced'] ?></td>
          </tr>
          <?php if (!empty($report['property_loss'])): ?>
          <tr>
            <th>Property Loss</th>
            <td><?= htmlspecialchars($report['property_loss']) ?></td>
          </tr>
          <?php endif; ?>
        </table>

        <!-- Media Evidence (unchanged) -->
        <?php if (mysqli_num_rows($media_files) > 0): ?>
        <div class="section">
          <h3>Media Evidence</h3>
          <div class="media-grid">
            <?php while ($media = mysqli_fetch_assoc($media_files)): ?>
              <?php if (strpos($media['file_type'], 'image/') === 0): ?>
                <div class="media-item">
                  <img src="../uploads/incident_media/<?= htmlspecialchars($media['file_name']) ?>" 
                       alt="Incident media">
                </div>
              <?php elseif (strpos($media['file_type'], 'video/') === 0): ?>
                <div class="media-item">
                  <video controls>
                    <source src="../uploads/incident_media/<?= htmlspecialchars($media['file_name']) ?>" 
                            type="<?= htmlspecialchars($media['file_type']) ?>">
                    Your browser does not support the video tag.
                  </video>
                </div>
              <?php endif; ?>
            <?php endwhile; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Reporter Contact (Admin Only) -->
        <div class="section no-print">
          <h3>Reporter Contact</h3>
          <p><strong>Phone:</strong> <?= htmlspecialchars($report['phone'] ?? 'N/A') ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($report['email'] ?? 'N/A') ?></p>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>