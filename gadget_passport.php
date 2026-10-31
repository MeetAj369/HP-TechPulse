<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_login();

if (!isset($_GET['id'])) {
    die('<p style="font-family:sans-serif;color:red;padding:40px;">Error: No device ID provided.</p>');
}
$gid        = (int)$_GET['id'];
$role       = current_role();
$uid        = current_uid();
$is_support = current_is_support();

// ── Fetch gadget ─────────────────────────────────────────────────
$gad_q = mysqli_query($conn,
    "SELECT g.*, v.name AS vendor_name, u.name AS assigned_name, u.email AS assigned_email, u.department AS assigned_dept
     FROM gadgets g
     LEFT JOIN vendors v ON g.vendor_id = v.id
     LEFT JOIN users u ON g.assigned_to = u.id
     WHERE g.id = $gid"
);
$gad = mysqli_fetch_assoc($gad_q);
if (!$gad) {
    die('<p style="font-family:sans-serif;color:red;padding:40px;">Error: Device not found.</p>');
}

// ── Access control ────────────────────────────────────────────────
if ($role === 'user' && !$is_support && (int)$gad['assigned_to'] !== $uid) {
    die('<p style="font-family:sans-serif;color:red;padding:40px;">Access denied: This passport belongs to another device.</p>');
}

// ── Computed data ─────────────────────────────────────────────────
$hs       = calculate_health_score($conn, $gid);
$dep_rows = calculate_depreciation($gad['purchase_price'], $gad['depreciation_rate'], $gad['useful_life_yrs']);

// Warranties
$wars = array();
$wq = mysqli_query($conn, "SELECT * FROM warranties WHERE gadget_id = $gid ORDER BY end_date ASC");
if ($wq) { while ($w = mysqli_fetch_assoc($wq)) { $wars[] = $w; } }

// Issues
$issues = array();
$iq = mysqli_query($conn, "SELECT i.*, u.name AS raised_name FROM issues i JOIN users u ON i.raised_by = u.id WHERE i.gadget_id = $gid ORDER BY i.raised_at DESC LIMIT 5");
if ($iq) { while ($i = mysqli_fetch_assoc($iq)) { $issues[] = $i; } }

// Maintenance / repair costs
$total_rep = 0.0;
$cq = mysqli_query($conn, "SELECT SUM(cost) FROM maintenance WHERE gadget_id = $gid AND completed_date IS NOT NULL");
if ($cq) { $total_rep = (float)mysqli_fetch_row($cq)[0]; }
$tco = (float)$gad['purchase_price'] + $total_rep;

// Status colour map
$status_map = array(
    'active'   => array('color'=>'#22c55e','bg'=>'rgba(34,197,94,0.12)',  'label'=>'Active'),
    'inactive' => array('color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.12)', 'label'=>'Inactive'),
    'retired'  => array('color'=>'#ef4444','bg'=>'rgba(239,68,68,0.12)',  'label'=>'Retired'),
    'repair'   => array('color'=>'#3b82f6','bg'=>'rgba(59,130,246,0.12)', 'label'=>'In Repair'),
);
$st_raw  = strtolower($gad['status']);
$st_info = isset($status_map[$st_raw]) ? $status_map[$st_raw] : array('color'=>'#94a3b8','bg'=>'rgba(148,163,184,0.12)','label'=>ucfirst($st_raw));

// Health colour
$hcolor_map = array(
    'Excellent'       => '#22c55e',
    'Good'            => '#3b82f6',
    'Needs Attention' => '#f59e0b',
    'Replace Soon'    => '#ef4444',
);
$hcolor = isset($hcolor_map[$hs['verdict']]) ? $hcolor_map[$hs['verdict']] : '#94a3b8';

// Passport document number
$doc_num = 'TP-' . strtoupper(substr(md5($gid . $gad['asset_tag']), 0, 8));

// Logo path
$logo_path = '/HP TechPulse/uploads/HP TechPulse logo.png';

// QR data — encode key device identity
$qr_data = 'HP TechPulse | ' . $gad['name'] . ' | ' . $gad['asset_tag'] . ' | S/N: ' . ($gad['serial_number'] ? $gad['serial_number'] : 'N/A');

// Back URL & PDF Download URL
$back_url = 'gadget_profile.php?id=' . $gid;
$pdf_url  = 'gadget_passport_pdf.php?id=' . $gid;
$token = isset($_SESSION['tp_token']) ? $_SESSION['tp_token'] : (isset($GLOBALS['tp_auth_token']) ? $GLOBALS['tp_auth_token'] : tp_get_request_token());
if ($token) {
    $back_url .= '&_t=' . urlencode($token);
    $pdf_url  .= '&_t=' . urlencode($token);
}

$page_title = 'Device Passport — ' . htmlspecialchars($gad['name']);
$active_nav = ($role === 'admin') ? 'gadgets' : 'dashboard';
include 'header.php';
?>

<style>
.passport-wrap {
  max-width: 900px;
  margin: 0 auto;
}

@media (max-width: 992px) {
  .passport-wrap { width: 100% !important; max-width: 100% !important; }
  .fin-grid { grid-template-columns: 1fr !important; }
  .specs-grid { grid-template-columns: 1fr !important; }
  .passport-header { flex-direction: column !important; align-items: flex-start !important; gap: 16px !important; padding: 20px 24px !important; }
  .passport-header-right { align-items: flex-start !important; width: 100% !important; flex-direction: row !important; justify-content: space-between !important; }
  .passport-body { padding: 0 20px 24px !important; }
  .passport-footer-section { flex-direction: column !important; align-items: flex-start !important; gap: 20px !important; }
  .glass-card { overflow-x: auto !important; }
}

.passport {
  background: linear-gradient(145deg, #0f172a 0%, #0d1321 100%);
  border: 1px solid var(--border2);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: var(--shadow), 0 0 0 1px rgba(232,19,42,0.08);
}

.passport-header {
  background: linear-gradient(135deg, #0f172a 0%, #1a1032 50%, #0f172a 100%);
  border-bottom: 1px solid var(--border);
  padding: 28px 36px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  position: relative;
  overflow: hidden;
}
.passport-header::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 80% 50%, rgba(232,19,42,0.06) 0%, transparent 60%);
  pointer-events: none;
}
.passport-header-left { flex: 1; }
.passport-header-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}
.passport-header-logo .brand-text {
  font-size: 1.15rem;
  font-weight: 900;
  color: #fff;
  letter-spacing: -0.02em;
}
.passport-header-logo .divider {
  width: 1px;
  height: 28px;
  background: rgba(255,255,255,0.2);
}
.passport-header-logo .doc-type {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.passport-device-name {
  font-size: 1.6rem;
  font-weight: 800;
  color: #fff;
  letter-spacing: -0.03em;
  line-height: 1.2;
  margin-bottom: 8px;
}
.passport-device-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}
.passport-meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.74rem;
  font-weight: 600;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.05);
  color: var(--text2);
}
.passport-meta-pill svg { flex-shrink: 0; }

.passport-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}
.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.passport-header-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
  flex-shrink: 0;
}
.qr-box {
  background: #fff;
  border-radius: 12px;
  padding: 10px;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.qr-box canvas, .qr-box img { width: 100px !important; height: 100px !important; }
.passport-doc-info {
  text-align: right;
}
.passport-doc-number {
  font-size: 0.68rem;
  font-weight: 800;
  color: var(--text3);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-family: 'Courier New', monospace;
}
.passport-doc-date {
  font-size: 0.72rem;
  color: var(--text3);
  margin-top: 2px;
}

.passport-body {
  padding: 0 36px 36px;
}
.passport-section {
  margin-top: 28px;
}
.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--text3);
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
}
.section-title svg { color: var(--primary); }

.glass-card {
  background: var(--glass);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  transition: border-color 0.2s;
}

.specs-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  background: var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border);
}
.spec-item {
  background: var(--glass);
  padding: 14px 18px;
  transition: background 0.15s;
}
.spec-label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text3);
  margin-bottom: 4px;
}
.spec-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text);
}
.spec-value.mono {
  font-family: 'Courier New', monospace;
  font-size: 0.82rem;
  color: var(--blue);
}

.health-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
.health-bar-wrap { flex: 1; }
.health-bar-track {
  height: 10px;
  background: rgba(255,255,255,0.07);
  border-radius: 99px;
  overflow: hidden;
}
.health-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.8s ease;
}
.health-score-big {
  font-size: 2.2rem;
  font-weight: 900;
  letter-spacing: -0.04em;
  line-height: 1;
}
.health-verdict {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-top: 2px;
}

.fin-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}
.fin-card {
  background: var(--glass);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px;
  text-align: center;
}
.fin-card .fin-label {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.09em;
  color: var(--text3);
  margin-bottom: 6px;
}
.fin-card .fin-value {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.02em;
}

.dep-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}
.dep-table th {
  padding: 9px 12px;
  text-align: left;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text3);
  border-bottom: 1px solid var(--border);
  background: rgba(255,255,255,0.02);
}
.dep-table td {
  padding: 10px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  color: var(--text2);
}
.dep-table td.dep-neg { color: #ef4444; font-weight: 700; }
.dep-table td.dep-val { color: var(--text); font-weight: 700; }
.dep-table td.dep-pct { color: var(--text3); }

.warranty-item, .issue-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}
.warranty-name, .issue-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 2px;
}
.warranty-dates, .issue-date {
  font-size: 0.72rem;
  color: var(--text3);
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 700;
  white-space: nowrap;
}
.badge-success { background: rgba(34,197,94,0.12); color: #22c55e; border: 1px solid rgba(34,197,94,0.2); }
.badge-warning { background: rgba(245,158,11,0.12); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); }
.badge-danger  { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
.badge-blue    { background: rgba(59,130,246,0.12); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); }
.badge-gray    { background: rgba(148,163,184,0.1); color: #94a3b8; border: 1px solid rgba(148,163,184,0.15); }
.no-data {
  font-size: 0.82rem;
  color: var(--text3);
  padding: 12px 0;
  font-style: italic;
}

.passport-footer-section {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 40px;
}
.sig-block { flex: 1; }
.sig-line {
  width: 100%;
  max-width: 200px;
  height: 1px;
  background: var(--border2);
  margin-bottom: 6px;
  margin-top: 32px;
}
.sig-label {
  font-size: 0.68rem;
  color: var(--text3);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
}
.passport-stamp {
  text-align: center;
  padding: 12px 20px;
  border: 2px dashed rgba(232,19,42,0.3);
  border-radius: var(--radius);
  color: rgba(232,19,42,0.5);
}
.passport-stamp .stamp-big {
  font-size: 1.1rem;
  font-weight: 900;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--primary);
  opacity: 0.7;
}
.passport-stamp .stamp-sub {
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--text3);
  margin-top: 2px;
}
.passport-generated {
  font-size: 0.68rem;
  color: var(--text3);
  text-align: center;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid rgba(255,255,255,0.04);
}

html.light-mode .passport {
  background: #ffffff !important;
  border-color: #cbd5e1 !important;
  box-shadow: 0 10px 40px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.04) !important;
}
html.light-mode .passport-header {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  border-bottom-color: #cbd5e1 !important;
}
html.light-mode .passport-header-logo .brand-text {
  color: #0f172a !important;
}
html.light-mode .passport-header-logo .divider {
  background: rgba(0,0,0,0.15) !important;
}
html.light-mode .passport-device-name {
  color: #0f172a !important;
}
html.light-mode .passport-meta-pill {
  background: #e2e8f0 !important;
  border-color: #cbd5e1 !important;
  color: #334155 !important;
}
html.light-mode .passport-doc-number {
  color: #2563eb !important;
}
html.light-mode .passport-doc-date {
  color: #475569 !important;
}
html.light-mode .qr-box {
  background: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  box-shadow: 0 4px 14px rgba(0,0,0,0.06) !important;
}
html.light-mode .section-title {
  color: #475569 !important;
  border-bottom-color: #e2e8f0 !important;
}
html.light-mode .glass-card {
  background: #ffffff !important;
  border-color: #e2e8f0 !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02) !important;
}
html.light-mode .specs-grid {
  background: #e2e8f0 !important;
  border-color: #e2e8f0 !important;
}
html.light-mode .spec-item {
  background: #ffffff !important;
}
html.light-mode .spec-item:hover {
  background: #f8fafc !important;
}
html.light-mode .spec-label {
  color: #64748b !important;
}
html.light-mode .spec-value {
  color: #0f172a !important;
}
html.light-mode .spec-value.mono {
  color: #2563eb !important;
}
html.light-mode .health-bar-track {
  background: #e2e8f0 !important;
}
html.light-mode .breakdown-row {
  background: #f8fafc !important;
  border-color: #e2e8f0 !important;
}
html.light-mode .breakdown-label {
  color: #334155 !important;
}
html.light-mode .breakdown-note {
  color: #64748b !important;
}
html.light-mode .fin-card {
  background: #f8fafc !important;
  border-color: #e2e8f0 !important;
}
html.light-mode .fin-card .fin-label {
  color: #64748b !important;
}
html.light-mode .fin-card .fin-value {
  color: #0f172a !important;
}
html.light-mode .dep-table th {
  background: #f1f5f9 !important;
  color: #475569 !important;
  border-bottom-color: #e2e8f0 !important;
}
html.light-mode .dep-table td {
  color: #334155 !important;
  border-bottom-color: #f1f5f9 !important;
}
html.light-mode .dep-table tr:hover td {
  background: #f8fafc !important;
}
html.light-mode .dep-table td.dep-val {
  color: #0f172a !important;
}
html.light-mode .dep-table td.dep-pct {
  color: #64748b !important;
}
html.light-mode .warranty-item,
html.light-mode .issue-item {
  border-bottom-color: #e2e8f0 !important;
}
html.light-mode .warranty-name,
html.light-mode .issue-title {
  color: #0f172a !important;
}
html.light-mode .warranty-dates,
html.light-mode .issue-date,
html.light-mode .no-data {
  color: #64748b !important;
}
html.light-mode .passport-footer-section {
  border-top-color: #e2e8f0 !important;
}
html.light-mode .sig-line {
  background: #cbd5e1 !important;
}
html.light-mode .sig-label {
  color: #64748b !important;
}
html.light-mode .passport-stamp {
  border-color: rgba(232,19,42,0.4) !important;
  background: rgba(232,19,42,0.02) !important;
}
html.light-mode .passport-stamp .stamp-sub {
  color: #64748b !important;
}
html.light-mode .passport-generated {
  border-top-color: #e2e8f0 !important;
  color: #64748b !important;
}
</style>

<div class="page-header" style="margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
    <div class="page-header-left">
      <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:4px;"><?php echo htmlspecialchars($gad['name']); ?> — Passport</h1>
      <p style="color:var(--text-sub); font-size:0.9rem;">
        Asset Tag: <strong><?php echo htmlspecialchars($gad['asset_tag']); ?></strong> &bull; 
        Category: <strong><?php echo htmlspecialchars($gad['category']); ?></strong> &bull; 
        Serial: <strong><?php echo htmlspecialchars($gad['serial_number'] ? $gad['serial_number'] : 'N/A'); ?></strong>
      </p>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
      <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-outline" style="font-size:0.85rem; padding:8px 16px;">
        &larr; Back to Device
      </a>
      <a href="<?php echo htmlspecialchars($pdf_url); ?>" class="btn btn-primary" id="btn-download-passport" style="font-size:0.85rem; padding:8px 18px;">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Download Passport PDF
      </a>
    </div>
  </div>

  <div class="passport-wrap">
  <div class="passport" id="passport-document">

    <div class="passport-header">
      <div class="passport-header-left">

        <!-- Branding -->
        <div class="passport-header-logo" style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
          <div style="overflow: hidden; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background: transparent; border: none; flex-shrink:0;">
            <img src="<?php echo $logo_path; ?>" alt="HP TechPulse Logo" style="height: 102%; width: 120%; object-fit: cover; object-position: top;">
          </div>
          <div class="brand-text" style="font-size:1.15rem;font-weight:900;letter-spacing:-0.02em;"><span style="color:var(--primary);font-weight:900;">HP</span> <span class="brand-tp-text">TechPulse</span></div>
          <div class="divider"></div>
          <div class="doc-type">Device Passport</div>
        </div>

        <!-- Device identity -->
        <div class="passport-device-name"><?php echo htmlspecialchars($gad['name']); ?></div>

        <div class="passport-device-meta">
          <span class="passport-meta-pill">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/></svg>
            <?php echo htmlspecialchars($gad['asset_tag']); ?>
          </span>
          <?php if ($gad['category']): ?>
          <span class="passport-meta-pill">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
            <?php echo htmlspecialchars($gad['category']); ?>
          </span>
          <?php endif; ?>
          <?php if ($gad['brand']): ?>
          <span class="passport-meta-pill">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <?php echo htmlspecialchars($gad['brand']); ?>
          </span>
          <?php endif; ?>
          <?php if ($gad['assigned_name']): ?>
          <span class="passport-meta-pill">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <?php echo htmlspecialchars($gad['assigned_name']); ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Status badge -->
        <span class="passport-status-badge" style="background:<?php echo $st_info['bg']; ?>;color:<?php echo $st_info['color']; ?>;">
          <span class="status-dot" style="background:<?php echo $st_info['color']; ?>;"></span>
          <?php echo htmlspecialchars($st_info['label']); ?>
        </span>

      </div><!-- /.passport-header-left -->

      <!-- Right: QR Code + Doc Info -->
      <div class="passport-header-right">
        <div class="qr-box" id="qr-container" title="Scan to verify device authenticity">
          <img src="/HP TechPulse/uploads/Qr_code.png" alt="QR Code" style="width:100%;height:100%;object-fit:contain;display:block;">
        </div>
        <div class="passport-doc-info">
          <div class="passport-doc-number">Doc: <?php echo $doc_num; ?></div>
          <div class="passport-doc-date">Generated: <?php echo date('d M Y, H:i'); ?></div>
          <div class="passport-doc-date">HP TechPulse</div>
        </div>
      </div>

    </div><!-- /.passport-header -->

    <div class="passport-body">

      <!-- Photo Display Card (if photo uploaded) -->
      <?php if (!empty($gad['image']) && file_exists('uploads/' . $gad['image'])): ?>
      <div class="passport-section">
        <div class="section-title">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Device Appearance &amp; Physical Verification
        </div>
        <div class="glass-card" style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
          <img src="/HP TechPulse/uploads/<?php echo htmlspecialchars($gad['image']); ?>"
               alt="<?php echo htmlspecialchars($gad['name']); ?>"
               style="max-width:180px;max-height:140px;object-fit:contain;border-radius:12px;border:1px solid var(--border);padding:6px;background:var(--bg2);">
          <div style="flex:1;min-width:200px;">
            <div style="font-size:0.95rem;font-weight:700;margin-bottom:4px;color:var(--text);"><?php echo htmlspecialchars($gad['name']); ?></div>
            <div style="font-size:0.8rem;color:var(--text2);margin-bottom:12px;">Physical inspection asset record &bull; Photo verified by IT Asset Manager</div>
            <span class="badge badge-success">Photo Record Verified</span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="passport-section">
        <div class="section-title">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
          Device Specifications
        </div>
        <div class="specs-grid">
          <div class="spec-item">
            <div class="spec-label">Brand / Manufacturer</div>
            <div class="spec-value"><?php echo htmlspecialchars($gad['brand'] ? $gad['brand'] : 'N/A'); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Model</div>
            <div class="spec-value"><?php echo htmlspecialchars($gad['model'] ? $gad['model'] : $gad['name']); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Serial Number</div>
            <div class="spec-value mono"><?php echo htmlspecialchars($gad['serial_number'] ? $gad['serial_number'] : 'N/A'); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Asset Tag</div>
            <div class="spec-value mono"><?php echo htmlspecialchars($gad['asset_tag']); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Category</div>
            <div class="spec-value"><?php echo htmlspecialchars($gad['category']); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Department</div>
            <div class="spec-value"><?php echo htmlspecialchars($gad['assigned_dept'] ? $gad['assigned_dept'] : ($gad['assigned_to'] ? 'Assigned' : 'Unassigned')); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Assigned Custodian</div>
            <div class="spec-value"><?php echo htmlspecialchars($gad['assigned_name'] ? $gad['assigned_name'] : 'Unassigned'); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Vendor / Supplier</div>
            <div class="spec-value"><?php echo htmlspecialchars($gad['vendor_name'] ? $gad['vendor_name'] : 'N/A'); ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Purchase Date</div>
            <div class="spec-value"><?php echo ($gad['purchase_date'] && $gad['purchase_date'] !== '0000-00-00') ? date('d M Y', strtotime($gad['purchase_date'])) : 'N/A'; ?></div>
          </div>
          <div class="spec-item">
            <div class="spec-label">Purchase Cost</div>
            <div class="spec-value"><?php echo $gad['purchase_price'] > 0 ? 'Rs. ' . number_format((float)$gad['purchase_price'], 2) : 'N/A'; ?></div>
          </div>
        </div>
      </div>

      <!-- ══ SECTION 2: HEALTH SCORE ══ -->
      <div class="passport-section">
        <div class="section-title">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Health Score &amp; Condition Monitoring
        </div>
        <div class="glass-card">
          <div class="health-row">
            <div class="health-bar-wrap">
              <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:0.8rem;font-weight:700;color:var(--text);">Overall Device Condition</span>
                <span style="font-size:0.8rem;font-weight:800;color:<?php echo $hcolor; ?>;"><?php echo $hs['score']; ?>/100</span>
              </div>
              <div class="health-bar-track">
                <div class="health-bar-fill" style="width:<?php echo $hs['score']; ?>%;background:<?php echo $hcolor; ?>;"></div>
              </div>
            </div>
            <div style="text-align:right;">
              <div class="health-score-big" style="color:<?php echo $hcolor; ?>;"><?php echo $hs['score']; ?></div>
              <div class="health-verdict" style="color:<?php echo $hcolor; ?>;"><?php echo htmlspecialchars($hs['verdict']); ?></div>
            </div>
          </div>

          <!-- Breakdown list -->
          <div class="health-breakdown">
            <?php foreach ($hs['breakdown'] as $b):
              $b_lbl = isset($b['label']) ? $b['label'] : (isset($b['factor']) ? $b['factor'] : '');
              $b_pts = isset($b['delta']) ? (float)$b['delta'] : (isset($b['points']) ? (float)$b['points'] : 0);
            ?>
            <div class="breakdown-row">
              <span class="breakdown-label"><?php echo htmlspecialchars($b_lbl); ?></span>
              <span class="breakdown-note"><?php echo htmlspecialchars(isset($b['note']) ? $b['note'] : ''); ?></span>
              <span class="breakdown-delta" style="color:<?php echo $b_pts >= 0 ? '#22c55e' : '#ef4444'; ?>;">
                <?php echo ($b_pts >= 0 ? '+' : '') . $b_pts; ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ══ SECTION 3: FINANCIAL VALUATION & DEPRECIATION ══ -->
      <div class="passport-section">
        <div class="section-title">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Financial Valuation &amp; Depreciation Schedule (WDV Method)
        </div>
        <div class="fin-grid">
          <div class="fin-card">
            <div class="fin-label">Original Cost</div>
            <div class="fin-value">Rs. <?php echo number_format((float)$gad['purchase_price'], 2); ?></div>
          </div>
          <div class="fin-card">
            <div class="fin-label">Total Repair Cost</div>
            <div class="fin-value" style="color:var(--warning);">Rs. <?php echo number_format($total_rep, 2); ?></div>
          </div>
          <div class="fin-card">
            <div class="fin-label">Total Cost of Ownership</div>
            <div class="fin-value" style="color:var(--blue);">Rs. <?php echo number_format($tco, 2); ?></div>
          </div>
        </div>

        <!-- Depreciation table -->
        <div class="glass-card" style="padding:0;overflow:hidden;">
          <table class="dep-table">
            <thead>
              <tr>
                <th>Year</th>
                <th>Opening Value</th>
                <th>Depreciation Rate</th>
                <th>Depreciation Amount</th>
                <th>Closing WDV Value</th>
                <th>Value Retained</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dep_rows as $dr):
                $dr_op = isset($dr['opening']) ? (float)$dr['opening'] : (isset($dr['start_val']) ? (float)$dr['start_val'] : 0);
                $dr_dp = isset($dr['dep']) ? (float)$dr['dep'] : (isset($dr['dep_amt']) ? (float)$dr['dep_amt'] : 0);
                $dr_cl = isset($dr['closing']) ? (float)$dr['closing'] : (isset($dr['end_val']) ? (float)$dr['end_val'] : 0);
              ?>
              <tr>
                <td><strong>Year <?php echo $dr['year']; ?></strong></td>
                <td>Rs. <?php echo number_format($dr_op, 2); ?></td>
                <td><?php echo (float)$gad['depreciation_rate']; ?>% p.a.</td>
                <td class="dep-neg">- Rs. <?php echo number_format($dr_dp, 2); ?></td>
                <td class="dep-val">Rs. <?php echo number_format($dr_cl, 2); ?></td>
                <td class="dep-pct"><?php echo $dr['pct_left']; ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ══ SECTION 4: WARRANTY & SUPPORT STATUS ══ -->
      <div class="passport-section">
        <div class="section-title">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          Warranty &amp; Support Coverage
        </div>
        <div class="glass-card">
          <?php if (count($wars) > 0): ?>
            <?php foreach ($wars as $w):
              $days = warranty_days_left($w['end_date']);
              if ($days <= 0) { $bclass = 'badge-danger'; $lbl = 'EXPIRED'; }
              elseif ($days <= 90) { $bclass = 'badge-warning'; $lbl = $days . ' days left'; }
              else { $bclass = 'badge-success'; $lbl = $days . ' days left'; }
            ?>
            <div class="warranty-item">
              <div class="warranty-left">
                <div class="warranty-name"><?php echo htmlspecialchars(ucfirst($w['type'])); ?> Coverage &bull; <?php echo htmlspecialchars($w['provider']); ?></div>
                <div class="warranty-dates">Valid: <?php echo date('d M Y', strtotime($w['start_date'])); ?> &ndash; <?php echo date('d M Y', strtotime($w['end_date'])); ?></div>
              </div>
              <span class="badge <?php echo $bclass; ?>"><?php echo $lbl; ?></span>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-data">No active warranty or AMC contract records found for this device.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ══ SECTION 5: ISSUE HISTORY ══ -->
      <div class="passport-section">
        <div class="section-title">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          Support Ticket &amp; Incident History (Last 5 Records)
        </div>
        <div class="glass-card">
          <?php if (count($issues) > 0): ?>
            <?php foreach ($issues as $iss):
              $st = strtolower($iss['status']);
              if ($st === 'resolved') $ibadge = 'badge-success';
              elseif ($st === 'in progress' || $st === 'testing') $ibadge = 'badge-warning';
              else $ibadge = 'badge-danger';
            ?>
            <div class="issue-item">
              <div class="issue-left">
                <div class="issue-title">Ticket #<?php echo $iss['id']; ?>: <?php echo htmlspecialchars($iss['title']); ?></div>
                <div class="issue-date">Raised by <?php echo htmlspecialchars($iss['raised_name']); ?> on <?php echo date('d M Y, H:i', strtotime($iss['raised_at'])); ?></div>
              </div>
              <span class="badge <?php echo $ibadge; ?>"><?php echo ucfirst($st); ?></span>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-data">No support tickets or fault incidents reported for this device. Clean operational history.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ══ SIGNATURE / CERTIFICATION FOOTER ══ -->
      <div class="passport-footer-section">
        <div class="sig-block">
          <div class="sig-line"></div>
          <div class="sig-label">IT Asset Manager</div>
        </div>
        <div class="sig-block">
          <div class="sig-line"></div>
          <div class="sig-label">Department Head</div>
        </div>
        <div class="sig-block">
          <div class="sig-line"></div>
          <div class="sig-label">Authorized Custodian</div>
        </div>
        <div class="passport-stamp">
          <div class="stamp-big">HP TechPulse</div>
          <div class="stamp-sub">Certified Asset Record</div>
        </div>
      </div>

      <div class="passport-generated">
        Generated on <?php echo date('l, d F Y \a\t H:i:s'); ?> &nbsp;&middot;&nbsp;
        HP TechPulse Enterprise Asset Management &nbsp;&middot;&nbsp;
        Document Ref: <?php echo $doc_num; ?>
      </div>

    </div><!-- /.passport-body -->
  </div><!-- /.passport -->
  </div><!-- /.passport-wrap -->

<?php include 'footer.php'; ?>
