<?php
// Secure session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>KWUPO – Kwande United Peoples Organization</title>
  <meta name="description" content="Official website of KWUPO: a socio-cultural organization of Kwande and Ushongo people in Benue State, Nigeria.">
  <link rel="canonical" href="https://kwupo.org.ng<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

  <!-- Open Graph -->
  <meta property="og:title" content="KWUPO – Kwande United Peoples Organization">
  <meta property="og:description" content="Uniting the Kwande and Ushongo people in heritage, progress, and purpose.">
  <meta property="og:url" content="https://kwupo.org.ng<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="https://kwupo.org.ng/assets/img/logo-120.png">

  <!-- Google Fonts -->
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vollkorn:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vollkorn:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap">
  </noscript>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&display=swap" rel="stylesheet">
<!-- Font Awesome 6 (Free) -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" 
      integrity="sha512-yQ6m+3kZcL8Z6N7Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3Xq3X==" 
      crossorigin="anonymous" referrerpolicy="no-referrer"> -->
   <!-- Font Awesome 6 Free -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">   
  <!-- DYNAMIC ASSET PATHS -->
  <?php
  // Detect if running in subdirectory (e.g., /kwupo on localhost)
  $subdir = '';
  if (isset($_SERVER['SCRIPT_NAME'])) {
      $path = parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH);
      $parts = explode('/', trim($path, '/'));
      if (count($parts) > 1 && $parts[0] !== '') {
          $subdir = '/' . $parts[0];
      }
  }
  $base = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) 
      ? $subdir 
      : '';
  ?>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
  <link rel="icon" href="<?= $base ?>/assets/img/favicon.png">
</head>
<body>

<!-- FIXED TRANSPARENT MENU -->
<header class="fixed-menu">
  <div class="container menu-container">
    <!-- Logo + Org Name -->
    <div class="logo-group">
      <a href="<?= $base ?>/">
        <img src="<?= $base ?>/assets/img/logo-60.png" alt="KWUPO Logo" class="logo-small">
      </a>
      <span class="org-name">KWUPO</span>
    </div>

    <!-- Public Navigation -->
    <nav class="main-nav">
      <ul>
        <li><a href="<?= $base ?>/">Home</a></li>
        <li><a href="<?= $base ?>/about-us">About Us</a></li>
        <li><a href="<?= $base ?>/news-and-events">Events</a></li>
        <li><a href="<?= $base ?>/gallery">Gallery</a></li>
        <li><a href="<?= $base ?>/execcomittee">Our Team</a></li>
        <li><a href="<?= $base ?>/contact">Contact Us</a></li>
      </ul>
    </nav>

    <!-- Auth / Profile -->
    <div class="profile-menu">
      <?php if (isset($_SESSION['user_id'])): ?>
        <div class="profile-dropdown">
          <img src="<?= $base ?>/assets/img/profile-placeholder.png" alt="Profile" class="profile-pic">
          <div class="dropdown-content">
            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
              <a href="<?= $base ?>/admin/index.php">Admin Dashboard</a>
            <?php elseif (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2): ?>
              <a href="<?= $base ?>/treasurer/index.php">Treasurer Dashboard</a>
            <?php else: ?>
              <a href="<?= $base ?>/member/index.php">Member Dashboard</a>
            <?php endif; ?>
            <a href="<?= $base ?>/member/settings.php">Profile Settings</a>
            <a href="<?= $base ?>/signout.php">Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= $base ?>/signin.php" class="menu-link">Sign In</a>
        <a href="<?= $base ?>/signup.php" class="btn btn-sm">Join Now</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="main-wrapper">