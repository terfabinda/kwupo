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
        <h1>Contact Us</h1>
        <p class="tagline">Reach out to the KWUPO leadership for inquiries, partnerships, or support</p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-grid">
      <!-- Contact Info -->
      <div class="contact-info">
        <h2>Get in Touch</h2>
        
        <div class="contact-item">
          <i class="fas fa-map-marker-alt"></i>
          <div>
            <h3>Head Office</h3>
            <p>KWUPO Secretariat,<br> 
               Ushongo Town, Benue State,<br>
               Nigeria</p>
          </div>
        </div>

        <div class="contact-item">
          <i class="fas fa-phone"></i>
          <div>
            <h3>Phone</h3>
            <p><a href="tel:+2348031234567">+234 803 123 4567</a><br>
               <a href="tel:+2348109876543">+234 810 987 6543</a></p>
          </div>
        </div>

        <div class="contact-item">
          <i class="fas fa-envelope"></i>
          <div>
            <h3>Email</h3>
            <p><a href="mailto:info@kwupo.org.ng">info@kwupo.org.ng</a><br>
               <a href="mailto:secretariat@kwupo.org.ng">secretariat@kwupo.org.ng</a></p>
          </div>
        </div>

        <div class="contact-item">
          <i class="fas fa-clock"></i>
          <div>
            <h3>Office Hours</h3>
            <p>Monday - Friday: 8:00 AM - 4:00 PM<br>
               Saturday: 9:00 AM - 1:00 PM<br>
               Sunday: Closed</p>
          </div>
        </div>
      </div>

      <!-- Contact Form -->
      <div class="contact-form-container">
        <h2>Send a Message</h2>
        
        <div id="contact-alert" style="display:none;" class="alert"></div>

        <form id="contact-form" class="contact-form">
          <div class="form-row">
            <div class="form-group">
              <label for="name">Full Name <span>*</span></label>
              <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
              <label for="email">Email <span>*</span></label>
              <input type="email" id="email" name="email" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone">
            </div>
            
            <div class="form-group">
              <label for="subject">Subject <span>*</span></label>
              <select id="subject" name="subject" required>
                <option value="">Select...</option>
                <option value="membership">Membership Inquiry</option>
                <option value="donation">Donation & Sponsorship</option>
                <option value="event">Event Participation</option>
                <option value="complaint">Complaint/Feedback</option>
                <option value="partnership">Partnership Opportunity</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="message">Message <span>*</span></label>
            <textarea id="message" name="message" rows="5" required placeholder="Please provide details of your inquiry..."></textarea>
          </div>

          <div class="form-checkboxes">
            <label>
              <input type="checkbox" name="consent" required>
              <span aria-hidden="true">✓</span>
              I consent to KWUPO processing my personal data for the purpose of responding to this inquiry. 
              <a href="<?= base_url('privacy-policy') ?>" target="_blank">Privacy Policy</a>
            </label>
          </div>

          <button type="submit" class="btn">Send Message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('contact-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  const alertBox = document.getElementById('contact-alert');
  const submitBtn = this.querySelector('button[type="submit"]');
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Sending...';

  try {
    const response = await fetch('<?= base_url('assets/php/contact-handler.php') ?>', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Show success message
      alertBox.className = 'alert success';
      alertBox.textContent = result.message;
      alertBox.style.display = 'block';
      
      // Reset form
      this.reset();
      
      // Scroll to alert
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        alertBox.style.display = 'none';
      }, 5000);
    } else {
      alertBox.className = 'alert error';
      alertBox.innerHTML = `<ul>${result.errors.map(err => `<li>${err}</li>`).join('')}</ul>`;
      alertBox.style.display = 'block';
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  } catch (err) {
    alertBox.className = 'alert error';
    alertBox.textContent = 'Network error. Please check your connection and try again.';
    alertBox.style.display = 'block';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Send Message';
  }
});
</script>

<style>
/* ===== CONTACT PAGE STYLES ===== */
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-top: 30px;
}

.contact-info h2,
.contact-form-container h2 {
  font-size: 1.8rem;
  margin-bottom: 25px;
  color: var(--black);
}

.contact-item {
  display: flex;
  gap: 15px;
  margin-bottom: 25px;
  align-items: flex-start;
}

.contact-item i {
  font-size: 1.4rem;
  color: var(--accent);
  width: 24px;
  text-align: center;
  margin-top: 4px;
}

.contact-item h3 {
  font-size: 1.2rem;
  margin-bottom: 8px;
  color: var(--black);
}

.contact-item p {
  margin: 0;
  line-height: 1.6;
}

.contact-item a {
  color: var(--accent);
  text-decoration: none;
  transition: opacity 0.2s;
}

.contact-item a:hover {
  opacity: 0.8;
  text-decoration: underline;
}

.contact-form-container {
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.contact-form .form-row {
  gap: 20px;
}

.contact-form .form-group {
  flex: 1;
  min-width: 200px;
}

.contact-form textarea {
  min-height: 140px;
}

.contact-form .form-checkboxes {
  margin: 20px 0;
}

.contact-form .form-checkboxes label {
  font-size: 0.95rem;
  line-height: 1.5;
}

.contact-form .form-checkboxes a {
  color: var(--accent);
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 992px) {
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 30px;
  }
  
  .contact-info {
    order: 2;
  }
  
  .contact-form-container {
    order: 1;
  }
}

@media (max-width: 576px) {
  .contact-form .form-row {
    flex-direction: column;
    gap: 15px;
  }
  
  .contact-form .form-group {
    min-width: auto;
  }
}
</style>