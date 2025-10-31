<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

// Filters
$search = trim($_GET['search'] ?? '');
$lga_filter = (int)($_GET['lga'] ?? 0);
$ward_filter = (int)($_GET['ward'] ?? 0);
$category_filter = (int)($_GET['category'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build WHERE clause
$where = "1=1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (ir.title LIKE ? OR ir.community_name LIKE ? OR u.firstname LIKE ? OR u.surname LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
    $types .= "ssss";
}

if ($lga_filter > 0) {
    $where .= " AND ir.lga_id = ?";
    $params[] = $lga_filter;
    $types .= "i";
}

if ($ward_filter > 0) {
    $where .= " AND ir.ward_id = ?";
    $params[] = $ward_filter;
    $types .= "i";
}

if ($category_filter > 0) {
    $where .= " AND ir.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($status_filter === 'verified') {
    $where .= " AND ir.is_verified = 1";
} elseif ($status_filter === 'unverified') {
    $where .= " AND ir.is_verified = 0";
}

if ($start_date) {
    $where .= " AND ir.incident_date >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if ($end_date) {
    $where .= " AND ir.incident_date <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// Fetch reference data
$lgas = mysqli_query($conn, "SELECT lga_id, lga_name FROM static_users_lga ORDER BY lga_name");
$categories = mysqli_query($conn, "SELECT category_id, category_name FROM static_incident_categories ORDER BY category_name");

// Fetch incidents
$sql = "
    SELECT 
        ir.*, 
        u.firstname, u.surname,
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
    WHERE $where
    ORDER BY ir.created_at DESC
    LIMIT 200
";

$stmt = mysqli_prepare($conn, $sql);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$incidents = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Fetch wards dynamically (for JS)
$wards = [];
if ($lga_filter) {
    $ward_result = mysqli_query($conn, "SELECT ward_id, ward_name FROM static_users_council_wards WHERE lga_id = $lga_filter ORDER BY ward_name");
    while ($row = mysqli_fetch_assoc($ward_result)) {
        $wards[] = $row;
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
.incident-status.verified { color: green; font-weight: bold; }
.incident-status.unverified { color: orange; }
.media-thumb {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 8px;
}
.print-only { display: none; }
@media print {
  .no-print { display: none !important; }
  .print-only { display: block !important; }
  body { font-size: 12pt; }
}
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header no-print">
        <h1>Incident Reports</h1>
        <div class="dashboard-actions">
          <button onclick="window.print()" class="btn btn-sm"><i class="fas fa-print"></i> Print</button>
          <a href="?export=csv<?= http_build_query($_GET) ?>" class="btn btn-sm">
            <i class="fas fa-file-export"></i> Export CSV
          </a>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters no-print">
        <form method="GET" class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <label>Search</label>
              <input type="text" name="search" placeholder="Search title, community, or reporter..." 
                     value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
              <label>LGA</label>
              <select name="lga" id="lga-filter">
                <option value="">All LGAs</option>
                <?php while ($lga = mysqli_fetch_assoc($lgas)): ?>
                  <option value="<?= $lga['lga_id'] ?>" <?= ($lga_filter == $lga['lga_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($lga['lga_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Ward</label>
              <select name="ward" id="ward-filter">
                <option value="">All Wards</option>
                <?php foreach ($wards as $w): ?>
                  <option value="<?= $w['ward_id'] ?>" <?= ($ward_filter == $w['ward_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['ward_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Category</label>
              <select name="category">
                <option value="">All Categories</option>
                <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                  <option value="<?= $cat['category_id'] ?>" <?= ($category_filter == $cat['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Status</label>
              <select name="status">
                <option value="">All Status</option>
                <option value="verified" <?= ($status_filter == 'verified') ? 'selected' : '' ?>>Verified</option>
                <option value="unverified" <?= ($status_filter == 'unverified') ? 'selected' : '' ?>>Unverified</option>
              </select>
            </div>
            <div class="form-group">
              <label>Start Date</label>
              <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="form-group">
              <label>End Date</label>
              <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="form-group" style="align-self: flex-end;">
              <button type="submit" class="btn btn-sm">Filter</button>
              <?php if ($search || $lga_filter || $status_filter || $start_date || $end_date): ?>
                <a href="incidents.php" class="btn btn-sm btn-outline">Clear</a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>

      <!-- Incidents Table -->
      <div class="table-responsive">
        <table class="members-table">
          <thead>
            <tr>
              <th>Report</th>
              <th>Reporter</th>
              <th>Location</th>
              <th>Category</th>
              <th>Date & Time</th>
              <th>Status</th>
              <th class="no-print">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($incidents)): ?>
              <tr><td colspan="7" class="text-center">No incident reports found.</td></tr>
            <?php else: ?>
              <?php foreach ($incidents as $ir): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($ir['title']) ?></strong><br>
                    <small><?= htmlspecialchars($ir['community_name']) ?></small>
                    <?php
                    // Check for media
                    $media = mysqli_query($conn, "SELECT file_name, file_type FROM incident_media WHERE report_id = {$ir['report_id']} LIMIT 1");
                    if ($m = mysqli_fetch_assoc($media)) {
                        if (strpos($m['file_type'], 'image/') === 0) {
                            echo '<img src="../uploads/incident_media/' . htmlspecialchars($m['file_name']) . '" class="media-thumb" alt="Media">';
                        }
                    }
                    ?>
                  </td>
                  <td>
                    <?= htmlspecialchars($ir['firstname'] . ' ' . $ir['surname']) ?><br>
                    <small><?= htmlspecialchars($ir['source_name'] ?? 'N/A') ?></small>
                  </td>
                  <td>
                    <?= htmlspecialchars($ir['ward_name'] ?? 'N/A') ?><br>
                    <small><?= htmlspecialchars($ir['lga_name'] ?? 'Benue') ?></small>
                  </td>
                  <td><?= htmlspecialchars($ir['category_name']) ?></td>
                  <td>
                    <?= htmlspecialchars($ir['incident_date']) ?><br>
                    <small><?= htmlspecialchars($ir['incident_time']) ?></small>
                  </td>
                  <td>
                    <span class="incident-status <?= $ir['is_verified'] ? 'verified' : 'unverified' ?>">
                      <?= $ir['is_verified'] ? 'Verified' : 'Unverified' ?>
                    </span>
                  </td>
                  <td class="no-print">
                    <a href="view-incident.php?id=<?= $ir['report_id'] ?>" class="btn-icon" title="View Details">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- CSV Export -->
      <?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
        <?php
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="kwupo_incidents_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'ID', 'Title', 'Reporter', 'Community', 'LGA', 'Ward', 'Category',
            'Date', 'Time', 'Affected', 'Deaths', 'Injured', 'Missing', 'Displaced',
            'Property Loss', 'Agency', 'Source', 'Status', 'Created At'
        ]);
        foreach ($incidents as $ir) {
            fputcsv($output, [
                $ir['report_id'],
                $ir['title'],
                $ir['firstname'] . ' ' . $ir['surname'],
                $ir['community_name'],
                $ir['lga_name'] ?? 'N/A',
                $ir['ward_name'] ?? 'N/A',
                $ir['category_name'],
                $ir['incident_date'],
                $ir['incident_time'],
                $ir['affected_population'],
                $ir['deaths'],
                $ir['injured'],
                $ir['missing'],
                $ir['displaced'],
                $ir['property_loss'],
                $ir['agency_name'] ?? 'N/A',
                $ir['source_name'] ?? 'N/A',
                $ir['is_verified'] ? 'Verified' : 'Unverified',
                $ir['created_at']
            ]);
        }
        fclose($output);
        exit;
        ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>
// Ward dropdown population
document.getElementById('lga-filter').addEventListener('change', function() {
  const lgaId = this.value;
  const wardSelect = document.getElementById('ward-filter');
  
  if (!lgaId) {
    wardSelect.innerHTML = '<option value="">All Wards</option>';
    return;
  }

  fetch(`../assets/php/get-wards.php?lga_id=${lgaId}`)
    .then(response => response.json())
    .then(wards => {
      let options = '<option value="">All Wards</option>';
      wards.forEach(w => options += `<option value="${w.ward_id}">${w.ward_name}</option>`);
      wardSelect.innerHTML = options;
      
      // Re-select if needed
      const currentWard = '<?= $ward_filter ?>';
      if (currentWard) wardSelect.value = currentWard;
    });
});
</script>

<?php include '../includes/footer.php'; ?>