<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Member access only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id"));

// Fetch static data
$categories = mysqli_query($conn, "SELECT * FROM static_incident_categories ORDER BY category_name");
$lgas = mysqli_query($conn, "SELECT * FROM static_users_lga ORDER BY lga_name");
$agencies = mysqli_query($conn, "SELECT * FROM static_response_agencies ORDER BY agency_name");
$sources = mysqli_query($conn, "SELECT * FROM static_information_sources ORDER BY source_name");

$errors = [];
$inputs = [
    'title' => '',
    'category_id' => '',
    'lga_id' => '',
    'ward_id' => '',
    'community' => '',
    'description' => '',
    'agency_id' => '',
    'source_id' => '',
    'affected' => 0,
    'deaths' => 0,
    'injured' => 0,
    'missing' => 0,
    'displaced' => 0,
    'incident_date' => date('Y-m-d'),
    'incident_time' => date('H:i'),
    'property_loss' => ''
];

// Restore session data if available (for edit or preview)
if (!empty($_SESSION['report_preview'])) {
    $inputs = $_SESSION['report_preview'];
}

// Handle FINAL submission (from preview)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_submit'])) {
    if (empty($_SESSION['report_preview'])) {
        $_SESSION['error'] = "Session expired. Please resubmit your report.";
        header('Location: report.php');
        exit;
    }
    
    $inputs = $_SESSION['report_preview'];
    $uploaded_files_info = $_SESSION['report_media'] ?? [];
    
    // Generate crisis code: CRI-YYYY-00XXX
    $year = date('Y');
    $last_report = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT crisis_code FROM incident_reports 
        WHERE crisis_code LIKE 'CRI-$year-%' 
        ORDER BY report_id DESC LIMIT 1
    "));
    
    if ($last_report) {
        $last_num = (int)substr($last_report['crisis_code'], -5);
        $new_num = str_pad($last_num + 1, 5, '0', STR_PAD_LEFT);
    } else {
        $new_num = '00001';
    }
    $crisis_code = "CRI-$year-$new_num";
    
    // Insert into DB
    mysqli_autocommit($conn, false);
    try {
        $stmt = mysqli_prepare($conn, "
     INSERT INTO incident_reports 
        (crisis_code, user_id, title, category_id, lga_id, ward_id, community_name, description, 
         incident_date, incident_time, agency_id, source_id,
         affected_population, deaths, injured, missing, displaced, property_loss, 
         is_verified, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
");
        
     mysqli_stmt_bind_param(
        $stmt,
        'sisiisssssiiiiiiis', // ← CORRECT 18-char string
        $crisis_code,
        $user_id,
        $inputs['title'],
        $inputs['category_id'],
        $inputs['lga_id'],
        $inputs['ward_id'],
        $inputs['community'],
        $inputs['description'],
        $inputs['incident_date'],
        $inputs['incident_time'],
        $inputs['agency_id'],
        $inputs['source_id'],
        $inputs['affected'],
        $inputs['deaths'],
        $inputs['injured'],
        $inputs['missing'],
        $inputs['displaced'],
        $inputs['property_loss']
    );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Report submission failed.");
        }
        
        $report_id = mysqli_insert_id($conn);
        
        // Insert media
        if (!empty($uploaded_files_info)) {
            $media_stmt = mysqli_prepare($conn, "
                INSERT INTO incident_media (report_id, file_name, file_type, file_size, uploaded_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            foreach ($uploaded_files_info as $file) {
                mysqli_stmt_bind_param($media_stmt, 'isss', 
                    $report_id, $file['file_name'], $file['file_type'], $file['file_size']
                );
                mysqli_stmt_execute($media_stmt);
            }
            mysqli_stmt_close($media_stmt);
        }
        
        mysqli_commit($conn);
        unset($_SESSION['report_preview'], $_SESSION['report_media']);
        $_SESSION['success'] = "Incident report submitted successfully. Your Incident ID is: <strong>$crisis_code</strong>";
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errors[] = "Submission failed: " . $e->getMessage();
    }
}

// Handle INITIAL form submission
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = [
        'title' => trim($_POST['title'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'lga_id' => (int)($_POST['lga_id'] ?? 0),
        'ward_id' => (int)($_POST['ward_id'] ?? 0),
        'community' => trim($_POST['community'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'agency_id' => (int)($_POST['agency_id'] ?? 0),
        'source_id' => (int)($_POST['source_id'] ?? 0),
        'incident_date' => trim($_POST['incident_date'] ?? ''),
        'incident_time' => trim($_POST['incident_time'] ?? ''),
        'affected' => (int)($_POST['affected'] ?? 0),
        'deaths' => (int)($_POST['deaths'] ?? 0),
        'injured' => (int)($_POST['injured'] ?? 0),
        'missing' => (int)($_POST['missing'] ?? 0),
        'displaced' => (int)($_POST['displaced'] ?? 0),
        'property_loss' => trim($_POST['property_loss'] ?? '')
    ];
    
    // Validation
    if (empty($inputs['title'])) $errors[] = "Incident title is required.";
    if (empty($inputs['description'])) $errors[] = "Incident description is required.";
    if (empty($inputs['community'])) $errors[] = "Community name is required.";
    if ($inputs['category_id'] <= 0) $errors[] = "Please select an incident category.";
    if ($inputs['lga_id'] <= 0) $errors[] = "Please select an LGA.";
    if ($inputs['ward_id'] <= 0) $errors[] = "Please select your ward.";
    if ($inputs['agency_id'] <= 0) $errors[] = "Please select a response agency.";
    if ($inputs['source_id'] <= 0) $errors[] = "Please select an information source.";
    if (empty($inputs['incident_date']) || !strtotime($inputs['incident_date'])) $errors[] = "Valid incident date is required.";
    if (empty($inputs['incident_time']) || !strtotime($inputs['incident_time'])) $errors[] = "Valid incident time is required.";
    
    $non_neg_fields = ['affected', 'deaths', 'injured', 'missing', 'displaced'];
    foreach ($non_neg_fields as $field) {
        if ($inputs[$field] < 0) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " cannot be negative.";
        }
    }
    
    // Handle file uploads
    $uploaded_files_info = [];
    $media_files = $_FILES['media_files'] ?? null;
    if ($media_files && empty($errors)) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'video/mp4'];
        $max_size = 5 * 1024 * 1024;
        $upload_dir = '../uploads/incident_media/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $count = count($media_files['name']);
        if ($count > 5) {
            $errors[] = "You can upload a maximum of 5 media files.";
        } else {
            for ($i = 0; $i < $count; $i++) {
                if ($media_files['error'][$i] !== UPLOAD_ERR_OK) continue;
                
                $file_name = $media_files['name'][$i];
                $file_tmp = $media_files['tmp_name'][$i];
                $file_size = $media_files['size'][$i];
                $file_type = $media_files['type'][$i];
                
                if ($file_size > $max_size) {
                    $errors[] = "File '$file_name' exceeds 5MB limit.";
                    break;
                }
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "File '$file_name' has an unsupported type.";
                    break;
                }
                
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_name = uniqid('kwupo_media_', true) . '.' . strtolower($ext);
                $destination = $upload_dir . $new_name;
                
                if (move_uploaded_file($file_tmp, $destination)) {
                    $uploaded_files_info[] = [
                        'file_name' => $new_name,
                        'file_type' => $file_type,
                        'file_size' => $file_size
                    ];
                } else {
                    $errors[] = "Failed to upload file '$file_name'.";
                    break;
                }
            }
        }
    }
    
    // If valid, go to preview
    if (empty($errors)) {
        $_SESSION['report_preview'] = $inputs;
        $_SESSION['report_media'] = $uploaded_files_info;
        header('Location: report.php?preview=1');
        exit;
    }
}

// Determine if showing preview
$is_preview = (isset($_GET['preview']) && !empty($_SESSION['report_preview']));
if ($is_preview) {
    $inputs = $_SESSION['report_preview'];
    $uploaded_files_info = $_SESSION['report_media'] ?? [];
}
?>

<?php include '../includes/header.php'; ?>

<style>
.report-instructions {
  background: #fff8e1;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 30px;
  border-left: 4px solid #f57f17;
  color: #5d4037;
}
.form-section {
  background: white;
  border: 1px solid #eee;
  border-radius: 8px;
  padding: 25px;
  margin-bottom: 30px;
}
.preview-section {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 25px;
  margin: 30px 0;
}
.media-preview {
  display: inline-block;
  margin: 5px;
}
.media-preview img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 4px;
}
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1><?= $is_preview ? 'Review Incident Report' : 'Report Incident' ?></h1>
        <a href="index.php" class="btn btn-outline">← Back to Dashboard</a>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>
      
      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($is_preview): ?>
        <!-- PREVIEW MODE -->
        <div class="preview-section">
          <h2>Incident Report Preview</h2>
          <p><strong>Incident ID:</strong> Will be generated as <code>CRI-<?= date('Y') ?>-XXXXX</code> upon submission</p>
          
          <h3>Incident Details</h3>
          <p><strong>Title:</strong> <?= htmlspecialchars($inputs['title']) ?></p>
          <p><strong>Category:</strong> 
            <?php mysqli_data_seek($categories, 0); 
            while ($cat = mysqli_fetch_assoc($categories)) {
                if ($cat['category_id'] == $inputs['category_id']) {
                    echo htmlspecialchars($cat['category_name']); break;
                }
            } ?>
          </p>
          <p><strong>Community:</strong> <?= htmlspecialchars($inputs['community']) ?></p>
          <p><strong>Date & Time:</strong> <?= htmlspecialchars($inputs['incident_date']) ?> at <?= htmlspecialchars($inputs['incident_time']) ?></p>
          <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($inputs['description'])) ?></p>
          
          <h3>Location</h3>
          <?php
          $lga_name = 'N/A';
          mysqli_data_seek($lgas, 0);
          while ($lga = mysqli_fetch_assoc($lgas)) {
              if ($lga['lga_id'] == $inputs['lga_id']) {
                  $lga_name = $lga['lga_name']; break;
              }
          }
          $ward_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ward_name FROM static_users_council_wards WHERE ward_id = {$inputs['ward_id']}"));
          $ward_name = $ward_row ? $ward_row['ward_name'] : 'N/A';
          ?>
          <p><?= htmlspecialchars($ward_name) ?>, <?= htmlspecialchars($lga_name) ?></p>
          
          <h3>Response & Source</h3>
          <?php
          $agency_name = 'N/A';
          mysqli_data_seek($agencies, 0);
          while ($ag = mysqli_fetch_assoc($agencies)) {
              if ($ag['agency_id'] == $inputs['agency_id']) {
                  $agency_name = $ag['agency_name']; break;
              }
          }
          $source_name = 'N/A';
          mysqli_data_seek($sources, 0);
          while ($src = mysqli_fetch_assoc($sources)) {
              if ($src['source_id'] == $inputs['source_id']) {
                  $source_name = $src['source_name']; break;
              }
          }
          ?>
          <p><strong>Agency:</strong> <?= htmlspecialchars($agency_name) ?><br>
             <strong>Source:</strong> <?= htmlspecialchars($source_name) ?></p>
          
          <h3>Impact Assessment</h3>
          <p>
            Affected: <?= (int)$inputs['affected'] ?> | 
            Deaths: <?= (int)$inputs['deaths'] ?> | 
            Injured: <?= (int)$inputs['injured'] ?> | 
            Missing: <?= (int)$inputs['missing'] ?> | 
            Displaced: <?= (int)$inputs['displaced'] ?>
          </p>
          <?php if (!empty($inputs['property_loss'])): ?>
            <p><strong>Property Loss:</strong> <?= htmlspecialchars($inputs['property_loss']) ?></p>
          <?php endif; ?>
          
          <?php if (!empty($uploaded_files_info)): ?>
            <h3>Media Evidence</h3>
            <?php foreach ($uploaded_files_info as $file): ?>
              <?php if (strpos($file['file_type'], 'image/') === 0): ?>
                <span class="media-preview">
                  <img src="../uploads/incident_media/<?= htmlspecialchars($file['file_name']) ?>" alt="Preview">
                </span>
              <?php else: ?>
                <span class="media-preview">[Video]</span>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <form method="POST">
          <input type="hidden" name="confirm_submit" value="1">
          <button type="submit" class="btn" style="background: var(--accent); color: white;">Confirm & Submit Report</button>
          <a href="report.php?edit=1" class="btn btn-outline">Edit Report</a>
        </form>
        
      <?php else: ?>
        <!-- ALWAYS SHOW FORM -->
        <div class="report-instructions">
          <p><strong>Important:</strong> This form is for reporting genuine community incidents. 
             False reports may result in account suspension. All reports are confidential.</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="report-form">
          <!-- Basic Information -->
          <div class="form-section">
            <h2>Incident Details</h2>
            
            <div class="form-row">
              <div class="form-group">
                <label for="title">Incident Title <span>*</span></label>
                <input type="text" id="title" name="title" 
                       value="<?= htmlspecialchars($inputs['title']) ?>" required>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="category_id">Category <span>*</span></label>
                <select id="category_id" name="category_id" required>
                  <option value="">Select</option>
                  <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= (int)$cat['category_id'] ?>" <?= $inputs['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              
              <div class="form-group">
                <label for="lga_id">LGA <span>*</span></label>
                <select id="lga_id" name="lga_id" required>
                  <option value="">Select</option>
                  <?php mysqli_data_seek($lgas, 0); while ($lga = mysqli_fetch_assoc($lgas)): ?>
                    <option value="<?= (int)$lga['lga_id'] ?>" <?= $inputs['lga_id'] == $lga['lga_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($lga['lga_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="ward_id">Ward <span>*</span></label>
                <select id="ward_id" name="ward_id" required>
                  <option value="">Select LGA first</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="agency_id">Response Agency <span>*</span></label>
                <select id="agency_id" name="agency_id" required>
                  <option value="">Select</option>
                  <?php while ($ag = mysqli_fetch_assoc($agencies)): ?>
                    <option value="<?= (int)$ag['agency_id'] ?>" <?= $inputs['agency_id'] == $ag['agency_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($ag['agency_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="source_id">Information Source <span>*</span></label>
                <select id="source_id" name="source_id" required>
                  <option value="">Select</option>
                  <?php while ($src = mysqli_fetch_assoc($sources)): ?>
                    <option value="<?= (int)$src['source_id'] ?>" <?= $inputs['source_id'] == $src['source_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($src['source_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="community">Community <span>*</span></label>
                <input type="text" id="community" name="community" 
                       value="<?= htmlspecialchars($inputs['community']) ?>" required>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="description">Description <span>*</span></label>
                <textarea id="description" name="description" required><?= htmlspecialchars($inputs['description']) ?></textarea>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="incident_date">Incident Date <span>*</span></label>
              <input type="date" id="incident_date" name="incident_date" 
                     value="<?= htmlspecialchars($inputs['incident_date']) ?>" required>
            </div>

            <div class="form-group">
              <label for="incident_time">Incident Time <span>*</span></label>
              <input type="time" id="incident_time" name="incident_time" 
                     value="<?= htmlspecialchars($inputs['incident_time']) ?>" required>
            </div>
          </div>

          <!-- Impact -->
          <div class="form-section">
            <h2>Impact Assessment</h2>
            <p>Enter 0 if not applicable.</p>
            
            <div class="form-row">
              <div class="form-group">
                <label for="affected">Affected</label>
                <input type="number" name="affected" min="0" value="<?= (int)$inputs['affected'] ?>">
              </div>
              <div class="form-group">
                <label for="deaths">Deaths</label>
                <input type="number" name="deaths" min="0" value="<?= (int)$inputs['deaths'] ?>">
              </div>
              <div class="form-group">
                <label for="injured">Injured</label>
                <input type="number" name="injured" min="0" value="<?= (int)$inputs['injured'] ?>">
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="missing">Missing</label>
                <input type="number" name="missing" min="0" value="<?= (int)$inputs['missing'] ?>">
              </div>
              <div class="form-group">
                <label for="displaced">Displaced</label>
                <input type="number" name="displaced" min="0" value="<?= (int)$inputs['displaced'] ?>">
              </div>
            </div>
          </div>

          <!-- Property Loss -->
          <div class="form-section">
            <h2>Property Loss (Optional)</h2>
            <div class="form-row">
              <div class="form-group">
                <label>Description</label>
                <textarea name="property_loss"><?= htmlspecialchars($inputs['property_loss']) ?></textarea>
              </div>
            </div>
          </div>

          <!-- Media -->
          <div class="form-section">
            <h2>Media Evidence (Optional)</h2>
            <p>Max 5 files (JPG, PNG, MP4). Max 5MB each.</p>
            <div class="form-row">
              <div class="form-group">
                <input type="file" name="media_files[]" multiple accept="image/*,video/mp4">
                <p class="help-text">Supported: JPG, PNG, MP4 (Max 5 files)</p>
              </div>
            </div>
          </div>

          <button type="submit" class="btn">Review Report</button>
        </form>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>
document.getElementById('lga_id').addEventListener('change', function() {
  const lgaId = this.value;
  const wardSelect = document.getElementById('ward_id');
  
  if (!lgaId) {
    wardSelect.innerHTML = '<option value="">Select LGA first</option>';
    wardSelect.disabled = true;
    return;
  }

  fetch(`../assets/php/get-wards.php?lga_id=${encodeURIComponent(lgaId)}`)
    .then(response => response.json())
    .then(wards => {
      let options = '<option value="">Select Ward</option>';
      wards.forEach(ward => {
        options += `<option value="${ward.ward_id}">${ward.ward_name}</option>`;
      });
      wardSelect.innerHTML = options;
      wardSelect.disabled = false;
      wardSelect.value = ''; // ← Ensures "Select Ward" is shown
    })
    .catch(() => {
      wardSelect.innerHTML = '<option value="">Error loading wards</option>';
      wardSelect.disabled = true;
    });
});

document.addEventListener('DOMContentLoaded', () => {
  const lgaSelect = document.getElementById('lga_id');
  if (lgaSelect.value) {
    lgaSelect.dispatchEvent(new Event('change'));
    
    // Re-select ward if data exists
    const savedWard = '<?= (int)($inputs['ward_id'] ?? 0) ?>';
    if (savedWard) {
      setTimeout(() => {
        const wardSelect = document.getElementById('ward_id');
        if (wardSelect.querySelector(`option[value="${savedWard}"]`)) {
          wardSelect.value = savedWard;
        }
      }, 300);
    }
  }
});
</script>

<?php include '../includes/footer.php'; ?>