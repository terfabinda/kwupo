<?php http_response_code(403); ?>
<?php include 'includes/header.php'; ?>

<section class="section">
  <div class="container text-center">
    <div style="font-size: 6rem; color: var(--accent); margin-bottom: 20px;">403</div>
    <h1>Access Denied</h1>
    <p style="font-size: 1.2rem; max-width: 600px; margin: 20px auto;">
      You don't have permission to access this page.
    </p>
    <div style="margin: 30px 0;">
      <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="/signin" class="btn">Sign In</a>
      <?php endif; ?>
      <a href="/" class="btn btn-outline" style="margin-left: 15px;">Return Home</a>
    </div>
    <p style="color: #666; margin-top: 30px;">
      Error Code: 403 – Forbidden
    </p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>