<?php
require_once __DIR__ . '/session_slot.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_is_logged  = isset($_SESSION['tp_user_id']);
$_is_admin   = isset($_SESSION['tp_role']) && $_SESSION['tp_role'] === 'admin';
$_is_support = isset($_SESSION['tp_support']) && (int)$_SESSION['tp_support'] === 1;
$_user_name  = isset($_SESSION['tp_name']) ? $_SESSION['tp_name'] : '';
$_uid        = isset($_SESSION['tp_user_id']) ? (int)$_SESSION['tp_user_id'] : 0;

// Determine which type of header to render
$_public_page = isset($is_public) ? (bool)$is_public : false;
$_nav_active  = isset($active_nav) ? $active_nav : '';
$_page_ttl    = isset($page_title) ? $page_title : 'HP TechPulse';

// Helper to detect active nav link
function nav_active($key, $current) {
    return ($key === $current) ? ' class="active"' : '';
}
?>
<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($_page_ttl); ?> — HP TechPulse</title>
<meta name="description" content="HP TechPulse: Enterprise IT Asset Management — track devices, manage warranties, resolve tickets and measure carbon footprint. 100% offline.">
<meta name="theme-color" content="#e8132a">
<link rel="stylesheet" href="/HP TechPulse/style.css">
<!-- Apply saved theme BEFORE first paint (no flash) -->
<script>
(function(){
  var t = localStorage.getItem('tp_theme');
  if(t === 'light') document.documentElement.classList.add('light-mode');
})();
</script>
</head>
<body id="body-root">

<?php if ($_public_page): ?>

<header class="site-header">
  <div class="container">
    <a href="/HP TechPulse/index.php" class="site-logo" style="text-decoration:none;">
      <div class="site-logo-mark" style="overflow: hidden; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: transparent; border: none;">
        <img src="/HP TechPulse/uploads/HP%20TechPulse%20logo.png" alt="Logo" style="height: 102%; width: 120%; object-fit: cover; object-position: top;">
      </div>
      <span><span style="color:var(--red);font-weight:900;">HP</span> <span style="font-weight:900;">TechPulse</span></span>
    </a>

    <nav class="site-nav">
      <a href="/HP TechPulse/index.php"<?php echo nav_active('home',$_nav_active); ?>>Home</a>
      <a href="/HP TechPulse/about.php"<?php echo nav_active('about',$_nav_active); ?>>About</a>
      <a href="/HP TechPulse/contact.php"<?php echo nav_active('contact',$_nav_active); ?>>Contact</a>
      <a href="/HP TechPulse/feedback.php"<?php echo nav_active('reviews',$_nav_active); ?>>Feedback</a>
      <?php if ($_is_logged): ?>
        <a href="/HP TechPulse/<?php echo $_is_admin?'admin_dashboard.php':'user_dashboard.php'; ?>" style="color:var(--blue);font-weight:700;">
          Go to Dashboard
        </a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <button class="theme-toggle-btn btn btn-ghost btn-icon" title="Toggle Theme" aria-label="Toggle Theme">
        <svg class="ti-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg class="ti-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </button>

      <?php if ($_is_logged): ?>
        <a href="/HP TechPulse/<?php echo $_is_admin?'admin_dashboard.php':'user_dashboard.php'; ?>" class="btn btn-outline" style="font-size:0.82rem;padding:7px 14px;">Dashboard</a>
        <a href="/HP TechPulse/logout.php" class="btn btn-primary" style="font-size:0.82rem;padding:7px 16px;">Sign Out</a>
      <?php else: ?>
        <a href="/HP TechPulse/login.php" class="btn btn-outline" style="font-size:0.82rem;padding:7px 14px;">Login</a>
        <a href="/HP TechPulse/register.php" class="btn btn-primary" style="font-size:0.82rem;padding:7px 16px;">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="public-body">

<?php else: ?>

<div class="dash-layout">

<!-- SIDEBAR -->
<nav class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon" style="overflow: hidden; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: transparent; border: none; padding: 0;">
      <img src="/HP TechPulse/uploads/HP%20TechPulse%20logo.png" alt="Logo" style="height: 120%; width: 120%; object-fit: cover; object-position: top;">
    </div>
    <div class="sidebar-logo-text">
      <span style="font-weight:900;"><span style="color:var(--red);">HP</span> TechPulse</span>
      <em><?php echo $_is_admin ? 'Admin Console' : ($_is_support ? 'IT Support' : 'Employee Portal'); ?></em>
    </div>
  </div>

  <div class="sidebar-nav">
    <?php if ($_is_admin): ?>
    <!-- Admin Navigation -->
    <div class="nav-section">
      <span class="nav-section-label">Overview</span>
      <a href="/HP TechPulse/admin_dashboard.php" class="nav-link<?php echo $_nav_active==='dashboard'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        Dashboard
      </a>
      <a href="/HP TechPulse/admin_analytics.php" class="nav-link<?php echo $_nav_active==='analytics'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Analytics
      </a>
    </div>

    <div class="nav-section">
      <span class="nav-section-label">Assets</span>
      <a href="/HP TechPulse/admin_gadgets.php" class="nav-link<?php echo $_nav_active==='gadgets'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        Device Inventory
      </a>
      <a href="/HP TechPulse/admin_vendors.php" class="nav-link<?php echo $_nav_active==='vendors'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Vendors
      </a>
      <a href="/HP TechPulse/admin_stock.php" class="nav-link<?php echo $_nav_active==='stock'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        Stock / Parts
      </a>
    </div>

    <div class="nav-section">
      <span class="nav-section-label">Operations</span>
      <a href="/HP TechPulse/admin_issues.php" class="nav-link<?php echo $_nav_active==='issues'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Support Tickets
        <?php
        $ni=mysqli_query($conn,"SELECT COUNT(*) FROM issues WHERE status!='resolved'");
        $nc=$ni?mysqli_fetch_row($ni)[0]:0;
        if($nc>0) echo '<span class="nav-badge">'.$nc.'</span>';
        ?>
      </a>
      <a href="/HP TechPulse/admin_maintenance.php" class="nav-link<?php echo $_nav_active==='maintenance'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Maintenance
      </a>
      <a href="/HP TechPulse/admin_users.php" class="nav-link<?php echo $_nav_active==='users'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Users
      </a>
    </div>

    <?php else: ?>
    <!-- User / IT Support Navigation -->
    <div class="nav-section">
      <span class="nav-section-label">My Portal</span>
      <a href="/HP TechPulse/user_dashboard.php" class="nav-link<?php echo $_nav_active==='dashboard'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        My Dashboard
      </a>
      <a href="/HP TechPulse/raise_issue.php" class="nav-link<?php echo $_nav_active==='raise'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Report Issue
      </a>
    </div>

    <?php if ($_is_support): ?>
    <div class="nav-section">
      <span class="nav-section-label">IT Support</span>
      <a href="/HP TechPulse/user_dashboard.php?view=support_queue" class="nav-link<?php echo $_nav_active==='issues'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Support Queue
      </a>
      <a href="/HP TechPulse/user_dashboard.php?view=maintenance_jobs" class="nav-link<?php echo $_nav_active==='maintenance'?' active':''; ?>">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Maintenance Jobs
      </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="nav-section" style="margin-top:auto;">
      <a href="/HP TechPulse/index.php" class="nav-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Home Site
      </a>
      <a href="/HP TechPulse/logout.php" class="nav-link nav-signout">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </a>
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?php echo strtoupper(substr($_user_name,0,1)); ?></div>
      <div>
        <div class="user-name"><?php echo htmlspecialchars($_user_name); ?></div>
        <div class="user-role"><?php echo $_is_admin?'Administrator':($_is_support?'IT Support':'Employee'); ?></div>
      </div>
    </div>
  </div>
</nav>

<!-- MAIN FRAME -->
<div class="main-frame">
  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-title"><?php echo htmlspecialchars($_page_ttl); ?></div>
    <div class="topbar-right">
      <button class="theme-toggle-btn topbar-btn" title="Toggle Theme">
        <svg class="ti-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg class="ti-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Theme
      </button>
      <?php if ($_is_admin): ?>
      <a href="/HP TechPulse/admin_gadgets.php?action=add" class="btn btn-primary btn-sm" style="padding:6px 14px;">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add Device
      </a>
      <?php endif; ?>
    </div>
  </div>
  <!-- Page content starts here -->
  <div class="page-content">
<?php endif; ?>
