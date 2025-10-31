<?php http_response_code(404); ?>
<?php include 'includes/header.php'; ?>

<section class="section">
  <div class="container text-center">
    <div style="font-size: 6rem; color: var(--accent); margin-bottom: 20px;">404</div>
    <h1>Page Not Found</h1>
    <p style="font-size: 1.2rem; max-width: 600px; margin: 20px auto;">
      The page you're looking for doesn't exist or has been moved.
    </p>
    <div style="margin: 30px 0;">
      <a href="/" class="btn">Return to Homepage</a>
      <a href="/contact" class="btn btn-outline" style="margin-left: 15px;">Contact Us</a>
    </div>
    <p style="color: #666; margin-top: 30px;">
      Error Code: 404 – Not Found
    </p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>