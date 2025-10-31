<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $press_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM press_releases WHERE press_id = $press_id AND is_published = 0");
    $_SESSION['success'] = "Draft deleted successfully.";
    header('Location: press.php');
    exit;
}

// Handle status filter
$status = $_GET['status'] ?? 'all';
$where = "1=1";
if ($status === 'published') {
    $where = "is_published = 1";
} elseif ($status === 'draft') {
    $where = "is_published = 0";
}

$releases = mysqli_query($conn, "
    SELECT * FROM press_releases 
    WHERE $where
    ORDER BY release_date DESC, created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Press Releases</h1>
        <a href="create-press.php" class="btn">+ New Press Release</a>
      </div>

      <!-- Status Filter -->
      <div class="filters" style="margin-bottom: 20px;">
        <div class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              <select onchange="location = this.value;">
                <option value="press.php?status=all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="press.php?status=published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="press.php?status=draft" <?= $status === 'draft' ? 'selected' : '' ?>>Drafts</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="members-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Release Date</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($releases) === 0): ?>
              <tr><td colspan="5" class="text-center">No press releases found.</td></tr>
            <?php else: ?>
              <?php while ($r = mysqli_fetch_assoc($releases)): ?>
              <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['release_date']) ?></td>
                <td>
                  <span class="status <?= $r['is_published'] ? 'paid' : 'unpaid' ?>">
                    <?= $r['is_published'] ? 'Published' : 'Draft' ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($r['created_at']) ?></td>
                <td>
                  <a href="edit-press.php?id=<?= $r['press_id'] ?>" class="btn-icon" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <?php if (!$r['is_published']): ?>
                    <a href="press.php?delete=<?= $r['press_id'] ?>" 
                       class="btn-icon text-danger" 
                       title="Delete Draft"
                       onclick="return confirm('Delete this draft? This cannot be undone.')">
                      <i class="fas fa-trash"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>