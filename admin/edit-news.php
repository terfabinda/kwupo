<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$news_id = (int)($_GET['id'] ?? 0);
if (!$news_id) {
    $_SESSION['error'] = "Invalid news item.";
    header('Location: news.php');
    exit;
}

// Fetch existing news
$news = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM news WHERE news_id = $news_id
"));
if (!$news) {
    $_SESSION['error'] = "News item not found.";
    header('Location: news.php');
    exit;
}

$errors = [];
$inputs = [
    'title' => $news['title'],
    'content' => $news['content'],
    'is_published' => $news['is_published'] ? '1' : '0'
];
$featured_image = $news['featured_image'];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [
        'title' => trim($_POST['title'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'is_published' => $_POST['is_published'] ?? '0'
    ];
    
    if (empty($inputs['title'])) $errors[] = "Title is required.";
    if (empty($inputs['content'])) $errors[] = "Content is required.";
    
    // Handle image upload
    $new_image = $featured_image;
    if (!empty($_FILES['featured_image']['name'])) {
        $upload_dir = '../uploads/news/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        // Delete old image
        if ($featured_image && file_exists($upload_dir . $featured_image)) {
            unlink($upload_dir . $featured_image);
        }
        
        $file = $_FILES['featured_image'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $file['error'] == 0) {
            $filename = 'news_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                $new_image = $filename;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image. Use JPG or PNG.";
        }
    }
    
    if (empty($errors)) {
        // Update DB
        $stmt = mysqli_prepare($conn, "
            UPDATE news 
            SET title = ?, content = ?, featured_image = ?, is_published = ?, published_at = ?
            WHERE news_id = ?
        ");
        
        $is_published = (int)($inputs['is_published'] === '1');
        
        // In edit-news.php
$published_at = null;
if ($is_published) {
    // Only set published_at if it wasn't published before
    $published_at = $news['is_published'] ? $news['published_at'] : date('Y-m-d H:i:s');
}
        mysqli_stmt_bind_param($stmt, 'sssssi', 
    $inputs['title'], $inputs['content'], $new_image, 
    $is_published, $published_at, $news_id
);
        mysqli_stmt_execute($stmt);
        
        $_SESSION['success'] = "News article updated successfully.";
        header('Location: news.php');
        exit;
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
.news-form { max-width: 800px; margin: 0 auto; }
.form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
.image-preview { max-width: 200px; margin: 10px 0; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Edit News Article</h1>
        <a href="news.php" class="btn btn-outline">← Back to News</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="news-form">
        <div class="form-section">
          <div class="form-group">
            <label for="title">Title <span>*</span></label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($inputs['title']) ?>" required>
          </div>
          
          <div class="form-group">
            <label>Current Image</label>
            <?php if ($featured_image): ?>
              <img src="../uploads/news/<?= htmlspecialchars($featured_image) ?>" class="image-preview" alt="Current">
              <p><a href="#" onclick="document.getElementById('remove-img').value='1'; return false;">Remove image</a></p>
              <input type="hidden" id="remove-img" name="remove_image" value="0">
            <?php else: ?>
              <p>No image uploaded</p>
            <?php endif; ?>
            
            <label>New Image (Optional)</label>
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
          
          <button type="submit" class="btn">Update News Article</button>
        </div>
      </form>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>