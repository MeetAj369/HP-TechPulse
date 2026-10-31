<?php
require_once __DIR__ . '/session_slot.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

redirect_if_logged_in();

$error   = '';
$success = '';


$fullname = '';
$email    = '';
$phone    = '';
$dept     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
    $phone    = isset($_POST['phone'])    ? trim($_POST['phone'])    : '';
    $dept     = isset($_POST['dept'])     ? trim($_POST['dept'])     : '';
    $pass     = isset($_POST['password']) ? $_POST['password']       : '';
    $cpass    = isset($_POST['cpassword'])? $_POST['cpassword']      : '';

    if ($fullname === '' || $email === '' || $pass === '' || $cpass === '') {
        $error = 'Full name, email and password fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $pass)) {
        $error = 'Password needs at least one uppercase letter (A-Z).';
    } elseif (!preg_match('/[a-z]/', $pass)) {
        $error = 'Password needs at least one lowercase letter (a-z).';
    } elseif (!preg_match('/[0-9]/', $pass)) {
        $error = 'Password needs at least one number (0-9).';
    } elseif (!preg_match('/[\W_]/', $pass)) {
        $error = 'Password needs at least one special character (!@#$ etc.).';
    } elseif ($pass !== $cpass) {
        $error = 'Passwords do not match. Please re-enter.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($res) > 0) {
            $error = 'This email is already registered. Please sign in instead.';
        } else {
            $pw_s   = mysqli_real_escape_string($conn, $pass);
            $fn_s   = mysqli_real_escape_string($conn, $fullname);
            $em_s   = mysqli_real_escape_string($conn, $email);
            $ph_s   = mysqli_real_escape_string($conn, $phone);
            $dp_s   = mysqli_real_escape_string($conn, $dept);
            $q = mysqli_query($conn,
                "INSERT INTO users (name,email,password,phone,department,role,created_at)
                 VALUES ('$fn_s','$em_s','$pw_s','$ph_s','$dp_s','user',NOW())"
            );
            if ($q) {
                $success = 'Account created! You can now sign in.';
                $fullname = $email = $phone = $dept = '';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}

$page_title = 'Create Account';
$is_public  = true;
$active_nav = '';
include 'header.php';
?>

<section class="auth-page">
  <div class="auth-bg"></div>
  <div class="auth-card" style="max-width:500px;">
    <!-- Logo -->
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
      </div>
      <h2>Create Account</h2>
      <p>Join HP TechPulse as a device custodian</p>
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
      <a href="/HP TechPulse/login.php" style="margin-left:8px;font-weight:800;color:var(--success);">Sign In Now</a>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate id="reg-form">

      <div class="form-grid" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label for="fullname">Full Name <span style="color:var(--red)">*</span></label>
          <input type="text" id="fullname" name="fullname" class="form-control" placeholder="e.g. Rajesh Kumar" value="<?php echo htmlspecialchars($fullname); ?>" required autocomplete="name">
        </div>
        <div class="form-group">
          <label for="email">Work Email <span style="color:var(--red)">*</span></label>
          <input type="email" id="email" name="email" class="form-control" placeholder="you@company.com" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
        </div>
      </div>

      <div class="form-grid" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label for="phone">Phone / Extension</label>
          <input type="text" id="phone" name="phone" class="form-control" placeholder="+91 9876543210" value="<?php echo htmlspecialchars($phone); ?>" autocomplete="tel">
        </div>
        <div class="form-group">
          <label for="dept">Department</label>
          <select id="dept" name="dept" class="form-control">
            <option value="">-- Select Department --</option>
            <?php
            $depts = array('IT','HR','Finance','Operations','Procurement','Marketing','Sales','Engineering','Support');
            foreach($depts as $d){
              $sel = ($dept === $d) ? ' selected' : '';
              echo '<option value="'.htmlspecialchars($d).'"'.$sel.'>'.htmlspecialchars($d).'</option>';
            }
            ?>
          </select>
        </div>
      </div>


      <div class="form-group">
        <label for="reg-pass">
          Password <span style="color:var(--red)">*</span>
          <span id="pw-grade-badge" style="display:none;margin-left:8px;padding:1px 9px;border-radius:10px;font-size:0.68rem;font-weight:800;vertical-align:middle;"></span>
        </label>
        <div class="pw-wrap">
          <input type="password" id="reg-pass" name="password" class="form-control" placeholder="Create a strong password" required autocomplete="new-password">
          <button type="button" class="pw-toggle" id="toggle-pass" title="Show/Hide">
            <svg id="eye1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg id="eye2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>


        <div class="pw-strength-wrap">
          <div class="pw-bars">
            <div class="pw-bar" id="bar1"></div>
            <div class="pw-bar" id="bar2"></div>
            <div class="pw-bar" id="bar3"></div>
            <div class="pw-bar" id="bar4"></div>
            <div class="pw-bar" id="bar5"></div>
          </div>
          <div class="pw-strength-label" id="pw-label" style="color:var(--text-muted);">Enter a password</div>

          <!-- Rules grid -->
          <div class="pw-rules">
            <div class="pw-rule" id="rule-len">
              <div class="pw-rule-icon">8+</div>
              <span>Min 8 characters</span>
            </div>
            <div class="pw-rule" id="rule-upper">
              <div class="pw-rule-icon">A</div>
              <span>Uppercase letter</span>
            </div>
            <div class="pw-rule" id="rule-lower">
              <div class="pw-rule-icon">a</div>
              <span>Lowercase letter</span>
            </div>
            <div class="pw-rule" id="rule-num">
              <div class="pw-rule-icon">9</div>
              <span>Number (0-9)</span>
            </div>
            <div class="pw-rule" id="rule-spec">
              <div class="pw-rule-icon">#</div>
              <span>Special character</span>
            </div>
            <div class="pw-rule" id="rule-match" style="grid-column:span 1;">
              <div class="pw-rule-icon" style="font-size:0.5rem;font-weight:900;">OK</div>
              <span>Passwords match</span>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="reg-cpass">Confirm Password <span style="color:var(--red)">*</span></label>
        <div class="pw-wrap">
          <input type="password" id="reg-cpass" name="cpassword" class="form-control" placeholder="Re-enter password" required autocomplete="off" onpaste="return false;" oncopy="return false;" oncut="return false;" ondrop="return false;" ondragstart="return false;" oncontextmenu="return false;">
          <button type="button" class="pw-toggle" id="toggle-cpass" title="Show/Hide">
            <svg id="eye3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg id="eye4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
          </button>
        </div>
        <div id="match-msg" style="font-size:0.76rem;margin-top:5px;min-height:16px;transition:color 0.2s;"></div>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top:6px;padding:13px;" id="reg-btn">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        Create My Account
      </button>
    </form>

    <div class="auth-footer-link" style="margin-top:16px;">
      Already have an account? <a href="/HP TechPulse/login.php">Sign In</a>
    </div>
    <div class="auth-footer-link" style="margin-top:6px;font-size:0.78rem;">
      <a href="/HP TechPulse/index.php" style="color:var(--text-muted);">Back to HP TechPulse</a>
    </div>
  </div>
</section>

<script>

(function(){
  var passEl  = document.getElementById('reg-pass');
  var cpassEl = document.getElementById('reg-cpass');
  var bars    = [document.getElementById('bar1'),document.getElementById('bar2'),document.getElementById('bar3'),document.getElementById('bar4'),document.getElementById('bar5')];
  var lbl     = document.getElementById('pw-label');
  var badge   = document.getElementById('pw-grade-badge');
  var matchEl = document.getElementById('match-msg');

  var rules = {
    len:   document.getElementById('rule-len'),
    upper: document.getElementById('rule-upper'),
    lower: document.getElementById('rule-lower'),
    num:   document.getElementById('rule-num'),
    spec:  document.getElementById('rule-spec'),
    match: document.getElementById('rule-match')
  };

  var grades = [
    {t:'—',      color:'var(--text-muted)'},
    {t:'Weak',   color:'var(--red)'},
    {t:'Fair',   color:'#f97316'},
    {t:'Good',   color:'var(--warning)'},
    {t:'Strong', color:'#22d3ee'},
    {t:'Excellent', color:'var(--success)'}
  ];

  function check(v){
    return {
      len:   v.length >= 8,
      upper: /[A-Z]/.test(v),
      lower: /[a-z]/.test(v),
      num:   /[0-9]/.test(v),
      spec:  /[\W_]/.test(v)
    };
  }

  function setRule(el, pass){
    if(pass){ el.classList.add('pass'); el.querySelector('.pw-rule-icon').textContent = 'OK'; }
    else     { el.classList.remove('pass'); }
  }

  function update(){
    var v = passEl.value;
    var c = check(v);
    var score = (c.len?1:0)+(c.upper?1:0)+(c.lower?1:0)+(c.num?1:0)+(c.spec?1:0);
    if(!v) score = 0;

    /* Bars */
    bars.forEach(function(b,i){
      b.className = 'pw-bar';
      if(v && i < score) b.classList.add('active-' + score);
    });

    /* Label */
    var g = grades[score];
    lbl.textContent = v ? g.t : 'Enter a password';
    lbl.style.color = g.color;

    /* Badge */
    if(v && score > 0){
      badge.style.display = 'inline-block';
      badge.textContent   = g.t;
      badge.style.background = g.color + '22';
      badge.style.color      = g.color;
      badge.style.border     = '1px solid ' + g.color + '44';
    } else {
      badge.style.display = 'none';
    }

    /* Rules */
    setRule(rules.len,   c.len);
    setRule(rules.upper, c.upper);
    setRule(rules.lower, c.lower);
    setRule(rules.num,   c.num);
    setRule(rules.spec,  c.spec);

    /* rule icons back to letter if not pass */
    if(!c.len)   rules.len.querySelector('.pw-rule-icon').textContent='8+';
    if(!c.upper) rules.upper.querySelector('.pw-rule-icon').textContent='A';
    if(!c.lower) rules.lower.querySelector('.pw-rule-icon').textContent='a';
    if(!c.num)   rules.num.querySelector('.pw-rule-icon').textContent='9';
    if(!c.spec)  rules.spec.querySelector('.pw-rule-icon').textContent='#';

    checkMatch();
  }

  function checkMatch(){
    var p = passEl.value; var c = cpassEl.value;
    if(c.length === 0){ matchEl.textContent=''; rules.match.classList.remove('pass'); rules.match.querySelector('.pw-rule-icon').textContent='OK'; return; }
    if(p === c){
      matchEl.textContent = 'Passwords match'; matchEl.style.color='var(--success)';
      rules.match.classList.add('pass');
    } else {
      matchEl.textContent = 'Passwords do not match'; matchEl.style.color='var(--red)';
      rules.match.classList.remove('pass'); rules.match.querySelector('.pw-rule-icon').textContent='OK';
    }
  }

  passEl.addEventListener('input', update);
  cpassEl.addEventListener('input', checkMatch);
})();


function makeToggle(btnId, inputId, icon1Id, icon2Id){
  var btn  = document.getElementById(btnId);
  var inp  = document.getElementById(inputId);
  var ic1  = document.getElementById(icon1Id);
  var ic2  = document.getElementById(icon2Id);
  if(!btn) return;
  btn.addEventListener('click', function(){
    if(inp.type==='password'){ inp.type='text'; ic1.style.display='none'; ic2.style.display=''; }
    else { inp.type='password'; ic1.style.display=''; ic2.style.display='none'; }
  });
}
makeToggle('toggle-pass',  'reg-pass',  'eye1','eye2');
makeToggle('toggle-cpass', 'reg-cpass', 'eye3','eye4');


(function() {
  var cpass = document.getElementById('reg-cpass');
  if (!cpass) { return; }

  var blocked = ['paste', 'copy', 'cut', 'drop', 'dragstart', 'contextmenu'];
  for (var i = 0; i < blocked.length; i++) {
    (function(evt) {
      cpass.addEventListener(evt, function(e) {
        if (e.preventDefault) { e.preventDefault(); }
        return false;
      }, false);
    })(blocked[i]);
  }

})();
</script>

<?php include 'footer.php'; ?>
