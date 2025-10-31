<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

$event_id = (int)($_GET['id'] ?? 0);
if (!$event_id) {
    $_SESSION['error'] = "Invalid event.";
    header('Location: events.php');
    exit;
}

$event = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM events WHERE event_id = $event_id
"));
if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header('Location: events.php');
    exit;
}

$errors = [];
$inputs = [
    'title' => $event['title'],
    'description' => $event['description'],
    'event_date' => $event['event_date'],
    'event_time' => $event['event_time'] ?? '',
    'location' => $event['location'],
    'is_published' => $event['is_published'] ? '1' : '0'
];
$featured_image = $event['featured_image'];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $new_image = $featured_image;
    if (!empty($_FILES['featured_image']['name'])) {
        $upload_dir = '../uploads/events/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        if ($featured_image && file_exists($upload_dir . $featured_image)) {
            unlink($upload_dir . $featured_image);
        }
        
        $file = $_FILES['featured_image'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed) && $file['error'] == 0) {
            $filename = 'event_' . time() . '.' . $ext;
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
        // Escape values
        $title = mysqli_real_escape_string($conn, $inputs['title']);
        $slug_base = strtolower(trim($inputs['title']));
        $slug = mysqli_real_escape_string($conn, preg_replace('/[^a-z0-9]+/', '-', $slug_base));
        $slug = trim($slug, '-');
        $description = mysqli_real_escape_string($conn, $inputs['description']);
        $event_date = mysqli_real_escape_string($conn, $inputs['event_date']);
        $event_time = !empty($inputs['event_time']) ? mysqli_real_escape_string($conn, $inputs['event_time']) : '';
        $location = mysqli_real_escape_string($conn, $inputs['location']);
        $featured_image_db = $new_image ? "'" . mysqli_real_escape_string($conn, $new_image) . "'" : "NULL";
        $is_published = (int)($inputs['is_published'] === '1');
        
        $query = "
            UPDATE events 
            SET title = '$title',
                slug = '$slug',
                description = '$description',
                event_date = '$event_date',
                event_time = " . ($event_time ? "'$event_time'" : "NULL") . ",
                location = '$location',
                featured_image = $featured_image_db,
                is_published = $is_published
            WHERE event_id = $event_id
        ";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Event updated successfully.";
            header('Location: events.php');
            exit;
        } else {
            $errors[] = "Failed to update event.";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
.event-form { max-width: 800px; margin: 0 auto; }
.form-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 25px; }
.image-preview { max-width: 200px; margin: 10px 0; }
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Edit Event</h1>
        <a href="events.php" class="btn btn-outline">← Back to Events</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

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
            <label>Current Image</label>
            <?php if ($featured_image): ?>
              <img src="../uploads/events/<?= htmlspecialchars($featured_image) ?>" class="image-preview" alt="Current">
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
            <label for="description">Description <span>*</span></label>
            <textarea id="description" name="description" rows="8" required><?= htmlspecialchars($inputs['description']) ?></textarea>
          </div>
          
          <button type="submit" class="btn">Update Event</button>
        </div>
      </form>
    </div>
  </main>
</div>

<?php include '../includes/footer.php'; ?>