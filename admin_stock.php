<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_support(); // Guard Admin and Support users

$role = current_role();
$is_admin = ($role === 'admin');
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$error = '';
$success = '';

// Check admin writes
if (($action === 'add' || $action === 'edit' || $action === 'delete' || $_SERVER['REQUEST_METHOD'] === 'POST') && !$is_admin) {
    tp_redirect('admin_stock.php?msg=admin_required');
}

// DELETE STOCK ITEM
if ($action === 'delete' && isset($_GET['id'])) {
    $sid = (int)$_GET['id'];
    $del = mysqli_query($conn, "DELETE FROM stock WHERE id=$sid");
    if ($del) {
        audit_log($conn, "Deleted inventory stock item ID: $sid", "stock", $sid);
        tp_redirect('admin_stock.php?msg=deleted');
    }
}

// POST ADD / EDIT STOCK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name         = esc($conn, $_POST['name']);
    $category     = esc($conn, $_POST['category']);
    $quantity     = (int)$_POST['quantity'];
    $min_quantity = (int)$_POST['min_quantity'];
    $unit_cost    = (float)$_POST['unit_cost'];
    $vendor_id    = empty($_POST['vendor_id']) ? "NULL" : (int)$_POST['vendor_id'];

    if (empty($name) || empty($category)) {
        $error = 'Part name and category are required.';
    } elseif ($unit_cost < 0) {
        $error = 'Unit cost cannot be negative. Please enter 0 or a positive value.';
    } elseif ($quantity < 0) {
        $error = 'Quantity in stock cannot be negative.';
    } elseif ($min_quantity < 0) {
        $error = 'Safety threshold cannot be negative.';
    } else {
        if ($sid === 0) {
            $ins = mysqli_query($conn,
                "INSERT INTO stock (name, category, quantity, min_quantity, unit_cost, vendor_id, created_at)
                 VALUES ('$name', '$category', $quantity, $min_quantity, $unit_cost, $vendor_id, NOW())"
            );
            if ($ins) {
                $new_id = mysqli_insert_id($conn);
                audit_log($conn, "Added spare stock: $name (Qty: $quantity)", "stock", $new_id);
                tp_redirect('admin_stock.php?msg=added');
            } else {
                $error = 'Database write failed.';
            }
        } else {
            $upd = mysqli_query($conn,
                "UPDATE stock SET 
                    name = '$name', 
                    category = '$category', 
                    quantity = $quantity, 
                    min_quantity = $min_quantity, 
                    unit_cost = $unit_cost, 
                    vendor_id = $vendor_id
                 WHERE id = $sid"
            );
            if ($upd) {
                audit_log($conn, "Updated stock item ID: $sid", "stock", $sid);
                tp_redirect('admin_stock.php?msg=updated');
            } else {
                $error = 'Database write failed.';
            }
        }
    }
}

// Fetch single stock item for editing
$s_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $sid = (int)$_GET['id'];
    $sq = mysqli_query($conn, "SELECT * FROM stock WHERE id=$sid LIMIT 1");
    $s_data = mysqli_fetch_assoc($sq);
}

// Fetch all vendors for forms
$vendors = array();
$vq = mysqli_query($conn, "SELECT id, name FROM vendors ORDER BY name ASC");
if ($vq) {
    while ($row = mysqli_fetch_assoc($vq)) { $vendors[] = $row; }
}

// Fetch all stock items
$stock = array();
$list_q = mysqli_query($conn,
    "SELECT s.*, v.name as vendor_name 
     FROM stock s
     LEFT JOIN vendors v ON s.vendor_id = v.id
     ORDER BY s.quantity <= s.min_quantity DESC, s.id DESC"
);
if ($list_q) {
    while ($row = mysqli_fetch_assoc($list_q)) { $stock[] = $row; }
}

$page_title = 'Spare Parts Inventory';
$active_nav = 'stock';
include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>
            <?php 
                if ($action === 'add') echo 'Register Spare Part';
                elseif ($action === 'edit') echo 'Modify Inventory details';
                else echo 'Spare Parts Inventory';
            ?>
        </h1>
        <p>Monitor spare accessories, replacement parts, and maintenance supplies.</p>
    </div>
    <?php if ($action !== 'list'): ?>
        <a href="admin_stock.php" class="btn btn-outline">&larr; Back to Inventory</a>
    <?php elseif ($is_admin): ?>
        <a href="admin_stock.php?action=add" class="btn btn-primary">
            <?php echo get_svg_icon('plus'); ?> Add Spare Part
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash-msg flash-success">
        <?php echo get_svg_icon('check'); ?>
        <span>
            <?php 
                if ($_GET['msg'] === 'added') echo 'Spare part added to inventory!';
                elseif ($_GET['msg'] === 'updated') echo 'Inventory details updated successfully!';
                elseif ($_GET['msg'] === 'deleted') echo 'Part removed from system catalog.';
                elseif ($_GET['msg'] === 'admin_required') echo 'Access Denied: Admin role is required for writes.';
            ?>
        </span>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- INVENTORY LISTING -->

    <!-- Summary Stats Bar -->
    <?php
    $total_parts = count($stock);
    $low_count   = 0;
    $total_val   = 0.0;
    foreach ($stock as $s) {
        if ($s['quantity'] <= $s['min_quantity']) $low_count++;
        $total_val += (float)$s['quantity'] * (float)$s['unit_cost'];
    }
    ?>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
        <div class="card" style="padding:18px 22px; display:flex; align-items:center; gap:14px; margin:0;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--red-dim);color:var(--red);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php echo get_svg_icon('gadgets'); ?>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:900;color:var(--text);line-height:1;"><?php echo $total_parts; ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-top:2px;">Total Part Types</div>
            </div>
        </div>
        <div class="card" style="padding:18px 22px; display:flex; align-items:center; gap:14px; margin:0; <?php echo $low_count > 0 ? 'border-color:rgba(239,68,68,0.3);' : ''; ?>">
            <div style="width:40px;height:40px;border-radius:10px;background:<?php echo $low_count > 0 ? 'rgba(239,68,68,0.12)' : 'var(--bg)'; ?>;color:<?php echo $low_count > 0 ? 'var(--danger)' : 'var(--text-muted)'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php echo get_svg_icon('issues'); ?>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:900;color:<?php echo $low_count > 0 ? 'var(--danger)' : 'var(--text)'; ?>;line-height:1;"><?php echo $low_count; ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-top:2px;">Low Stock / Order Needed</div>
            </div>
        </div>
        <div class="card" style="padding:18px 22px; display:flex; align-items:center; gap:14px; margin:0;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--blue-dim,rgba(59,130,246,0.1));color:var(--blue);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php echo get_svg_icon('analytics'); ?>
            </div>
            <div>
                <div style="font-size:1.2rem;font-weight:900;color:var(--text);line-height:1;"><?php echo fmt_inr($total_val); ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-top:2px;">Total Inventory Value</div>
            </div>
        </div>
    </div>

    <div class="card" style="overflow:hidden; padding:0;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:700px;">
                <thead>
                    <tr style="background:var(--bg);">
                        <th style="padding:12px 18px; text-align:left; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:180px;">Part Name</th>
                        <th style="padding:12px 14px; text-align:left; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:100px;">Category</th>
                        <th style="padding:12px 14px; text-align:center; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:110px;">In Stock</th>
                        <th style="padding:12px 14px; text-align:left; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:120px;">Safety Buffer</th>
                        <th style="padding:12px 14px; text-align:right; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:100px;">Unit Cost</th>
                        <th style="padding:12px 14px; text-align:right; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:120px;">Total Value</th>
                        <th style="padding:12px 14px; text-align:left; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:140px;">Preferred Supplier</th>
                        <?php if ($is_admin): ?><th style="padding:12px 14px; text-align:center; font-size:0.7rem; font-weight:800; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); border-bottom:1px solid var(--border); white-space:nowrap; min-width:90px;">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($stock) > 0): ?>
                        <?php foreach ($stock as $s):
                            $is_low    = $s['quantity'] <= $s['min_quantity'];
                            $row_val   = (float)$s['quantity'] * (float)$s['unit_cost'];
                        ?>
                            <tr style="border-bottom:1px solid var(--border); transition:background 0.15s; <?php echo $is_low ? 'background:rgba(239,68,68,0.025);' : ''; ?>" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='<?php echo $is_low ? 'rgba(239,68,68,0.025)' : 'transparent'; ?>'">

                                <!-- Part Name -->
                                <td style="padding:14px 18px; vertical-align:middle;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <?php if ($is_low): ?>
                                        <div style="width:6px;height:6px;border-radius:50%;background:var(--danger);flex-shrink:0;"></div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:700; font-size:0.88rem; color:var(--text); line-height:1.3;"><?php echo htmlspecialchars($s['name']); ?></div>
                                            <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">ID #<?php echo $s['id']; ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td style="padding:14px; vertical-align:middle;">
                                    <?php
                                    $cat_colors = array(
                                        'RAM'=>'badge-blue','Storage'=>'badge-purple','Power'=>'badge-amber',
                                        'Consumable'=>'badge-green','Parts'=>'badge-red','Accessory'=>'badge-gray','Tool'=>'badge-gray'
                                    );
                                    $cat_cls = isset($cat_colors[$s['category']]) ? $cat_colors[$s['category']] : 'badge-gray';
                                    ?>
                                    <span class="badge <?php echo $cat_cls; ?>" style="font-size:0.72rem; white-space:nowrap;"><?php echo htmlspecialchars($s['category']); ?></span>
                                </td>

                                <!-- In Stock -->
                                <td style="padding:14px; text-align:center; vertical-align:middle;">
                                    <div style="font-size:1.3rem; font-weight:900; color:<?php echo $is_low ? 'var(--danger)' : 'var(--text)'; ?>; line-height:1;"><?php echo (int)$s['quantity']; ?></div>
                                    <div style="font-size:0.68rem; color:var(--text-muted); font-weight:600; margin-top:2px; text-transform:uppercase; letter-spacing:0.04em;">units</div>
                                </td>

                                <!-- Safety Buffer -->
                                <td style="padding:14px; vertical-align:middle;">
                                    <div style="font-size:0.8rem; color:var(--text-sub);">Min: <strong style="color:var(--text);"><?php echo (int)$s['min_quantity']; ?></strong> units</div>
                                    <?php if ($is_low): ?>
                                        <span class="badge badge-red" style="font-size:0.65rem; margin-top:5px; display:inline-flex;">
                                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                            Order Needed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-green" style="font-size:0.65rem; margin-top:5px; display:inline-flex;">OK</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Unit Cost -->
                                <td style="padding:14px; text-align:right; vertical-align:middle; white-space:nowrap;">
                                    <span style="font-weight:700; font-size:0.88rem; color:var(--text);"><?php echo fmt_inr($s['unit_cost']); ?></span>
                                </td>

                                <!-- Total Value -->
                                <td style="padding:14px; text-align:right; vertical-align:middle; white-space:nowrap;">
                                    <span style="font-weight:800; font-size:0.9rem; color:var(--text);"><?php echo fmt_inr($row_val); ?></span>
                                </td>

                                <!-- Preferred Supplier -->
                                <td style="padding:14px; vertical-align:middle;">
                                    <?php if ($s['vendor_name']): ?>
                                        <div style="font-size:0.82rem; color:var(--text); font-weight:600;"><?php echo htmlspecialchars($s['vendor_name']); ?></div>
                                    <?php else: ?>
                                        <span style="font-size:0.78rem; color:var(--text-muted);">Not assigned</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <?php if ($is_admin): ?>
                                <td style="padding:14px; text-align:center; vertical-align:middle;">
                                    <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                                        <a href="admin_stock.php?action=edit&id=<?php echo $s['id']; ?>" class="btn btn-secondary btn-sm" title="Edit" style="padding:6px 8px;"><?php echo get_svg_icon('edit'); ?></a>
                                        <a href="admin_stock.php?action=delete&id=<?php echo $s['id']; ?>" class="btn btn-danger btn-sm" title="Delete" style="padding:6px 8px;" onclick="return confirm('Remove this spare part from inventory?');"><?php echo get_svg_icon('delete'); ?></a>
                                    </div>
                                </td>
                                <?php endif; ?>

                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? 8 : 7; ?>" style="padding:60px 20px; text-align:center;">
                                <div class="empty-state">
                                    <?php echo get_svg_icon('gadgets'); ?>
                                    <p>No inventory items registered. Start by adding spare parts.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif (($action === 'add' || $action === 'edit') && $is_admin): ?>
    <!-- INVENTORY FORM -->
    <div class="card" style="max-width:650px; margin:0 auto;">
        <div class="card-header">
            <h3>Inventory Details</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div style="background:var(--danger-bg); color:var(--danger); padding:10px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600; font-size:0.82rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="admin_stock.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $s_data ? $s_data['id'] : 0; ?>">

                <div class="form-group">
                    <label for="name">Part / Accessory Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. 512GB SSD Samsung 870 EVO" required value="<?php echo $s_data ? htmlspecialchars($s_data['name']) : ''; ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="category">Inventory Category</label>
                        <select name="category" id="category" class="form-control" required>
                            <option value="RAM" <?php echo ($s_data && $s_data['category'] === 'RAM') ? 'selected' : ''; ?>>RAM</option>
                            <option value="Storage" <?php echo ($s_data && $s_data['category'] === 'Storage') ? 'selected' : ''; ?>>Storage (SSD/HDD)</option>
                            <option value="Consumable" <?php echo ($s_data && $s_data['category'] === 'Consumable') ? 'selected' : ''; ?>>Consumable (Toner/Drum)</option>
                            <option value="Power" <?php echo ($s_data && $s_data['category'] === 'Power') ? 'selected' : ''; ?>>Power (Adapter/Battery)</option>
                            <option value="Parts" <?php echo ($s_data && $s_data['category'] === 'Parts') ? 'selected' : ''; ?>>Internal Parts (Capacitor/Screen)</option>
                            <option value="Accessory" <?php echo ($s_data && $s_data['category'] === 'Accessory') ? 'selected' : ''; ?>>Accessory (Cable/Adapter)</option>
                            <option value="Tool" <?php echo ($s_data && $s_data['category'] === 'Tool') ? 'selected' : ''; ?>>Technician Tool</option>
                            <option value="Other" <?php echo ($s_data && $s_data['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unit_cost">Unit Purchase Cost (INR)</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" id="unit_cost" class="form-control" placeholder="0.00" value="<?php echo $s_data ? $s_data['unit_cost'] : '0.00'; ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="quantity">Quantity in Stock</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" min="0" required value="<?php echo $s_data ? $s_data['quantity'] : '0'; ?>">
                    </div>

                    <div class="form-group">
                        <label for="min_quantity">Safety Threshold (Minimum)</label>
                        <input type="number" name="min_quantity" id="min_quantity" class="form-control" min="0" required value="<?php echo $s_data ? $s_data['min_quantity'] : '2'; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="vendor_id">Preferred Supplier / Vendor</label>
                    <select name="vendor_id" id="vendor_id" class="form-control">
                        <option value="">-- Choose registered supplier vendor --</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo ($s_data && $s_data['vendor_id'] == $v['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:28px;">
                    <a href="admin_stock.php" style="font-weight:600; color:var(--text-muted);">&larr; Back to Inventory</a>
                    <button type="submit" class="btn btn-primary">
                        Save Inventory details
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
