<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

redirect_if_logged_in();

$step    = 'form';   // form | done
$error   = '';
$success = '';
$email   = isset($_GET['email']) ? trim($_GET['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'], $_POST['new_password'], $_POST['confirm_password'])) {
    $email    = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $pin      = trim($_POST['pin']);
    $newpass  = $_POST['new_password'];
    $confpass = $_POST['confirm_password'];

    $ferr = '';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $ferr = 'Invalid email address.';
    } elseif ($pin === '' || !preg_match('/^\d{6}$/', $pin)) {
        $ferr = 'Please enter the 6-digit reset PIN.';
    } elseif (strlen($newpass) < 8) {
        $ferr = 'New password must be at least 8 characters long.';
    } elseif ($newpass !== $confpass) {
        $ferr = 'Passwords do not match. Please re-enter them.';
    }

    if ($ferr) {
        $error = $ferr;
    } else {
        $em_s = mysqli_real_escape_string($conn, $email);
        $q    = mysqli_query($conn, "SELECT id, name FROM users WHERE email='$em_s' LIMIT 1");
        $user = $q ? mysqli_fetch_assoc($q) : null;

        if (!$user) {
            $error = 'No account found with that email address.';
        } else {
            $uid      = (int)$user['id'];
            $pin_hash = sha1($pin);

            // Look for valid, unused, unexpired token
            $rq = mysqli_query($conn,
                "SELECT id FROM password_resets
                 WHERE user_id=$uid AND token='$pin_hash' AND used=0 AND expires_at > NOW()
                 LIMIT 1"
            );
            $reset_row = $rq ? mysqli_fetch_assoc($rq) : null;

            if (!$reset_row) {
                $error = 'Invalid or expired PIN. Please request a new one.';
            } else {
                // Mark token as used
                mysqli_query($conn,
                    "UPDATE password_resets SET used=1 WHERE id=" . (int)$reset_row['id']
                );

                // Update password
                $safe_pass = mysqli_real_escape_string($conn, $newpass);
                mysqli_query($conn, "UPDATE users SET password='$safe_pass' WHERE id=$uid");

                // Audit log
                if (function_exists('audit_log')) {
                    audit_log($conn, 'Password reset via PIN', 'users', $uid);
                }

                $step = 'done';
                $success = 'Your password has been updated successfully. You can now sign in.';
            }
        }
    }
}

$page_title = 'Reset Password';
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
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <h2>Set New Password</h2>
      <p>Enter your PIN and choose a new password</p>
    </div>

    <?php if ($step === 'done'): ?>
    <!-- Success state -->
    <div style="text-align:center;padding:10px 0 28px;">
      <div style="width:64px;height:64px;background:rgba(34,197,94,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <svg fill="none" stroke="#22c55e" viewBox="0 0 24 24" width="30" height="30"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:8px;color:var(--text);">Password Updated!</h3>
      <p style="font-size:0.86rem;color:var(--text-sub);margin-bottom:26px;">Your password has been changed successfully. Please sign in with your new password.</p>
      <a href="/HP TechPulse/login.php" class="btn btn-primary btn-full" style="padding:13px;text-decoration:none;font-size:0.93rem;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        Sign In Now
      </a>
    </div>

    <?php else: ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate id="reset-form">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

      <div class="form-group">
        <label for="rp-email-display">Email Address</label>
        <input type="email" id="rp-email-display"
               class="form-control"
               value="<?php echo htmlspecialchars($email); ?>"
               readonly
               style="opacity:0.6;cursor:not-allowed;">
      </div>

      <div class="form-group">
        <label for="rp-pin">6-Digit Reset PIN</label>
        <input type="text" id="rp-pin" name="pin"
               class="form-control"
               placeholder="Enter your 6-digit PIN"
               maxlength="6"
               pattern="\d{6}"
               inputmode="numeric"
               autocomplete="one-time-code"
               style="font-size:1.5rem;letter-spacing:0.25em;font-weight:800;text-align:center;font-family:monospace;"
               required>
        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:5px;">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11" style="vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          PIN is valid for 15 minutes from when it was generated
        </p>
      </div>

      <div class="form-group">
        <label for="rp-newpass">New Password</label>
        <div class="pw-wrap">
          <input type="password" id="rp-newpass" name="new_password"
                 class="form-control"
                 placeholder="Minimum 8 characters"
                 required minlength="8"
                 autocomplete="new-password"
                 oninput="checkStrength(this.value)">
          <button type="button" class="pw-toggle" id="toggle-rp-pw1" title="Show/Hide">
            <svg id="reye1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg id="reye2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>
        <!-- Password strength bar -->
        <div style="margin-top:8px;height:4px;border-radius:4px;background:var(--border);overflow:hidden;">
          <div id="pw-strength-bar" style="height:100%;width:0%;border-radius:4px;transition:width 0.3s,background 0.3s;"></div>
        </div>
        <p id="pw-strength-lbl" style="font-size:0.72rem;margin-top:4px;color:var(--text-muted);"></p>
      </div>

      <div class="form-group">
        <label for="rp-confpass">Confirm New Password</label>
        <div class="pw-wrap">
          <input type="password" id="rp-confpass" name="confirm_password"
                 class="form-control"
                 placeholder="Re-enter new password"
                 required autocomplete="new-password"
                 oninput="checkMatch()">
          <button type="button" class="pw-toggle" id="toggle-rp-pw2" title="Show/Hide">
            <svg id="reye3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg id="reye4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>
        <p id="pw-match-lbl" style="font-size:0.72rem;margin-top:4px;"></p>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;padding:13px;" id="rp-submit-btn">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Update Password
      </button>
    </form>

    <div class="auth-footer-link" style="margin-top:16px;">
      Don't have a PIN? <a href="/HP TechPulse/forgot_password.php">Request one</a>
    </div>
    <div class="auth-footer-link" style="margin-top:5px;font-size:0.78rem;">
      <a href="/HP TechPulse/login.php" style="color:var(--text-muted);">← Back to Sign In</a>
    </div>

    <?php endif; ?>
  </div>
</section>

<script>
(function(){
  // pw-toggle 1
  var btn1 = document.getElementById('toggle-rp-pw1');
  var inp1 = document.getElementById('rp-newpass');
  var e1   = document.getElementById('reye1');
  var e2   = document.getElementById('reye2');
  if (btn1) btn1.addEventListener('click', function(){
    if (inp1.type === 'password') { inp1.type = 'text';     e1.style.display = 'none'; e2.style.display = '';     }
    else                          { inp1.type = 'password'; e1.style.display = '';     e2.style.display = 'none'; }
  });
  // pw-toggle 2
  var btn2 = document.getElementById('toggle-rp-pw2');
  var inp2 = document.getElementById('rp-confpass');
  var e3   = document.getElementById('reye3');
  var e4   = document.getElementById('reye4');
  if (btn2) btn2.addEventListener('click', function(){
    if (inp2.type === 'password') { inp2.type = 'text';     e3.style.display = 'none'; e4.style.display = '';     }
    else                          { inp2.type = 'password'; e3.style.display = '';     e4.style.display = 'none'; }
  });
  // PIN: numeric only
  var pinField = document.getElementById('rp-pin');
  if (pinField) {
    pinField.addEventListener('input', function(){
      this.value = this.value.replace(/\D/g,'').slice(0,6);
    });
  }
})();

function checkStrength(v) {
  var bar = document.getElementById('pw-strength-bar');
  var lbl = document.getElementById('pw-strength-lbl');
  if (!bar || !lbl) return;
  var score = 0;
  if (v.length >= 8)  score++;
  if (v.length >= 12) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;
  var colors = ['#e8132a','#f59e0b','#f59e0b','#22c55e','#22c55e'];
  var labels = ['Too short','Weak','Fair','Good','Strong'];
  var width  = [20, 40, 60, 80, 100];
  var i = Math.max(0, score - 1);
  bar.style.width     = width[Math.min(i, 4)] + '%';
  bar.style.background = colors[Math.min(i, 4)];
  lbl.textContent     = v.length ? labels[Math.min(i, 4)] : '';
  lbl.style.color     = colors[Math.min(i, 4)];
}

function checkMatch() {
  var p1  = document.getElementById('rp-newpass');
  var p2  = document.getElementById('rp-confpass');
  var lbl = document.getElementById('pw-match-lbl');
  if (!p1 || !p2 || !lbl) return;
  if (p2.value === '') { lbl.textContent = ''; return; }
  if (p1.value === p2.value) {
    lbl.textContent = '✓ Passwords match';
    lbl.style.color = 'var(--success)';
  } else {
    lbl.textContent = '✗ Passwords do not match';
    lbl.style.color = 'var(--red)';
  }
}
</script>

<?php include 'footer.php'; ?>
