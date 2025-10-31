<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Clear session if not in preview/edit
if (!isset($_GET['preview']) && !isset($_GET['edit'])) {
    unset($_SESSION['press_preview']);
}

$errors = [];
$inputs = [
    'title' => '',
    'content' => '',
    'release_date' => date('Y-m-d'),
    'is_published' => '0'
];

// Restore session data if editing or previewing
if (!empty($_SESSION['press_preview'])) {
    $inputs = $_SESSION['press_preview'];
}

// Handle FINAL publish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_publish'])) {
    if (empty($_SESSION['press_preview'])) {
        $_SESSION['error'] = "Session expired. Please resubmit.";
        header('Location: create-press.php');
        exit;
    }
    
    $inputs = $_SESSION['press_preview'];
    
    // Generate slug
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($inputs['title'])));
    $slug = trim($slug, '-');
    
    // Insert into DB
    $stmt = mysqli_prepare($conn, "
        INSERT INTO press_releases (title, slug, content, release_date, is_published, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $is_published = (int)($inputs['is_published'] === '1');
    mysqli_stmt_bind_param($stmt, 'ssssii', 
        $inputs['title'], $slug, $inputs['content'], $inputs['release_date'], $is_published, $user_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        unset($_SESSION['press_preview']);
        $_SESSION['success'] = "Press release published successfully!";
        header('Location: press.php');
        exit;
    } else {
        $errors[] = "Failed to publish press release.";
    }
}

// Handle INITIAL form submission
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [
        'title' => trim($_POST['title'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'release_date' => trim($_POST['release_date'] ?? ''),
        'is_published' => $_POST['is_published'] ?? '0'
    ];
    
    // Validation
    if (empty($inputs['title'])) $errors[] = "Title is required.";
    if (empty($inputs['content'])) $errors[] = "Content is required.";
    if (empty($inputs['release_date']) || !strtotime($inputs['release_date'])) {
        $errors[] = "Valid release date is required.";
    }
    
    if (empty($errors)) {
        $_SESSION['press_preview'] = $inputs;
        header('Location: create-press.php?preview=1');
        exit;
    }
}

$is_preview = (isset($_GET['preview']) && !empty($_SESSION['press_preview']));
if ($is_preview) {
    $inputs = $_SESSION['press_preview'];
}
?>

<?php include '../includes/header.php'; ?>

<style>
.press-form { max-width: 800px; margin: 0 auto; }
.form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
.preview-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 25px; margin: 25px 0; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1><?= $is_preview ? 'Review Press Release' : 'Create Press Release' ?></h1>
        <a href="press.php" class="btn btn-outline">← Back to Releases</a>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>
      
      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <?php if ($is_preview): ?>
        <!-- PREVIEW -->
        <div class="preview-section">
          <h2><?= htmlspecialchars($inputs['title']) ?></h2>
          <p><strong>Release Date:</strong> <?= htmlspecialchars($inputs['release_date']) ?></p>
          <p><strong>Status:</strong> <?= $inputs['is_published'] === '1' ? 'Published' : 'Draft' ?></p>
          <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px;">
            <?= nl2br(htmlspecialchars($inputs['content'])) ?>
          </div>
        </div>
        
        <form method="POST" style="text-align: center;">
          <input type="hidden" name="confirm_publish" value="1">
          <button type="submit" class="btn" style="background: var(--accent); color: white;">Publish Press Release</button>
          <a href="edit-press.php?id=<?= $press_id ?>" class="btn btn-outline">Edit</a>
        </form>
        
      <?php else: ?>
        <!-- FORM -->
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
                Publish immediately (uncheck to save as draft)
              </label>
            </div>
            
            <div class="form-group">
              <label for="content">Content <span>*</span></label>
              <textarea id="content" name="content" rows="12" required><?= htmlspecialchars($inputs['content']) ?></textarea>
            </div>
            
            <button type="submit" class="btn">Review Press Release</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>