<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_support(); // Guard: Admin and support tech users only

$role = current_role();
$is_admin = ($role === 'admin');
$uid = current_uid();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$error = '';
$success = '';

// Block write operations for support-only staff
if (($action === 'add' || $action === 'edit' || $action === 'delete' || $_SERVER['REQUEST_METHOD'] === 'POST') && !$is_admin) {
    tp_redirect('admin_gadgets.php?msg=admin_required');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $gid = (int)$_GET['id'];
    // Mark as retired instead of deleting to preserve issue logs and TCO records
    $del = mysqli_query($conn, "UPDATE gadgets SET status='retired', assigned_to=NULL WHERE id=$gid");
    if ($del) {
        audit_log($conn, "Retired gadget ID: $gid", "gadgets", $gid);
        tp_redirect('admin_gadgets.php?msg=retired');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gid               = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name              = esc($conn, $_POST['name']);
    $category          = esc($conn, $_POST['category']);
    $brand             = esc($conn, $_POST['brand']);
    $model             = esc($conn, $_POST['model']);
    $serial_number     = esc($conn, $_POST['serial_number']);
    $purchase_date     = esc($conn, $_POST['purchase_date']);
    $purchase_price    = max(0, (float)$_POST['purchase_price']);
    $useful_life_yrs   = max(1, (int)$_POST['useful_life_yrs']);
    $depreciation_rate = max(0, (float)$_POST['depreciation_rate']);
    $vendor_id         = empty($_POST['vendor_id']) ? "NULL" : (int)$_POST['vendor_id'];
    $assigned_to       = empty($_POST['assigned_to']) ? "NULL" : (int)$_POST['assigned_to'];
    $status            = esc($conn, $_POST['status']);
    $co2_manufacture   = max(0, (float)$_POST['co2_manufacture']);
    $co2_repair        = max(0, (float)$_POST['co2_repair']);
    $description       = esc($conn, $_POST['description']);

    // Handle image file upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_name = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($ext, $allowed_ext)) {
            $new_name = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
                $image_name = $new_name;
            }
        }
    }

    if (empty($name) || empty($category) || empty($purchase_date)) {
        $error = 'Name, category, and purchase date are required.';
    } elseif ($purchase_price < 0) {
        $error = 'Purchase price cannot be negative.';
    } elseif ($depreciation_rate < 0 || $depreciation_rate > 100) {
        $error = 'Depreciation rate must be between 0 and 100.';
    } else {
        if ($gid === 0) {
            // INSERT NEW GADGET
            $asset_tag = generate_asset_tag($conn);
            $img_field = !empty($image_name) ? "'$image_name'" : "NULL";
            
            $sql = "INSERT INTO gadgets (asset_tag, name, category, brand, model, serial_number, purchase_date, purchase_price, useful_life_yrs, depreciation_rate, vendor_id, assigned_to, status, description, image, co2_manufacture, co2_repair, created_at)
                    VALUES ('$asset_tag', '$name', '$category', '$brand', '$model', '$serial_number', '$purchase_date', $purchase_price, $useful_life_yrs, $depreciation_rate, $vendor_id, $assigned_to, '$status', '$description', $img_field, $co2_manufacture, $co2_repair, NOW())";
            
            $res = mysqli_query($conn, $sql);
            if ($res) {
                $new_id = mysqli_insert_id($conn);
                audit_log($conn, "Added new gadget: $name ($asset_tag)", "gadgets", $new_id);
                tp_redirect('admin_gadgets.php?msg=added');
            } else {
                $error = 'Database write failed. Check your database settings.';
            }
        } else {
            // UPDATE EXISTING GADGET
            $img_clause = "";
            if (!empty($image_name)) {
                $img_clause = ", image = '$image_name'";
            }

            $sql = "UPDATE gadgets SET 
                        name = '$name', 
                        category = '$category', 
                        brand = '$brand', 
                        model = '$model', 
                        serial_number = '$serial_number', 
                        purchase_date = '$purchase_date', 
                        purchase_price = $purchase_price, 
                        useful_life_yrs = $useful_life_yrs, 
                        depreciation_rate = $depreciation_rate, 
                        vendor_id = $vendor_id, 
                        assigned_to = $assigned_to, 
                        status = '$status', 
                        description = '$description',
                        co2_manufacture = $co2_manufacture,
                        co2_repair = $co2_repair
                        $img_clause
                    WHERE id = $gid";

            $res = mysqli_query($conn, $sql);
            if ($res) {
                audit_log($conn, "Updated gadget details for ID: $gid", "gadgets", $gid);
                tp_redirect('admin_gadgets.php?msg=updated');
            } else {
                $error = 'Database write failed.';
            }
        }
    }
}


$g_data = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $gid = (int)$_GET['id'];
    $g_data_q = mysqli_query($conn, "SELECT * FROM gadgets WHERE id=$gid LIMIT 1");
    $g_data = mysqli_fetch_assoc($g_data_q);
}

// Fetch users for selector
$users = array();
$uq = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role='user' ORDER BY name ASC");
if ($uq) {
    while($u = mysqli_fetch_assoc($uq)) { $users[] = $u; }
}

// Fetch vendors for selector
$vendors = array();
$vq = mysqli_query($conn, "SELECT id, name FROM vendors ORDER BY name ASC");
if ($vq) {
    while($v = mysqli_fetch_assoc($vq)) { $vendors[] = $v; }
}

// Fetch gadget list
$gadgets = array();
$search_clause = "";
$cat_filter = "";
$status_filter = "";
$assign_filter = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $s = esc($conn, $_GET['search']);
    $search_clause = " AND (g.name LIKE '%$s%' OR g.asset_tag LIKE '%$s%' OR g.brand LIKE '%$s%' OR g.serial_number LIKE '%$s%') ";
}
if (isset($_GET['cat']) && !empty($_GET['cat'])) {
    $c = esc($conn, $_GET['cat']);
    $cat_filter = " AND g.category = '$c' ";
}
if (isset($_GET['status_f']) && !empty($_GET['status_f'])) {
    $sf = esc($conn, $_GET['status_f']);
    $status_filter = " AND g.status = '$sf' ";
}
if (isset($_GET['assign_f']) && !empty($_GET['assign_f'])) {
    $af = esc($conn, $_GET['assign_f']);
    if ($af === 'assigned') {
        $assign_filter = " AND g.assigned_to IS NOT NULL ";
    } elseif ($af === 'unassigned') {
        $assign_filter = " AND g.assigned_to IS NULL ";
    }
}

$list_q = mysqli_query($conn,
    "SELECT g.*, u.name as assigned_name, v.name as vendor_name
     FROM gadgets g
     LEFT JOIN users u ON g.assigned_to = u.id
     LEFT JOIN vendors v ON g.vendor_id = v.id
     WHERE g.status != 'retired' $search_clause $cat_filter $status_filter $assign_filter
     ORDER BY g.id DESC"
);
if ($list_q) {
    while ($row = mysqli_fetch_assoc($list_q)) { $gadgets[] = $row; }
}

$page_title = 'Inventory Management';
$active_nav = 'gadgets';
include 'header.php';
?>

<style>
.inv-stat-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.inv-stat{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;display:flex;align-items:center;gap:14px;transition:box-shadow 0.2s;}
.inv-stat:hover{box-shadow:0 4px 20px rgba(0,0,0,0.18);}
.inv-stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.inv-stat-icon svg{width:22px;height:22px;}
.inv-stat-val{font-size:1.55rem;font-weight:900;line-height:1;letter-spacing:-0.03em;color:var(--text);}
.inv-stat-lbl{font-size:0.72rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:3px;}
/* Search bar */
.inv-toolbar{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;}
.inv-toolbar .form-control{height:38px;font-size:0.86rem;}
.inv-search-wrap{flex:1;min-width:220px;position:relative;}
.inv-search-wrap .form-control{padding-left:38px;}
.inv-search-wrap svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;}
/* Table enhancements */
.inv-table{width:100%;border-collapse:collapse;}
.inv-table thead tr{border-bottom:2px solid var(--border);}
.inv-table th{padding:11px 14px;font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;color:var(--text-muted);text-align:left;white-space:nowrap;}
.inv-table td{padding:12px 14px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:0.875rem;}
.inv-table tbody tr{transition:background 0.15s;}
.inv-table tbody tr:hover{background:var(--bg-alt);}
.inv-table tbody tr:last-child td{border-bottom:none;}
.inv-device-thumb{width:46px;height:46px;border-radius:10px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;}
.inv-device-icon{width:46px;height:46px;border-radius:10px;background:var(--red-dim);border:1px solid var(--border-r);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--red);}
.inv-device-icon svg{width:24px;height:24px;}
.score-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:20px;font-size:0.78rem;font-weight:700;}
.inv-actions{display:flex;gap:6px;align-items:center;}
.inv-actions a,.inv-actions button{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);background:var(--bg);color:var(--text-sub);transition:all 0.18s;text-decoration:none;cursor:pointer;}
.inv-actions a:hover{background:var(--bg-alt);color:var(--text);border-color:var(--text-muted);}
.inv-actions a.danger:hover{background:rgba(220,38,38,0.12);color:var(--red);border-color:var(--red);}
.inv-actions a.primary:hover{background:rgba(26,107,255,0.12);color:var(--blue);border-color:var(--blue);}
.inv-actions svg{width:15px;height:15px;}
.inv-empty{text-align:center;padding:60px 20px;color:var(--text-muted);}
.inv-empty svg{width:48px;height:48px;opacity:0.35;margin-bottom:12px;}
/* Form sections */
.form-section-hd{display:flex;align-items:center;gap:10px;margin:28px 0 16px;padding-bottom:10px;border-bottom:1px solid var(--border);}
.form-section-hd-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.form-section-hd span{font-size:0.8rem;font-weight:800;text-transform:uppercase;letter-spacing:0.07em;}
/* Add button */
.btn-add-device{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;font-size:0.86rem;}
@media(max-width:900px){.inv-stat-bar{grid-template-columns:repeat(2,1fr);}}
@media(max-width:560px){.inv-stat-bar{grid-template-columns:1fr 1fr;}.inv-toolbar{flex-direction:column;align-items:stretch;}}
</style>

<!-- Page Header -->
<div class="page-header">
  <div class="page-header-left">
    <h1>
      <?php
        if ($action === 'add')  echo 'Register New Device';
        elseif ($action === 'edit') echo 'Edit Device Details';
        else echo 'Device Inventory';
      ?>
    </h1>
    <p>
      <?php
        if ($action === 'add')  echo 'Fill in the form below to register a new hardware asset.';
        elseif ($action === 'edit') echo 'Update specifications, assignment, or financial parameters.';
        else echo 'Manage hardware assets, lifecycle assignments, and depreciation records.';
      ?>
    </p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <?php if ($action !== 'list'): ?>
      <a href="admin_gadgets.php" class="btn btn-outline">← Back to Inventory</a>
    <?php elseif ($is_admin): ?>
      <a href="admin_gadgets.php?action=add" class="btn btn-primary btn-add-device">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
        Register Device
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
      if ($_GET['msg'] === 'added')        echo 'Device registered successfully in the inventory!';
      elseif ($_GET['msg'] === 'updated')  echo 'Device details updated successfully.';
      elseif ($_GET['msg'] === 'retired')  echo 'Device has been retired from the active catalog.';
      elseif ($_GET['msg'] === 'admin_required') echo 'Access denied — admin privileges required.';
    ?>
  </span>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<?php
$st_total  = 0; $st_active = 0; $st_repair = 0; $st_unassigned = 0;
$sq = mysqli_query($conn, "SELECT status, assigned_to FROM gadgets WHERE status != 'retired'");
if ($sq) { while ($sr = mysqli_fetch_assoc($sq)) {
  $st_total++;
  if ($sr['status'] === 'active')    $st_active++;
  if ($sr['status'] === 'in-repair') $st_repair++;
  if (!$sr['assigned_to'])           $st_unassigned++;
}}
?>

<!-- Stat Bar (Clickable quick filters) -->
<div class="inv-stat-bar">
  <a href="admin_gadgets.php" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(26,107,255,0.12);">
      <svg fill="none" stroke="var(--blue)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
    </div>
    <div>
      <div class="inv-stat-val"><?php echo $st_total; ?></div>
      <div class="inv-stat-lbl">Total Assets</div>
    </div>
  </a>
  <a href="admin_gadgets.php?status_f=active" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(34,197,94,0.12);">
      <svg fill="none" stroke="#22c55e" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:#22c55e;"><?php echo $st_active; ?></div>
      <div class="inv-stat-lbl">Active</div>
    </div>
  </a>
  <a href="admin_gadgets.php?status_f=in-repair" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(245,158,11,0.12);">
      <svg fill="none" stroke="var(--warning)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:var(--warning);"><?php echo $st_repair; ?></div>
      <div class="inv-stat-lbl">In Repair</div>
    </div>
  </a>
  <a href="admin_gadgets.php?assign_f=unassigned" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(232,19,42,0.1);">
      <svg fill="none" stroke="var(--red)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:var(--red);"><?php echo $st_unassigned; ?></div>
      <div class="inv-stat-lbl">Unassigned</div>
    </div>
  </a>
</div>

<!-- Toolbar: Search + Category + Assignment + Status Filters -->
<form action="admin_gadgets.php" method="GET" class="inv-toolbar">
  <input type="hidden" name="action" value="list">
  <div class="inv-search-wrap">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Search by name, asset tag, brand, serial…" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
  </div>
  <select name="cat" class="form-control" style="width:150px;height:38px;">
    <option value="">All Categories</option>
    <?php foreach (array('Laptop','Mobile','Monitor','Tablet','Desktop','Printer','Accessory') as $cat): ?>
    <option value="<?php echo $cat; ?>" <?php echo (isset($_GET['cat']) && $_GET['cat'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
    <?php endforeach; ?>
  </select>
  <select name="assign_f" class="form-control" style="width:160px;height:38px;">
    <option value="">All Assignment</option>
    <option value="assigned"   <?php echo (isset($_GET['assign_f']) && $_GET['assign_f'] === 'assigned')   ? 'selected' : ''; ?>>Assigned Devices</option>
    <option value="unassigned" <?php echo (isset($_GET['assign_f']) && $_GET['assign_f'] === 'unassigned') ? 'selected' : ''; ?>>Unassigned Devices</option>
  </select>
  <select name="status_f" class="form-control" style="width:140px;height:38px;">
    <option value="">All Statuses</option>
    <option value="active"    <?php echo (isset($_GET['status_f']) && $_GET['status_f'] === 'active')    ? 'selected' : ''; ?>>Active</option>
    <option value="in-repair" <?php echo (isset($_GET['status_f']) && $_GET['status_f'] === 'in-repair') ? 'selected' : ''; ?>>In Repair</option>
    <option value="inactive"  <?php echo (isset($_GET['status_f']) && $_GET['status_f'] === 'inactive')  ? 'selected' : ''; ?>>Inactive</option>
  </select>
  <button type="submit" class="btn btn-outline" style="height:38px;padding:0 18px;font-size:0.85rem;">Filter</button>
  <?php if (!empty($_GET['search']) || !empty($_GET['cat']) || !empty($_GET['status_f']) || !empty($_GET['assign_f'])): ?>
  <a href="admin_gadgets.php" class="btn btn-ghost" style="height:38px;padding:0 14px;font-size:0.85rem;">Clear</a>
  <?php endif; ?>
  <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted);white-space:nowrap;"><?php echo count($gadgets); ?> device<?php echo count($gadgets) !== 1 ? 's' : ''; ?> found</span>
</form>

<!-- Inventory Table -->
<div class="card" style="overflow:hidden;">
  <div class="table-wrap">
    <table class="inv-table">
      <thead>
        <tr>
          <th>Device</th>
          <th>Category</th>
          <th>Health</th>
          <th>Purchase Value</th>
          <th>Custodian</th>
          <th>Status</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($gadgets) > 0): ?>
          <?php foreach ($gadgets as $g):
            $hs = calculate_health_score($conn, $g['id']);
            /* health colour alpha bg */
            $hbg = 'rgba(34,197,94,0.1)'; $htc = '#22c55e';
            if ($hs['score'] < 80 && $hs['score'] >= 60) { $hbg='rgba(37,99,235,0.1)'; $htc='#3b82f6'; }
            elseif ($hs['score'] < 60 && $hs['score'] >= 40) { $hbg='rgba(245,158,11,0.1)'; $htc='#f59e0b'; }
            elseif ($hs['score'] < 40) { $hbg='rgba(220,38,38,0.1)'; $htc='var(--red)'; }
          ?>
          <tr>
            <!-- Device thumb + name -->
            <td style="min-width:220px;">
              <div style="display:flex;align-items:center;gap:12px;">
                <?php if (!empty($g['image']) && file_exists('uploads/' . $g['image'])): ?>
                  <img src="/HP TechPulse/uploads/<?php echo htmlspecialchars($g['image']); ?>" alt="" class="inv-device-thumb">
                <?php else: ?>
                  <div class="inv-device-icon"><?php echo get_category_icon($g['category'], 22); ?></div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:700;color:var(--text);margin-bottom:2px;"><?php echo htmlspecialchars($g['name']); ?></div>
                  <div style="font-size:0.73rem;color:var(--text-muted);"><?php echo htmlspecialchars($g['asset_tag']); ?> &middot; <?php echo htmlspecialchars($g['brand'] ?: 'Unknown'); ?></div>
                  <?php if ($g['serial_number']): ?>
                  <div style="font-size:0.7rem;color:var(--text-darker);font-family:monospace;"><?php echo htmlspecialchars($g['serial_number']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <!-- Category -->
            <td>
              <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.82rem;font-weight:600;color:var(--text-sub);">
                <?php echo get_category_icon($g['category'], 14); ?>
                <?php echo htmlspecialchars($g['category']); ?>
              </span>
            </td>
            <!-- Health Score -->
            <td style="min-width:110px;">
              <span class="score-pill" style="background:<?php echo $hbg; ?>;color:<?php echo $htc; ?>;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <?php echo $hs['score']; ?>/100
              </span>
              <div style="font-size:0.68rem;color:var(--text-muted);margin-top:4px;"><?php echo $hs['verdict']; ?></div>
            </td>
            <!-- Purchase Value -->
            <td style="min-width:120px;">
              <div style="font-weight:700;color:var(--text);"><?php echo fmt_inr($g['purchase_price']); ?></div>
              <div style="font-size:0.73rem;color:var(--text-muted);"><?php echo date('d M Y', strtotime($g['purchase_date'])); ?></div>
            </td>
            <!-- Custodian -->
            <td>
              <?php if ($g['assigned_name']): ?>
                <div style="font-weight:600;font-size:0.85rem;color:var(--text);"><?php echo htmlspecialchars($g['assigned_name']); ?></div>
              <?php else: ?>
                <span style="font-size:0.8rem;color:var(--text-muted);font-style:italic;">Unassigned</span>
              <?php endif; ?>
            </td>
            <!-- Status -->
            <td><?php echo gadget_status_badge($g['status']); ?></td>
            <!-- Actions -->
            <td style="text-align:center;">
              <div class="inv-actions" style="justify-content:center;">
                <a href="gadget_profile.php?id=<?php echo $g['id']; ?>" title="View Profile">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </a>
                <a href="gadget_passport.php?id=<?php echo $g['id']; ?>" title="Device Passport">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </a>
                <?php if ($is_admin): ?>
                <a href="admin_gadgets.php?action=edit&id=<?php echo $g['id']; ?>" title="Edit Device" class="primary">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <a href="admin_gadgets.php?action=delete&id=<?php echo $g['id']; ?>" title="Retire Asset" class="danger" onclick="return confirm('Retire this asset from the active catalog? This action can be reversed by admin.');">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
        <tr>
          <td colspan="7">
            <div class="inv-empty">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              <p style="font-size:0.9rem;font-weight:600;margin:0;">No devices match your current filters.</p>
              <p style="font-size:0.8rem;margin:6px 0 0;"><a href="admin_gadgets.php" style="color:var(--red);">Clear filters</a> to see all assets.</p>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif (($action === 'add' || $action === 'edit') && $is_admin): ?>

<div class="card" style="max-width:900px;margin:0 auto;">
  <div class="card-body" style="padding:28px 32px;">

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:24px;">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form action="admin_gadgets.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?php echo $g_data ? $g_data['id'] : 0; ?>">

      <!-- Section 1: Device Specs -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:var(--blue);"></div>
        <span style="color:var(--blue);">Device Specifications</span>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="name">Asset Model Name <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Dell Latitude 5520" required value="<?php echo $g_data ? htmlspecialchars($g_data['name']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="category">Device Category <span style="color:var(--red)">*</span></label>
          <select name="category" id="category" class="form-control" required>
            <?php foreach (array('Laptop','Mobile','Monitor','Tablet','Desktop','Printer','Accessory') as $cat): ?>
            <option value="<?php echo $cat; ?>" <?php echo ($g_data && $g_data['category'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;">
        <div class="form-group">
          <label for="brand">Brand / Manufacturer</label>
          <input type="text" name="brand" id="brand" class="form-control" placeholder="e.g. Dell, HP, Apple" value="<?php echo $g_data ? htmlspecialchars($g_data['brand']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="model">Model Number</label>
          <input type="text" name="model" id="model" class="form-control" placeholder="e.g. Latitude 5520" value="<?php echo $g_data ? htmlspecialchars($g_data['model']) : ''; ?>">
        </div>
        <div class="form-group">
          <label for="serial_number">Serial Number</label>
          <input type="text" name="serial_number" id="serial_number" class="form-control" placeholder="Hardware serial" value="<?php echo $g_data ? htmlspecialchars($g_data['serial_number']) : ''; ?>">
        </div>
      </div>

      <!-- Section 2: Financial -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:var(--red);"></div>
        <span style="color:var(--text);">Financial &amp; Lifecycle Parameters</span>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="purchase_date">Purchase Date <span style="color:var(--red)">*</span></label>
          <input type="date" name="purchase_date" id="purchase_date" class="form-control" required value="<?php echo $g_data ? $g_data['purchase_date'] : ''; ?>">
        </div>
        <div class="form-group">
          <label for="purchase_price">Purchase Price (&#8377;)</label>
          <input type="number" step="0.01" min="0" name="purchase_price" id="purchase_price" class="form-control" placeholder="75000.00" value="<?php echo $g_data ? $g_data['purchase_price'] : '0.00'; ?>">
        </div>
      </div>

      <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;">
        <div class="form-group">
          <label for="useful_life_yrs">Useful Life (Years)</label>
          <input type="number" name="useful_life_yrs" id="useful_life_yrs" class="form-control" min="1" max="15" value="<?php echo $g_data ? $g_data['useful_life_yrs'] : '5'; ?>">
        </div>
        <div class="form-group">
          <label for="depreciation_rate">Depreciation Rate (%)</label>
          <input type="number" step="0.01" name="depreciation_rate" id="depreciation_rate" class="form-control" min="0" max="100" value="<?php echo $g_data ? $g_data['depreciation_rate'] : '20.00'; ?>">
        </div>
        <div class="form-group">
          <label for="status">Device Status</label>
          <select name="status" id="status" class="form-control" required>
            <option value="active"    <?php echo ($g_data && $g_data['status'] === 'active')    ? 'selected' : ''; ?>>Active</option>
            <option value="in-repair" <?php echo ($g_data && $g_data['status'] === 'in-repair') ? 'selected' : ''; ?>>In Repair</option>
            <option value="inactive"  <?php echo ($g_data && $g_data['status'] === 'inactive')  ? 'selected' : ''; ?>>Inactive</option>
          </select>
        </div>
      </div>

      <!-- Section 3: Assignment -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:#a855f7;"></div>
        <span style="color:var(--text);">Assignment &amp; Vendor</span>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="vendor_id">Preferred Supplier</label>
          <select name="vendor_id" id="vendor_id" class="form-control">
            <option value="">— No vendor selected —</option>
            <?php foreach ($vendors as $v): ?>
            <option value="<?php echo $v['id']; ?>" <?php echo ($g_data && $g_data['vendor_id'] == $v['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['name']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="assigned_to">Assign Custodian</label>
          <select name="assigned_to" id="assigned_to" class="form-control">
            <option value="">— Leave unassigned —</option>
            <?php foreach ($users as $u): ?>
            <option value="<?php echo $u['id']; ?>" <?php echo ($g_data && $g_data['assigned_to'] == $u['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($u['name'] . ' (' . $u['email'] . ')'); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Section 4: Green Pulse -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:#22c55e;"></div>
        <span style="color:#22c55e;">Green Pulse Sustainability</span>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="co2_manufacture">Manufacturing CO₂ Footprint (kg)</label>
          <input type="number" step="0.1" name="co2_manufacture" id="co2_manufacture" class="form-control" value="<?php echo $g_data ? $g_data['co2_manufacture'] : '250.0'; ?>">
        </div>
        <div class="form-group">
          <label for="co2_repair">Per-Repair CO₂ Footprint (kg)</label>
          <input type="number" step="0.1" name="co2_repair" id="co2_repair" class="form-control" value="<?php echo $g_data ? $g_data['co2_repair'] : '12.5'; ?>">
        </div>
      </div>

      <!-- Section 5: Media & Notes -->
      <div class="form-section-hd">
        <div class="form-section-hd-dot" style="background:var(--text-muted);"></div>
        <span style="color:var(--text);">Media &amp; Notes</span>
      </div>

      <!-- Device Photo Upload -->
      <div class="form-group">
        <label for="image">Device Photo</label>
        <?php if ($g_data && !empty($g_data['image']) && file_exists('uploads/' . $g_data['image'])): ?>
        <div style="display:flex;align-items:center;gap:14px;padding:12px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;">
          <img src="/HP TechPulse/uploads/<?php echo htmlspecialchars($g_data['image']); ?>" alt="Current" style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:1px solid var(--border);flex-shrink:0;">
          <div>
            <div style="font-weight:700;font-size:0.86rem;color:var(--text);margin-bottom:3px;"><?php echo htmlspecialchars($g_data['image']); ?></div>
            <div style="font-size:0.76rem;color:var(--text-muted);">Upload a new image below to replace this one.</div>
          </div>
        </div>
        <?php endif; ?>

        <div id="img-drop-zone" style="border:2px dashed var(--border);border-radius:var(--radius);padding:28px 20px;text-align:center;cursor:pointer;transition:all 0.25s;background:var(--bg-input);position:relative;"
          onclick="document.getElementById('image').click();"
          ondragover="event.preventDefault();this.style.borderColor='var(--red)';this.style.background='var(--red-dim)';"
          ondragleave="this.style.borderColor='var(--border)';this.style.background='var(--bg-input)';"
          ondrop="handleImgDrop(event);">
          <div id="img-drop-content">
            <div style="margin-bottom:10px;display:flex;justify-content:center;"><?php echo get_upload_icon(); ?></div>
            <div style="font-weight:700;font-size:0.9rem;color:var(--text);margin-bottom:4px;">Click to upload or drag &amp; drop</div>
            <div style="font-size:0.78rem;color:var(--text-muted);">JPG, PNG, GIF &bull; Max 5 MB &bull; Recommended 600×600 px</div>
          </div>
          <img id="img-preview" src="" alt="Preview" style="display:none;max-height:180px;max-width:100%;border-radius:10px;margin:0 auto;object-fit:contain;">
        </div>
        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" style="display:none;" onchange="previewImg(this);">
      </div>

      <div class="form-group">
        <label for="description">Technical Specifications / Notes</label>
        <textarea name="description" id="description" class="form-control" style="height:100px;" placeholder="Processor, RAM, storage, OS, or any additional lifecycle notes…"><?php echo $g_data ? htmlspecialchars($g_data['description']) : ''; ?></textarea>
      </div>

      <!-- Form Footer -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:32px;padding-top:20px;border-top:1px solid var(--border);">
        <a href="admin_gadgets.php" style="font-weight:600;color:var(--text-muted);font-size:0.88rem;text-decoration:none;">← Back to Inventory</a>
        <button type="submit" class="btn btn-primary" style="padding:11px 28px;">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
          <?php echo $action === 'edit' ? 'Save Changes' : 'Register Device'; ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function previewImg(input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var prev = document.getElementById('img-preview');
    var content = document.getElementById('img-drop-content');
    prev.src = e.target.result; prev.style.display = 'block';
    content.style.display = 'none';
    document.getElementById('img-drop-zone').style.borderColor = 'var(--red)';
    document.getElementById('img-drop-zone').style.background  = 'var(--red-dim)';
  };
  reader.readAsDataURL(input.files[0]);
}
function handleImgDrop(e) {
  e.preventDefault();
  document.getElementById('img-drop-zone').style.borderColor = 'var(--border)';
  document.getElementById('img-drop-zone').style.background  = 'var(--bg-input)';
  var files = e.dataTransfer.files;
  if (files && files.length > 0) {
    try { document.getElementById('image').files = files; } catch(ex) {}
    previewImg({files: files});
  }
}
</script>

<?php endif; ?>
<?php include 'footer.php'; ?>



