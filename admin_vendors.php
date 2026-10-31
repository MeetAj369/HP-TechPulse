<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_support();

$role     = current_role();
$is_admin = ($role === 'admin');
$action   = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$error    = '';

if (($action === 'add' || $action === 'edit' || $action === 'delete' || $_SERVER['REQUEST_METHOD'] === 'POST') && !$is_admin) {
    tp_redirect('admin_vendors.php?msg=admin_required');
}

// DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $vid = (int)$_GET['id'];
    $del = mysqli_query($conn, "DELETE FROM vendors WHERE id=$vid");
    if ($del) {
        audit_log($conn, "Deleted vendor ID: $vid", "vendors", $vid);
        tp_redirect('admin_vendors.php?msg=deleted');
    }
}

// ADD / EDIT POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vid            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name           = esc($conn, $_POST['name']);
    $contact_person = esc($conn, $_POST['contact_person']);
    $phone          = esc($conn, $_POST['phone']);
    $email          = esc($conn, $_POST['email']);
    $category       = esc($conn, $_POST['category']);
    $address        = esc($conn, $_POST['address']);

    if (empty($name)) {
        $error = 'Vendor / Company name is required.';
    } else {
        if ($vid === 0) {
            $ins = mysqli_query($conn,
                "INSERT INTO vendors (name, contact_person, phone, email, category, address, created_at)
                 VALUES ('$name', '$contact_person', '$phone', '$email', '$category', '$address', NOW())"
            );
            if ($ins) {
                $new_id = mysqli_insert_id($conn);
                audit_log($conn, "Registered vendor: $name", "vendors", $new_id);
                tp_redirect('admin_vendors.php?msg=added');
            } else {
                $error = 'Database write failed.';
            }
        } else {
            $upd = mysqli_query($conn,
                "UPDATE vendors SET
                    name='$name', contact_person='$contact_person',
                    phone='$phone', email='$email',
                    category='$category', address='$address'
                 WHERE id=$vid"
            );
            if ($upd) {
                audit_log($conn, "Updated vendor ID: $vid", "vendors", $vid);
                tp_redirect('admin_vendors.php?msg=updated');
            } else {
                $error = 'Database write failed.';
            }
        }
    }
}

// Fetch single vendor for edit form
$v_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $vid  = (int)$_GET['id'];
    $vq   = mysqli_query($conn, "SELECT * FROM vendors WHERE id=$vid LIMIT 1");
    $v_data = mysqli_fetch_assoc($vq);
}

// Search / filter
$search_clause = '';
$cat_clause    = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $s = esc($conn, $_GET['search']);
    $search_clause = " AND (name LIKE '%$s%' OR contact_person LIKE '%$s%' OR phone LIKE '%$s%' OR email LIKE '%$s%') ";
}
if (isset($_GET['cat_f']) && !empty($_GET['cat_f'])) {
    $cf = esc($conn, $_GET['cat_f']);
    $cat_clause = " AND category='$cf' ";
}

// Fetch all vendors
$vendors = array();
$vq = mysqli_query($conn, "SELECT * FROM vendors WHERE 1=1 $search_clause $cat_clause ORDER BY id DESC");
if ($vq) {
    while ($row = mysqli_fetch_assoc($vq)) { $vendors[] = $row; }
}

// KPI stats — mutually exclusive per exact category
$st_total        = 0; $st_supplier = 0; $st_service = 0; $st_sup_svc = 0; $st_dist = 0;
$sq = mysqli_query($conn, "SELECT category FROM vendors");
if ($sq) { while ($sr = mysqli_fetch_assoc($sq)) {
    $st_total++;
    $cat = $sr['category'];
    if ($cat === 'Supplier')           $st_supplier++;
    elseif ($cat === 'Service Center') $st_service++;
    elseif ($cat === 'Supplier & Service') $st_sup_svc++;
    elseif ($cat === 'Distributor')    $st_dist++;
}}

$page_title = 'Vendor Directory';
$active_nav = 'vendors';
include 'header.php';
?>

<style>
.vnd-stat-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.vnd-stat{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;display:flex;align-items:center;gap:14px;transition:box-shadow 0.2s;text-decoration:none;}
.vnd-stat:hover{box-shadow:0 4px 20px rgba(0,0,0,0.18);}
.vnd-stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.vnd-stat-icon svg{width:22px;height:22px;}
.vnd-stat-val{font-size:1.55rem;font-weight:900;line-height:1;letter-spacing:-0.03em;color:var(--text);}
.vnd-stat-lbl{font-size:0.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:3px;}
.vnd-toolbar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;}
.vnd-toolbar .form-control{height:38px;font-size:0.86rem;}
.vnd-search-wrap{flex:1;min-width:220px;position:relative;}
.vnd-search-wrap .form-control{padding-left:38px;}
.vnd-search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;}
.vnd-table{width:100%;border-collapse:collapse;}
.vnd-table thead tr{border-bottom:2px solid var(--border);}
.vnd-table th{padding:11px 14px;font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted);text-align:left;white-space:nowrap;}
.vnd-table td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:0.875rem;}
.vnd-table tbody tr{transition:background 0.15s;}
.vnd-table tbody tr:hover{background:var(--bg-alt);}
.vnd-table tbody tr:last-child td{border-bottom:none;}
.vnd-actions{display:flex;gap:6px;align-items:center;}
.vnd-actions a{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:var(--bg);color:var(--text-sub);transition:all 0.18s;text-decoration:none;cursor:pointer;}
.vnd-actions a:hover{background:var(--bg-alt);color:var(--text);border-color:var(--text-muted);}
.vnd-actions a.danger:hover{background:rgba(220,38,38,0.12);color:var(--red);border-color:var(--red);}
.vnd-actions a.primary:hover{background:rgba(26,107,255,0.12);color:var(--blue);border-color:var(--blue);}
.vnd-actions svg{width:15px;height:15px;}
.vnd-empty{text-align:center;padding:60px 20px;color:var(--text-muted);}
.vnd-empty svg{width:48px;height:48px;opacity:0.35;margin-bottom:12px;}
.vnd-avatar{width:40px;height:40px;border-radius:10px;background:var(--bg-alt);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800;color:var(--text-muted);flex-shrink:0;text-transform:uppercase;}
.form-section-hd{display:flex;align-items:center;gap:10px;margin:28px 0 16px;padding-bottom:10px;border-bottom:1px solid var(--border);}
.form-section-hd-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.form-section-hd span{font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;}
.cat-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:0.76rem;font-weight:700;}
@media(max-width:900px){.vnd-stat-bar{grid-template-columns:repeat(2,1fr);}}
@media(max-width:560px){.vnd-stat-bar{grid-template-columns:1fr 1fr;}.vnd-toolbar{flex-direction:column;align-items:stretch;}}
</style>

<!-- Page Header -->
<div class="page-header">
  <div class="page-header-left">
    <h1>
      <?php
        if ($action === 'add')  echo 'Register New Vendor';
        elseif ($action === 'edit') echo 'Edit Vendor Details';
        else echo 'Vendor & Supplier Directory';
      ?>
    </h1>
    <p>
      <?php
        if ($action === 'add')  echo 'Fill in the details to onboard a new hardware supplier or service partner.';
        elseif ($action === 'edit') echo 'Update contact details, category, or address for this vendor.';
        else echo 'Manage hardware suppliers, service centers, and distribution partners.';
      ?>
    </p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <?php if ($action !== 'list'): ?>
      <a href="admin_vendors.php" class="btn btn-outline">&#8592; Back to Directory</a>
    <?php elseif ($is_admin): ?>
      <a href="admin_vendors.php?action=add" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;font-size:0.86rem;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
        Add Vendor
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Flash messages -->
<?php if (isset($_GET['msg'])): ?>
<div class="flash-msg flash-success">
  <?php echo get_svg_icon('check'); ?>
  <span>
    <?php
      if ($_GET['msg'] === 'added')          echo 'Vendor registered successfully in the directory!';
      elseif ($_GET['msg'] === 'updated')    echo 'Vendor details updated successfully.';
      elseif ($_GET['msg'] === 'deleted')    echo 'Vendor removed from the directory.';
      elseif ($_GET['msg'] === 'admin_required') echo 'Access denied — admin privileges required.';
    ?>
  </span>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<!-- KPI Stat Bar — one card per distinct category, no double-counting -->
<div class="vnd-stat-bar">
  <a href="admin_vendors.php" class="vnd-stat">
    <div class="vnd-stat-icon" style="background:rgba(26,107,255,0.12);">
      <svg fill="none" stroke="var(--blue)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    </div>
    <div>
      <div class="vnd-stat-val"><?php echo $st_total; ?></div>
      <div class="vnd-stat-lbl">Total Vendors</div>
    </div>
  </a>
  <a href="admin_vendors.php?cat_f=Supplier" class="vnd-stat">
    <div class="vnd-stat-icon" style="background:rgba(34,197,94,0.12);">
      <svg fill="none" stroke="#22c55e" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
    </div>
    <div>
      <div class="vnd-stat-val" style="color:#22c55e;"><?php echo $st_supplier; ?></div>
      <div class="vnd-stat-lbl">Suppliers</div>
    </div>
  </a>
  <a href="admin_vendors.php?cat_f=Service+Center" class="vnd-stat">
    <div class="vnd-stat-icon" style="background:rgba(245,158,11,0.12);">
      <svg fill="none" stroke="var(--warning)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>
    <div>
      <div class="vnd-stat-val" style="color:var(--warning);"><?php echo $st_service; ?></div>
      <div class="vnd-stat-lbl">Service Centers</div>
    </div>
  </a>
  <a href="admin_vendors.php?cat_f=Distributor" class="vnd-stat">
    <div class="vnd-stat-icon" style="background:rgba(168,85,247,0.12);">
      <svg fill="none" stroke="#a855f7" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
    </div>
    <div>
      <div class="vnd-stat-val" style="color:#a855f7;"><?php echo $st_dist; ?></div>
      <div class="vnd-stat-lbl">Distributors</div>
    </div>
  </a>
</div>

<!-- Toolbar: Search + Category Filter -->
<form action="admin_vendors.php" method="GET" class="vnd-toolbar">
  <div class="vnd-search-wrap">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Search by name, contact, phone, email…" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
  </div>
  <select name="cat_f" class="form-control" style="width:180px;height:38px;">
    <option value="">All Categories</option>
    <?php foreach (array('Supplier', 'Service Center', 'Supplier & Service', 'Distributor') as $cat): ?>
    <option value="<?php echo $cat; ?>" <?php echo (isset($_GET['cat_f']) && $_GET['cat_f'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline" style="height:38px;padding:0 18px;font-size:0.85rem;">Filter</button>
  <?php if (!empty($_GET['search']) || !empty($_GET['cat_f'])): ?>
  <a href="admin_vendors.php" class="btn btn-ghost" style="height:38px;padding:0 14px;font-size:0.85rem;">Clear</a>
  <?php endif; ?>
  <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted);white-space:nowrap;"><?php echo count($vendors); ?> vendor<?php echo count($vendors) !== 1 ? 's' : ''; ?> found</span>
</form>

<!-- Vendor Table -->
<div class="card" style="overflow:hidden;">
  <div class="table-wrap">
    <table class="vnd-table">
      <thead>
        <tr>
          <th>Vendor / Company</th>
          <th>Category</th>
          <th>Contact Person</th>
          <th>Phone &amp; Email</th>
          <th>Address</th>
          <?php if ($is_admin): ?><th style="text-align:center;">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (count($vendors) > 0): ?>
          <?php foreach ($vendors as $v):
            $initials = strtoupper(substr($v['name'], 0, 1));
            $cat = $v['category'];
            if ($cat === 'Supplier & Service') { $badge_bg='rgba(34,197,94,0.12)'; $badge_c='#22c55e'; }
            elseif ($cat === 'Service Center') { $badge_bg='rgba(245,158,11,0.12)'; $badge_c='#f59e0b'; }
            elseif ($cat === 'Distributor')    { $badge_bg='rgba(168,85,247,0.12)'; $badge_c='#a855f7'; }
            else                               { $badge_bg='rgba(26,107,255,0.12)'; $badge_c='var(--blue)'; }
          ?>
          <tr>
            <!-- Vendor Name with avatar -->
            <td style="min-width:200px;">
              <div style="display:flex;align-items:center;gap:12px;">
                <div class="vnd-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                  <div style="font-weight:700;color:var(--text);margin-bottom:2px;"><?php echo htmlspecialchars($v['name']); ?></div>
                  <?php if (!empty($v['created_at'])): ?>
                  <div style="font-size:0.7rem;color:var(--text-muted);">Since <?php echo date('M Y', strtotime($v['created_at'])); ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <!-- Category badge -->
            <td>
              <span class="cat-badge" style="background:<?php echo $badge_bg; ?>;color:<?php echo $badge_c; ?>;">
                <?php echo htmlspecialchars($cat); ?>
              </span>
            </td>
            <!-- Contact Person -->
            <td>
              <div style="font-weight:600;font-size:0.85rem;color:var(--text);"><?php echo htmlspecialchars($v['contact_person'] ? $v['contact_person'] : '—'); ?></div>
            </td>
            <!-- Phone + Email -->
            <td style="min-width:170px;">
              <div style="font-weight:600;color:var(--text);font-size:0.85rem;"><?php echo htmlspecialchars($v['phone'] ? $v['phone'] : '—'); ?></div>
              <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;"><?php echo htmlspecialchars($v['email'] ? $v['email'] : '—'); ?></div>
            </td>
            <!-- Address -->
            <td>
              <span style="font-size:0.8rem;color:var(--text-sub);"><?php echo htmlspecialchars($v['address'] ? $v['address'] : '—'); ?></span>
            </td>
            <!-- Actions -->
            <?php if ($is_admin): ?>
            <td style="text-align:center;">
              <div class="vnd-actions" style="justify-content:center;">
                <a href="admin_vendors.php?action=edit&id=<?php echo $v['id']; ?>" title="Edit Vendor" class="primary">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <a href="admin_vendors.php?action=delete&id=<?php echo $v['id']; ?>" title="Remove Vendor" class="danger" onclick="return confirm('Remove this vendor from the directory?');">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </a>
              </div>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
        <tr>
          <td colspan="<?php echo $is_admin ? 6 : 5; ?>">
            <div class="vnd-empty">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              <p style="font-size:0.9rem;font-weight:600;margin:0;">No vendors match your current filters.</p>
              <p style="font-size:0.8rem;margin:6px 0 0;"><a href="admin_vendors.php" style="color:var(--red);">Clear filters</a> to see all vendors.</p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif (($action === 'add' || $action === 'edit') && $is_admin): ?>

<!-- Add / Edit Form -->
<div class="card" style="max-width:820px;margin:0 auto;">
  <div class="card-body" style="padding:28px 32px;">

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:24px;">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form action="admin_vendors.php" method="POST">
      <input type="hidden" name="id" value="<?php echo $v_data ? $v_data['id'] : 0; ?>">

      <!-- Section 1: Company Identity -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:var(--blue);"></div>
        <span style="color:var(--blue);">Company Identity</span>
      </div>

      <div class="form-group">
        <label for="name">Company / Vendor Name <span style="color:var(--red)">*</span></label>
        <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Dell Technologies India Pvt. Ltd." required value="<?php echo $v_data ? htmlspecialchars($v_data['name']) : ''; ?>">
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="category">Vendor Category</label>
          <select name="category" id="category" class="form-control">
            <?php foreach (array('Supplier', 'Service Center', 'Supplier & Service', 'Distributor') as $cat): ?>
            <option value="<?php echo $cat; ?>" <?php echo ($v_data && $v_data['category'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="contact_person">Primary Contact Representative</label>
          <input type="text" name="contact_person" id="contact_person" class="form-control" placeholder="e.g. Rahul Sharma" value="<?php echo $v_data ? htmlspecialchars($v_data['contact_person']) : ''; ?>">
        </div>
      </div>

      <!-- Section 2: Contact Details -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:var(--red);"></div>
        <span style="color:var(--text);">Contact Details</span>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="phone">Support Phone Line</label>
          <input type="text" name="phone" id="phone" class="form-control" placeholder="e.g. 1800-425-2355" value="<?php echo $v_data ? htmlspecialchars($v_data['phone']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="email">Business Email</label>
          <input type="email" name="email" id="email" class="form-control" placeholder="support@vendor.com" value="<?php echo $v_data ? htmlspecialchars($v_data['email']) : ''; ?>">
        </div>
      </div>

      <!-- Section 3: Location -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:#22c55e;"></div>
        <span style="color:var(--text);">Registered Location</span>
      </div>

      <div class="form-group">
        <label for="address">Head Office / Registered Address</label>
        <textarea name="address" id="address" class="form-control" style="height:80px;" placeholder="Full registered address of the vendor..."><?php echo $v_data ? htmlspecialchars($v_data['address']) : ''; ?></textarea>
      </div>

      <!-- Form Footer -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:32px;padding-top:20px;border-top:1px solid var(--border);">
        <a href="admin_vendors.php" style="font-weight:600;color:var(--text-muted);font-size:0.88rem;text-decoration:none;">&#8592; Back to Directory</a>
        <button type="submit" class="btn btn-primary" style="padding:11px 28px;">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
          <?php echo $action === 'edit' ? 'Save Changes' : 'Register Vendor'; ?>
        </button>
      </div>
    </form>
  </div>
</div>

<?php endif; ?>
<?php include 'footer.php'; ?>
