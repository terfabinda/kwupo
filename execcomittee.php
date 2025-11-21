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
        <h1>Executive Committee</h1>
        <p class="tagline">About KWUPO Leadership</p>
      </div>
      <div class="hero-col hero-col-image">
        <img src="<?= base_url('assets/img/signin-hero.jpg') ?>" alt="KWUPO Member Login">
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="exec-intro">
      <p class="lead">
        The Executive Committee of the Kwande United Peoples Organization (KWUPO) is a dynamic assembly of visionary leaders, seasoned professionals, and passionate advocates—united by a shared commitment to the progress, unity, and cultural preservation of the Kwande and Ushongo people.
      </p>
      <p>
        Inaugurated on <strong>15th March 2025</strong>, this pioneering leadership team embodies KWUPO’s core values: integrity, inclusivity, and innovation. With diverse expertise spanning engineering, education, public administration, finance, healthcare, and traditional governance, the Committee brings a holistic and future-focused approach to community development.
      </p>
      <p>
        Guided by a progressive mandate, the EXCO champions initiatives that bridge heritage and modernity—from digital transformation and youth empowerment to sustainable agriculture and infrastructural advocacy. Their collaborative spirit, strategic foresight, and deep-rooted connection to Kwande identity ensure that every decision is made with tomorrow’s generation in mind.
      </p>
      <p class="highlight">
        <em>“Leadership is not about authority—it is about service, vision, and the courage to build bridges where others see divides.”</em>
      </p>
      <p>
        Meet the stewards of our collective future—men and women whose dedication fuels KWUPO’s mission to uplift, unite, and empower.
      </p>
      <p>&nbsp;</p>
    </div>

    <div class="container">
    <!-- <h2>Executive Committee</h2> -->
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