<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

$page_title = 'Contact Us';
$active_nav = 'contact';
$is_public  = true;

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cname    = isset($_POST['cname'])    ? trim($_POST['cname'])    : '';
    $cemail   = isset($_POST['cemail'])   ? trim($_POST['cemail'])   : '';
    $csubject = isset($_POST['csubject']) ? trim($_POST['csubject']) : '';
    $cmsg     = isset($_POST['cmsg'])     ? trim($_POST['cmsg'])     : '';

    if ($cname === '' || $cemail === '' || $cmsg === '') {
        $error = 'Name, email and message are required.';
    } elseif (!filter_var($cemail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($cmsg) < 10) {
        $error = 'Message must be at least 10 characters.';
    } else {
        // Store in a contact_messages table (auto-created if missing)
        $qcheck = mysqli_query($conn, "SHOW TABLES LIKE 'contact_messages'");
        if ($qcheck && mysqli_num_rows($qcheck) === 0) {
            mysqli_query($conn,
                "CREATE TABLE IF NOT EXISTS contact_messages (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    name       VARCHAR(120) NOT NULL,
                    email      VARCHAR(120) NOT NULL,
                    subject    VARCHAR(200),
                    message    TEXT NOT NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8"
            );
        }
        $cn_s = mysqli_real_escape_string($conn, $cname);
        $ce_s = mysqli_real_escape_string($conn, $cemail);
        $cs_s = mysqli_real_escape_string($conn, $csubject);
        $cm_s = mysqli_real_escape_string($conn, $cmsg);
        $q = mysqli_query($conn,
            "INSERT INTO contact_messages(name,email,subject,message,created_at)
             VALUES('$cn_s','$ce_s','$cs_s','$cm_s',NOW())"
        );
        if ($q) {
            $success = 'Your message has been sent! We will get back to you within 24 hours.';
        } else {
            $error = 'Could not send your message. Please try again.';
        }
    }
}

include 'header.php';
?>

<!-- Hero -->
<section style="padding:72px 0 52px;text-align:center;border-bottom:1px solid var(--border);position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 20% 50%,rgba(232,19,42,0.08) 0%,transparent 60%),radial-gradient(ellipse 40% 60% at 80% 50%,rgba(26,107,255,0.06) 0%,transparent 60%);"></div>
  <div class="container" style="position:relative;z-index:1;">
    <div class="section-eyebrow">Get in Touch</div>
    <h1 class="section-title text-center" style="max-width:560px;margin:0 auto 14px;">We're here to help you succeed</h1>
    <p style="color:var(--text-sub);font-size:0.95rem;max-width:480px;margin:0 auto;">Whether you have a setup question, a feature request, or need technical guidance — our team responds within 24 hours.</p>
  </div>
</section>

<!-- Contact Grid -->
<section style="padding:72px 0;">
  <div class="container">
    <div class="contact-grid">

      <!-- Info Card -->
      <div class="contact-info-card">
        <h3>Contact Information</h3>
        <p>Reach out through any of these channels and we'll respond as quickly as possible.</p>

        <div class="contact-detail">
          <div class="contact-detail-icon" style="background:var(--grad);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div class="contact-detail-txt">
            <strong>Headquarters</strong>
            <span>Anand, Gujarat, India</span>
          </div>
        </div>

        <div class="contact-detail">
          <div class="contact-detail-icon" style="background:linear-gradient(135deg,var(--blue),#06b6d4);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </div>
          <div class="contact-detail-txt">
            <strong>Email Support</strong>
            <span>support@hptechpulse.local<br>admin@hptechpulse.local</span>
          </div>
        </div>

        <div class="contact-detail">
          <div class="contact-detail-icon" style="background:linear-gradient(135deg,var(--success),#06b6d4);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          </div>
          <div class="contact-detail-txt">
            <strong>Phone (Internal)</strong>
            <span>+91 93600 00369 (IT Helpdesk)<br>+91 96369 00369 (Admin)</span>
          </div>
        </div>

        <div class="contact-detail">
          <div class="contact-detail-icon" style="background:linear-gradient(135deg,var(--warning),#f97316);">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="contact-detail-txt">
            <strong>Working Hours</strong>
            <span>Monday – Friday: 9AM – 6PM<br>Saturday: 10AM – 2PM</span>
          </div>
        </div>

        <!-- Quick links -->
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
          <div style="font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);margin-bottom:12px;">Quick Actions</div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <a href="/HP TechPulse/register.php" class="btn btn-primary btn-full" style="font-size:0.84rem;padding:9px 16px;">Create Account</a>
            <a href="/HP TechPulse/login.php" class="btn btn-outline btn-full" style="font-size:0.84rem;padding:9px 16px;">Sign In to Dashboard</a>
            <a href="/HP TechPulse/feedback.php" class="btn btn-ghost btn-full" style="font-size:0.84rem;padding:9px 16px;border:1px solid var(--border);">Leave Feedback</a>
          </div>
        </div>
      </div>

      <!-- Form Card -->
      <div class="contact-form-card">
        <div style="margin-bottom:26px;">
          <div class="section-eyebrow">Send a Message</div>
          <h2 style="font-size:1.5rem;font-weight:900;letter-spacing:-0.02em;">What can we help you with?</h2>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <div class="form-grid" style="grid-template-columns:1fr 1fr;">
            <div class="form-group">
              <label for="cname">Your Name <span style="color:var(--red)">*</span></label>
              <input type="text" id="cname" name="cname" class="form-control" placeholder="Rajesh Kumar" required>
            </div>
            <div class="form-group">
              <label for="cemail">Email Address <span style="color:var(--red)">*</span></label>
              <input type="email" id="cemail" name="cemail" class="form-control" placeholder="you@company.com" required>
            </div>
          </div>

          <div class="form-group">
            <label for="csubject">Subject</label>
            <select id="csubject" name="csubject" class="form-control">
              <option value="">-- Choose a topic --</option>
              <option>Technical Support</option>
              <option>Account Help</option>
              <option>Feature Request</option>
              <option>Device Setup Query</option>
              <option>Bug Report</option>
              <option>General Inquiry</option>
            </select>
          </div>

          <div class="form-group">
            <label for="cmsg">Message <span style="color:var(--red)">*</span></label>
            <textarea id="cmsg" name="cmsg" class="form-control" rows="6" placeholder="Describe your question or issue in detail..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:0.93rem;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Send Message
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Strip -->
<section style="padding:60px 0;background:var(--bg-alt);border-top:1px solid var(--border);">
  <div class="container" style="max-width:780px;">
    <div class="section-eyebrow text-center">Got Questions?</div>
    <h2 class="section-title text-center" style="margin-bottom:36px;">Frequently Asked Questions</h2>
    <?php
    $faqs = array(
      array(
        'q' => 'How quickly does the IT support desk respond to submitted inquiries?',
        'a' => 'Inquiry tickets submitted through our contact form are automatically categorized and routed to the appropriate IT administrative team. Critical operational inquiries receive an initial response within 1–2 business hours, while general requests are processed within 24 hours.'
      ),
      array(
        'q' => 'How do I report an urgent hardware emergency or total system failure?',
        'a' => 'For critical hardware faults requiring immediate dispatch, registered employees can log an incident directly through their Dashboard with Priority set to "Critical". This triggers priority escalation across the IT Support queue for immediate technician assignment.'
      ),
      array(
        'q' => 'Can I request new hardware assets or equipment upgrades through the portal?',
        'a' => 'Yes. Employees can submit hardware procurement and upgrade requests by selecting "Feature Request" or "Device Setup Query" in the contact form, specifying required specifications. Your IT Administrator will review asset availability and allocation.'
      ),
      array(
        'q' => 'What should I do if an assigned device is lost, damaged, or stolen?',
        'a' => 'Immediately submit a critical notification to IT Support specifying the asset tag and incident details. IT Administrators will flag the device status, update security permissions, and initiate replacement hardware provisioning.'
      ),
      array(
        'q' => 'How are device custodian reassignments and employee offboarding handled?',
        'a' => 'IT Administrators can instantly reassign asset custodians from the Admin Inventory panel. Upon employee offboarding, all hardware items linked to the user profile are flagged for health inspection before being re-allocated to active inventory.'
      ),
      array(
        'q' => 'What information should I include when submitting a technical bug report?',
        'a' => 'When logging a technical bug, please provide the affected page URL, device model, asset tag (if applicable), and a clear step-by-step description of the behavior. Including exact error codes helps our team resolve issues faster.'
      ),
      array(
        'q' => 'How do I contact HP TechPulse administrative support directly?',
        'a' => 'You can reach our administrative helpdesk via the contact form above, by emailing support@hptechpulse.local, or by calling our internal helpdesk extension at Ext. 1001 during standard business hours (Mon–Fri, 9:00 AM – 6:00 PM).'
      ),
    );
    foreach($faqs as $fi => $f):
    ?>
    <div class="ctp-faq-item" style="border:1px solid var(--border);border-radius:var(--radius);margin-bottom:10px;background:var(--bg-card);overflow:hidden;transition:border-color 0.25s;">
      <button class="ctp-faq-btn" type="button" style="width:100%;text-align:left;padding:20px 24px;background:none;border:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:16px;color:var(--text);">
        <span style="font-size:0.95rem;font-weight:700;line-height:1.4;"><?php echo htmlspecialchars($f['q']); ?></span>
        <span class="ctp-faq-icon" style="width:28px;height:28px;border:1.5px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;font-weight:700;color:var(--text-muted);transition:all 0.3s;line-height:1;">+</span>
      </button>
      <div class="ctp-faq-body" style="height:0;overflow:hidden;transition:height 0.35s cubic-bezier(0.4,0,0.2,1);">
        <div style="padding:0 24px 20px;font-size:0.9rem;color:var(--text-sub);line-height:1.75;border-top:1px solid var(--border);"><?php echo $f['a']; ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>


<style>
/* Scoped to contact.php — uses ctp- prefix to avoid ANY conflict with style.css faq rules */
.ctp-faq-item.ctp-open { border-color: var(--red) !important; }
.ctp-faq-item.ctp-open .ctp-faq-icon {
  background: var(--red);
  border-color: var(--red);
  color: #fff;
  transform: rotate(45deg);
}
.ctp-faq-btn:hover { background: var(--bg-alt) !important; }
</style>

<script>
// Self-contained FAQ accordion for contact.php
// Uses scrollHeight (not max-height or display) — immune to style.css overrides
(function() {
  var items = document.querySelectorAll('.ctp-faq-item');

  function closeItem(item) {
    var body = item.querySelector('.ctp-faq-body');
    var icon = item.querySelector('.ctp-faq-icon');
    item.classList.remove('ctp-open');
    body.style.height = '0';
    icon.textContent = '+';
  }

  function openItem(item) {
    var body = item.querySelector('.ctp-faq-body');
    var icon = item.querySelector('.ctp-faq-icon');
    item.classList.add('ctp-open');
    // Measure natural height then animate to it
    body.style.height = body.scrollHeight + 'px';
    icon.textContent = '+'; // icon rotation handled by CSS transform
  }

  for (var i = 0; i < items.length; i++) {
    (function(item) {
      var btn = item.querySelector('.ctp-faq-btn');
      btn.addEventListener('click', function() {
        var isOpen = item.classList.contains('ctp-open');
        // Close all
        for (var j = 0; j < items.length; j++) { closeItem(items[j]); }
        // Toggle open
        if (!isOpen) { openItem(item); }
      });
    })(items[i]);
  }
})();
</script>

<?php include 'footer.php'; ?>
