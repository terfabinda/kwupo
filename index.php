<?php include 'includes/header.php'; ?>

<!-- ===== 2. HERO BACKGROUND CONTAINER ===== -->
<section class="hero-section">
  <!-- Transparent background image container -->
  <div class="hero-bg-overlay"></div>

  <div class="container">
    <div class="hero-content">
      <!-- COLUMN A (Left) -->
      <div class="hero-col hero-col-text">
        <img src="assets/img/logo-120.png" alt="KWUPO Logo" class="logo-large">
        <h1>Kwande United Peoples Organization</h1>
        <p class="tagline">A Socio Economic and Cultural Organization of Kwande/Ushongo People</p>
        <div class="hero-buttons">
          <a href="signup" class="btn">JOIN NOW</a>
          <a href="donate" class="btn btn-outline">Donate</a>
        </div>
      </div>

      <!-- COLUMN B (Right) -->
      <div class="hero-col hero-col-image">
        <img src="assets/img/hero-image.png" alt="KWUPO Community" style="max-width: 130%; height: auto; margin-left: 80px;">
      </div>
    </div>
  </div>
</section>

<!-- ===== 3. ABOUT KWUPO ===== -->
<section class="section">
  <div class="container">
    <h2>About KWUPO</h2>
   <p> The <strong>Kwande United Peoples Organization (KWUPO)</strong> is a dynamic socio-cultural organization founded to champion the unity, sustainable development, and collective empowerment of the Kwande and Ushongo people of Benue State, Nigeria. Rooted in our rich Tiv heritage and guided by principles of integrity, inclusivity, and service, KWUPO serves as a unifying voice for our communities at home and in the diaspora.</p>
<p>&nbsp;</p>
<p>We are committed to preserving our cultural identity through language, tradition, and intergenerational dialogue, while simultaneously driving progress in education, agriculture, healthcare, and civic engagement. By fostering collaboration among stakeholders, advocating for equitable development, and mobilizing our people toward common goals, KWUPO strives to build a resilient, self-reliant, and prosperous future for all Kwande and Ushongo sons and daughters.</p>
<p>&nbsp;</p>
<p>More than an association, KWUPO is a movement — a covenant of solidarity that honors our past, empowers our present, and secures our legacy for generations to come. </p>
<p>&nbsp;</p>
    <a href="about-us" class="read-more">Read more →</a>
  </div>
</section>

<!-- ===== 4. STATS ===== -->
<section class="section bg-light">
  <div class="container">
    <h2>Stats</h2>
    <div class="stats-grid">
      <div class="stat-item"><span>500+</span> Members</div>
      <div class="stat-item"><span>12</span> Projects</div>
      <div class="stat-item"><span>3</span> LGAs Served</div>
    </div>
    <a href="stats" class="read-more">View more →</a>
  </div>
</section>

<!-- ===== 5. VISION / MISSION ===== -->
<section class="section">
  <div class="container">
    <h2>Vision / Mission</h2>
    <p>Our vision is a united, prosperous, and culturally vibrant Kwande/Ushongo nation. Our mission is to empower our people through unity, education, and sustainable development.</p>
    <div class="vm-links">
      <a href="vision">Vision</a>
      <a href="mission">Mission</a>
    </div>
  </div>
</section>

<!-- ===== 6. LATEST NEWS AND EVENTS ===== -->
<section class="section bg-light">
  <div class="container">
    <h2>Latest News and Events</h2>
    <p>Stay updated with community gatherings, development initiatives, and official announcements.</p>
    <a href="news-and-events" class="read-more">View more →</a>
  </div>
</section>

<!-- ===== 7. IMAGE GALLERY (9 images) ===== -->
<section class="section">
  <div class="container">
    <h2>Image Gallery</h2>
    <div class="gallery-grid">
      <?php for ($i = 1; $i <= 9; $i++): ?>
        <img src="assets/img/gallery/<?= $i ?>.jpg" alt="Gallery image <?= $i ?>">
      <?php endfor; ?>
    </div>
    <a href="gallery" class="read-more">View more →</a>
  </div>
</section>

<!-- ===== 8. EXECUTIVE COMMITTEE (12 placeholders) ===== -->
<section class="section bg-light">
  <div class="container">
    <h2>Executive Committee</h2>
    <div class="exco-grid">
      <?php for ($i = 1; $i <= 12; $i++): ?>
        <div class="exco-placeholder"></div>
      <?php endfor; ?>
    </div>
    <a href="execcomittee" class="read-more">View more →</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>