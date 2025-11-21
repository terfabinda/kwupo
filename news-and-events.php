<?php
// Initialize session + helpers
require 'includes/init.php';
require 'includes/helpers.php';
?>

<?php include 'includes/header.php'; ?>

<section class="hero-section hero-compact">
  <div class="container">
    <div class="hero-content">
      <div class="hero-col hero-col-text">
        <h1>News and Events</h1>
        <p class="tagline">Access News and Events about KWUPO</p>
      </div>
      <div class="hero-col hero-col-image">
        <img src="<?= base_url('assets/img/signin-hero.jpg') ?>" alt="KWUPO Member Login">
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div id="news-events-container">
      <h2>News and Events Coming Soon</h2>
      <p>Our news and events section is currently under development. Please check back later to stay updated on the latest happenings within KWUPO.</p>
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