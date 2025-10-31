<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

// Handle search/filter
$search = trim($_GET['search'] ?? '');
$lga_filter = (int)($_GET['lga'] ?? 0);
$status_filter = $_GET['status'] ?? '';

// Build query
$where = "u.role_id = 3"; // Only members
$params = [];
$types = "";

if ($search) {
    $where .= " AND (u.firstname LIKE ? OR u.surname LIKE ? OR u.phone LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    $types .= "sss";
}

if ($lga_filter > 0) {
    $where .= " AND w.lga_id = ?";
    $params[] = $lga_filter;
    $types .= "i";
}

// ✅ UPDATED: Filter by REGISTRATION status (not dues)
if ($status_filter === 'paid') {
    $where .= " AND EXISTS (
        SELECT 1 FROM payments p 
        JOIN payment_type_history h ON p.history_id = h.history_id
        JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
        WHERE p.user_id = u.user_id 
        AND t.type_name = 'Registration Fee' 
        AND p.status_id = 3
    )";
} elseif ($status_filter === 'unpaid') {
    $where .= " AND NOT EXISTS (
        SELECT 1 FROM payments p 
        JOIN payment_type_history h ON p.history_id = h.history_id
        JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
        WHERE p.user_id = u.user_id 
        AND t.type_name = 'Registration Fee' 
        AND p.status_id = 3
    )";
}

// Fetch LGAs for filter dropdown
$lgas = mysqli_query($conn, "SELECT lga_id, lga_name FROM static_users_lga ORDER BY lga_name");

// ✅ UPDATED: Fetch registration status (not dues)
$sql = "
    SELECT u.*, 
           w.ward_name, 
           l.lga_name,
           tp.title_prefix
    FROM users u
    LEFT JOIN static_users_council_wards w ON u.ward_id = w.ward_id
    LEFT JOIN static_users_lga l ON w.lga_id = l.lga_id
    LEFT JOIN static_user_titles tp ON u.title_prefix_id = tp.title_id
    WHERE $where
    ORDER BY u.surname, u.firstname
";

$stmt = mysqli_prepare($conn, $sql);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$members = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Helper: Get registration status for each member
function getRegistrationStatus($conn, $user_id) {
    $reg = mysqli_query($conn, "
        SELECT 1 
        FROM payments p
        JOIN payment_type_history h ON p.history_id = h.history_id
        JOIN static_payment_types t ON h.payment_type_id = t.payment_type_id
        WHERE p.user_id = $user_id 
          AND t.type_name = 'Registration Fee'
          AND p.status_id = 3
        LIMIT 1
    ");
    return (mysqli_num_rows($reg) > 0) ? 'Paid' : 'Unpaid';
}
?>

<?php include '../includes/header.php'; ?>

<style>
.member-avatarx {
  width: 40px !important;
  height: 40px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  display: block !important;
  margin-right: 12px !important;
  border: 2px solid #eee;
  float: left !important;
  margin-top: -8px !important;
}

</style>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <?php include '../includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Manage Members</h1>
        <div class="dashboard-actions">
          <a href="<?= base_url('admin/members.php?export=csv') ?>" class="btn btn-sm">
            <i class="fas fa-file-export"></i> Export CSV
          </a>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters">
        <form method="GET" class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <input type="text" name="search" placeholder="Search name or phone..." 
                     value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
              <select name="lga">
                <option value="">All LGAs</option>
                <?php mysqli_data_seek($lgas, 0); while ($lga = mysqli_fetch_assoc($lgas)): ?>
                  <option value="<?= $lga['lga_id'] ?>" <?= ($lga_filter == $lga['lga_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($lga['lga_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <select name="status">
                <option value="">All Status</option>
                <option value="paid" <?= ($status_filter == 'paid') ? 'selected' : '' ?>>Paid</option>
                <option value="unpaid" <?= ($status_filter == 'unpaid') ? 'selected' : '' ?>>Unpaid</option>
              </select>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-sm">Filter</button>
              <?php if ($search || $lga_filter || $status_filter): ?>
                <a href="members.php" class="btn btn-sm btn-outline">Clear</a>
              <?php endif; ?>
            </div>
          </div>
        </form>
      </div>

      <!-- Members Table -->
      <div class="table-responsive">
        <table class="members-table">
          <thead>
            <tr>
              <th>Full Name</th> <!-- ✅ Now includes image + name -->
              <th>Phone</th>
              <th>Ward / LGA</th>
              <th>Registration Status</th> <!-- ✅ Updated label -->
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($members)): ?>
              <tr>
                <td colspan="5" class="text-center">No members found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($members as $member): ?>
                <tr>
                  <td class="member-name-cell">
                    <?php if ($member['profile_image']): ?>
                      <img src="/kwupo/uploads/profiles/<?= htmlspecialchars($member['profile_image']) ?>" 
                      
                           alt="Profile" class="member-avatarx">
                    <?php else: ?>
                      <div class="member-avatarx">
                        <i class="fas fa-user"></i>
                      </div>
                    <?php endif; ?>
                    <?php
                    $name = '';
                    if ($member['title_prefix']) $name .= $member['title_prefix'] . ' ';
                    $name .= $member['firstname'] . ' ' . $member['surname'];
                    if ($member['title_suffix']) $name .= ', ' . $member['title_suffix'];
                    echo htmlspecialchars($name);
                    ?>
                  </td>
                  <td><?= htmlspecialchars($member['phone']) ?></td>
                  <td>
                    <?= htmlspecialchars($member['ward_name'] ?? 'N/A') ?><br>
                    <small><?= htmlspecialchars($member['lga_name'] ?? 'Benue') ?></small>
                  </td>
                  <td>
                    <?php 
                    $reg_status = getRegistrationStatus($conn, $member['user_id']);
                    $status_class = ($reg_status === 'Paid') ? 'paid' : 'unpaid';
                    ?>
                    <span class="status <?= $status_class ?>"><?= $reg_status ?></span>
                  </td>
                  <td>
                    <a href="<?= base_url('member-view.php?id=' . $member['user_id']) ?>" class="btn-icon" title="View">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="<?= base_url('member-edit.php?id=' . $member['user_id']) ?>" class="btn-icon" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Export Handling -->
      <?php
      if (isset($_GET['export']) && $_GET['export'] === 'csv') {
          header('Content-Type: text/csv');
          header('Content-Disposition: attachment; filename="kwupo_members_' . date('Y-m-d') . '.csv"');
          
          $output = fopen('php://output', 'w');
          fputcsv($output, ['Full Name', 'Phone', 'Email', 'Ward', 'LGA', 'Registration Status', 'Registration Date']);
          
          foreach ($members as $m) {
              $name = '';
              if ($m['title_prefix']) $name .= $m['title_prefix'] . ' ';
              $name .= $m['firstname'] . ' ' . $m['surname'];
              if ($m['title_suffix']) $name .= ', ' . $m['title_suffix'];
              
              $reg_status = getRegistrationStatus($conn, $m['user_id']);
              
              fputcsv($output, [
                  $name,
                  $m['phone'],
                  $m['email'] ?? '',
                  $m['ward_name'] ?? 'N/A',
                  $m['lga_name'] ?? 'Benue',
                  $reg_status,
                  $m['created_at']
              ]);
          }
          fclose($output);
          exit;
      }
      ?>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>