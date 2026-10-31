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
if (($action === 'schedule' || $action === 'delete' || $_SERVER['REQUEST_METHOD'] === 'POST') && !$is_admin) {
    tp_redirect('admin_maintenance.php?msg=admin_required');
}

if ($action === 'delete' && isset($_GET['id'])) {
    $mid = (int)$_GET['id'];
    $del = mysqli_query($conn, "DELETE FROM maintenance WHERE id=$mid");
    if ($del) {
        audit_log($conn, "Cancelled scheduled maintenance ID: $mid", "maintenance", $mid);
        tp_redirect('admin_maintenance.php?msg=deleted');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gadget_id      = (int)$_POST['gadget_id'];
    $service_type   = esc($conn, $_POST['service_type']);
    $scheduled_date = esc($conn, $_POST['scheduled_date']);
    $technician_id  = empty($_POST['technician_id']) ? "NULL" : (int)$_POST['technician_id'];
    $notes          = esc($conn, $_POST['notes']);

    if (empty($gadget_id) || empty($service_type) || empty($scheduled_date)) {
        $error = 'Device, service type, and scheduled date are required.';
    } else {
        $ins = mysqli_query($conn,
            "INSERT INTO maintenance (gadget_id, service_type, scheduled_date, technician_id, notes, created_at)
             VALUES ($gadget_id, '$service_type', '$scheduled_date', $technician_id, '$notes', NOW())"
        );
        if ($ins) {
            $new_id = mysqli_insert_id($conn);
            // Temporarily set gadget status to in-repair if scheduling immediate/critical work
            if (strtotime($scheduled_date) <= time()) {
                mysqli_query($conn, "UPDATE gadgets SET status='in-repair' WHERE id=$gadget_id");
            }
            audit_log($conn, "Scheduled maintenance for gadget ID $gadget_id on $scheduled_date", "maintenance", $new_id);
            tp_redirect('admin_maintenance.php?msg=scheduled');
        } else {
            $error = 'Database write failed.';
        }
    }
}

// Fetch all active devices for selector
$gadgets = array();
$gq = mysqli_query($conn, "SELECT id, name, asset_tag FROM gadgets WHERE status != 'retired' ORDER BY name ASC");
if ($gq) {
    while ($row = mysqli_fetch_assoc($gq)) { $gadgets[] = $row; }
}

// Fetch all support technicians (users where is_support = 1)
$techs = array();
$tq = mysqli_query($conn, "SELECT id, name FROM users WHERE role='user' AND is_support=1 ORDER BY name ASC");
if ($tq) {
    while ($row = mysqli_fetch_assoc($tq)) { $techs[] = $row; }
}

// Fetch maintenance list
$jobs = array();
$list_q = mysqli_query($conn,
    "SELECT m.*, g.name as gname, g.asset_tag, u.name as tech_name 
     FROM maintenance m
     JOIN gadgets g ON m.gadget_id = g.id
     LEFT JOIN users u ON m.technician_id = u.id
     ORDER BY m.completed_date IS NULL DESC, m.scheduled_date ASC"
);
if ($list_q) {
    while ($row = mysqli_fetch_assoc($list_q)) { $jobs[] = $row; }
}

$page_title = 'Maintenance Console';
$active_nav = 'maintenance';
include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Maintenance Scheduler</h1>
        <p>Schedule proactive maintenance tasks, checkups, and software upgrades.</p>
    </div>
    <?php if ($action !== 'list'): ?>
        <a href="admin_maintenance.php" class="btn btn-outline">&larr; Back to Schedule</a>
    <?php elseif ($is_admin): ?>
        <a href="admin_maintenance.php?action=schedule" class="btn btn-primary">
            <?php echo get_svg_icon('plus'); ?> Schedule Service
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash-msg flash-success">
        <?php echo get_svg_icon('check'); ?>
        <span>
            <?php 
                if ($_GET['msg'] === 'scheduled') echo 'Maintenance task scheduled successfully!';
                elseif ($_GET['msg'] === 'deleted') echo 'Scheduled maintenance task cancelled.';
                elseif ($_GET['msg'] === 'admin_required') echo 'Access Denied: Admin role is required for writes.';
            ?>
        </span>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- LIST VIEW -->
    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Device details</th>
                        <th>Service Type</th>
                        <th>Scheduled Date</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Cost (INR)</th>
                        <th>Action/Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($jobs) > 0): ?>
                        <?php foreach ($jobs as $j): 
                            $is_done = !empty($j['completed_date']);
                            $is_overdue = !$is_done && strtotime($j['scheduled_date']) < time();
                        ?>
                            <tr style="<?php echo $is_overdue ? 'background: rgba(239, 68, 68, 0.02);' : ''; ?>">
                                <td>
                                    <div style="font-weight:700; color:var(--text);"><?php echo htmlspecialchars($j['gname']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($j['asset_tag']); ?></div>
                                </td>
                                <td><span class="badge badge-blue"><?php echo htmlspecialchars($j['service_type']); ?></span></td>
                                <td>
                                    <div style="font-weight:600; color:var(--text);"><?php echo date('d M Y', strtotime($j['scheduled_date'])); ?></div>
                                    <?php if ($is_overdue): ?>
                                        <span class="badge badge-red" style="font-size:0.65rem; margin-top:4px; display:inline-flex;">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $j['tech_name'] ? htmlspecialchars($j['tech_name']) : '<span style="color:var(--text-darker);">Unassigned</span>'; ?></td>
                                <td>
                                    <?php if ($is_done): ?>
                                        <span class="badge badge-green">Completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-amber">Scheduled</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $is_done ? fmt_inr($j['cost']) : '—'; ?></strong></td>
                                <td>
                                    <div style="font-size:0.8rem; color:var(--text-muted); max-width:200px;">
                                        <?php if ($is_done && !empty($j['completed_date'])): ?>
                                            <strong>Closed:</strong> <?php echo date('d M Y', strtotime($j['completed_date'])); ?><br>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($j['notes'] ? $j['notes'] : '—'); ?>
                                    </div>
                                    <?php if (!$is_done && $is_admin): ?>
                                        <div style="margin-top:6px;">
                                            <a href="admin_maintenance.php?action=delete&id=<?php echo $j['id']; ?>" class="btn btn-danger btn-sm" style="padding:2px 6px; font-size:0.7rem;" onclick="return confirm('Cancel this scheduled maintenance?');">Cancel</a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">No maintenance records logged.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'schedule' && $is_admin): ?>
    <!-- FORM SCHEDULE VIEW -->
    <div class="card" style="max-width:650px; margin:0 auto;">
        <div class="card-header">
            <h3>Schedule Asset Maintenance</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div style="background:var(--danger-bg); color:var(--danger); padding:10px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600; font-size:0.82rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="admin_maintenance.php" method="POST">
                
                <div class="form-group">
                    <label for="gadget_id">Select Device</label>
                    <select name="gadget_id" id="gadget_id" class="form-control" required>
                        <option value="">-- Choose active company gadget --</option>
                        <?php foreach ($gadgets as $g): ?>
                            <option value="<?php echo $g['id']; ?>" <?php echo (isset($_POST['gadget_id']) && $_POST['gadget_id'] == $g['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g['name'] . ' [' . $g['asset_tag'] . ']'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="service_type">Service Action Type</label>
                        <select name="service_type" id="service_type" class="form-control" required>
                            <option value="Annual Cleaning & Check">Annual Cleaning & Check</option>
                            <option value="RAM Upgrade">RAM Upgrade</option>
                            <option value="Storage Migration (HDD -> SSD)">Storage Migration (HDD -> SSD)</option>
                            <option value="Battery Health Check">Battery Health Check</option>
                            <option value="OS Upgrades & Rollback">OS Upgrades & Rollback</option>
                            <option value="Internal Parts Replacement">Internal Parts Replacement</option>
                            <option value="General Diagnostics">General Diagnostics</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="scheduled_date">Scheduled Date</label>
                        <input type="date" name="scheduled_date" id="scheduled_date" class="form-control" required value="<?php echo isset($_POST['scheduled_date']) ? $_POST['scheduled_date'] : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="technician_id">Assign to IT Support Staff</label>
                    <select name="technician_id" id="technician_id" class="form-control">
                        <option value="">-- Choose support technician --</option>
                        <?php foreach ($techs as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo (isset($_POST['technician_id']) && $_POST['technician_id'] == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Administrative scheduling notes</label>
                    <textarea name="notes" id="notes" class="form-control" style="height:100px;" placeholder="Add specific task instructions or details of hardware modifications required..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:28px;">
                    <a href="admin_maintenance.php" style="font-weight:600; color:var(--text-muted);">&larr; Back to Schedule</a>
                    <button type="submit" class="btn btn-primary">
                        Schedule Job Order
                    </button>
                </div>

            </form>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
