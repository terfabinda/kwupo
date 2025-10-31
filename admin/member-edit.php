<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Admin access only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) {
    die('Invalid member ID');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $title_prefix_id = (int)($_POST['title_prefix_id'] ?? 0);
    $title_suffix = trim($_POST['title_suffix'] ?? '');
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $is_confirmed = !empty($_POST['is_confirmed']);
    $is_active = !empty($_POST['is_active']);
    $role_id = (int)($_POST['role_id'] ?? 3);

    // Only allow role 2 (finance) or 3 (member)
if ($role_id !== 2 && $role_id !== 3) {
    $role_id = 3;
}

    // Validation
    $errors = [];
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($surname)) $errors[] = "Surname is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (!preg_match('/^0[789][01]\d{8}$/', $phone)) $errors[] = "Invalid Nigerian phone number.";
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if ($ward_id <= 0) $errors[] = "Please select a ward.";

    // Validate title_prefix_id
    if ($title_prefix_id > 0) {
        $check = mysqli_query($conn, "SELECT title_id FROM static_user_titles WHERE title_id = $title_prefix_id");
        if (mysqli_num_rows($check) === 0) {
            $errors[] = "Invalid title selection.";
        }
    }

    // Check for duplicate phone/email (excluding current user)
    if (empty($errors)) {
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE (phone = ? OR email = ?) AND user_id != ?");
        mysqli_stmt_bind_param($check, 'ssi', $phone, $email, $user_id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "Phone or email already in use by another member.";
        }
    }

    // Update if valid
    // In form processing (after validation)
if (empty($errors)) {
    $query = "
        UPDATE users 
        SET firstname = ?, middlename = ?, surname = ?, 
            title_prefix_id = ?, title_suffix = ?, 
            ward_id = ?, phone = ?, email = ?, 
            is_confirmed = ?, is_active = ?, role_id = ?
        WHERE user_id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    $prefix_val = ($title_prefix_id > 0) ? $title_prefix_id : null;
    
    // Include phone/email in bind_param (they're submitted as readonly)
    mysqli_stmt_bind_param($stmt, 'sssisssssiii', 
    $firstname,        // 1
    $middlename,       // 2
    $surname,          // 3
    $prefix_val,       // 4
    $title_suffix,     // 5
    $ward_id,          // 6
    $phone,            // 7
    $email,            // 8
    $is_confirmed,     // 9
    $is_active,        // 10
    $role_id,          // 11
    $user_id           // 12 ← 12th variable!
);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Member updated successfully!";
        header("Location: member-view.php?id=$user_id");
        exit;
    } else {
        $errors[] = "Update failed. Please try again.";
    }
}

}

// Fetch current member data
$sql = "
    SELECT u.*, w.lga_id 
    FROM users u
    LEFT JOIN static_users_council_wards w ON u.ward_id = w.ward_id
    WHERE u.user_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$member) {
    die('Member not found');
}

// Fetch LGAs and Wards
$lgas = mysqli_query($conn, "SELECT lga_id, lga_name FROM static_users_lga ORDER BY lga_name");
$wards = [];
if ($member['lga_id']) {
    $ward_result = mysqli_query($conn, "SELECT ward_id, ward_name FROM static_users_council_wards WHERE lga_id = {$member['lga_id']} ORDER BY ward_name");
    while ($row = mysqli_fetch_assoc($ward_result)) {
        $wards[] = $row;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <aside class="dashboard-sidebar">
    <div class="sidebar-header">
      <i class="fas fa-shield-alt"></i>
      <h3>Admin Panel</h3>
    </div>
    <nav class="sidebar-nav">
      <ul>
        <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="members.php"><i class="fas fa-user-friends"></i> Manage Members</a></li>
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
        <h1>Edit Member</h1>
        <a href="<?= base_url("member-view.php?id=$user_id") ?>" class="btn btn-outline">← View Profile</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="signup-form">
        <!-- Name & Titles -->
        <div class="form-row">
          <div class="form-group">
            <label for="title_prefix_id">Title (Prefix)</label>
            <select id="title_prefix_id" name="title_prefix_id">
              <option value="">None</option>
              <?php
              $prefixes = mysqli_query($conn, "SELECT title_id, title_prefix FROM static_user_titles ORDER BY display_order");
              while ($p = mysqli_fetch_assoc($prefixes)) {
                $selected = ($member['title_prefix_id'] == $p['title_id']) ? 'selected' : '';
                echo "<option value='{$p['title_id']}' $selected>{$p['title_prefix']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <label for="title_suffix">Suffix</label>
            <input type="text" id="title_suffix" name="title_suffix" 
                   value="<?= htmlspecialchars($member['title_suffix'] ?? '') ?>" 
                   placeholder="e.g., PhD, MON, OON">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="firstname">First Name <span>*</span></label>
            <input type="text" id="firstname" name="firstname" 
                   value="<?= htmlspecialchars($member['firstname']) ?>" required>
          </div>
          <div class="form-group">
            <label for="middlename">Middle Name</label>
            <input type="text" id="middlename" name="middlename" 
                   value="<?= htmlspecialchars($member['middlename'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="surname">Surname <span>*</span></label>
            <input type="text" id="surname" name="surname" 
                   value="<?= htmlspecialchars($member['surname']) ?>" required>
          </div>
        </div>

<!-- Contact (Read-Only) -->
<div class="form-row">
  <div class="form-group">
    <label for="phone">Phone Number <span>*</span></label>
    <input type="tel" id="phone" name="phone" 
           value="<?= htmlspecialchars($member['phone']) ?>" 
           readonly>
    <p class="help-text">Phone cannot be edited after registration</p>
  </div>
  <div class="form-group">
    <label for="email">Email Address</label>
    <input type="email" id="email" name="email" 
           value="<?= htmlspecialchars($member['email'] ?? 'N/A') ?>" 
           readonly>
    <p class="help-text">Email cannot be edited after registration</p>
  </div>
</div>

        <!-- Location -->
        <div class="form-row">
          <div class="form-group">
            <label for="lga_id">Local Government Area (LGA) <span>*</span></label>
            <select id="lga_id" name="lga_id" required>
              <option value="">Select LGA</option>
              <?php while ($lga = mysqli_fetch_assoc($lgas)): ?>
                <option value="<?= $lga['lga_id'] ?>" <?= ($lga['lga_id'] == $member['lga_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lga['lga_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="ward_id">Council Ward <span>*</span></label>
            <select id="ward_id" name="ward_id" required>
              <option value="">Select Ward</option>
              <?php foreach ($wards as $ward): ?>
                <option value="<?= $ward['ward_id'] ?>" <?= ($ward['ward_id'] == $member['ward_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ward['ward_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- In the admin-controls section -->
<div class="form-group">
  <label for="role_id">Role</label>
  <select id="role_id" name="role_id">
    <option value="3" <?= ($member['role_id'] == 3) ? 'selected' : '' ?>>Member</option>
    <option value="2" <?= ($member['role_id'] == 2) ? 'selected' : '' ?>>Treasurer/Finance Officer</option>
    <!-- Admins can't demote other admins -->
  </select>
  <p class="help-text">Only admins can manage finance roles</p>
</div>

        <!-- Admin Controls -->
        <div class="form-row admin-controls">
          <div class="form-group">
            <label>
              <input type="checkbox" name="is_confirmed" <?= $member['is_confirmed'] ? 'checked' : '' ?>>
              Confirm Registration
            </label>
            <p class="help-text">Check to approve this member's registration</p>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" name="is_active" <?= $member['is_active'] ? 'checked' : '' ?>>
              Activate Account
            </label>
            <p class="help-text">Deactivate to block login access</p>
          </div>
        </div>

        <button type="submit" class="btn">Save Changes</button>
      </form>
    </div>
  </main>
</div>

<!-- Ward Dropdown Population -->
<script>
document.getElementById('lga_id').addEventListener('change', function() {
  const lgaId = this.value;
  const wardSelect = document.getElementById('ward_id');
  
  if (!lgaId) {
    wardSelect.innerHTML = '<option value="">Select Ward</option>';
    return;
  }

  const baseUrl = '<?= base_url("") ?>';
  fetch(`${baseUrl}assets/php/get-wards?lga_id=${lgaId}`)
    .then(response => response.json())
    .then(wards => {
      let options = '<option value="">Select Ward</option>';
      wards.forEach(ward => {
        options += `<option value="${ward.ward_id}">${ward.ward_name}</option>`;
      });
      wardSelect.innerHTML = options;
    });
});
</script>

<?php include '../includes/footer.php'; ?>