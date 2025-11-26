<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
              // ✅ Sorted alphabetically by title_prefix
              $titles = mysqli_query($conn, "SELECT title_id, title_prefix FROM static_user_titles ORDER BY title_prefix ASC");
              while ($title = mysqli_fetch_assoc($titles)) {
                echo "<option value='" . htmlspecialchars($title['title_id']) . "'>" 
                   . htmlspecialchars($title['title_prefix']) . "</option>";
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
              $lgas = mysqli_query($conn, "SELECT lga_id, lga_name FROM static_users_lga ORDER BY lga_name");
              while ($lga = mysqli_fetch_assoc($lgas)) {
                echo "<option value='" . htmlspecialchars($lga['lga_id']) . "'>" 
                   . htmlspecialchars($lga['lga_name']) . "</option>";
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

        <!-- ✅ PASSWORD SECTION — UPGRADED -->
        <div class="form-group password-group">
          <label for="password">Password <span>*</span></label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" 
                   required minlength="8" 
                   placeholder="At least 8 characters">
            <button type="button" class="password-toggle" 
                    aria-label="Show password" 
                    data-target="password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <p class="help-text">Use 8+ characters with a mix of letters, numbers, and symbols.</p>
        </div>

        <div class="form-group password-group">
          <label for="password_confirm">Confirm Password <span>*</span></label>
          <div class="password-wrapper">
            <input type="password" id="password_confirm" name="password_confirm" 
                   required minlength="8">
            <button type="button" class="password-toggle" 
                    aria-label="Show password" 
                    data-target="password_confirm">
              <i class="fas fa-eye"></i>
            </button>
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

<!-- ✅ PASSWORD TOGGLE + VALIDATION SCRIPT -->
<script>
// Ward dropdown
document.getElementById('lga_id').addEventListener('change', function() {
  const lgaId = this.value;
  const wardSelect = document.getElementById('ward_id');
  
  if (!lgaId) {
    wardSelect.innerHTML = '<option value="">Select LGA first</option>';
    wardSelect.disabled = true;
    return;
  }

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
    });
});

// ✅ PASSWORD TOGGLE
document.querySelectorAll('.password-toggle').forEach(button => {
  button.addEventListener('click', function() {
    const inputId = this.dataset.target;
    const input = document.getElementById(inputId);
    const icon = this.querySelector('i');
    
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
      this.setAttribute('aria-label', 'Hide password');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
      this.setAttribute('aria-label', 'Show password');
    }
  });
});

// ✅ PASSWORD MATCH VALIDATION
document.getElementById('signup-form').addEventListener('submit', function(e) {
  const pass1 = document.getElementById('password').value;
  const pass2 = document.getElementById('password_confirm').value;
  const alertBox = document.getElementById('alert-box');
  
  if (pass1 !== pass2) {
    e.preventDefault();
    alertBox.className = 'alert error';
    alertBox.innerHTML = '<ul><li>Passwords do not match.</li></ul>';
    alertBox.style.display = 'block';
    alertBox.scrollIntoView({ behavior: 'smooth' });
    return false;
  }
});

// Form submission (AJAX)
document.getElementById('signup-form').addEventListener('submit', async function(e) {
  // Skip if passwords don’t match (handled above)
  if (e.defaultPrevented) return;

  e.preventDefault();
  const formData = new FormData(this);
  const alertBox = document.getElementById('alert-box');
  const submitBtn = this.querySelector('button[type="submit"]');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Registering...';

  try {
    const response = await fetch('<?= base_url("assets/php/signup-handler") ?>', {
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
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Register Now';
  }
});
</script>

<!-- ✅ CSS FOR PASSWORD TOGGLE -->
<style>
.password-group {
  position: relative;
}

.password-wrapper {
  position: relative;
}

.password-wrapper input {
  padding-right: 50px; /* space for icon */
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  font-size: 1.1rem;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: color 0.2s;
}

.password-toggle:hover {
  color: var(--accent);
  background: #f5f5f5;
}

.password-toggle:focus {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.form-group .help-text {
  font-size: 0.85rem;
  color: #666;
  margin-top: 5px;
}
</style>