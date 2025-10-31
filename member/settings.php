<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

// Define Nigeria's country_id
define('NIGERIA_COUNTRY_ID', 143);

// Member access only
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: ../signin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id"));
if (!$user) {
    $_SESSION['error'] = "Account not found.";
    header('Location: ../signin.php');
    exit;
}

// Fetch user's address (if exists)
$address_record = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_addresses WHERE user_id = $user_id"));

// Safe fallback to prevent "array offset on null" warnings
$addr = $address_record ?: [
    'address_line' => '',
    'country_id' => NIGERIA_COUNTRY_ID, // Default to Nigeria
    'state_id' => null,
    'lga_id' => null,
    'state_text' => '',
    'lga_text' => ''
];

// Fetch reference data
$countries = mysqli_query($conn, "SELECT country_id, country_name FROM static_countries ORDER BY country_name");
$nigeria_states = mysqli_query($conn, "SELECT state_id, state_name FROM static_states WHERE country_id = " . NIGERIA_COUNTRY_ID . " ORDER BY state_name");

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Personal info
    $firstname = trim($_POST['firstname'] ?? '');
    $middlename = trim($_POST['middlename'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $title_prefix_id = (int)($_POST['title_prefix_id'] ?? 0);
    $title_suffix = trim($_POST['title_suffix'] ?? '');
    $ward_id = (int)($_POST['ward_id'] ?? 0);
    
    // Address info
    $address_line = trim($_POST['address_line'] ?? '');
    $country_id = (int)($_POST['country_id'] ?? 0);
    $state_id = (int)($_POST['state_id'] ?? 0);
    $lga_id = (int)($_POST['lga_id'] ?? 0);
    $state_text = trim($_POST['state_text'] ?? '');
    $lga_text = trim($_POST['lga_text'] ?? '');
    
    // Validation
    if (empty($firstname)) $errors[] = "First name is required.";
    if (empty($surname)) $errors[] = "Surname is required.";
    if (empty($address_line)) $errors[] = "Residential address is required.";
    if ($country_id <= 0) $errors[] = "Please select country.";
    if ($ward_id <= 0) $errors[] = "Please select your ward.";
    
    // Country-specific validation
if ($country_id == NIGERIA_COUNTRY_ID) { // Nigeria
    if ($state_id <= 0) $errors[] = "Please select state.";
    if ($lga_id <= 0) $errors[] = "Please select LGA.";
} else {
    if (empty($state_text)) $errors[] = "State/Province is required.";
}
    
    // Validate title
    if ($title_prefix_id > 0) {
        $check = mysqli_query($conn, "SELECT title_id FROM static_user_titles WHERE title_id = $title_prefix_id");
        if (mysqli_num_rows($check) === 0) {
            $errors[] = "Invalid title selection.";
        }
    }
    
    // Handle profile image upload (FULL LOGIC)
    $profile_image = $user['profile_image']; // keep existing if not changed
    if (!empty($_FILES['profile_image']['name'])) {
        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading image. Please try again.";
        } else {
            $upload_dir = dirname(__DIR__) . '/uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file = $_FILES['profile_image'];
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($ext, $allowed)) {
                $errors[] = "Only JPG and PNG images are allowed.";
            } elseif ($file['size'] > $max_size) {
                $errors[] = "Image must be under 5MB.";
            } else {
                // Delete old image
                if ($user['profile_image']) {
                    $old_path = $upload_dir . $user['profile_image'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                
                // Save new image
                $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $profile_image = $filename;
                } else {
                    $errors[] = "Failed to save image. Check folder permissions.";
                }
            }
        }
    }
    
    // Save if valid
if (empty($errors)) {
    mysqli_autocommit($conn, false);
    try {
        // Update user (including profile_image)
        $prefix_val = ($title_prefix_id > 0) ? $title_prefix_id : null;
        $stmt_user = mysqli_prepare($conn, "
            UPDATE users 
            SET firstname = ?, middlename = ?, surname = ?, occupation = ?,
                title_prefix_id = ?, title_suffix = ?, ward_id = ?, profile_image = ?
            WHERE user_id = ?
        ");
        mysqli_stmt_bind_param($stmt_user, 'ssssssssi', 
            $firstname, $middlename, $surname, $occupation,
            $prefix_val, $title_suffix, $ward_id, $profile_image, $user_id
        );
        mysqli_stmt_execute($stmt_user);
        
        // Prepare address values as VARIABLES (required for bind_param)
        $addr_state_id = $state_id ?: null;
        $addr_lga_id = $lga_id ?: null;
        $addr_state_text = $state_text ?: null;
        $addr_lga_text = $lga_text ?: null;
        
        // Upsert address
        if ($address_record) {
            $stmt_addr = mysqli_prepare($conn, "
                UPDATE user_addresses 
                SET address_line = ?, country_id = ?, 
                    state_id = ?, lga_id = ?,
                    state_text = ?, lga_text = ?
                WHERE user_id = ?
            ");
            mysqli_stmt_bind_param($stmt_addr, 'siiiiis', 
                $address_line, $country_id,
                $addr_state_id, $addr_lga_id,
                $addr_state_text, $addr_lga_text,
                $user_id
            );
        } else {
            $stmt_addr = mysqli_prepare($conn, "
                INSERT INTO user_addresses 
                (user_id, address_line, country_id, state_id, lga_id, state_text, lga_text)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt_addr, 'isiiiii', 
                $user_id, $address_line, $country_id,
                $addr_state_id, $addr_lga_id,
                $addr_state_text, $addr_lga_text
            );
        }
        mysqli_stmt_execute($stmt_addr);
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Profile updated successfully!";
        
        // Refresh data
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id"));
        $address_record = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_addresses WHERE user_id = $user_id"));
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $errors[] = "Update failed. Please try again.";
    }
}
}

// Fetch LGAs for Nigeria (used in JS)
$nigeria_states = mysqli_query($conn, "SELECT state_id, state_name FROM static_states WHERE country_id = " . NIGERIA_COUNTRY_ID . " ORDER BY state_name");
?>

<?php include '../includes/header.php'; ?>

<style>
.profile-preview {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--accent);
  margin: 0 auto;
  display: block;
}
.profile-placeholder {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  color: #999;
  border: 1px solid #ddd;
}
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Profile Settings</h1>
        <a href="index.php" class="btn btn-outline">← Back to Dashboard</a>
      </div>

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
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

      <form method="POST" enctype="multipart/form-data" class="signup-form">
        <!-- Profile Image -->
        <div class="form-group text-center" style="margin-bottom: 30px;">
          <?php if (!empty($user['profile_image'])): ?>
            <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" 
                 alt="Profile" 
                 class="profile-preview">
          <?php else: ?>
            <div class="profile-placeholder">
              <i class="fas fa-user fa-3x"></i>
            </div>
          <?php endif; ?>
          
          <div style="margin-top: 15px;">
            <input type="file" name="profile_image" accept="image/jpeg,image/png">
            <p style="font-size: 0.85rem; color: #666; margin-top: 8px;">
              JPG or PNG only (max 5MB). Leave empty to keep current image.
            </p>
          </div>
        </div>

        <!-- Personal Info -->
        <div class="form-row">
          <div class="form-group">
            <label for="firstname">First Name <span>*</span></label>
            <input type="text" id="firstname" name="firstname" 
                   value="<?= htmlspecialchars($user['firstname']) ?>" required>
          </div>
          <div class="form-group">
            <label for="middlename">Middle Name</label>
            <input type="text" id="middlename" name="middlename" 
                   value="<?= htmlspecialchars($user['middlename'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="surname">Surname <span>*</span></label>
            <input type="text" id="surname" name="surname" 
                   value="<?= htmlspecialchars($user['surname']) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="occupation">Occupation</label>
            <input type="text" id="occupation" name="occupation" 
                   value="<?= htmlspecialchars($user['occupation'] ?? '') ?>"
                   placeholder="e.g., Farmer, Teacher">
          </div>
        </div>

        <!-- Titles -->
        <div class="form-row">
          <div class="form-group">
            <label for="title_prefix_id">Title (Prefix)</label>
            <select id="title_prefix_id" name="title_prefix_id">
              <option value="">None</option>
              <?php
              $prefixes = mysqli_query($conn, "SELECT title_id, title_prefix FROM static_user_titles ORDER BY display_order");
              while ($p = mysqli_fetch_assoc($prefixes)) {
                $selected = ($user['title_prefix_id'] == $p['title_id']) ? 'selected' : '';
                echo "<option value='{$p['title_id']}' $selected>{$p['title_prefix']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="form-group">
            <label for="title_suffix">Suffix</label>
            <input type="text" id="title_suffix" name="title_suffix" 
                   value="<?= htmlspecialchars($user['title_suffix'] ?? '') ?>" 
                   placeholder="e.g., PhD, MON, OON">
          </div>
        </div>

        <!-- Residential Address -->
        <div class="form-row">
          <div class="form-group">
            <label for="address_line">Residential Address <span>*</span></label>
            <textarea id="address_line" name="address_line" rows="3" required
                      placeholder="House number, street, village/town"><?= htmlspecialchars($addr['address_line'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="country_id">Country <span>*</span></label>
            <select id="country_id" name="country_id" required>
              <option value="">Select Country</option>
              <?php mysqli_data_seek($countries, 0); while ($c = mysqli_fetch_assoc($countries)): ?>
                <option value="<?= (int)$c['country_id'] ?>" 
                        <?= ($addr['country_id'] == $c['country_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['country_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <!-- Dynamic State/LGA will be inserted here by JS -->
        <div id="dynamic-address-fields">
          <?php if ($address_record && $addr['country_id'] == NIGERIA_COUNTRY_ID): ?>
            <div class="form-row">
              <div class="form-group">
                <label>State <span>*</span></label>
                <select name="state_id" required>
                  <option value="">Select State</option>
                  <?php mysqli_data_seek($nigeria_states, 0); while ($s = mysqli_fetch_assoc($nigeria_states)): ?>
                    <option value="<?= $s['state_id'] ?>" 
                            <?= ($addr['state_id'] == $s['state_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($s['state_name']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>LGA <span>*</span></label>
                <select name="lga_id" required>
                  <option value="">Select LGA</option>
                  <?php 
                  if ($addr['state_id']) {
                    $lgas = mysqli_query($conn, "SELECT lga_id, state_lga FROM static_states_lga WHERE state_id = {$addr['state_id']} ORDER BY state_lga");
                    while ($l = mysqli_fetch_assoc($lgas)): ?>
                      <option value="<?= $l['lga_id'] ?>" 
                              <?= ($addr['lga_id'] == $l['lga_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($l['state_lga']) ?>
                      </option>
                    <?php endwhile;
                  }
                  ?>
                </select>
              </div>
            </div>
          <?php elseif ($address_record): ?>
            <div class="form-row">
              <div class="form-group">
                <label>State / Province <span>*</span></label>
                <input type="text" name="state_text" 
                       value="<?= htmlspecialchars($addr['state_text'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>District / City (Optional)</label>
                <input type="text" name="lga_text" 
                       value="<?= htmlspecialchars($addr['lga_text'] ?? '') ?>">
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Council Ward (Representation) -->
        <div class="form-row">
          <div class="form-group">
            <label for="lga_id_rep">Local Government Area (LGA) <span>*</span></label>
            <select id="lga_id_rep" name="lga_id_rep" required>
              <option value="">Select LGA</option>
              <?php
              $lgas_rep = mysqli_query($conn, "SELECT lga_id, lga_name FROM static_users_lga ORDER BY lga_name");
              $ward_info = $user['ward_id'] ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT lga_id FROM static_users_council_wards WHERE ward_id = {$user['ward_id']}")) : null;
              while ($lga = mysqli_fetch_assoc($lgas_rep)): ?>
                <option value="<?= $lga['lga_id'] ?>" <?= ($ward_info['lga_id'] ?? 0) == $lga['lga_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lga['lga_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="ward_id">Council Ward <span>*</span></label>
            <select id="ward_id" name="ward_id" required>
              <option value="">Select Ward</option>
              <?php
              if ($ward_info) {
                $wards = mysqli_query($conn, "SELECT ward_id, ward_name FROM static_users_council_wards WHERE lga_id = {$ward_info['lga_id']} ORDER BY ward_name");
                while ($w = mysqli_fetch_assoc($wards)): ?>
                  <option value="<?= $w['ward_id'] ?>" <?= $user['ward_id'] == $w['ward_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($w['ward_name']) ?>
                  </option>
                <?php endwhile;
              }
              ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn">Save Changes</button>
      </form>
    </div>
  </main>
</div>

<script>
document.getElementById('country_id').addEventListener('change', function() {
  const countryId = this.value;
  const container = document.getElementById('dynamic-address-fields');
  
  if (countryId === '<?= NIGERIA_COUNTRY_ID ?>') {
    // Nigeria: show state/LGA dropdowns
    container.innerHTML = `
      <div class="form-row">
        <div class="form-group">
          <label>State <span>*</span></label>
          <select id="state_id" name="state_id" required>
            <option value="">Select State</option>
            <?php 
            // Output all Nigerian states
            $nigeria_states = mysqli_query($conn, "SELECT state_id, state_name FROM static_states WHERE country_id = " . NIGERIA_COUNTRY_ID . " ORDER BY state_name");
            while ($s = mysqli_fetch_assoc($nigeria_states)) {
              echo "<option value='{$s['state_id']}'>{$s['state_name']}</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label>LGA <span>*</span></label>
          <select name="lga_id" required>
            <option value="">Select State first</option>
          </select>
        </div>
      </div>
    `;
    
    // Re-select saved state (if any)
    setTimeout(() => {
      const stateSelect = document.getElementById('state_id');
      const savedState = '<?= (int)($addr['state_id'] ?? 0) ?>';
      if (stateSelect && savedState) {
        stateSelect.value = savedState;
        stateSelect.dispatchEvent(new Event('change'));
      }
    }, 100);
    
    // Add LGA loader
    document.getElementById('state_id').addEventListener('change', function() {
      const stateId = this.value;
      const lgaSelect = document.querySelector('[name="lga_id"]');
      if (!stateId) {
        lgaSelect.innerHTML = '<option value="">Select State first</option>';
        return;
      }
      
      fetch(`../assets/php/get-lgas.php?state_id=${encodeURIComponent(stateId)}`)
        .then(response => response.json())
        .then(lgas => {
          let opts = '<option value="">Select LGA</option>';
          lgas.forEach(l => opts += `<option value="${l.lga_id}">${l.state_lga}</option>`);
          lgaSelect.innerHTML = opts;
          
          // Re-select saved LGA
          const savedLga = '<?= (int)($addr['lga_id'] ?? 0) ?>';
          if (savedLga) {
            lgaSelect.value = savedLga;
          }
        })
        .catch(() => {
          lgaSelect.innerHTML = '<option value="">Error loading LGAs</option>';
        });
    });
    
  } else if (countryId) {
    // International: show text inputs
    container.innerHTML = `
      <div class="form-row">
        <div class="form-group">
          <label>State / Province <span>*</span></label>
          <input type="text" name="state_text" 
                 value="<?= htmlspecialchars($addr['state_text'] ?? '') ?>" 
                 placeholder="e.g., California, Ontario" required>
        </div>
        <div class="form-group">
          <label>District / City (Optional)</label>
          <input type="text" name="lga_text" 
                 value="<?= htmlspecialchars($addr['lga_text'] ?? '') ?>" 
                 placeholder="e.g., Los Angeles, Toronto">
        </div>
      </div>
    `;
  } else {
    container.innerHTML = '';
  }
});

// Council ward logic (unchanged)
document.getElementById('lga_id_rep').addEventListener('change', function() {
  const lgaId = this.value;
  const wardSelect = document.getElementById('ward_id');
  if (!lgaId) {
    wardSelect.innerHTML = '<option value="">Select Ward</option>';
    return;
  }
  fetch(`../assets/php/get-wards.php?lga_id=${encodeURIComponent(lgaId)}`)
    .then(r => r.json())
    .then(wards => {
      let opts = '<option value="">Select Ward</option>';
      wards.forEach(w => opts += `<option value="${w.ward_id}">${w.ward_name}</option>`);
      wardSelect.innerHTML = opts;
      
      const savedWard = '<?= (int)($user['ward_id'] ?? 0) ?>';
      if (savedWard) wardSelect.value = savedWard;
    });
});

// Trigger on page load
document.addEventListener('DOMContentLoaded', () => {
  const country = document.getElementById('country_id');
  if (country.value) {
    country.dispatchEvent(new Event('change'));
  }
  
  const lgaRep = document.getElementById('lga_id_rep');
  if (lgaRep.value) {
    lgaRep.dispatchEvent(new Event('change'));
  }
});
</script>

<?php include '../includes/footer.php'; ?>