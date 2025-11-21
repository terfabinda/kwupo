<?php include 'includes/header.php'; ?>

<section class="hero-section hero-compact">
  <div class="container">
    <div class="hero-content">
      <div class="hero-col hero-col-text">
        <h1>Forgot Password</h1>
        <p class="tagline">Reset your KWUPO account password</p>
      </div>
      <div class="hero-col hero-col-image">
        <img src="assets/img/forgot-password-hero.jpg" alt="Reset Password">
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div id="forgot-container">
      <h2>Reset Your Password</h2>
      <p>Enter your phone number or email address, and we’ll send you a link to reset your password.</p>

      <div id="alert-box" style="display:none;" class="alert"></div>

      <form id="forgot-form" class="signin-form">
        <div class="form-group">
          <label for="identifier">Phone or Email <span>*</span></label>
          <input type="text" 
                 id="identifier" 
                 name="identifier" 
                 placeholder="08012345678 or email@example.com"
                 required>
        </div>

        <button type="submit" class="btn">Send Reset Link</button>
      </form>

      <div class="form-footer">
        <p><a href="signin">← Back to Sign In</a></p>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('forgot-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const alertBox = document.getElementById('alert-box');
  const submitBtn = this.querySelector('button[type="submit"]');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Sending...';

  try {
    const response = await fetch('assets/php/forgot-password-handler', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    // Always show success (security best practice)
    document.getElementById('forgot-container').innerHTML = `
      <div class="alert success">
        <h3>Check Your Messages</h3>
        <p>If an account exists with that phone or email, you’ll receive a password reset link shortly.</p>
        <a href="signin" class="btn">Back to Sign In</a>
      </div>
    `;
  } catch (err) {
    alertBox.className = 'alert error';
    alertBox.style.display = 'block';
    alertBox.textContent = 'Network error. Please try again.';
    alertBox.scrollIntoView({ behavior: 'smooth' });
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Send Reset Link';
  }
});
</script>