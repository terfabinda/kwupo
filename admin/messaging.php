<?php
session_start();
require '../includes/init.php';
require '../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../signin.php');
    exit;
}

// Fetch members for targeting
$members = mysqli_query($conn, "
    SELECT user_id, firstname, surname, phone, email, 
           w.ward_name, l.lga_name
    FROM users u
    LEFT JOIN static_users_council_wards w ON u.ward_id = w.ward_id
    LEFT JOIN static_users_lga l ON w.lga_id = l.lga_id
    WHERE u.role_id = 3
");

// Handle message send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $recipients = $_POST['recipients'] ?? [];
    
    if (empty($subject) || empty($content) || empty($recipients)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        // Get phone numbers
        $phone_list = [];
        foreach ($recipients as $user_id) {
            $user = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT phone FROM users WHERE user_id = " . (int)$user_id
            ));
            if ($user && !empty($user['phone'])) {
                // Clean phone number (remove +234, 0, etc.)
                $phone = preg_replace('/[^0-9]/', '', $user['phone']);
                if (substr($phone, 0, 3) === '234') {
                    $phone = $phone;
                } elseif (substr($phone, 0, 1) === '0') {
                    $phone = '234' . substr($phone, 1);
                } else {
                    $phone = '234' . $phone;
                }
                $phone_list[] = $phone;
            }
        }
        
        if (empty($phone_list)) {
            $_SESSION['error'] = "No valid phone numbers found.";
        } else {
            if ($type === 'whatsapp') {
                // Generate WhatsApp link (simple, no API)
                $text = urlencode($subject . "\n\n" . $content);
                $link = "https://wa.me/?text=$text";
                $_SESSION['whatsapp_link'] = $link;
                $_SESSION['recipient_count'] = count($phone_list);
                
            } elseif ($type === 'sms') {
                // TODO: Integrate Termii API
                $_SESSION['success'] = "SMS feature coming soon!";
            }
            
            // Log message
            $recipient_count = count($phone_list);
            $stmt = mysqli_prepare($conn, "
                INSERT INTO message_logs (message_type, subject, content, recipient_count, sent_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmt, 'ssssi', 
                $type, $subject, $content, $recipient_count, $_SESSION['user_id']
            );
            mysqli_stmt_execute($stmt);
            
            if ($type === 'sms') {
                header('Location: messaging.php');
                exit;
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<style>
.messaging-tabs {
  display: flex;
  margin-bottom: 20px;
  border-bottom: 2px solid #eee;
}
.tab {
  padding: 12px 24px;
  cursor: pointer;
  font-weight: 600;
  color: #666;
  border-bottom: 3px solid transparent;
}
.tab.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
}
.tab-content { display: none; }
.tab-content.active { display: block; }
.recipient-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #ddd;
  padding: 10px;
  border-radius: 6px;
  margin: 10px 0;
}
</style>

<div class="dashboard-layout">
  <?php include '../includes/sidebar.php'; ?>

  <main class="dashboard-main">
    <div class="container">
      <div class="dashboard-header">
        <h1>Messaging</h1>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>
      
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['whatsapp_link'])): ?>
        <div class="alert success">
          <p><strong>WhatsApp Message Ready!</strong></p>
          <p>Recipients: <?= $_SESSION['recipient_count'] ?> members</p>
          <a href="<?= $_SESSION['whatsapp_link'] ?>" target="_blank" class="btn" style="background: #25D366; color: white;">
            <i class="fab fa-whatsapp"></i> Open WhatsApp
          </a>
          <p class="help-text">Click to open WhatsApp Web with pre-filled message. Send to each recipient individually.</p>
        </div>
        <?php unset($_SESSION['whatsapp_link'], $_SESSION['recipient_count']); ?>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="messaging-tabs">
        <div class="tab active" data-tab="whatsapp">WhatsApp</div>
        <div class="tab" data-tab="sms">Bulk SMS</div>
      </div>

      <!-- WhatsApp Form -->
      <div class="tab-content active" id="whatsapp-form">
        <form method="POST">
          <input type="hidden" name="type" value="whatsapp">
          
          <div class="form-section">
            <div class="form-group">
              <label>Subject <span>*</span></label>
              <input type="text" name="subject" placeholder="e.g., Monthly Dues Reminder" required>
            </div>
            
            <div class="form-group">
              <label>Message <span>*</span></label>
              <textarea name="content" rows="6" placeholder="Your message content..." required></textarea>
              <p class="help-text">Max 700 characters for WhatsApp compatibility.</p>
            </div>
            
            <div class="form-group">
              <label>Recipients <span>*</span></label>
              <div class="recipient-list">
                <?php while ($m = mysqli_fetch_assoc($members)): ?>
                  <label style="display: block; margin: 5px 0;">
                    <input type="checkbox" name="recipients[]" value="<?= $m['user_id'] ?>" checked>
                    <?= htmlspecialchars($m['firstname'] . ' ' . $m['surname']) ?> 
                    (<?= htmlspecialchars($m['ward_name'] ?? 'N/A') ?>)
                  </label>
                <?php endwhile; ?>
              </div>
              <button type="button" class="btn btn-sm" onclick="toggleAllRecipients(true)">Select All</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="toggleAllRecipients(false)">Deselect All</button>
            </div>
            
            <button type="submit" class="btn" style="background: #25D366; color: white;">
              <i class="fab fa-whatsapp"></i> Generate WhatsApp Message
            </button>
          </div>
        </form>
      </div>

      <!-- SMS Form -->
      <div class="tab-content" id="sms-form">
        <div class="alert info">
          <p><strong>Bulk SMS is coming soon!</strong></p>
          <p>We're integrating with Termii to send SMS to members for critical alerts.</p>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById(tab.dataset.tab + '-form').classList.add('active');
  });
});

// Recipient selection
function toggleAllRecipients(check) {
  document.querySelectorAll('input[name="recipients[]"]').forEach(cb => {
    cb.checked = check;
  });
}
</script>

<?php include '../includes/footer.php'; ?>