<?php
require_once __DIR__ . '/session_slot.php';

$_public_page_footer = isset($is_public) ? (bool)$is_public : false;
?>

<?php if ($_public_page_footer): ?>
<!-- PUBLIC FOOTER -->
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Brand -->
      <div class="footer-brand">
        <div class="site-logo" style="margin-bottom:12px;">
          <div class="site-logo-mark" style="overflow: hidden; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: transparent; border: none; padding: 0;">
            <img src="/HP TechPulse/uploads/HP%20TechPulse%20logo.png" alt="Logo" style="height: 102%; width: 120%; object-fit: cover; object-position: top;">
          </div>
          <span style="font-weight:900;font-size:1rem;"><span style="color:var(--red);">HP</span> TechPulse</span>
        </div>
        <p>HP TechPulse helps IT teams track every device, manage support tickets, monitor warranties, and reduce e-waste — all from one simple platform.</p>
      </div>

      <!-- Platform -->
      <div class="footer-col">
        <h4>Platform</h4>
        <a href="/HP TechPulse/index.php">Home</a>
        <a href="/HP TechPulse/about.php">About Us</a>
        <a href="/HP TechPulse/feedback.php">Reviews</a>
        <a href="/HP TechPulse/contact.php">Contact</a>
        <a href="/HP TechPulse/login.php">Sign In</a>
        <a href="/HP TechPulse/register.php">Register</a>
      </div>

      <!-- Features -->
      <div class="footer-col">
        <h4>Features</h4>
        <a href="/HP TechPulse/login.php">Device Inventory</a>
        <a href="/HP TechPulse/login.php">Support Tickets</a>
        <a href="/HP TechPulse/login.php">Warranty Tracking</a>
        <a href="/HP TechPulse/login.php">Health Scoring</a>
        <a href="/HP TechPulse/login.php">Green Pulse Eco</a>
        <a href="/HP TechPulse/login.php">PDF Passports</a>
      </div>

      <!-- Contact -->
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-contact-item">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>support@hptechpulse.local</span>
        </div>
        <div class="footer-contact-item">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <span>+91 93600 00369</span>
        </div>
        <div class="footer-contact-item">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span>Anand, Gujarat, India</span>
        </div>
        <div style="margin-top:16px;">
          <a href="/HP TechPulse/contact.php" class="btn btn-primary" style="font-size:0.8rem;padding:8px 16px;border-radius:8px;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Send Message
          </a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> HP TechPulse. All rights reserved.</p>
      <div class="footer-bottom-links">
        <a href="/HP TechPulse/about.php">About</a>
        <a href="/HP TechPulse/feedback.php">Reviews</a>
        <a href="/HP TechPulse/contact.php">Contact</a>
      </div>
    </div>
  </div>
</footer>
</div><!-- .public-body -->

<?php else: ?>
<!-- Dashboard footer: just close divs -->
</div><!-- .page-content -->
</div><!-- .main-frame -->
</div><!-- .dash-layout -->
<?php endif; ?>


<script>

(function(){

  var urlMatch = window.location.search.match(/[?&]_t=([a-f0-9]{64})/i);
  if (urlMatch) {
    sessionStorage.setItem('tp_auth_token', urlMatch[1]);
  }
  var token = sessionStorage.getItem('tp_auth_token');

  if (!token || token.length !== 64) return; /* Not logged in — nothing to do */


  function withToken(url) {
    if (!url || url.charAt(0) === '#' || url.indexOf('javascript') === 0) return url;
    if (url.indexOf('/HP TechPulse/') === -1 && url.indexOf('.php') === -1) return url; /* external link */
    url = url.replace(/&amp;/g, '&');
    url = url.replace(/([?&])_t=[^&]*/gi, '');
    url = url.replace(/[?&]+$/, '');
    url = url.replace(/\?&/, '?');
    url = url.replace(/&&+/g, '&');
    return url + (url.indexOf('?') !== -1 ? '&' : '?') + '_t=' + token;
  }


  function patchForms() {
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
      if (!forms[i].querySelector('input[name="_t"]')) {
        var inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = '_t';
        inp.value = token;
        forms[i].appendChild(inp);
      }
    }
  }


  document.addEventListener('click', function(e) {
    var el = e.target;
    while (el && el.tagName !== 'A') el = el.parentNode;
    if (!el || !el.href) return;
    if (el.href.indexOf('/HP TechPulse/') !== -1 || el.href.indexOf('.php') !== -1) {
      el.href = withToken(el.href);
    }
  }, true);


  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', patchForms);
  } else {
    patchForms();
  }
})();


(function(){
  var root = document.documentElement;
  var btns = document.querySelectorAll('.theme-toggle-btn');

  btns.forEach(function(btn){
    btn.addEventListener('click', function(){
      var nowLight = root.classList.toggle('light-mode');
      localStorage.setItem('tp_theme', nowLight ? 'light' : 'dark');
    });
  });
})();




(function(){
  document.querySelectorAll('.faq-question').forEach(function(btn){
    btn.addEventListener('click', function(){
      var item = btn.parentElement;
      var wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function(i){
        i.classList.remove('open');
        var ic = i.querySelector('.faq-icon');
        if(ic) ic.textContent = '+';
      });
      if(!wasOpen){
        item.classList.add('open');
        var myIc = btn.querySelector('.faq-icon');
        if(myIc) myIc.textContent = '-';
      }
    });
  });
})();


(function(){
  var bars = document.querySelectorAll('.health-bar-fill');
  if(!bars.length) return;
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){
        var pct = e.target.getAttribute('data-pct');
        if(pct) e.target.style.width = pct + '%';
        io.unobserve(e.target);
      }
    });
  }, {threshold:0.3});
  bars.forEach(function(b){ b.style.width='0'; io.observe(b); });
})();


(function(){
  var els = document.querySelectorAll('.stat-value[data-count]');
  if(!els.length) return;
  var io2 = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(!e.isIntersecting) return;
      var el = e.target;
      var target = parseInt(el.getAttribute('data-count'));
      var dur = 1200; var start = Date.now();
      (function frame(){
        var pct  = Math.min((Date.now()-start)/dur, 1);
        var ease = 1 - Math.pow(1-pct, 3);
        el.textContent = Math.floor(ease * target);
        if(pct < 1) requestAnimationFrame(frame);
        else el.textContent = target;
      })();
      io2.unobserve(el);
    });
  }, {threshold:0.5});
  els.forEach(function(el){ io2.observe(el); });
})();


(function(){
  var btns = document.querySelectorAll('.star-btn');
  var inp  = document.getElementById('rating-val');
  if(!btns.length) return;
  btns.forEach(function(btn, i){
    btn.addEventListener('click', function(){
      if(inp) inp.value = i + 1;
      btns.forEach(function(b, j){ b.classList.toggle('on', j <= i); });
    });
    btn.addEventListener('mouseover', function(){
      btns.forEach(function(b, j){ b.style.color = j <= i ? 'var(--warning)' : 'var(--border)'; });
    });
    btn.addEventListener('mouseout', function(){
      var cur = inp ? parseInt(inp.value) : 0;
      btns.forEach(function(b, j){ b.style.color = j < cur ? 'var(--warning)' : 'var(--border)'; });
    });
  });
})();


(function(){
  setTimeout(function(){
    document.querySelectorAll('.alert').forEach(function(a){
      a.style.transition = 'opacity 0.5s, max-height 0.5s';
      a.style.opacity = '0';
      a.style.maxHeight = '0';
      a.style.overflow = 'hidden';
      setTimeout(function(){ a.style.display='none'; }, 500);
    });
  }, 6000);
})();


(function(){
  document.addEventListener('click', function(e) {
    /* Walk up to find the nearest .btn ancestor */
    var el = e.target;
    while (el && el !== document.body) {
      if (el.classList && el.classList.contains('btn')) { break; }
      el = el.parentNode;
    }
    if (!el || !el.classList || !el.classList.contains('btn')) return;

    /* Create ripple span */
    var ripple = document.createElement('span');
    ripple.className = 'tp-ripple';

    var rect = el.getBoundingClientRect();
    var x = e.clientX - rect.left;
    var y = e.clientY - rect.top;
    ripple.style.left = x + 'px';
    ripple.style.top  = y + 'px';

    el.appendChild(ripple);

    /* Remove after animation ends */
    setTimeout(function() {
      if (ripple.parentNode) { ripple.parentNode.removeChild(ripple); }
    }, 600);
  }, true);
})();
</script>
</body>
</html>
