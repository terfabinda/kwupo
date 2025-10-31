<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) exit('Invalid member');

// Fetch member + related data
$sql = "
    SELECT u.*, 
           w.ward_name, 
           l.lga_name,
           tp.title_prefix
    FROM users u
    LEFT JOIN static_users_council_wards w ON u.ward_id = w.ward_id
    LEFT JOIN static_users_lga l ON w.lga_id = l.lga_id
    LEFT JOIN static_user_titles tp ON u.title_prefix_id = tp.title_id
    WHERE u.user_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$member) exit('Member not found');
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <!-- SIDEBAR MENU (ADDED) -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-header">
      <i class="fas fa-shield-alt"></i>
      <h3>Admin Panel</h3>
    </div>
    <nav class="sidebar-nav">
      <ul>
        <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="members.php" class="active"><i class="fas fa-user-friends"></i> Manage Members</a></li>
        <li><a href="dues.php"><i class="fas fa-file-invoice-dollar"></i> Dues & Payments</a></li>
        <li><a href="incidents.php"><i class="fas fa-bell"></i> Incident Reports</a></li>
        <li><a href="press.php"><i class="fas fa-newspaper"></i> Press Releases</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Organization Settings</a></li>
        <li><hr></li>
        <li><a href="../member/settings.php"><i class="fas fa-user-cog"></i> My Profile</a></li>
        <li><a href="../signout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
      </ul>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Member Profile</h1>
        <a href="<?= base_url('members.php') ?>" class="btn btn-outline">← Back to List</a>
      </div>

      <div class="member-profile">
        <div class="profile-header">
          <?php if ($member['profile_image']): ?>
            <img src="<?= base_url('uploads/profiles/' . $member['profile_image']) ?>" alt="Profile">
          <?php else: ?>
            <i class="fas fa-user fa-3x"></i>
          <?php endif; ?>
          <div>
            <?php
            $full_name = '';
            if ($member['title_prefix']) $full_name .= $member['title_prefix'] . ' ';
            $full_name .= $member['firstname'] . ' ' . $member['surname'];
            if ($member['title_suffix']) $full_name .= ', ' . $member['title_suffix'];
            ?>
            <h2><?= htmlspecialchars($full_name) ?></h2>
            <p>Member ID: #<?= $member['user_id'] ?></p>
          </div>
        </div>

        <div class="profile-details">
          <h3>Personal Information</h3>
          <div class="detail-grid">
            <div><strong>Phone:</strong> <?= htmlspecialchars($member['phone']) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($member['email'] ?? 'N/A') ?></div>
            <div><strong>Ward:</strong> <?= htmlspecialchars($member['ward_name'] ?? 'N/A') ?></div>
            <div><strong>LGA:</strong> <?= htmlspecialchars($member['lga_name'] ?? 'Benue') ?></div>
            <div><strong>Status:</strong> 
              <span class="status <?= $member['is_active'] ? 'paid' : 'unpaid' ?>">
                <?= $member['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </div>
            <div><strong>Registration:</strong> 
              <?= $member['is_confirmed'] ? 'Confirmed' : '<span style="color:#c62828">Pending</span>' ?>
            </div>
          </div>

          <h3>Membership</h3>
          <div class="detail-grid">
            <div><strong>Joined:</strong> <?= date('M j, Y', strtotime($member['created_at'])) ?></div>
            <div><strong>Last Updated:</strong> <?= date('M j, Y', strtotime($member['updated_at'])) ?></div>
          </div>
        </div>

        <div class="profile-actions">
          <a href="<?= base_url('member-edit.php?id=' . $member['user_id']) ?>" class="btn">
            <i class="fas fa-edit"></i> Edit Member
          </a>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>