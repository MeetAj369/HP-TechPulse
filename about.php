<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

$page_title = 'About HP TechPulse';
$active_nav = 'about';
$is_public  = true;
include 'header.php';
?>

<section style="padding:90px 0 70px;text-align:center;position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(232,19,42,0.07) 0%,transparent 65%);pointer-events:none;"></div>
  <div class="container" style="position:relative;z-index:1;">
    <div class="section-eyebrow">About HP TechPulse</div>
    <h1 class="section-title" style="max-width:680px;margin:0 auto 20px;font-size:clamp(2rem,4vw,3rem);">
      Bringing order to your<br>company's IT assets
    </h1>
    <p style="font-size:1.05rem;color:var(--text-sub);max-width:560px;margin:0 auto 36px;line-height:1.75;">
      HP TechPulse is a complete IT asset management platform — built for IT teams and employees who want clarity, speed, and control over every device in their organization.
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="/HP TechPulse/register.php" class="btn btn-primary" style="padding:13px 28px;">Get Started</a>
      <a href="/HP TechPulse/contact.php" class="btn btn-outline" style="padding:13px 28px;">Contact Us</a>
    </div>
  </div>
</section>

<section style="border-top:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--bg-alt);">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0;">
      <?php
      $stats = array(
        array('val'=>'6+',   'lbl'=>'Core Modules'),
        array('val'=>'5',    'lbl'=>'Ticket Stages'),
        array('val'=>'100%', 'lbl'=>'Offline Ready'),
        array('val'=>'2',    'lbl'=>'User Roles'),
      );
      foreach($stats as $i=>$s):
      ?>
      <div style="padding:32px 24px;text-align:center;<?php echo $i>0?'border-left:1px solid var(--border);':''; ?>">
        <div style="font-size:2.2rem;font-weight:900;background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;"><?php echo $s['val']; ?></div>
        <div style="font-size:0.82rem;color:var(--text-muted);margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;"><?php echo $s['lbl']; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section style="padding:80px 0;">
  <div class="container">
    <div class="grid-2" style="gap:64px;align-items:center;">
      <div>
        <div class="section-eyebrow">Our Mission</div>
        <h2 style="font-size:1.9rem;font-weight:800;letter-spacing:-0.025em;line-height:1.25;margin-bottom:20px;">
          Giving every organization<br>complete visibility over IT
        </h2>
        <p style="font-size:0.97rem;color:var(--text-sub);line-height:1.8;margin-bottom:18px;">
          Too many companies track devices through spreadsheets, handle tickets through email, and lose warranty records entirely. HP TechPulse replaces all of that with a single, intuitive platform built for real-world use.
        </p>
        <p style="font-size:0.97rem;color:var(--text-sub);line-height:1.8;">
          Whether you are managing 5 devices or 500, the experience is the same — clear dashboards, instant ticket tracking, and a health score for every piece of equipment you own.
        </p>
      </div>
      <div style="display:grid;gap:18px;">
        <?php
        $vals = array(
          array('icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z','c'=>'success','t'=>'Works Offline','b'=>'Runs entirely on your local network. No cloud dependency, no subscription, no data sent anywhere.'),
          array('icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','c'=>'blue','t'=>'Role-Based Access','b'=>'Admins, employees, and IT support each see exactly what they need — nothing more, nothing less.'),
          array('icon'=>'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945','c'=>'success','t'=>'Eco Tracking Built-In','b'=>'The Green Pulse module tracks carbon footprint per device and shows real savings from extending hardware life.'),
        );
        foreach($vals as $v):
        ?>
        <div style="display:flex;gap:16px;align-items:flex-start;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;transition:border-color 0.2s;" onmouseover="this.style.borderColor='var(--red)'" onmouseout="this.style.borderColor='var(--border)'">
          <div style="width:42px;height:42px;border-radius:11px;background:var(--<?php echo $v['c']; ?>-dim);color:var(--<?php echo $v['c']; ?>);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $v['icon']; ?>"/></svg>
          </div>
          <div>
            <div style="font-weight:800;font-size:0.92rem;margin-bottom:4px;"><?php echo $v['t']; ?></div>
            <div style="font-size:0.83rem;color:var(--text-sub);line-height:1.6;"><?php echo $v['b']; ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section style="padding:72px 0;background:var(--bg-alt);border-top:1px solid var(--border);border-bottom:1px solid var(--border);">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px;">
      <div class="section-eyebrow">Designed for Everyone</div>
      <h2 class="section-title text-center" style="max-width:500px;margin:0 auto;">Who uses HP TechPulse?</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;">
      <?php
      $roles = array(
        array('icon'=>'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18','c'=>'red','role'=>'Administrator','desc'=>'Full control. Add, edit, and delete devices. Manage all users and roles. View every ticket. Generate PDF device passports. Monitor the entire fleet health from one dashboard.','badge'=>'Full Access'),
        array('icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z','c'=>'blue','role'=>'Employee','desc'=>'View your own assigned devices, check warranty status and health scores, and raise support tickets for any faults. Track the status of your own reports in real time.','badge'=>'Self-Service'),
        array('icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z','c'=>'success','role'=>'IT Support','desc'=>'Manage the full ticket queue. Assign and resolve faults, log maintenance work orders, update device status after repairs, and download audit reports for management review.','badge'=>'Ticket Queue'),
      );
      foreach($roles as $r):
      ?>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px;transition:transform 0.25s,border-color 0.25s;" onmouseover="this.style.transform='translateY(-4px)';this.style.borderColor='var(--<?php echo $r['c']; ?>)'" onmouseout="this.style.transform='translateY(0)';this.style.borderColor='var(--border)'">
        <div style="width:48px;height:48px;border-radius:14px;background:var(--<?php echo $r['c']; ?>-dim);color:var(--<?php echo $r['c']; ?>);display:flex;align-items:center;justify-content:center;margin-bottom:18px;">
          <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?php echo $r['icon']; ?>"/></svg>
        </div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
          <span style="font-size:1.05rem;font-weight:800;"><?php echo $r['role']; ?></span>
          <span class="badge badge-<?php echo $r['c']==='success'?'green':($r['c']==='red'?'red':'blue'); ?>"><?php echo $r['badge']; ?></span>
        </div>
        <p style="font-size:0.85rem;color:var(--text-sub);line-height:1.7;"><?php echo $r['desc']; ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section style="padding:80px 0;">
  <div class="container">
    <div style="text-align:center;margin-bottom:52px;">
      <div class="section-eyebrow">Platform Highlights</div>
      <h2 class="section-title text-center" style="max-width:480px;margin:0 auto;">Built on principles that matter</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;">
      <?php
      $highlights = array(
        array(
          'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4',
          'c'    => 'var(--blue)',
          'bg'   => 'var(--blue-dim)',
          't'    => 'Enterprise Telemetry',
          'd'    => 'Real-time synchronization across all enterprise devices, support requests, and user assignments with automated health metrics.'
        ),
        array(
          'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
          'c'    => '#22c55e',
          'bg'   => 'var(--success-dim)',
          't'    => 'Instant Support Tickets',
          'd'    => 'Submit helpdesk tickets in seconds with automated technician dispatching and real-time status tracking.'
        ),
        array(
          'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
          'c'    => 'var(--warning)',
          'bg'   => 'var(--warning-dim)',
          't'    => 'Warranty Lifecycle Alerts',
          'd'    => 'Track hardware warranty expiration timelines automatically and prevent unexpected coverage gaps across your fleet.'
        ),
        array(
          'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945',
          'c'    => '#06b6d4',
          'bg'   => 'rgba(6,182,212,0.12)',
          't'    => 'Green Pulse Sustainability',
          'd'    => 'Monitor device carbon footprints, track hardware lifecycle extension savings, and view personal environmental ratings.'
        ),
      );
      foreach($highlights as $h):
      ?>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:24px;transition:border-color 0.2s;" onmouseover="this.style.borderColor='<?php echo $h['c']; ?>'" onmouseout="this.style.borderColor='var(--border)'">
        <div style="width:42px;height:42px;border-radius:11px;background:<?php echo $h['bg']; ?>;color:<?php echo $h['c']; ?>;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $h['icon']; ?>"/></svg>
        </div>
        <div style="font-weight:800;font-size:0.94rem;margin-bottom:7px;"><?php echo $h['t']; ?></div>
        <div style="font-size:0.83rem;color:var(--text-sub);line-height:1.65;"><?php echo $h['d']; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section style="padding:80px 0 90px;background:var(--bg-alt);border-top:1px solid var(--border);">
  <div class="container" style="max-width:760px;">
    <div style="text-align:center;margin-bottom:52px;">
      <div class="section-eyebrow">Got Questions?</div>
      <h2 class="section-title text-center">Frequently Asked Questions</h2>
    </div>

    <?php
    $faqs = array(
      array(
        'q' => 'What is HP TechPulse and who is it designed for?',
        'a' => 'HP TechPulse is a unified IT Asset Management & Helpdesk platform engineered for enterprise teams and growing organizations. It gives employees a streamlined self-service portal while providing IT administrators real-time visibility, automated telemetry, and total lifecycle control over every hardware asset.'
      ),
      array(
        'q' => 'How does the Device Health Score work?',
        'a' => 'Every hardware asset is continuously evaluated with a dynamic 0–100 Health Score. This score is computed automatically based on hardware age, active incident tickets, maintenance service history, and warranty status to proactively highlight assets requiring attention before failures occur.'
      ),
      array(
        'q' => 'How does the IT Helpdesk & Ticket Dispatching system function?',
        'a' => 'Employees can log hardware faults in seconds by setting priority and describing the issue. IT Support receives the request instantly and progresses it through 5 structured lifecycle stages: Raised, Assigned, In Progress, Testing, and Resolved, with complete timeline tracking.'
      ),
      array(
        'q' => 'What is Green Pulse and how are environmental scores calculated?',
        'a' => 'Green Pulse is our built-in hardware sustainability module. It calculates manufacturing carbon footprints (CO₂) and tracks cost avoidance when hardware useful life is extended. Every user receives a personalized Eco Grade (A through D) based on fleet health and asset longevity.'
      ),
      array(
        'q' => 'How does Written-Down Value (WDV) depreciation tracking work?',
        'a' => 'HP TechPulse automatically generates complete financial lifecycle schedules using the Written-Down Value method. Depreciation is applied annually as a fixed percentage against remaining book value, providing accounting teams with instant financial insights and residual asset valuations.'
      ),
      array(
        'q' => 'Can administrators export device passports and audit reports?',
        'a' => 'Yes. Administrators can generate comprehensive Device Passports and structured inventory reports containing hardware specifications, custodian assignments, full service history, and financial schedules formatted for internal compliance and executive reviews.'
      ),
      array(
        'q' => 'How is organization data managed and secured?',
        'a' => 'All platform telemetry, user profiles, and asset records are securely managed within your organization\'s private environment. Role-based access control (RBAC) ensures administrators, technicians, and employees access only authorized tools and data.'
      ),
    );
    foreach($faqs as $i=>$faq):
    ?>
    <div class="ctp-faq-item" style="border:1px solid var(--border);border-radius:var(--radius);margin-bottom:10px;background:var(--bg-card);overflow:hidden;transition:border-color 0.25s;">
      <button class="ctp-faq-btn" type="button" style="width:100%;text-align:left;padding:20px 24px;background:none;border:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:16px;color:var(--text);">
        <span style="font-size:0.95rem;font-weight:700;line-height:1.4;"><?php echo $faq['q']; ?></span>
        <span class="ctp-faq-icon" style="width:28px;height:28px;border:1.5px solid var(--border);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;font-weight:700;color:var(--text-muted);transition:all 0.3s;line-height:1;">+</span>
      </button>
      <div class="ctp-faq-body" style="height:0;overflow:hidden;transition:height 0.35s cubic-bezier(0.4,0,0.2,1);">
        <div style="padding:0 24px 20px;font-size:0.9rem;color:var(--text-sub);line-height:1.75;border-top:1px solid var(--border);"><?php echo $faq['a']; ?></div>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="text-align:center;margin-top:40px;">
      <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:16px;">Still have questions? We are happy to help.</p>
      <a href="/HP TechPulse/contact.php" class="btn btn-primary" style="padding:12px 28px;">Contact Us</a>
    </div>
  </div>
</section>

<style>
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
(function() {
  var items = document.querySelectorAll('.ctp-faq-item');

  function closeItem(item) {
    var body = item.querySelector('.ctp-faq-body');
    if (!body) return;
    item.classList.remove('ctp-open');
    body.style.height = '0';
  }

  function openItem(item) {
    var body = item.querySelector('.ctp-faq-body');
    if (!body) return;
    item.classList.add('ctp-open');
    body.style.height = body.scrollHeight + 'px';
  }

  for (var i = 0; i < items.length; i++) {
    (function(item) {
      var btn = item.querySelector('.ctp-faq-btn');
      if (!btn) return;
      btn.addEventListener('click', function() {
        var isOpen = item.classList.contains('ctp-open');
        for (var j = 0; j < items.length; j++) {
          closeItem(items[j]);
        }
        if (!isOpen) {
          openItem(item);
        }
      });
    })(items[i]);
  }
})();
</script>

<?php include 'footer.php'; ?>
