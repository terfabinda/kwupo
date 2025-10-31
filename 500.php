<?php http_response_code(500); ?>
<?php include 'includes/header.php'; ?>

<section class="section">
  <div class="container text-center">
    <div style="font-size: 6rem; color: var(--accent); margin-bottom: 20px;">500</div>
    <h1>Server Error</h1>
    <p style="font-size: 1.2rem; max-width: 600px; margin: 20px auto;">
      Something went wrong on our end. Our team has been notified and is working to fix it.
    </p>
    <div style="margin: 30px 0;">
      <a href="/" class="btn">Return to Homepage</a>
      <a href="/contact" class="btn btn-outline" style="margin-left: 15px;">Report Issue</a>
    </div>
    <p style="color: #666; margin-top: 30px;">
      Error Code: 500 – Internal Server Error
    </p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>