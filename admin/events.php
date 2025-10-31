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
    $event_id = (int)$_GET['delete'];
    $event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT featured_image FROM events WHERE event_id = $event_id"));
    if ($event && $event['featured_image']) {
        $img_path = '../uploads/events/' . $event['featured_image'];
        if (file_exists($img_path)) unlink($img_path);
    }
    mysqli_query($conn, "DELETE FROM events WHERE event_id = $event_id");
    $_SESSION['success'] = "Event deleted successfully.";
    header('Location: events.php');
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

$events = mysqli_query($conn, "
    SELECT e.*, u.firstname, u.surname
    FROM events e
    JOIN users u ON e.created_by = u.user_id
    WHERE $where
    ORDER BY e.event_date DESC, e.created_at DESC
");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Events Management</h1>
        <a href="create-event.php" class="btn">+ New Event</a>
      </div>

      <!-- Status Filter -->
      <div class="filters" style="margin-bottom: 20px;">
        <div class="filter-form">
          <div class="form-row">
            <div class="form-group">
              <label>Status</label>
              <select onchange="location = this.value;">
                <option value="events.php?status=all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="events.php?status=published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="events.php?status=draft" <?= $status === 'draft' ? 'selected' : '' ?>>Drafts</option>
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
              <th>Date & Time</th>
              <th>Location</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($events) === 0): ?>
              <tr><td colspan="6" class="text-center">No events found.</td></tr>
            <?php else: ?>
              <?php while ($e = mysqli_fetch_assoc($events)): ?>
              <tr>
                <td><?= htmlspecialchars($e['title']) ?></td>
                <td>
                  <?= htmlspecialchars($e['event_date']) ?>
                  <?= $e['event_time'] ? 'at ' . htmlspecialchars($e['event_time']) : '' ?>
                </td>
                <td><?= htmlspecialchars($e['location']) ?></td>
                <td>
                  <span class="status <?= $e['is_published'] ? 'paid' : 'unpaid' ?>">
                    <?= $e['is_published'] ? 'Published' : 'Draft' ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($e['created_at']) ?></td>
                <td>
                  <a href="edit-event.php?id=<?= $e['event_id'] ?>" class="btn-icon" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="events.php?delete=<?= $e['event_id'] ?>" 
                     class="btn-icon text-danger" 
                     title="Delete"
                     onclick="return confirm('Delete this event? This cannot be undone.')">
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