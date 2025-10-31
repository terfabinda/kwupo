<?php
// Determine user role
$role_id = $_SESSION['role_id'] ?? 3;
$is_admin = ($role_id == 1);
$is_finance = ($role_id == 2);
$is_member = ($role_id == 3);

// Determine correct path to settings.php
// If current page is in /member/, use relative 'settings.php'
// Otherwise (admin/finance), use '../member/settings.php'
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
$settings_path = ($current_dir === 'member') 
    ? 'settings.php' 
    : '../member/settings.php';
?>

<aside class="dashboard-sidebar">
  <div class="sidebar-header">
    <?php if ($is_admin): ?>
      <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
    <?php elseif ($is_finance): ?>
      <h3><i class="fas fa-chart-line"></i> Finance Portal</h3>
    <?php else: ?>
      <h3><i class="fas fa-user-circle"></i> Member Portal</h3>
    <?php endif; ?>
  </div>
  
  <nav class="sidebar-nav">
    <ul>
      <!-- Dashboard -->
      <li>
        <a href="<?= base_url('index.php') ?>" 
           class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
          <i class="fas fa-home"></i> Dashboard
        </a>
      </li>

      <!-- Admin Only -->
      <?php if ($is_admin): ?>
        <li><a href="<?= base_url('members.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'members') !== false ? 'active' : '' ?>">
          <i class="fas fa-user-friends"></i> Manage Members
        </a></li>
        <li><a href="<?= base_url('payments.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'payments') !== false ? 'active' : '' ?>">
          <i class="fas fa-file-invoice-dollar"></i> Dues & Payments
        </a></li>
        <li><a href="<?= base_url('incidents.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'incidents') !== false ? 'active' : '' ?>">
          <i class="fas fa-bell"></i> Incident Reports
        </a></li>
        <li><a href="<?= base_url('press.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'press') !== false ? 'active' : '' ?>">
          <i class="fas fa-newspaper"></i> Press Releases
        </a></li>
        <li><a href="<?= base_url('news.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'news') !== false ? 'active' : '' ?>">
          <i class="fas fa-newspaper"></i> News and Updates
        </a></li>
        <li><a href="<?= base_url('events.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'events') !== false ? 'active' : '' ?>">
          <i class="fas fa-calendar"></i> Events
        </a></li>
        <li><a href="<?= base_url('messaging.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'messaging') !== false ? 'active' : '' ?>">
          <i class="fas fa-sms"></i> Messaging
        </a></li>
              <li><a href=""><i class="fas fa-envelope"></i> Email</a></li>
      <li>
        <a href="<?= base_url('settings.php') ?>" 
        class="<?= strpos($_SERVER['REQUEST_URI'], 'settings') !== false ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Organization Settings</a>
    </li>
      <?php endif; ?>

      <!-- Finance Only -->
      <?php if ($is_finance): ?>
        <li><a href="<?= base_url('dues.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'dues') !== false ? 'active' : '' ?>">
          <i class="fas fa-file-invoice-dollar"></i> Manage Dues
        </a></li>
        <li><a href="<?= base_url('payments.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'payments') !== false ? 'active' : '' ?>">
          <i class="fas fa-receipt"></i> Payment Records
        </a></li>
        <li><a href="<?= base_url('reports.php') ?>" 
               class="<?= strpos($_SERVER['REQUEST_URI'], 'reports') !== false ? 'active' : '' ?>">
          <i class="fas fa-file-alt"></i> Financial Reports
        </a></li>
      <?php endif; ?>

      <!-- Member Features (only for role_id = 3) -->
      <?php if ($is_member): ?>
        <li><a href="<?= base_url('payments.php') ?>" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>">
          <i class="fas fa-file-invoice-dollar"></i> Pay Dues
        </a></li>
        <li><a href="<?= base_url('report.php') ?>" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>">
          <i class="fas fa-exclamation-triangle"></i> Report Incident
        </a></li>
      <?php endif; ?>

      <!-- Common Footer Links -->
      <li><hr></li>
      <!-- My Profile Settings: visible to ALL roles -->
      <li>
        <a href="<?= base_url($settings_path) ?>" 
           class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
          <i class="fas fa-user-cog"></i> My Profile Settings
        </a>
      </li>
      <li><hr></li>
      <li>
        <a href="<?= base_url('../signout.php') ?>">
          <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
      </li>
    </ul>
  </nav>
</aside>