<?php
// MUST include helpers.php to use base_url()
require_once __DIR__ . '/helpers.php';
?>

  </main>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-content">
        <div>
          <img src="<?= base_url('assets/img/logo-60.png') ?>" alt="KWUPO" class="footer-logo">
        </div>
        <div class="footer-links">
          <h3>Quick Links</h3>
          <ul>
            <li><a href="<?= base_url('about-us') ?>">About KWUPO</a></li>
            <li><a href="<?= base_url('vision') ?>">Vision</a></li>
            <li><a href="<?= base_url('mission') ?>">Mission</a></li>
            <li><a href="<?= base_url('news-and-events') ?>">News & Events</a></li>
            <li><a href="<?= base_url('gallery') ?>">Gallery</a></li>
            <li><a href="<?= base_url('execcomittee') ?>">Executive Committee</a></li>
            <li><a href="<?= base_url('contact') ?>">Contact</a></li>
          </ul>
        </div>
        <div class="footer-social">
          <h3>Connect</h3>
          <div class="social-icons">
            <a href="#" aria-label="Facebook">f</a>
            <a href="#" aria-label="Twitter">x</a>
          </div>
        </div>
      </div>
      <div class="copyright">
        &copy; 2025–2030 Kwande United People's Organization (KWUPO).<br>
        Designed and Hosted by Eternex Systems Ltd
      </div>
    </div>
  </footer>

</body>
</html>