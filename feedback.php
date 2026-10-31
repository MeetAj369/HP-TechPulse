<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

$page_title = 'Reviews & Feedback';
$active_nav = 'reviews';
$is_public  = true;

$error   = ''; $success = '';
$logged_in = isset($_SESSION['tp_user_id']);
$user_name = isset($_SESSION['tp_name']) ? $_SESSION['tp_name'] : '';

// Handle new feedback submission
if ($logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = isset($_POST['rating'])  ? (int)$_POST['rating']            : 0;
    $title   = isset($_POST['title'])   ? trim($_POST['title'])             : '';
    $comment = isset($_POST['comment']) ? trim($_POST['comment'])           : '';
    $uid     = (int)$_SESSION['tp_user_id'];

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a star rating (1-5).';
    } elseif (strlen($comment) < 10) {
        $error = 'Feedback must be at least 10 characters.';
    } else {
        // Use a simple feedback table (falls back gracefully if missing)
        $qcheck = mysqli_query($conn,"SHOW TABLES LIKE 'feedback'");
        if(mysqli_num_rows($qcheck)==0){
            mysqli_query($conn,"CREATE TABLE IF NOT EXISTS feedback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                rating TINYINT NOT NULL,
                title VARCHAR(120),
                comment TEXT,
                created_at DATETIME,
                INDEX(user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        // Single clean insert (fixed)
        $uid_s   = (int)$uid;
        $rat_s   = (int)$rating;
        $title_s = mysqli_real_escape_string($conn, $title);
        $comm_s  = mysqli_real_escape_string($conn, $comment);
        $q = mysqli_query($conn,
            "INSERT INTO feedback(user_id,rating,title,comment,created_at)
             VALUES($uid_s,$rat_s,'$title_s','$comm_s',NOW())"
        );
        if ($q) {
            $success = 'Thank you! Your review has been published.';
        } else {
            $error = 'Could not save feedback. Please try again.';
        }
    }
}

// Fetch existing reviews from feedback table if it exists
$reviews_db = array();
$qcheck2 = mysqli_query($conn,"SHOW TABLES LIKE 'feedback'");
if($qcheck2 && mysqli_num_rows($qcheck2)>0){
    $r2 = mysqli_query($conn,"SELECT f.*,u.name FROM feedback f JOIN users u ON f.user_id=u.id ORDER BY f.created_at DESC LIMIT 20");
    if($r2) while($row=mysqli_fetch_assoc($r2)) $reviews_db[] = $row;
}

// Static seed reviews — newest date first (July → June → June → May → April → March)
$static_reviews = array(
    array('name'=>'Rohan Desai','dept'=>'Procurement','rating'=>5,'title'=>'Real-world grade product','comment'=>'The UI feels like a premium SaaS product — not a college project. Dark mode is stunning and the light mode works perfectly too. Faculty were genuinely impressed.','date'=>'July 2026'),
    array('name'=>'Ananya Pillai','dept'=>'IT Support Lead','rating'=>5,'title'=>'Ticket management done right','comment'=>'The support ticket workflow is exactly what we needed. Assigning tickets to technicians, tracking status updates, and auto-timestamps on every action make audits smooth.','date'=>'June 2026'),
    array('name'=>'Vikram Singh','dept'=>'Finance Controller','rating'=>4,'title'=>'Depreciation tracking is a lifesaver','comment'=>'The WDV depreciation schedule saved us hours at audit time. The PDF report is professional enough to share with external auditors directly.','date'=>'June 2026'),
    array('name'=>'Sneha Rao','dept'=>'HR Manager','rating'=>5,'title'=>'Employees love the portal','comment'=>'The user dashboard is clean and simple. My team can check their assigned devices and raise tickets in seconds without needing any training.','date'=>'May 2026'),
    array('name'=>'Arjun Kumar','dept'=>'Operations Head','rating'=>5,'title'=>'Best offline IT tool','comment'=>'Our college lab runs WampServer 2.4 and HP TechPulse installed flawlessly. The carbon footprint simulator impressed every faculty member. Outstanding work.','date'=>'April 2026'),
    array('name'=>'Priya Mehta','dept'=>'IT Department','rating'=>5,'title'=>'Transformed our IT ops','comment'=>'We used to manage devices in three separate Excel sheets. HP TechPulse replaced all of that with one smart platform. The health scoring and PDF passport features are incredible.','date'=>'March 2026'),
);


include 'header.php';
?>

<section style="padding:72px 0 52px;text-align:center;border-bottom:1px solid var(--border);position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 80% 30%,rgba(232,19,42,0.08) 0%,transparent 60%),radial-gradient(ellipse 40% 60% at 20% 70%,rgba(26,107,255,0.06) 0%,transparent 60%);"></div>
  <div class="container">
    <div class="section-eyebrow">What People Say</div>
    <h1 class="section-title" style="max-width:580px;margin:0 auto 16px;">Trusted by IT teams, loved by everyone</h1>
    <p style="font-size:0.95rem;color:var(--text-sub);max-width:500px;margin:0 auto 28px;">Read honest feedback from employees and administrators who use HP TechPulse every day.</p>
    <!-- Rating summary -->
    <div style="display:inline-flex;align-items:center;gap:16px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 28px;">
      <div style="font-size:2.8rem;font-weight:900;letter-spacing:-0.04em;color:var(--warning);">4.9</div>
      <div>
        <div class="review-stars" style="justify-content:flex-start;">
          <?php for($i=0;$i<5;$i++): ?><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="star-filled"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><?php endfor; ?>
        </div>
        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:3px;"><?php echo count($static_reviews)+count($reviews_db); ?> verified reviews</div>
      </div>
    </div>
  </div>
</section>

<section style="padding:64px 0;">
  <div class="container">
    <h2 class="section-title" style="margin-bottom:32px;">Featured Reviews</h2>
    <div class="reviews-grid">

      <!-- Dynamic DB reviews first (newest first, ORDER BY created_at DESC) -->
      <?php foreach($reviews_db as $rv): ?>
      <div class="review-card">
        <div class="review-stars">
          <?php for($i=1;$i<=5;$i++): ?>
            <?php if($i<=$rv['rating']): ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="star-filled"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <?php else: ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--border)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <?php if(!empty($rv['title'])): ?>
        <div style="font-weight:700;font-size:0.92rem;margin-bottom:8px;">"<?php echo htmlspecialchars($rv['title']); ?>"</div>
        <?php endif; ?>
        <p class="review-text">"<?php echo htmlspecialchars($rv['comment']); ?>"</p>
        <div class="review-author">
          <div class="review-avatar"><?php echo strtoupper(substr($rv['name'],0,1)); ?></div>
          <div>
            <div class="review-author-name"><?php echo htmlspecialchars($rv['name']); ?></div>
            <div class="review-author-dept">Verified User &bull; <?php echo date('M Y', strtotime($rv['created_at'])); ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Static featured reviews (newest date first: July → March) -->
      <?php foreach($static_reviews as $rv): ?>
      <div class="review-card">
        <div class="review-stars">
          <?php
          for($i=1;$i<=5;$i++){
            if($i<=$rv['rating']){
              echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="star-filled"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
            } else {
              echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--border)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
            }
          }
          ?>
        </div>
        <?php if(!empty($rv['title'])): ?>
        <div style="font-weight:700;font-size:0.92rem;margin-bottom:8px;">"<?php echo htmlspecialchars($rv['title']); ?>"</div>
        <?php endif; ?>
        <p class="review-text">"<?php echo htmlspecialchars($rv['comment']); ?>"</p>
        <div class="review-author">
          <div class="review-avatar"><?php echo strtoupper(substr($rv['name'],0,1)); ?></div>
          <div>
            <div class="review-author-name"><?php echo htmlspecialchars($rv['name']); ?></div>
            <div class="review-author-dept"><?php echo htmlspecialchars($rv['dept']); ?> &bull; <?php echo $rv['date']; ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div><!-- /.reviews-grid -->
  </div>
</section>


<section style="padding:48px 0;background:var(--bg-alt);border-top:1px solid var(--border);">
  <div class="container" style="max-width:680px;">
    <div class="section-eyebrow text-center">Share Your Experience</div>
    <h2 class="section-title text-center" style="margin-bottom:8px;">Leave a Review</h2>
    <p style="text-align:center;color:var(--text-sub);font-size:0.88rem;margin-bottom:36px;">Your honest feedback helps us improve HP TechPulse for every team.</p>

    <?php if(!$logged_in): ?>
      <div class="card" style="text-align:center;padding:36px;">
        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 14px;color:var(--primary);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <p style="color:var(--text-sub);margin-bottom:20px;">Please sign in to leave a review.</p>
        <a href="login.php" class="btn btn-primary">Sign In to Review</a>
      </div>

    <?php elseif(!empty($success)): ?>
      <div class="alert alert-success">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?php echo htmlspecialchars($success); ?>
      </div>
      <div style="text-align:center;margin-top:16px;">
        <a href="feedback.php" class="btn btn-outline">View All Reviews</a>
      </div>

    <?php else: ?>
      <?php if(!empty($error)): ?>
        <div class="alert alert-error">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <form method="POST" class="feedback-form">
            <div class="form-group">
              <label>Your Rating</label>
              <div class="star-rating">
                <?php for($i=1;$i<=5;$i++): ?>
                  <button type="button" class="star-btn" data-star="<?php echo $i; ?>">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  </button>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="rating-val" value="0">
            </div>

            <div class="form-group">
              <label for="rtitle">Review Title <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
              <input type="text" id="rtitle" name="title" class="form-control" placeholder="e.g. Brilliant platform for our team">
            </div>

            <div class="form-group">
              <label for="comment">Your Review <span style="color:var(--danger)">*</span></label>
              <textarea id="comment" name="comment" class="form-control" rows="5" placeholder="Tell us about your experience with HP TechPulse..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:11px 26px;">
              <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Submit Review
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
// Star rating handler
(function(){
  var btns=document.querySelectorAll('.star-btn');
  var inp=document.getElementById('rating-val');
  btns.forEach(function(btn,i){
    btn.addEventListener('click',function(){
      if(inp) inp.value=i+1;
      btns.forEach(function(b,j){ b.classList.toggle('on',j<=i); });
    });
    btn.addEventListener('mouseover',function(){
      btns.forEach(function(b,j){ b.style.color=j<=i?'var(--warning)':'var(--border)'; });
    });
    btn.addEventListener('mouseout',function(){
      var current=inp?parseInt(inp.value):0;
      btns.forEach(function(b,j){ b.style.color=j<current?'var(--warning)':'var(--border)'; });
    });
  });
})();
</script>

<?php include 'footer.php'; ?>
