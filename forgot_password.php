<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

redirect_if_logged_in();

$step    = 'request';   // request | sent
$error   = '';
$email   = '';
$pin_display = '';
$user_name   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if ($email === '') {
        $error = 'Please enter your registered work email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $em_s = mysqli_real_escape_string($conn, $email);
        $q    = mysqli_query($conn, "SELECT id, name FROM users WHERE email='$em_s' LIMIT 1");
        $user = $q ? mysqli_fetch_assoc($q) : null;

        if (!$user) {
            // Security: don't confirm whether account exists
            // But for corporate intranet we can be friendly
            $error = 'No account found with that email address. Please contact your IT administrator.';
        } else {
            // Expire any old unused tokens for this user
            mysqli_query($conn,
                "UPDATE password_resets SET used=1 WHERE user_id=" . (int)$user['id'] . " AND used=0"
            );

            // Generate 6-digit numeric PIN (easy to communicate verbally/via chat)
            $pin = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Store hashed pin (sha1 is OK for short-lived 6-digit code, offline system)
            $pin_hash  = sha1($pin);
            $expires   = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutes
            $uid       = (int)$user['id'];

            mysqli_query($conn,
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES ($uid, '$pin_hash', '$expires')"
            );

            // Audit log
            if (function_exists('audit_log')) {
                audit_log($conn, 'Password reset PIN generated', 'users', $uid);
            }

            $pin_display = $pin;
            $user_name   = $user['name'];
            $step        = 'sent';
        }
    }
}

$page_title = 'Forgot Password';
$is_public  = true;
$active_nav = '';
include 'header.php';
?>

<section class="auth-page">
  <div class="auth-bg"></div>

  <div class="auth-card" style="max-width:440px;">
    <!-- Logo & Title -->
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
      </div>
      <h2>Forgot Password?</h2>
      <p>We'll generate a reset PIN for you</p>
    </div>

    <?php if ($step === 'request'): ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="alert alert-info" style="background:rgba(26,107,255,0.08);border:1px solid rgba(26,107,255,0.18);border-radius:10px;padding:12px 15px;font-size:0.83rem;color:var(--blue);display:flex;gap:10px;align-items:flex-start;margin-bottom:20px;">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="flex-shrink:0;margin-top:2px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span>A <strong>6-digit reset PIN</strong> will be generated. Share it with your IT administrator to verify your identity, then use it to set a new password.</span>
    </div>

    <form method="POST" novalidate id="forgot-form">
      <div class="form-group">
        <label for="fp-email">Work Email Address</label>
        <input type="email" id="fp-email" name="email"
               class="form-control"
               placeholder="you@company.com"
               value="<?php echo htmlspecialchars($email); ?>"
               required autocomplete="email">
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;padding:13px;" id="fp-submit-btn">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        Generate Reset PIN
      </button>
    </form>

    <div class="auth-footer-link" style="margin-top:16px;">
      Remembered it? <a href="/HP TechPulse/login.php">Back to Sign In</a>
    </div>

    <?php else: /* step = sent */ ?>

    <!-- Success: show PIN -->
    <div style="text-align:center;padding:10px 0 24px;">
      <div style="width:64px;height:64px;background:rgba(34,197,94,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <svg fill="none" stroke="#22c55e" viewBox="0 0 24 24" width="30" height="30"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:5px;color:var(--text);">PIN Generated!</h3>
      <p style="font-size:0.85rem;color:var(--text-sub);margin-bottom:20px;">Hello <strong><?php echo htmlspecialchars($user_name); ?></strong>, your reset PIN is:</p>

      <div id="reset-pin-box" style="
        font-size:2.6rem;font-weight:900;letter-spacing:0.28em;
        background:var(--bg-input);border:2px dashed var(--red);
        border-radius:16px;padding:18px 24px;
        color:var(--red);margin-bottom:8px;
        font-family:monospace;cursor:pointer;position:relative;
        transition:background 0.2s;user-select:all;
      " title="Click to copy" onclick="copyPin(this)">
        <?php echo htmlspecialchars($pin_display); ?>
        <span id="pin-copied" style="position:absolute;top:6px;right:10px;font-size:0.65rem;font-weight:700;color:var(--success);opacity:0;transition:opacity 0.3s;letter-spacing:0.05em;">COPIED!</span>
      </div>
      <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:24px;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12" style="vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        This PIN expires in <strong id="pin-timer" style="color:var(--warning);">15:00</strong> &nbsp;·&nbsp; Single use only
      </p>

      <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:12px 15px;font-size:0.8rem;color:var(--warning);text-align:left;display:flex;gap:10px;align-items:flex-start;margin-bottom:24px;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="flex-shrink:0;margin-top:2px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span>Note this PIN and go to the <strong>Reset Password</strong> page to set your new password. Do not share this PIN with anyone other than your verified IT admin.</span>
      </div>

      <a href="/HP TechPulse/reset_password.php?email=<?php echo urlencode($email); ?>" class="btn btn-primary btn-full" style="padding:13px;font-size:0.93rem;text-decoration:none;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        Set New Password →
      </a>
    </div>

    <div class="auth-footer-link" style="margin-top:12px;">
      <a href="/HP TechPulse/login.php">← Back to Sign In</a>
    </div>

    <script>
    function copyPin(el) {
      var pin = el.textContent.trim().replace(/\s+/g,'');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(pin);
      } else {
        var t = document.createElement('textarea');
        t.value = pin; document.body.appendChild(t); t.select(); document.execCommand('copy'); document.body.removeChild(t);
      }
      var lbl = document.getElementById('pin-copied');
      lbl.style.opacity = 1;
      setTimeout(function(){ lbl.style.opacity = 0; }, 1800);
    }
    // Countdown timer
    (function(){
      var secs = 15 * 60;
      var el   = document.getElementById('pin-timer');
      var iv   = setInterval(function(){
        secs--;
        if (secs <= 0) { clearInterval(iv); if(el) el.textContent = 'Expired'; return; }
        var m = Math.floor(secs/60), s = secs%60;
        if(el) el.textContent = (m<10?'0':'')+m+':'+(s<10?'0':'')+s;
      }, 1000);
    })();
    </script>

    <?php endif; ?>
  </div>
</section>

<?php include 'footer.php'; ?>
