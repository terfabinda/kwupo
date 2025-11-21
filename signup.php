<?php
// Enable errors temporarily for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load session, DB, AND helpers
require 'includes/init.php';
require 'includes/helpers.php';
?>

<?php include 'includes/header.php'; ?>

<section class="hero-section hero-compact">
  <div class="container">
    <div class="hero-content">
      <div class="hero-col hero-col-text">
        <h1>Become a Member</h1>
        <p class="tagline">Join the Kwande and Ushongo family today.</p>
      </div>
      <div class="hero-col hero-col-image">
        <img src="<?= base_url('assets/img/signup-hero.jpg') ?>" alt="Join KWUPO">
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div id="signup-container">
      <h2>Create Your Account</h2>

      <div id="alert-box" style="display:none;" class="alert"></div>

      <form id="signup-form" class="signup-form">
        <div class="form-row">
          <div class="form-group">
  <label for="title_prefix_id">Title (Optional)</label>
  <select id="title_prefix_id" name="title_prefix_id">
    <option value="">Select Title</option>
    <?php
    $titles = mysqli_query($conn, "SELECT title_id, title_prefix FROM static_user_titles ORDER BY display_order");
    while ($title = mysqli_fetch_assoc($titles)) {
      echo "<option value='{$title['title_id']}'>{$title['title_prefix']}</option>";
    }
    ?>
  </select>
</div>
          <div class="form-group">
            <label for="firstname">First Name <span>*</span></label>
            <input type="text" id="firstname" name="firstname" required>
          </div>
          <div class="form-group">
            <label for="middlename">Middle Name</label>
            <input type="text" id="middlename" name="middlename">
          </div>
          <div class="form-group">
            <label for="surname">Surname <span>*</span></label>
            <input type="text" id="surname" name="surname" required>
          </div>
          <div class="form-group">
    <label for="title_suffix">Suffix (After Name, Optional)</label>
    <input type="text" id="title_suffix" name="title_suffix" 
           placeholder="e.g., PhD, MON, OON, FMCPath">
  </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="lga_id">Local Government Area (LGA) <span>*</span></label>
            <select id="lga_id" name="lga_id" required>
              <option value="">Select LGA</option>
              <?php
              // Use $conn from init.php
              $lgas = mysqli_query($conn, "SELECT lga_id, lga_name FROM static_users_lga ORDER BY lga_name");
              while ($lga = mysqli_fetch_assoc($lgas)) {
                echo "<option value='{$lga['lga_id']}'>{$lga['lga_name']}</option>";
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label for="ward_id">Council Ward <span>*</span></label>
            <select id="ward_id" name="ward_id" required disabled>
              <option value="">Select LGA first</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="phone">Phone Number <span>*</span></label>
            <input type="tel" id="phone" name="phone" placeholder="08012345678" required>
          </div>
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Password <span>*</span></label>
            <input type="password" id="password" name="password" required minlength="6">
          </div>
        </div>

        <div class="form-checkboxes">
  <label>
    <input type="checkbox" name="terms" required>
    <span aria-hidden="true"></span>
    I agree to the <a href="<?= base_url('terms') ?>" target="_blank">Terms and Conditions</a>
    <span aria-hidden="true">*</span>
  </label>
  <label>
    <input type="checkbox" name="privacy" required>
    <span aria-hidden="true"></span>
    I agree to the <a href="<?= base_url('privacy-policy') ?>" target="_blank">Privacy Policy</a>
    <span aria-hidden="true">*</span>
  </label>
</div>

        <button type="submit" class="btn">Register Now</button>
      </form>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Ward dropdown population
document.getElementById('lga_id').addEventListener('change', function() {
  const lgaId = this.value;
  const wardSelect = document.getElementById('ward_id');
  
  if (!lgaId) {
    wardSelect.innerHTML = '<option value="">Select LGA first</option>';
    wardSelect.disabled = true;
    return;
  }

  // Use PHP to inject the correct base URL
  const baseUrl = '<?= base_url("") ?>';
  
  fetch(`${baseUrl}assets/php/get-wards?lga_id=${lgaId}`)
    .then(response => response.json())
    .then(wards => {
      let options = '<option value="">Select Ward</option>';
      wards.forEach(ward => {
        options += `<option value="${ward.ward_id}">${ward.ward_name}</option>`;
      });
      wardSelect.innerHTML = options;
      wardSelect.disabled = false;
    })
    .catch(() => {
      wardSelect.innerHTML = '<option value="">Error loading wards</option>';
      wardSelect.disabled = true;
    });
});

// Form submission
document.getElementById('signup-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const alertBox = document.getElementById('alert-box');
  
  // ✅ Use PHP to inject the correct base URL (reliable)
  const baseUrl = '<?= base_url("") ?>';
  
  try {
    const response = await fetch(baseUrl + 'assets/php/signup-handler', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('signup-container').innerHTML = `
        <div class="alert success">
          <h3>Registration Successful!</h3>
          <p>Thank you for joining KWUPO. Your application is under review.</p>
          <a href="<?= base_url() ?>" class="btn">Back to Home</a>
        </div>
      `;
    } else {
      alertBox.className = 'alert error';
      alertBox.style.display = 'block';
      alertBox.innerHTML = '<ul><li>' + result.errors.join('</li><li>') + '</li></ul>';
      alertBox.scrollIntoView({ behavior: 'smooth' });
    }
  } catch (err) {
    alertBox.className = 'alert error';
    alertBox.style.display = 'block';
    alertBox.textContent = 'Network error. Please try again.';
  }
});
</script>