<?php
require_once __DIR__ . '/session_slot.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

redirect_if_logged_in();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $pass  = isset($_POST['password']) ? $_POST['password']       : '';

    if ($email === '' || $pass === '') {
        $error = 'Please enter both your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $em_s = mysqli_real_escape_string($conn, $email);
        $q    = mysqli_query($conn,
            "SELECT id, name, password, role, is_support FROM users WHERE email='$em_s' LIMIT 1"
        );
        $user = $q ? mysqli_fetch_assoc($q) : null;

        if (!$user) {
            $error = 'No account found with this email address.';
        } elseif ($pass !== $user['password']) {
            $error = 'Incorrect password. Please try again.';
        } else {

            $token = tp_create_session(
                $conn,
                (int)$user['id'],
                $user['name'],
                $user['role'],
                (int)$user['is_support']
            );

            if (!$token) {
                $error = 'Session could not be created. Please try again.';
            } else {
                // Update last login timestamp
                mysqli_query($conn,
                    "UPDATE users SET last_login = NOW() WHERE id = " . (int)$user['id']
                );

                // Audit log
                if (function_exists('audit_log')) {
                    audit_log($conn, 'User signed in via token session', 'users', $user['id']);
                }

                // Determine redirect destination
                $dest = ($user['role'] === 'admin')
                    ? '/HP TechPulse/admin_dashboard.php'
                    : '/HP TechPulse/user_dashboard.php';


                $token_js  = htmlspecialchars($token, ENT_QUOTES);
                $dest_js   = htmlspecialchars($dest,  ENT_QUOTES);
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
                echo '<style>body{margin:0;background:#0b0f1a;}</style>';
                echo '<script>';
                echo 'sessionStorage.setItem("tp_auth_token","' . $token_js . '");';
                echo 'window.location.replace("' . $dest_js . '?_t=' . $token_js . '");';
                echo '</script>';
                echo '</head><body></body></html>';
                exit();
            }
        }
    }
}

$page_title = 'Sign In';
$is_public  = true;
$active_nav = '';
include 'header.php';
?>

<section class="auth-page">
  <div class="auth-bg"></div>

  <div class="auth-card">
    <!-- Logo & Title -->
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
      <h2>Welcome Back</h2>
      <p>Sign in to your HP TechPulse account</p>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      Account created successfully! Please sign in below.
    </div>
    <?php endif; ?>

    <form method="POST" novalidate id="login-form">
      <div class="form-group">
        <label for="email">Work Email</label>
        <input type="email" id="email" name="email"
               class="form-control"
               placeholder="you@company.com"
               value="<?php echo htmlspecialchars($email); ?>"
               required autocomplete="email">
      </div>

      <div class="form-group">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
          <label for="password" style="margin-bottom:0;">Password</label>
          <a href="/HP TechPulse/forgot_password.php" style="font-size:0.78rem;color:var(--red);font-weight:600;text-decoration:none;transition:opacity 0.2s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">Forgot password?</a>
        </div>
        <div class="pw-wrap">
          <input type="password" id="password" name="password"
                 class="form-control"
                 placeholder="Enter your password"
                 required autocomplete="current-password">
          <button type="button" class="pw-toggle" id="toggle-login-pw" title="Show/Hide">
            <svg id="leye1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg id="leye2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;padding:13px;">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
        Sign In
      </button>
    </form>

    <div class="auth-footer-link" style="margin-top:16px;">
      New here? <a href="/HP TechPulse/register.php">Create an account</a>
    </div>
    <div class="auth-footer-link" style="margin-top:6px;font-size:0.78rem;">
      Can't access your account? <a href="/HP TechPulse/forgot_password.php" style="color:var(--text-sub);">Reset password</a>
    </div>
    <div class="auth-footer-link" style="margin-top:5px;font-size:0.78rem;">
      <a href="/HP TechPulse/index.php" style="color:var(--text-muted);">Back to HP TechPulse</a>
    </div>
  </div>
</section>

<script>
(function(){
  var btn = document.getElementById('toggle-login-pw');
  var inp = document.getElementById('password');
  var e1  = document.getElementById('leye1');
  var e2  = document.getElementById('leye2');
  if (!btn) return;
  btn.addEventListener('click', function(){
    if (inp.type === 'password') { inp.type = 'text';     e1.style.display = 'none'; e2.style.display = '';     }
    else                         { inp.type = 'password'; e1.style.display = '';     e2.style.display = 'none'; }
  });
})();
</script>

<?php include 'footer.php'; ?>
