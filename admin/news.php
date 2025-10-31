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
    $news_id = (int)$_GET['delete'];
    // Delete featured image if exists
    $news = mysqli_fetch_assoc(mysqli_query($conn, "SELECT featured_image FROM news WHERE news_id = $news_id"));
    if ($news && $news['featured_image']) {
        $img_path = '../uploads/news/' . $news['featured_image'];
        if (file_exists($img_path)) unlink($img_path);
    }
    mysqli_query($conn, "DELETE FROM news WHERE news_id = $news_id");
    $_SESSION['success'] = "News item deleted successfully.";
    header('Location: news.php');
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

$news_items = mysqli_query($conn, "
    SELECT n.*, u.firstname, u.surname
    FROM news n
    JOIN users u ON n.created_by = u.user_id
    WHERE $where
    ORDER BY n.published_at DESC, n.created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>News Management</h1>
        <a href="create-news.php" class="btn">+ New News Article</a>
      </div>

      <!-- Status Filter -->
      <div class="filters" style="margin-bottom: 20px;">
        <div class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              <select onchange="location = this.value;">
                <option value="news.php?status=all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="news.php?status=published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="news.php?status=draft" <?= $status === 'draft' ? 'selected' : '' ?>>Drafts</option>
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
              <th>Author</th>
              <th>Status</th>
              <th>Published</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($news_items) === 0): ?>
              <tr><td colspan="6" class="text-center">No news items found.</td></tr>
            <?php else: ?>
              <?php while ($n = mysqli_fetch_assoc($news_items)): ?>
              <tr>
                <td><?= htmlspecialchars($n['title']) ?></td>
                <td><?= htmlspecialchars($n['firstname'] . ' ' . $n['surname']) ?></td>
                <td>
                  <span class="status <?= $n['is_published'] ? 'paid' : 'unpaid' ?>">
                    <?= $n['is_published'] ? 'Published' : 'Draft' ?>
                  </span>
                </td>
                <td><?= $n['published_at'] ? htmlspecialchars($n['published_at']) : '—' ?></td>
                <td><?= htmlspecialchars($n['created_at']) ?></td>
                <td>
                  <a href="edit-news.php?id=<?= $n['news_id'] ?>" class="btn-icon" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="news.php?delete=<?= $n['news_id'] ?>" 
                     class="btn-icon text-danger" 
                     title="Delete"
                     onclick="return confirm('Delete this news item? This cannot be undone.')">
                    <i class="fas fa-trash"></i>
                  </a>
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