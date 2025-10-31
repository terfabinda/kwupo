<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Clear session if not in preview/edit
if (!isset($_GET['preview']) && !isset($_GET['edit'])) {
    unset($_SESSION['news_preview'], $_SESSION['news_image']);
}

$errors = [];
$inputs = [
    'title' => '',
    'content' => '',
    'is_published' => '0'
];

// Restore session data
if (!empty($_SESSION['news_preview'])) {
    $inputs = $_SESSION['news_preview'];
}

// Handle FINAL publish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_publish'])) {
    if (empty($_SESSION['news_preview'])) {
        $_SESSION['error'] = "Session expired. Please resubmit.";
        header('Location: create-news.php');
        exit;
    }
    
    $inputs = $_SESSION['news_preview'];
    $featured_image = $_SESSION['news_image'] ?? null;
    
    // Escape values for direct insert
    $title = mysqli_real_escape_string($conn, $inputs['title']);
    $slug_base = strtolower(trim($inputs['title']));
    $slug = mysqli_real_escape_string($conn, preg_replace('/[^a-z0-9]+/', '-', $slug_base));
    $slug = trim($slug, '-');
    $content = mysqli_real_escape_string($conn, $inputs['content']);
    $featured_image_db = $featured_image ? "'" . mysqli_real_escape_string($conn, $featured_image) . "'" : "NULL";
    $is_published = (int)($inputs['is_published'] === '1');
    
    // Handle published_at safely
    if ($is_published) {
        $published_at_db = "'" . date('Y-m-d H:i:s') . "'";
    } else {
        $published_at_db = "NULL";
    }
    
    $created_by = (int)$user_id;
    
    // Direct insert (safe with escaping)
    $query = "
        INSERT INTO news (title, slug, content, featured_image, is_published, published_at, created_by, created_at)
        VALUES (
            '$title', 
            '$slug', 
            '$content', 
            $featured_image_db, 
            $is_published, 
            $published_at_db, 
            $created_by, 
            NOW()
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        unset($_SESSION['news_preview'], $_SESSION['news_image']);
        $_SESSION['success'] = "News article published successfully!";
        header('Location: news.php');
        exit;
    } else {
        $errors[] = "Failed to publish news article.";
    }
}

// Handle INITIAL form submission
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [
        'title' => trim($_POST['title'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'is_published' => isset($_POST['is_published']) ? '1' : '0'  // Critical for checkbox
    ];
    
    if (empty($inputs['title'])) $errors[] = "Title is required.";
    if (empty($inputs['content'])) $errors[] = "Content is required.";
    
    // Handle image upload
    $featured_image = null;
    if (!empty($_FILES['featured_image']['name'])) {
        $upload_dir = '../uploads/news/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file = $_FILES['featured_image'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $file['error'] == 0) {
            $filename = 'news_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $featured_image = $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image. Use JPG or PNG.";
        }
    }
    
    if (empty($errors)) {
        $_SESSION['news_preview'] = $inputs;
        $_SESSION['news_image'] = $featured_image;
        header('Location: create-news.php?preview=1');
        exit;
    }
}

$is_preview = (isset($_GET['preview']) && !empty($_SESSION['news_preview']));
if ($is_preview) {
    $inputs = $_SESSION['news_preview'];
    $featured_image = $_SESSION['news_image'] ?? null;
}
?>

<?php include '../includes/header.php'; ?>

<style>
.news-form { max-width: 800px; margin: 0 auto; }
.form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
.preview-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 25px; margin: 25px 0; }
.image-preview { max-width: 200px; margin: 10px 0; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1><?= $is_preview ? 'Review News Article' : 'Create News Article' ?></h1>
        <a href="news.php" class="btn btn-outline">← Back to News</a>
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
          <?php if ($featured_image): ?>
            <img src="../uploads/news/<?= htmlspecialchars($featured_image) ?>" class="image-preview" alt="Featured">
          <?php endif; ?>
          <h2><?= htmlspecialchars($inputs['title']) ?></h2>
          <p><strong>Status:</strong> <?= $inputs['is_published'] === '1' ? 'Published' : 'Draft' ?></p>
          <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px;">
            <?= nl2br(htmlspecialchars($inputs['content'])) ?>
          </div>
        </div>
        
        <form method="POST" style="text-align: center;">
          <input type="hidden" name="confirm_publish" value="1">
          <button type="submit" class="btn" style="background: var(--accent); color: white;">Publish News Article</button>
          <a href="create-news.php?edit=1" class="btn btn-outline">Edit</a>
        </form>
        
      <?php else: ?>
        <!-- FORM -->
        <form method="POST" enctype="multipart/form-data" class="news-form">
          <div class="form-section">
            <div class="form-group">
              <label for="title">Title <span>*</span></label>
              <input type="text" id="title" name="title" value="<?= htmlspecialchars($inputs['title']) ?>" required>
            </div>
            
            <div class="form-group">
              <label>Featured Image (Optional)</label>
              <input type="file" name="featured_image" accept="image/jpeg,image/png">
              <p class="help-text">JPG or PNG, max 2MB</p>
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
            
            <button type="submit" class="btn">Review News Article</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>