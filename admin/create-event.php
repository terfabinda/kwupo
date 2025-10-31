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
    unset($_SESSION['event_preview'], $_SESSION['event_image']);
}

$errors = [];
$inputs = [
    'title' => '',
    'description' => '',
    'event_date' => date('Y-m-d'),
    'event_time' => '',
    'location' => '',
    'is_published' => '0'
];

// Restore session data
if (!empty($_SESSION['event_preview'])) {
    $inputs = $_SESSION['event_preview'];
}

// Handle FINAL publish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_publish'])) {
    if (empty($_SESSION['event_preview'])) {
        $_SESSION['error'] = "Session expired. Please resubmit.";
        header('Location: create-event.php');
        exit;
    }
    
    $inputs = $_SESSION['event_preview'];
    $featured_image = $_SESSION['event_image'] ?? null;
    
    // Escape values
    $title = mysqli_real_escape_string($conn, $inputs['title']);
    $slug_base = strtolower(trim($inputs['title']));
    $slug = mysqli_real_escape_string($conn, preg_replace('/[^a-z0-9]+/', '-', $slug_base));
    $slug = trim($slug, '-');
    $description = mysqli_real_escape_string($conn, $inputs['description']);
    $event_date = mysqli_real_escape_string($conn, $inputs['event_date']);
    $event_time = !empty($inputs['event_time']) ? mysqli_real_escape_string($conn, $inputs['event_time']) : '';
    $location = mysqli_real_escape_string($conn, $inputs['location']);
    $featured_image_db = $featured_image ? "'" . mysqli_real_escape_string($conn, $featured_image) . "'" : "NULL";
    $is_published = (int)($inputs['is_published'] === '1');
    $created_by = (int)$user_id;
    
    // Build time string
    $event_datetime_db = $event_time ? "'$event_date $event_time'" : "'$event_date'";
    
    $query = "
        INSERT INTO events (title, slug, description, event_date, event_time, location, featured_image, is_published, created_by, created_at)
        VALUES (
            '$title',
            '$slug',
            '$description',
            '$event_date',
            " . ($event_time ? "'$event_time'" : "NULL") . ",
            '$location',
            $featured_image_db,
            $is_published,
            $created_by,
            NOW()
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        unset($_SESSION['event_preview'], $_SESSION['event_image']);
        $_SESSION['success'] = "Event published successfully!";
        header('Location: events.php');
        exit;
    } else {
        $errors[] = "Failed to publish event.";
    }
}

// Handle INITIAL form submission
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'event_date' => trim($_POST['event_date'] ?? ''),
        'event_time' => trim($_POST['event_time'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'is_published' => isset($_POST['is_published']) ? '1' : '0'
    ];
    
    if (empty($inputs['title'])) $errors[] = "Title is required.";
    if (empty($inputs['description'])) $errors[] = "Description is required.";
    if (empty($inputs['event_date']) || !strtotime($inputs['event_date'])) {
        $errors[] = "Valid event date is required.";
    }
    if (empty($inputs['location'])) $errors[] = "Location is required.";
    
    // Handle image upload
    $featured_image = null;
    if (!empty($_FILES['featured_image']['name'])) {
        $upload_dir = '../uploads/events/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $file = $_FILES['featured_image'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $file['error'] == 0) {
            $filename = 'event_' . time() . '.' . $ext;
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
        $_SESSION['event_preview'] = $inputs;
        $_SESSION['event_image'] = $featured_image;
        header('Location: create-event.php?preview=1');
        exit;
    }
}

$is_preview = (isset($_GET['preview']) && !empty($_SESSION['event_preview']));
if ($is_preview) {
    $inputs = $_SESSION['event_preview'];
    $featured_image = $_SESSION['event_image'] ?? null;
}
?>

<?php include '../includes/header.php'; ?>

<style>
.event-form { max-width: 800px; margin: 0 auto; }
.form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
.preview-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 25px; margin: 25px 0; }
.image-preview { max-width: 200px; margin: 10px 0; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1><?= $is_preview ? 'Review Event' : 'Create Event' ?></h1>
        <a href="events.php" class="btn btn-outline">← Back to Events</a>
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
            <img src="../uploads/events/<?= htmlspecialchars($featured_image) ?>" class="image-preview" alt="Featured">
          <?php endif; ?>
          <h2><?= htmlspecialchars($inputs['title']) ?></h2>
          <p><strong>Date:</strong> <?= htmlspecialchars($inputs['event_date']) ?> <?= $inputs['event_time'] ? 'at ' . htmlspecialchars($inputs['event_time']) : '' ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($inputs['location']) ?></p>
          <p><strong>Status:</strong> <?= $inputs['is_published'] === '1' ? 'Published' : 'Draft' ?></p>
          <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 6px;">
            <?= nl2br(htmlspecialchars($inputs['description'])) ?>
          </div>
        </div>
        
        <form method="POST" style="text-align: center;">
          <input type="hidden" name="confirm_publish" value="1">
          <button type="submit" class="btn" style="background: var(--accent); color: white;">Publish Event</button>
          <a href="create-event.php?edit=1" class="btn btn-outline">Edit</a>
        </form>
        
      <?php else: ?>
        <!-- FORM -->
        <form method="POST" enctype="multipart/form-data" class="event-form">
          <div class="form-section">
            <div class="form-group">
              <label for="title">Title <span>*</span></label>
              <input type="text" id="title" name="title" value="<?= htmlspecialchars($inputs['title']) ?>" required>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="event_date">Event Date <span>*</span></label>
                <input type="date" id="event_date" name="event_date" value="<?= htmlspecialchars($inputs['event_date']) ?>" required>
              </div>
              <div class="form-group">
                <label for="event_time">Event Time</label>
                <input type="time" id="event_time" name="event_time" value="<?= htmlspecialchars($inputs['event_time']) ?>">
              </div>
            </div>
            
            <div class="form-group">
              <label for="location">Location <span>*</span></label>
              <input type="text" id="location" name="location" value="<?= htmlspecialchars($inputs['location']) ?>" required>
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
              <label for="description">Description <span>*</span></label>
              <textarea id="description" name="description" rows="8" required><?= htmlspecialchars($inputs['description']) ?></textarea>
            </div>
            
            <button type="submit" class="btn">Review Event</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>