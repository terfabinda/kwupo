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
    <h2>Our Vision</h2>
    <p style="text-align:center">Our vision is To unite and empower the people of Kwande through the preservation of our shared heritage, promotion of cultural pride, and advancement of social and economic development for future generations.</p>
<h2>Our Mission Statement</h2>
    <p style="text-align:center">To foster unity, celebrate our Kwande heritage, and uphold the legacy of courage and resilience that defines our people by promoting education, cultural awareness, community development, and collective progress within Kwande and beyond.</p>    
<!-- <div class="vm-links">
      <a href="vision">Vision</a>
      <a href="mission">Mission</a>
    </div> -->
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

<!-- ===== 7.5. ROYAL FATHERS OF KWANDE ===== -->
<section class="section royal-fathers-section">
  <div class="container">
    <h2>Royal Fathers of Kwande</h2>
    <p class="section-subtitle" style="text-align: center; margin: 15px auto 40px; max-width: 700px; color: #555;">
      Custodians of tradition, wisdom, and unity for the Kwande and Ushongo people.
    </p>

    <div class="royal-fathers-grid">
      <!-- Left: Ter Ushongo -->
      <div class="royal-card">
        <div class="royal-image-container">
          <img src="assets/img/ter-ushongo.png" alt="HRH Ter Ushongo III" class="royal-image">
        </div>
        <h3 class="royal-name">
          HRH Chief Augustine Kwaghtse Kuma, <small>MNJVS, KSS, KSJI</small><br>
          <span class="royal-title">Ter Ushongo III</span>
        </h3>
        <p class="royal-designation">Chairman, Ushongo Traditional Council</p>
      </div>

      <!-- Center: Tor Kwande & Nomwhange U Tiv (larger + solid ring halo) -->
      <div class="royal-card center">
        <div class="royal-image-container">
          <img src="assets/img/tor-kwande.png" alt="HRH Tor Kwande & Nomwhange U Tiv" class="royal-image center">
        </div>
        <h3 class="royal-name">
          HRH Ambrose Pinne Iyortyer<br>
          <span class="royal-title">Tor Kwande &amp;<br>Nomwhange U Tiv</span>
        </h3>
        <p class="royal-designation">Chairman, Kwande Intermediate Area Traditional Council and Grand Patron of KWUPO</p>
      </div>

      <!-- Right: Ter Kwande -->
      <div class="royal-card">
        <div class="royal-image-container">
          <img src="assets/img/ter-kwande.png" alt="HRH Ter Kwande" class="royal-image">
        </div>
        <h3 class="royal-name">
          HRH Chief Engr Timothy Tavershima Ahile<br>
          <span class="royal-title">Ter Kwande</span>
        </h3>
        <p class="royal-designation">Chairman, Kwande Traditional Council</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== 8. EXECUTIVE COMMITTEE (12 placeholders) ===== -->
<section class="section bg-light">
  <div class="container">
    <h2>Executive Committee</h2>
    <!-- Center: Tor Kwande & Nomwhange U Tiv (larger + solid ring halo) -->
      <div style="margin:0 auto;" class="royal-card">
        <div class="royal-image-container">
          <img src="assets/img/aloko-simon-nachi.png" alt="President General KWUPO" class="royal-image center">
        </div>
        <h3 class="royal-name">
          Aloko Simon Nachi<br>
          <span class="royal-title">President General</span>
        </h3>
        
      </div>
      <?php
// Fetch EXCO members (adjust query to match your DB structure)
$exco_query = "
    SELECT 
        CONCAT(firstname, ' ', middlename, ' ', surname) AS exco_full_name,
        designation AS exco_designation,
        profile_image AS exco_image
    FROM users 
    WHERE role_id = 2 AND is_active = 1
    ORDER BY exco_order ASC  -- Add a sort_order field if needed
    LIMIT 12
";

//$exco_result = mysqli_query($conn, $exco_query);
$exco_members = [];//mysqli_fetch_all($exco_result, MYSQLI_ASSOC);

// Fallback data (if DB not ready yet)
if (empty($exco_members)) {
    $exco_members = [
        ['exco_full_name' => 'Boniface Upah', 'exco_designation' => 'Vice President', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'Agbul Aondolumun Philip', 'exco_designation' => 'Secretary General', 'exco_image' => 'agbul-aondolumun.png'],
        ['exco_full_name' => 'Viashima Akombor Aende', 'exco_designation' => 'Asst. Secretary', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'QS Godwin Alumuku', 'exco_designation' => 'Treasurer', 'exco_image' => 'godwin-alumuku.png'],
        ['exco_full_name' => 'Michael Sekpe', 'exco_designation' => 'Financial Secretary General', 'exco_image' => 'comrade-sekpe.png'],
        ['exco_full_name' => 'Joseph Nyitse', 'exco_designation' => 'Welfare Officer', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'Terfa Ayoosu', 'exco_designation' => 'Secretary General', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'Kid Judge Jack Iorember Tamen', 'exco_designation' => 'Legal Adviser', 'exco_image' => 'kid-judge-iorember.png'],
        ['exco_full_name' => 'Terzungwe Uzan', 'exco_designation' => 'Publicity Secretary', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'Mrs. Ngeren Aondo Ugese', 'exco_designation' => 'Women Leader', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'Mongol Tavershima Iorkase', 'exco_designation' => 'Youth Leader', 'exco_image' => 'default.jpg'],
        ['exco_full_name' => 'Prof. Terkula Tarnande', 'exco_designation' => 'Ex Officio', 'exco_image' => 'comrade-festus-terkula.png'],
    ];
    // Generate 12 placeholders if needed
    while (count($exco_members) < 12) {
        $exco_members[] = [
            'exco_full_name' => 'Name Pending',
            'exco_designation' => 'Position',
            'exco_image' => 'default.jpg'
        ];
    }
}
?>
     <div class="exco-grid">
  <?php foreach ($exco_members as $member): ?>
    <div class="exco-card">
      <div class="exco-image-container">
        <?php
        // Default fallback
        $imgPath = 'assets/img/profile-placeholder.png';

        if (!empty($member['exco_image'])) {
            $safeFilename = basename($member['exco_image']);
            
            // ✅ Build relative path to image in assets/img/
            $relativePath = "\assets\img\\" . $safeFilename;
            $orelativePath = "assets\img\\";
            
            // ✅ Convert to absolute path *relative to current script*
            $absolutePath = __DIR__ .  $relativePath; // assuming this file is in root (e.g., index.php)

            // 🔍 Debug tip: UNCOMMENT to test paths
            // echo("Checking: " . $absolutePath . " → " . (file_exists($absolutePath) ? 'FOUND' : 'MISSING'));

            if (file_exists($absolutePath)) {
                $imgPath = $orelativePath . $safeFilename;
            }
        }
        ?>
        <img 
          src="<?= htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8') ?>" 
          alt="<?= htmlspecialchars($member['exco_full_name'] ?? 'Executive Member', ENT_QUOTES, 'UTF-8') ?>" 
          class="exco-image"
          onerror="this.onerror=null; this.src='assets/img/profile-placeholder.png'">
      </div>
      <h3 class="exco-name"><?= htmlspecialchars($member['exco_full_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></h3>
      <p class="exco-designation"><?= htmlspecialchars($member['exco_designation'] ?? '—', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  <?php endforeach; ?>
</div>
    <a href="execcomittee" class="read-more">View more →</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>