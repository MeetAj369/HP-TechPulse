<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

$page_title = 'Home';
$active_nav = 'home';
$is_public  = true;

// Live stats from DB
$sg = mysqli_query($conn,"SELECT COUNT(*) FROM gadgets WHERE status!='retired'");
$su = mysqli_query($conn,"SELECT COUNT(*) FROM users WHERE role='user'");
$si = mysqli_query($conn,"SELECT COUNT(*) FROM issues WHERE status='resolved'");
$sw = mysqli_query($conn,"SELECT COUNT(*) FROM warranties WHERE end_date>=CURDATE()");
$live_gadgets  = $sg ? (int)mysqli_fetch_row($sg)[0] : 18;
$live_users    = $su ? (int)mysqli_fetch_row($su)[0] : 5;
$live_resolved = $si ? (int)mysqli_fetch_row($si)[0] : 2;
$live_warranty = $sw ? (int)mysqli_fetch_row($sw)[0] : 3;

include 'header.php';
?>


<section class="hero" style="position:relative;overflow:hidden;">


  <div class="hero-devices" aria-hidden="true">

    <!-- LEFT SIDE -->
    <div class="hdev hdev-laptop" style="--hdev-delay:0s;left:2%;top:12%;">
      <div class="hdev-icon">
        <svg viewBox="0 0 64 50" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="6" y="4" width="52" height="34" rx="4" fill="currentColor" opacity=".12"/>
          <rect x="6" y="4" width="52" height="34" rx="4" stroke="currentColor" stroke-width="2.5"/>
          <rect x="12" y="9" width="40" height="23" rx="2" fill="currentColor" opacity=".08"/>
          <rect x="12" y="9" width="40" height="23" rx="2" stroke="currentColor" stroke-width="1.5" opacity=".4"/>
          <rect x="16" y="13" width="12" height="3" rx="1.5" fill="currentColor" opacity=".5"/>
          <rect x="16" y="19" width="20" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <rect x="16" y="23" width="16" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <path d="M0 40h64l-4 6H4L0 40z" fill="currentColor" opacity=".08"/>
          <path d="M0 40h64" stroke="currentColor" stroke-width="2.5"/>
          <rect x="26" y="41" width="12" height="3" rx="1.5" fill="currentColor" opacity=".4"/>
        </svg>
      </div>
      <div class="hdev-label">Laptop</div>
    </div>

    <div class="hdev hdev-monitor" style="--hdev-delay:0.6s;left:3.5%;top:52%;">
      <div class="hdev-icon">
        <svg viewBox="0 0 64 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="4" y="4" width="56" height="36" rx="4" fill="currentColor" opacity=".12"/>
          <rect x="4" y="4" width="56" height="36" rx="4" stroke="currentColor" stroke-width="2.5"/>
          <rect x="10" y="9" width="44" height="26" rx="2" fill="currentColor" opacity=".08"/>
          <circle cx="32" cy="43.5" r="5" fill="currentColor" opacity=".15"/>
          <circle cx="32" cy="43.5" r="5" stroke="currentColor" stroke-width="2"/>
          <rect x="20" y="48" width="24" height="3" rx="1.5" fill="currentColor" opacity=".4"/>
          <rect x="14" y="14" width="18" height="3" rx="1.5" fill="currentColor" opacity=".5"/>
          <rect x="14" y="20" width="26" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <rect x="14" y="25" width="20" height="2" rx="1" fill="currentColor" opacity=".3"/>
        </svg>
      </div>
      <div class="hdev-label">Monitor</div>
    </div>

    <div class="hdev hdev-printer" style="--hdev-delay:1.2s;left:1%;top:78%;">
      <div class="hdev-icon">
        <svg viewBox="0 0 64 52" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="8" y="16" width="48" height="26" rx="4" fill="currentColor" opacity=".12"/>
          <rect x="8" y="16" width="48" height="26" rx="4" stroke="currentColor" stroke-width="2.5"/>
          <rect x="14" y="4" width="36" height="16" rx="3" fill="currentColor" opacity=".1"/>
          <rect x="14" y="4" width="36" height="16" rx="3" stroke="currentColor" stroke-width="2"/>
          <rect x="14" y="36" width="36" height="14" rx="3" fill="currentColor" opacity=".08"/>
          <rect x="14" y="36" width="36" height="14" rx="3" stroke="currentColor" stroke-width="1.5" opacity=".5"/>
          <rect x="20" y="40" width="24" height="2" rx="1" fill="currentColor" opacity=".4"/>
          <rect x="20" y="44" width="18" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <circle cx="48" cy="26" r="3" fill="currentColor" opacity=".5"/>
          <circle cx="41" cy="26" r="3" fill="currentColor" opacity=".3"/>
        </svg>
      </div>
      <div class="hdev-label">Printer</div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="hdev hdev-mobile" style="--hdev-delay:0.3s;right:2%;top:8%;">
      <div class="hdev-icon">
        <svg viewBox="0 0 36 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="3" width="30" height="54" rx="6" fill="currentColor" opacity=".12"/>
          <rect x="3" y="3" width="30" height="54" rx="6" stroke="currentColor" stroke-width="2.5"/>
          <rect x="8" y="9" width="20" height="36" rx="2" fill="currentColor" opacity=".08"/>
          <circle cx="18" cy="52" r="3" fill="currentColor" opacity=".4"/>
          <rect x="13" y="5" width="10" height="2" rx="1" fill="currentColor" opacity=".4"/>
          <rect x="10" y="14" width="8" height="8" rx="2" fill="currentColor" opacity=".2"/>
          <rect x="10" y="26" width="16" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <rect x="10" y="30" width="12" height="2" rx="1" fill="currentColor" opacity=".25"/>
        </svg>
      </div>
      <div class="hdev-label">Mobile</div>
    </div>

    <div class="hdev hdev-tablet" style="--hdev-delay:0.9s;right:3%;top:46%;">
      <div class="hdev-icon">
        <svg viewBox="0 0 46 62" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="3" width="40" height="56" rx="5" fill="currentColor" opacity=".12"/>
          <rect x="3" y="3" width="40" height="56" rx="5" stroke="currentColor" stroke-width="2.5"/>
          <rect x="8" y="9" width="30" height="42" rx="2" fill="currentColor" opacity=".08"/>
          <circle cx="23" cy="56" r="2.5" fill="currentColor" opacity=".4"/>
          <rect x="12" y="14" width="22" height="3" rx="1.5" fill="currentColor" opacity=".5"/>
          <rect x="12" y="20" width="18" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <rect x="12" y="24" width="14" height="2" rx="1" fill="currentColor" opacity=".25"/>
          <rect x="12" y="30" width="10" height="10" rx="2" fill="currentColor" opacity=".15"/>
          <rect x="25" y="30" width="9" height="10" rx="2" fill="currentColor" opacity=".1"/>
        </svg>
      </div>
      <div class="hdev-label">Tablet</div>
    </div>

    <div class="hdev hdev-desktop" style="--hdev-delay:1.5s;right:1.5%;top:76%;">
      <div class="hdev-icon">
        <svg viewBox="0 0 60 56" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="4" y="4" width="36" height="28" rx="3" fill="currentColor" opacity=".12"/>
          <rect x="4" y="4" width="36" height="28" rx="3" stroke="currentColor" stroke-width="2.5"/>
          <rect x="8" y="8" width="28" height="19" rx="1.5" fill="currentColor" opacity=".08"/>
          <rect x="8" y="12" width="12" height="3" rx="1.5" fill="currentColor" opacity=".5"/>
          <rect x="8" y="18" width="18" height="2" rx="1" fill="currentColor" opacity=".3"/>
          <rect x="44" y="8" width="12" height="36" rx="3" fill="currentColor" opacity=".12"/>
          <rect x="44" y="8" width="12" height="36" rx="3" stroke="currentColor" stroke-width="2"/>
          <rect x="46" y="12" width="8" height="5" rx="1" fill="currentColor" opacity=".3"/>
          <rect x="46" y="20" width="8" height="2" rx="1" fill="currentColor" opacity=".25"/>
          <rect x="46" y="24" width="8" height="2" rx="1" fill="currentColor" opacity=".2"/>
          <rect x="12" y="36" width="8" height="12" rx="1.5" fill="currentColor" opacity=".2"/>
          <rect x="6" y="46" width="32" height="4" rx="2" fill="currentColor" opacity=".15"/>
          <rect x="6" y="46" width="32" height="4" rx="2" stroke="currentColor" stroke-width="1.5" opacity=".4"/>
        </svg>
      </div>
      <div class="hdev-label">Desktop</div>
    </div>

  </div>


  <div class="hero-content">
    <div class="hero-badge">
      <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
      Corporate Gadget Lifecycle & Health Intelligence Management Platform
    </div>

    <h1 class="hero-title">
      The smarter way to manage<br>
      your <span class="type-wrap"><span id="type-text" class="type-text"></span><span class="type-cursor" id="type-cursor"></span></span>
    </h1>

    <p class="hero-sub">
      HP TechPulse brings device management, IT support, health monitoring, and sustainability insights together in one unified experience for IT teams and employees.
    </p>

    <div class="hero-actions">
      <a href="/HP TechPulse/register.php" class="btn btn-primary" id="cta-main" style="padding:13px 28px;font-size:0.97rem;">
        <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        Get Started Free
      </a>
      <a href="/HP TechPulse/login.php" class="btn btn-outline" style="padding:13px 28px;font-size:0.97rem;">
        Sign In
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>

    <!-- LIVE METRICS STRIP -->
    <div class="metrics-strip">
      <div class="metric-item">
        <div class="metric-val"><span class="grad-text" data-count="<?php echo $live_gadgets; ?>">0</span></div>
        <div class="metric-lbl">Devices Tracked</div>
      </div>
      <div class="metric-item" style="border-left:1px solid var(--border);padding-left:44px;">
        <div class="metric-val"><span class="grad-text" data-count="<?php echo $live_users; ?>">0</span></div>
        <div class="metric-lbl">Active Employees</div>
      </div>
      <div class="metric-item" style="border-left:1px solid var(--border);padding-left:44px;">
        <div class="metric-val"><span class="grad-text" data-count="<?php echo $live_resolved; ?>">0</span></div>
        <div class="metric-lbl">Tickets Resolved</div>
      </div>
      <div class="metric-item" style="border-left:1px solid var(--border);padding-left:44px;">
        <div class="metric-val"><span style="background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">99%</span></div>
        <div class="metric-lbl">Uptime SLA</div>
      </div>
    </div>
  </div>
</section>

<style>

.hero-devices {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.hdev {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  animation: hdevFloat 5s ease-in-out infinite;
  animation-delay: var(--hdev-delay, 0s);
  opacity: 0;
  animation-fill-mode: forwards;
}

/* Fade in on load then keep floating */
@keyframes hdevFloat {
  0%   { transform: translateY(0px);   opacity: 0.55; }
  50%  { transform: translateY(-14px); opacity: 0.75; }
  100% { transform: translateY(0px);   opacity: 0.55; }
}

.hdev-icon {
  width: 72px;
  height: 72px;
  background: var(--bg-card);
  border: 1.5px solid var(--border);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px;
  backdrop-filter: blur(8px);
  box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 0 0 1px rgba(255,255,255,0.04);
  transition: box-shadow 0.3s;
}
.hdev-icon svg {
  width: 100%;
  height: 100%;
}

.hdev-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-muted);
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 3px 10px;
}

/* Device accent colors */
.hdev-laptop  { color: var(--blue);    } .hdev-laptop .hdev-icon  { border-color: rgba(26,107,255,0.25); box-shadow: 0 8px 32px rgba(26,107,255,0.1); }
.hdev-monitor { color: #9333ea;        } .hdev-monitor .hdev-icon { border-color: rgba(147,51,234,0.25); box-shadow: 0 8px 32px rgba(147,51,234,0.1); }
.hdev-printer { color: var(--success); } .hdev-printer .hdev-icon { border-color: rgba(34,197,94,0.25);  box-shadow: 0 8px 32px rgba(34,197,94,0.1); }
.hdev-mobile  { color: var(--red);     } .hdev-mobile .hdev-icon  { border-color: rgba(232,19,42,0.25);  box-shadow: 0 8px 32px rgba(232,19,42,0.1); }
.hdev-tablet  { color: var(--warning); } .hdev-tablet .hdev-icon  { border-color: rgba(245,158,11,0.25); box-shadow: 0 8px 32px rgba(245,158,11,0.1); }
.hdev-desktop { color: #06b6d4;        } .hdev-desktop .hdev-icon { border-color: rgba(6,182,212,0.25);  box-shadow: 0 8px 32px rgba(6,182,212,0.1); }

/* Light mode: softer appearance */
html.light-mode .hdev-icon { box-shadow: 0 6px 24px rgba(0,0,0,0.07); }

/* Hide devices on small screens */
@media(max-width:900px){ .hero-devices { display:none; } }

/* Make hero-content sit above device layer */
.hero-content { position: relative; z-index: 1; }
</style>



<section class="phone-section">
  <div class="phone-section-bg"></div>
  <div class="container">
    <div class="phone-section-inner">
      <!-- Text Left -->
      <div class="phone-section-text">
        <div class="section-eyebrow">Live Asset View</div>
        <h2>Your IT assets.<br><span class="g-text">Always in your pocket.</span></h2>
        <p>Every device assigned to you is just one click away. Check health scores, view warranty status, and raise support tickets — without needing any IT background.</p>

        <div class="feat-chips">
          <div class="feat-chip">
            <div class="feat-chip-icon" style="background:var(--red-dim);">
              <svg fill="none" stroke="var(--red)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
              <div class="feat-chip-text">Real-Time Health Score</div>
              <div class="feat-chip-sub">Instant condition monitoring for every device</div>
            </div>
          </div>
          <div class="feat-chip">
            <div class="feat-chip-icon" style="background:var(--blue-dim);">
              <svg fill="none" stroke="var(--blue)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
              <div class="feat-chip-text">Warranty Countdown</div>
              <div class="feat-chip-sub">Never miss a warranty expiry again</div>
            </div>
          </div>
          <div class="feat-chip">
            <div class="feat-chip-icon" style="background:var(--success-dim);">
              <svg fill="none" stroke="var(--success)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
            </div>
            <div>
              <div class="feat-chip-text">Green Pulse Eco Tracker</div>
              <div class="feat-chip-sub">Carbon footprint and sustainability scoring</div>
            </div>
          </div>
          <div class="feat-chip">
            <div class="feat-chip-icon" style="background:var(--warning-dim);">
              <svg fill="none" stroke="var(--warning)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
              <div class="feat-chip-text">Device Passport PDF</div>
              <div class="feat-chip-sub">Audit-ready PDF report with zero extra libraries</div>
            </div>
          </div>
        </div>
      </div>


      <!-- Multi-Device Showcase (replaces phone-only) -->
      <div class="phone-mockup-wrap" style="perspective:900px;">

        <!-- Floating notification bubbles -->
        <div class="notif notif-1">
          <div class="notif-dot" style="background:var(--success);"></div>
          <div><div class="notif-txt">Warranty Active</div><div class="notif-sub">HP EliteBook 840 &bull; 284 days left</div></div>
        </div>
        <div class="notif notif-2">
          <div class="notif-dot" style="background:var(--blue);"></div>
          <div><div class="notif-txt">Health Score: 87/100</div><div class="notif-sub">Dell Latitude &bull; Good</div></div>
        </div>
        <div class="notif notif-3">
          <div class="notif-dot" style="background:var(--warning);"></div>
          <div><div class="notif-txt">Ticket #42 Resolved</div><div class="notif-sub">Canon Printer &bull; 2h ago</div></div>
        </div>
        <div class="notif notif-4">
          <div class="notif-dot" style="background:var(--red);"></div>
          <div><div class="notif-txt">Maintenance Due</div><div class="notif-sub">HP M479 &bull; Tomorrow</div></div>
        </div>

        <!-- Device Showcase -->
        <div id="device-showcase" style="position:relative;width:280px;height:320px;">

          <!-- DEVICE 0: LAPTOP -->
          <div class="dev-card" id="dev-0" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;transition:opacity 0.5s;">
            <svg width="200" height="130" viewBox="0 0 200 130" fill="none" style="filter:drop-shadow(0 10px 24px rgba(232,19,42,0.3));">
              <rect x="20" y="8" width="160" height="95" rx="8" fill="#12121f" stroke="rgba(255,255,255,0.12)" stroke-width="1.5"/>
              <rect x="26" y="14" width="148" height="83" rx="5" fill="#0d0d1e"/>
              <rect x="33" y="22" width="55" height="6" rx="3" fill="rgba(232,19,42,0.55)"/>
              <rect x="33" y="33" width="134" height="3.5" rx="2" fill="rgba(255,255,255,0.09)"/>
              <rect x="33" y="41" width="110" height="3.5" rx="2" fill="rgba(255,255,255,0.07)"/>
              <rect x="33" y="51" width="134" height="24" rx="5" fill="rgba(26,107,255,0.13)" stroke="rgba(26,107,255,0.22)" stroke-width="0.5"/>
              <rect x="39" y="58" width="48" height="10" rx="3" fill="rgba(26,107,255,0.45)"/>
              <rect x="33" y="82" width="36" height="10" rx="5" fill="rgba(232,19,42,0.55)"/>
              <path d="M8 108 L28 104 L172 104 L192 108 L198 113 L2 113 Z" fill="#0a0a18" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
              <rect x="82" y="104" width="36" height="4" rx="2" fill="rgba(255,255,255,0.05)"/>
            </svg>
            <div style="text-align:center;">
              <div style="font-size:0.9rem;font-weight:800;">HP EliteBook 840 G9</div>
              <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">Laptop &bull; TP-2024-0001</div>
              <div style="display:flex;gap:6px;justify-content:center;margin-top:7px;">
                <span class="badge badge-green">Active</span>
                <span class="badge badge-blue">Health 87%</span>
              </div>
            </div>
          </div>

          <!-- DEVICE 1: MONITOR -->
          <div class="dev-card" id="dev-1" style="position:absolute;inset:0;display:none;flex-direction:column;align-items:center;justify-content:center;gap:10px;">
            <svg width="200" height="140" viewBox="0 0 200 140" fill="none" style="filter:drop-shadow(0 10px 24px rgba(26,107,255,0.3));">
              <rect x="14" y="5" width="172" height="105" rx="9" fill="#12121f" stroke="rgba(255,255,255,0.12)" stroke-width="1.5"/>
              <rect x="20" y="12" width="160" height="92" rx="6" fill="#0d0d1e"/>
              <rect x="30" y="22" width="140" height="38" rx="4" fill="rgba(26,107,255,0.1)" stroke="rgba(26,107,255,0.18)" stroke-width="0.5"/>
              <rect x="36" y="28" width="48" height="7" rx="3" fill="rgba(26,107,255,0.5)"/>
              <rect x="36" y="40" width="128" height="3" rx="1.5" fill="rgba(255,255,255,0.08)"/>
              <rect x="36" y="47" width="95" height="3" rx="1.5" fill="rgba(255,255,255,0.06)"/>
              <rect x="30" y="68" width="42" height="28" rx="4" fill="rgba(34,197,94,0.12)" stroke="rgba(34,197,94,0.25)" stroke-width="0.5"/>
              <rect x="36" y="74" width="30" height="4" rx="2" fill="rgba(34,197,94,0.5)"/>
              <rect x="36" y="81" width="20" height="8" rx="2" fill="rgba(34,197,94,0.3)"/>
              <rect x="80" y="68" width="90" height="28" rx="4" fill="rgba(245,158,11,0.08)" stroke="rgba(245,158,11,0.2)" stroke-width="0.5"/>
              <rect x="92" y="108" width="16" height="16" rx="2" fill="#0a0a18"/>
              <rect x="72" y="122" width="56" height="5" rx="2.5" fill="#0a0a18" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
            </svg>
            <div style="text-align:center;">
              <div style="font-size:0.9rem;font-weight:800;">Samsung 27" Monitor</div>
              <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">Monitor &bull; TP-2024-0005</div>
              <div style="display:flex;gap:6px;justify-content:center;margin-top:7px;">
                <span class="badge badge-green">Active</span>
                <span class="badge badge-cyan">Health 100%</span>
              </div>
            </div>
          </div>

          <!-- DEVICE 2: PRINTER -->
          <div class="dev-card" id="dev-2" style="position:absolute;inset:0;display:none;flex-direction:column;align-items:center;justify-content:center;gap:10px;">
            <svg width="200" height="130" viewBox="0 0 200 130" fill="none" style="filter:drop-shadow(0 10px 24px rgba(245,158,11,0.25));">
              <rect x="30" y="30" width="140" height="60" rx="8" fill="#12121f" stroke="rgba(255,255,255,0.12)" stroke-width="1.5"/>
              <rect x="46" y="38" width="108" height="44" rx="4" fill="rgba(245,158,11,0.04)"/>
              <!-- Paper slot -->
              <rect x="50" y="88" width="100" height="18" rx="4" fill="#0a0a18" stroke="rgba(255,255,255,0.07)" stroke-width="1"/>
              <rect x="58" y="92" width="84" height="3" rx="1.5" fill="rgba(255,255,255,0.05)"/>
              <rect x="58" y="98" width="84" height="3" rx="1.5" fill="rgba(255,255,255,0.04)"/>
              <!-- Paper out -->
              <rect x="66" y="16" width="68" height="18" rx="1" fill="white" opacity="0.92"/>
              <rect x="72" y="20" width="36" height="2" rx="1" fill="rgba(0,0,0,0.12)"/>
              <rect x="72" y="25" width="52" height="2" rx="1" fill="rgba(0,0,0,0.08)"/>
              <!-- Top slot -->  
              <rect x="52" y="28" width="96" height="5" rx="2" fill="#0a0a18" stroke="rgba(255,255,255,0.06)" stroke-width="1"/>
              <!-- Lights -->
              <circle cx="158" cy="52" r="4.5" fill="rgba(34,197,94,0.7)"/>
              <circle cx="158" cy="65" r="4.5" fill="rgba(245,158,11,0.5)"/>
              <rect x="45" y="47" width="98" height="18" rx="3" fill="rgba(245,158,11,0.06)"/>
            </svg>
            <div style="text-align:center;">
              <div style="font-size:0.9rem;font-weight:800;">HP LaserJet M479fdw</div>
              <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">Printer &bull; TP-2024-0008</div>
              <div style="display:flex;gap:6px;justify-content:center;margin-top:7px;">
                <span class="badge badge-amber">Maintenance</span>
                <span class="badge badge-amber">Health 62%</span>
              </div>
            </div>
          </div>

          <!-- DEVICE 3: MOBILE -->
          <div class="dev-card" id="dev-3" style="position:absolute;inset:0;display:none;flex-direction:column;align-items:center;justify-content:center;gap:10px;">
            <svg width="80" height="140" viewBox="0 0 80 140" fill="none" style="filter:drop-shadow(0 10px 24px rgba(147,51,234,0.3));">
              <rect x="5" y="4" width="70" height="132" rx="12" fill="#12121f" stroke="rgba(255,255,255,0.12)" stroke-width="1.5"/>
              <rect x="10" y="14" width="60" height="104" rx="7" fill="#0d0d1e"/>
              <rect x="24" y="6" width="32" height="5" rx="2.5" fill="rgba(0,0,0,0.5)"/>
              <rect x="28" y="36" width="24" height="24" rx="8" fill="url(#pm3)"/>
              <defs><linearGradient id="pm3" x1="28" y1="36" x2="52" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#e8132a"/><stop offset="1" stop-color="#1a6bff"/></linearGradient></defs>
              <rect x="15" y="72" width="50" height="4" rx="2" fill="rgba(255,255,255,0.08)"/>
              <rect x="15" y="80" width="50" height="4" rx="2" fill="rgba(255,255,255,0.06)"/>
              <rect x="15" y="88" width="34" height="4" rx="2" fill="rgba(255,255,255,0.05)"/>
              <rect x="15" y="100" width="50" height="18" rx="5" fill="rgba(26,107,255,0.15)" stroke="rgba(26,107,255,0.2)" stroke-width="0.5"/>
              <rect x="32" y="130" width="16" height="2.5" rx="1.25" fill="rgba(255,255,255,0.18)"/>
            </svg>
            <div style="text-align:center;">
              <div style="font-size:0.9rem;font-weight:800;">Samsung Galaxy A54</div>
              <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">Mobile &bull; TP-2024-0012</div>
              <div style="display:flex;gap:6px;justify-content:center;margin-top:7px;">
                <span class="badge badge-green">Active</span>
                <span class="badge badge-purple">Health 95%</span>
              </div>
            </div>
          </div>

          <!-- dot navigation -->
          <div id="dev-dots" style="position:absolute;bottom:-26px;left:50%;transform:translateX(-50%);display:flex;gap:5px;">
            <span class="dev-dot active" data-dev="0" style="width:20px;height:5px;border-radius:3px;background:var(--red);display:inline-block;cursor:pointer;transition:all 0.3s;"></span>
            <span class="dev-dot" data-dev="1" style="width:7px;height:5px;border-radius:3px;background:var(--border);display:inline-block;cursor:pointer;transition:all 0.3s;"></span>
            <span class="dev-dot" data-dev="2" style="width:7px;height:5px;border-radius:3px;background:var(--border);display:inline-block;cursor:pointer;transition:all 0.3s;"></span>
            <span class="dev-dot" data-dev="3" style="width:7px;height:5px;border-radius:3px;background:var(--border);display:inline-block;cursor:pointer;transition:all 0.3s;"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>




<section class="how-section">
  <div class="container">
    <div class="text-center" style="margin-bottom:0;">
      <div class="section-eyebrow">Simple, Powerful, Offline</div>
      <h2 class="section-title text-center">How HP TechPulse Works</h2>
      <p style="text-align:center;color:var(--text-sub);font-size:0.95rem;max-width:520px;margin:0 auto;">From device entry to resolved tickets — four easy steps that any employee or IT admin can follow without any training.</p>
    </div>

    <div class="how-steps-creative">
      <?php
      $steps = array(
        array('n'=>'01','lbl'=>'Step','title'=>'Register & Sign In','body'=>'Create your secure account with real-time password strength checking. Admins and employees each get a personalized role-based portal.','color'=>'var(--red)','icon'=>'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'),
        array('n'=>'02','lbl'=>'Step','title'=>'View Your Devices','body'=>'See every company gadget assigned to your account — with live health scores, warranty countdowns, and repair history all in one place.','color'=>'#c030b0','icon'=>'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'),
        array('n'=>'03','lbl'=>'Step','title'=>'Report Any Issue','body'=>'Raise a support ticket for any device fault in seconds. Describe the problem, set priority, and IT gets it instantly — no emails needed.','color'=>'#5a30d0','icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'),
        array('n'=>'04','lbl'=>'Step','title'=>'IT Resolves Fast','body'=>'IT Support triages, updates ticket status, logs maintenance work, and downloads the full Device Passport PDF for audit records — all offline.','color'=>'var(--blue)','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'),
      );
      foreach($steps as $i=>$step):
      ?>
      <div class="how-step" data-step="<?php echo $i; ?>">
        <div class="how-step-num" style="border-color:<?php echo $step['color']; ?>20;">
          <div class="how-step-num-inner" style="background:linear-gradient(135deg,<?php echo $step['color']; ?>,transparent);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"><?php echo $step['n']; ?></div>
          <div class="how-step-num-label"><?php echo $step['lbl']; ?></div>
          <!-- Icon badge -->
          <div class="how-step-icon" style="background:<?php echo $step['color']; ?>;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $step['icon']; ?>"/></svg>
          </div>
        </div>
        <?php if($i<3): ?>
        <div class="how-step-arrow">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
        <?php endif; ?>
        <h3><?php echo $step['title']; ?></h3>
        <p><?php echo $step['body']; ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Interactive demo bar -->
    <div style="margin-top:50px;background:var(--bg-alt);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px 36px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
      <div style="flex:1;min-width:200px;">
        <div style="font-size:0.8rem;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:4px;">Green Pulse Live Demo</div>
        <div style="font-size:1.05rem;font-weight:800;">Extend device lifetime by <span id="life-years" style="color:var(--success);">2 years</span></div>
        <div style="font-size:0.82rem;color:var(--text-sub);margin-top:3px;">Move the slider to see CO2 savings</div>
      </div>
      <div style="flex:2;min-width:260px;">
        <input type="range" class="sim-slider" id="life-slider" min="1" max="5" value="2" step="1">
        <div class="sim-results">
          <div class="sim-card">
            <div class="sim-val green" id="co2-save">320 kg</div>
            <div class="sim-lbl">CO2 Saved vs New Device</div>
          </div>
          <div class="sim-card">
            <div class="sim-val blue" id="cost-save">Rs. 54,000</div>
            <div class="sim-lbl">Procurement Cost Saved</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>



<section class="features-section" style="background:var(--bg-alt);border-top:1px solid var(--border);">
  <div class="container">
    <div class="section-eyebrow">What HP TechPulse Does</div>
    <h2 class="section-title" style="max-width:520px;">One platform. Every IT challenge.<br>Solved.</h2>
    <div class="features-grid">
      <?php
      $feats = array(
        array('icon'=>'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z','color'=>'var(--red)','bg'=>'var(--red-dim)','title'=>'Device Inventory','body'=>'Register every company device — laptop, monitor, printer, mobile — with brand, model, serial, vendor, and purchase details. Always sorted newest first.'),
        array('icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','color'=>'var(--warning)','bg'=>'var(--warning-dim)','title'=>'Support Ticket System','body'=>'Employees report device faults in seconds. IT resolves them through a clear 5-stage workflow: Raised → Assigned → In Progress → Testing → Resolved.'),
        array('icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z','color'=>'var(--success)','bg'=>'var(--success-dim)','title'=>'Device Health Scoring','body'=>'Each device gets a live health score (0–100) calculated from age, repair count, open faults, and warranty status — displayed as a color-coded progress bar.'),
        array('icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','color'=>'var(--blue)','bg'=>'var(--blue-dim)','title'=>'Role-Based Access','body'=>'Admins manage all devices and users. Employees only see their assigned devices and their own tickets. IT Support sees the full ticket queue. Zero overlap.'),
        array('icon'=>'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064','color'=>'#22c55e','bg'=>'var(--success-dim)','title'=>'Green Pulse Eco Tracker','body'=>'Track the CO2 footprint of every device in your company. Use the lifetime simulator to see exactly how much carbon and money is saved by extending device use.'),
        array('icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','color'=>'#9333ea','bg'=>'rgba(147,51,234,0.12)','title'=>'Device Passport PDF','body'=>'Generate a professional PDF report for any device — health score, warranty dates, depreciation schedule, and full issue history — no extra software needed.'),
      );
      foreach($feats as $f):
      ?>
      <div class="feature-card">
        <div class="feature-icon" style="background:<?php echo $f['bg']; ?>;">
          <svg fill="none" stroke="<?php echo $f['color']; ?>" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo $f['icon']; ?>"/></svg>
        </div>
        <h3><?php echo $f['title']; ?></h3>
        <p><?php echo $f['body']; ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>



<section style="padding:80px 0;text-align:center;position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 60% 80% at 50% 50%,rgba(232,19,42,0.08) 0%,transparent 70%);pointer-events:none;"></div>
  <div class="container" style="position:relative;z-index:1;">
    <div class="section-eyebrow">Ready?</div>
    <h2 class="section-title text-center" style="max-width:580px;margin:0 auto 14px;">Start managing your IT assets the smart way</h2>
    <p style="color:var(--text-sub);margin-bottom:30px;font-size:0.95rem;">Join HP TechPulse and bring complete order to your company's devices, tickets, and sustainability goals.</p>
    <div class="hero-actions">
      <a href="/HP TechPulse/register.php" class="btn btn-primary" style="padding:12px 28px;font-size:0.95rem;">Create Free Account</a>
      <a href="/HP TechPulse/about.php"    class="btn btn-outline"  style="padding:12px 28px;font-size:0.95rem;">Learn More</a>
    </div>
  </div>
</section>



<script>

(function(){
  var words = [
    'company hardware',
    'IT asset lifecycle',
    'support tickets',
    'device warranties',
    'carbon footprint',
    'your entire fleet'
  ];
  var el     = document.getElementById('type-text');
  var cursor = document.getElementById('type-cursor');
  if(!el) return;
  var wi = 0, ci = 0, deleting = false, pausing = false;

  function tick(){
    if(pausing) return;
    var w = words[wi];
    if(!deleting){
      ci++;
      el.textContent = w.substring(0, ci);
      if(ci >= w.length){
        pausing = true;
        setTimeout(function(){ deleting = true; pausing = false; setTimeout(tick, 80); }, 2200);
        return;
      }
      setTimeout(tick, 70 + Math.random()*50);
    } else {
      ci--;
      el.textContent = w.substring(0, ci);
      if(ci <= 0){
        deleting = false;
        wi = (wi + 1) % words.length;
        setTimeout(tick, 500);
        return;
      }
      setTimeout(tick, 40 + Math.random()*20);
    }
  }
  // Start first word after a short delay so page loads first
  setTimeout(tick, 800);
})();


(function(){
  var els = document.querySelectorAll('[data-count]');
  function countUp(el){
    var target = parseInt(el.getAttribute('data-count'));
    var dur = 1600; var start = Date.now();
    function frame(){
      var pct = Math.min((Date.now()-start)/dur, 1);
      var ease = 1 - Math.pow(1-pct, 3);
      el.textContent = Math.floor(ease * target);
      if(pct<1) requestAnimationFrame(frame);
      else el.textContent = target;
    }
    frame();
  }
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if(e.isIntersecting){ countUp(e.target); io.unobserve(e.target); } });
  }, {threshold:0.6});
  els.forEach(function(el){ io.observe(el); });
})();


(function(){
  var slider = document.getElementById('life-slider');
  var lyEl   = document.getElementById('life-years');
  var co2El  = document.getElementById('co2-save');
  var costEl = document.getElementById('cost-save');
  if(!slider) return;
  function update(){
    var y = parseInt(slider.value);
    lyEl.textContent   = y + (y===1?' year':' years');
    var co2 = y * 160;
    var cost = y * 27000;
    co2El.textContent  = co2 + ' kg';
    costEl.textContent = 'Rs. ' + cost.toLocaleString();
  }
  slider.addEventListener('input', update);
  update();
})();


(function(){
  var steps = document.querySelectorAll('.how-step');
  steps.forEach(function(s){
    s.addEventListener('mouseenter', function(){
      steps.forEach(function(x){ x.style.opacity='0.5'; });
      s.style.opacity='1';
    });
    s.addEventListener('mouseleave', function(){
      steps.forEach(function(x){ x.style.opacity='1'; });
    });
  });
})();


(function(){
  var total = 4;
  var current = 0;
  var timer = null;

  function showDev(n){
    for(var i=0;i<total;i++){
      var card = document.getElementById('dev-' + i);
      var dot  = document.querySelector('[data-dev="' + i + '"]');
      if(card){
        card.style.display = (i===n) ? 'flex' : 'none';
        card.style.opacity = (i===n) ? '1' : '0';
      }
      if(dot){
        if(i===n){
          dot.style.width  = '20px';
          dot.style.background = 'var(--red)';
        } else {
          dot.style.width  = '7px';
          dot.style.background = 'var(--border)';
        }
      }
    }
    current = n;
  }

  function next(){ showDev((current+1) % total); }

  // Auto-rotate every 3 seconds
  timer = setInterval(next, 3000);

  // Dot clicks
  document.querySelectorAll('.dev-dot').forEach(function(dot){
    dot.addEventListener('click', function(){
      clearInterval(timer);
      showDev(parseInt(this.getAttribute('data-dev')));
      timer = setInterval(next, 3000);
    });
  });

  showDev(0); // start
})();



</script>
<?php include 'footer.php'; ?>
