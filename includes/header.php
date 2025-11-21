<?php
// Secure session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolve base path dynamically (for localhost subdirs like /kwupo)
$base = '';
if (isset($_SERVER['HTTP_HOST'])) {
    if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
        // Running locally – detect subdirectory
        $scriptPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH);
        $pathParts = explode('/', trim($scriptPath, '/'));
        if (count($pathParts) > 1 && !empty($pathParts[0])) {
            $base = '/' . $pathParts[0];
        }
    }
    // Production: $base remains empty (root domain: https://kwupo.org.ng)
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>KWUPO – Kwande United Peoples Organization</title>
  <meta name="description" content="Official website of KWUPO: a socio-cultural organization of Kwande and Ushongo people in Benue State, Nigeria.">
  <link rel="canonical" href="https://kwupo.org.ng<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

  <!-- Open Graph -->
  <meta property="og:title" content="KWUPO – Kwande United Peoples Organization">
  <meta property="og:description" content="Uniting the Kwande and Ushongo people in heritage, progress, and purpose.">
  <meta property="og:url" content="https://kwupo.org.ng<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="https://kwupo.org.ng/assets/img/logo-120.png">

  <!-- Google Fonts -->
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vollkorn:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vollkorn:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap">
  </noscript>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
  <link rel="icon" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/assets/img/favicon.png">
</head>
<body>

<!-- FIXED MENU -->
<header class="fixed-menu">
  <div class="container menu-container">
    <!-- Logo + Org Name -->
    <div class="logo-group">
      <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/">
        <img src="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/assets/img/logo-60.png" alt="KWUPO Logo" class="logo-small">
      </a>
      <span class="org-name">KWUPO</span>
    </div>

    <!-- Public Navigation -->
    <nav class="main-nav">
      <ul>
        <li><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/">Home</a></li>
        <li><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/about-us">About Us</a></li>
        <li><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/news-and-events">Events</a></li>
        <li><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/gallery">Gallery</a></li>
        <li><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/execcomittee">Our Team</a></li>
        <li><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/contact">Contact Us</a></li>
      </ul>
    </nav>

    <!-- Auth / Profile Menu -->
    <div class="profile-menu">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <?php
        // Determine profile image path safely
        $profileImagePath = $base . '/assets/img/profile-placeholder.png'; // default
        if (!empty($_SESSION['profile_image'])) {
            // Sanitize filename to prevent path traversal
            $safeFilename = basename($_SESSION['profile_image']);
            $fullUploadPath = $_SERVER['DOCUMENT_ROOT'] . $base . '/uploads/profiles/' . $safeFilename;
            
            // Only use if file actually exists
            if (file_exists($fullUploadPath)) {
                $profileImagePath = $base . '/uploads/profiles/' . $safeFilename;
            }
        }
        ?>
        <div class="profile-dropdown">
          <img src="<?= htmlspecialchars($profileImagePath, ENT_QUOTES, 'UTF-8') ?>" 
               alt="Profile" 
               class="profile-pic"
               onerror="this.src='<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/assets/img/profile-placeholder.png'">
          <div class="dropdown-content">
            <?php if (!empty($_SESSION['role_id'])): ?>
              <?php if ($_SESSION['role_id'] == 1): ?>
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/admin/index.php">Admin Dashboard</a>
              <?php elseif ($_SESSION['role_id'] == 2): ?>
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/treasurer/index.php">Treasurer Dashboard</a>
              <?php else: ?>
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/member/index.php">Member Dashboard</a>
              <?php endif; ?>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/member/settings.php">Profile Settings</a>
            <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/signout.php">Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/signin.php" class="menu-link">Sign In</a>
        <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/signup.php" class="btn btn-sm">Join Now</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="main-wrapper">