<?php
// Initialize session + helpers
require 'includes/init.php';
require 'includes/helpers.php';
?>

<?php include 'includes/header.php'; ?>

<section class="hero-section">
  <div class="container">
    <div class="hero-content">
      <div class="hero-col hero-col-text">
        <h1>Sign In</h1>
        <p class="tagline">Access your KWUPO member dashboard</p>
      </div>
      <div class="hero-col hero-col-image">
        <img src="<?= base_url('assets/img/signin-hero.jpg') ?>" alt="KWUPO Member Login">
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div id="signin-container">
      <h2>Welcome Back</h2>

      <div id="alert-box" style="display:none;" class="alert"></div>

      <form id="signin-form" class="signin-form">
        <div class="form-group">
          <label for="identifier">Phone or Email <span>*</span></label>
          <input type="text" 
                 id="identifier" 
                 name="identifier" 
                 placeholder="08012345678 or email@example.com"
                 required>
        </div>

        <div class="form-group">
          <label for="password">Password <span>*</span></label>
          <input type="password" id="password" name="password" required>
        </div>

        <div class="form-checkboxes">
          <label>
            <input type="checkbox" name="remember"> Remember me
          </label>
        </div>

        <button type="submit" class="btn">Sign In</button>
      </form>

      <div class="form-footer">
        <p><a href="<?= base_url('forgot-password') ?>">Forgot your password?</a></p>
        <p>Don't have an account? <a href="<?= base_url('signup') ?>">Join KWUPO today</a></p>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('signin-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const alertBox = document.getElementById('alert-box');
  const submitBtn = this.querySelector('button[type="submit"]');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Signing In...';

  // ✅ Use PHP-generated base URL (reliable)
  const baseUrl = '<?= base_url("") ?>';

  try {
    const response = await fetch(baseUrl + 'assets/php/signin-handler', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      setTimeout(() => {
        window.location.href = baseUrl + result.redirect;
      }, 800);
    } else {
      alertBox.className = 'alert error';
      alertBox.style.display = 'block';
      alertBox.textContent = result.errors[0];
      alertBox.scrollIntoView({ behavior: 'smooth' });
    }
  } catch (err) {
    alertBox.className = 'alert error';
    alertBox.style.display = 'block';
    alertBox.textContent = 'Network error. Please try again.';
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Sign In';
  }
});
</script>