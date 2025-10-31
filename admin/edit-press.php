<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$press_id = (int)($_GET['id'] ?? 0);
if (!$press_id) {
    $_SESSION['error'] = "Invalid press release.";
    header('Location: press.php');
    exit;
}

// Fetch existing release
$release = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM press_releases WHERE press_id = $press_id
"));
if (!$release) {
    $_SESSION['error'] = "Press release not found.";
    header('Location: press.php');
    exit;
}

$errors = [];
$inputs = [
    'title' => $release['title'],
    'content' => $release['content'],
    'release_date' => $release['release_date'],
    'is_published' => $release['is_published'] ? '1' : '0'
];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [
        'title' => trim($_POST['title'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'release_date' => trim($_POST['release_date'] ?? ''),
        'is_published' => $_POST['is_published'] ?? '0'
    ];
    
    if (empty($inputs['title'])) $errors[] = "Title is required.";
    if (empty($inputs['content'])) $errors[] = "Content is required.";
    if (empty($inputs['release_date']) || !strtotime($inputs['release_date'])) {
        $errors[] = "Valid release date is required.";
    }
    
    if (empty($errors)) {
        // Update DB
        $stmt = mysqli_prepare($conn, "
            UPDATE press_releases 
            SET title = ?, content = ?, release_date = ?, is_published = ?
            WHERE press_id = ?
        ");
        $is_published = (int)($inputs['is_published'] === '1');
        mysqli_stmt_bind_param($stmt, 'ssssi', 
            $inputs['title'], $inputs['content'], $inputs['release_date'], $is_published, $press_id
        );
        mysqli_stmt_execute($stmt);
        
        $_SESSION['success'] = "Press release updated successfully.";
        header('Location: press.php');
        exit;
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
.press-form { max-width: 800px; margin: 0 auto; }
.form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Edit Press Release</h1>
        <a href="press.php" class="btn btn-outline">← Back to Releases</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="press-form">
        <div class="form-section">
          <div class="form-group">
            <label for="title">Title <span>*</span></label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($inputs['title']) ?>" required>
          </div>
          
          <div class="form-group">
            <label for="release_date">Release Date <span>*</span></label>
            <input type="date" id="release_date" name="release_date" value="<?= htmlspecialchars($inputs['release_date']) ?>" required>
          </div>
          
          <div class="form-group">
            <label>
              <input type="checkbox" name="is_published" value="1" <?= $inputs['is_published'] === '1' ? 'checked' : '' ?>>
              Publish immediately
            </label>
          </div>
          
          <div class="form-group">
            <label for="content">Content <span>*</span></label>
            <textarea id="content" name="content" rows="12" required><?= htmlspecialchars($inputs['content']) ?></textarea>
          </div>
          
          <button type="submit" class="btn">Update Press Release</button>
        </div>
      </form>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>