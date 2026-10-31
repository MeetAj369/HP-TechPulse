<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_admin(); // Guard Admin access

$page_title = 'Admin Console';
$active_nav = 'dashboard';

$total_gadgets  = 0;
$active_gadgets = 0;
$in_repair      = 0;
$total_users    = 0;
$total_issues   = 0;
$critical_open  = 0;
$low_stock_cnt  = 0;
$maint_due      = 0;
$asset_val      = 0.0;

// Gadget details
$g_stats_q = mysqli_query($conn, "SELECT status, COUNT(*) as c, SUM(purchase_price) as val FROM gadgets GROUP BY status");
if ($g_stats_q) {
    while($row = mysqli_fetch_assoc($g_stats_q)) {
        $c = (int)$row['c'];
        $total_gadgets += $c;
        if ($row['status'] === 'active') { $active_gadgets = $c; }
        elseif ($row['status'] === 'in-repair') { $in_repair = $c; }
        if ($row['status'] !== 'retired') { $asset_val += (float)$row['val']; }
    }
}

// User count
$u_q = mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='user'");
if ($u_q) { $total_users = (int)mysqli_fetch_row($u_q)[0]; }

// Issue statistics
$i_q = mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE status != 'resolved'");
if ($i_q) { $total_issues = (int)mysqli_fetch_row($i_q)[0]; }

$i_crit_q = mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE priority='critical' AND status != 'resolved'");
if ($i_crit_q) { $critical_open = (int)mysqli_fetch_row($i_crit_q)[0]; }

// Low stock items count
$s_q = mysqli_query($conn, "SELECT COUNT(*) FROM stock WHERE quantity <= min_quantity");
if ($s_q) { $low_stock_cnt = (int)mysqli_fetch_row($s_q)[0]; }

// Pipeline Status Counts
$pipeline_counts = array('raised'=>0, 'assigned'=>0, 'in-progress'=>0, 'testing'=>0, 'resolved'=>0);
$pipe_q = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM issues GROUP BY status");
if ($pipe_q) {
    while ($pr = mysqli_fetch_assoc($pipe_q)) {
        if (isset($pipeline_counts[$pr['status']])) {
            $pipeline_counts[$pr['status']] = (int)$pr['c'];
        }
    }
}


// Recent issues
$recent_issues_q = mysqli_query($conn,
    "SELECT i.*, g.name as gname, g.asset_tag, u.name as raised_name
     FROM issues i
     JOIN gadgets g ON i.gadget_id = g.id
     JOIN users u ON i.raised_by = u.id
     ORDER BY i.raised_at DESC LIMIT 5"
);
$recent_issues = array();
if ($recent_issues_q) {
    while ($row = mysqli_fetch_assoc($recent_issues_q)) { $recent_issues[] = $row; }
}

// Low stock items list
$low_stock_q = mysqli_query($conn, "SELECT * FROM stock WHERE quantity <= min_quantity ORDER BY quantity ASC LIMIT 5");
$low_stock_list = array();
if ($low_stock_q) {
    while ($row = mysqli_fetch_assoc($low_stock_q)) { $low_stock_list[] = $row; }
}

// Warranties expiring in next 60 days
$expiring_q = mysqli_query($conn,
    "SELECT w.end_date, g.name, g.asset_tag
     FROM warranties w 
     JOIN gadgets g ON w.gadget_id = g.id
     WHERE w.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY w.end_date ASC LIMIT 5"
);
$expiring_list = array();
if ($expiring_q) {
    while ($row = mysqli_fetch_assoc($expiring_q)) { $expiring_list[] = $row; }
}

// Recent Audit Logs
$activity_q = mysqli_query($conn,
    "SELECT al.action, al.created_at, u.name
     FROM audit_log al 
     JOIN users u ON al.user_id = u.id
     ORDER BY al.created_at DESC LIMIT 6"
);
$activities = array();
if ($activity_q) {
    while ($row = mysqli_fetch_assoc($activity_q)) { $activities[] = $row; }
}

include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Admin Control Panel</h1>
        <p>Real-time overview of assets, support tickets, and system health &nbsp;&bull;&nbsp; <?php echo date('l, d F Y'); ?></p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
        <a href="admin_analytics.php" class="btn btn-outline">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Analytics
        </a>
        <a href="admin_gadgets.php?action=add" class="btn btn-primary">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Device
        </a>
    </div>
</div>

<!-- Stats Indicators -->
<div class="stats-grid">
    <div class="stat-card cyan">
        <div class="stat-icon cyan"><?php echo get_svg_icon('gadgets'); ?></div>
        <div class="stat-label">Total Gadgets</div>
        <div class="stat-value" data-count="<?php echo $total_gadgets; ?>"><?php echo $total_gadgets; ?></div>
        <div class="stat-sub"><?php echo $active_gadgets; ?> active &bull; <?php echo $in_repair; ?> in repair</div>
        <a href="admin_gadgets.php" class="stat-link">View Inventory &rarr;</a>
    </div>

    <div class="stat-card red">
        <div class="stat-icon red"><?php echo get_svg_icon('issues'); ?></div>
        <div class="stat-label">Open Support Tickets</div>
        <div class="stat-value" data-count="<?php echo $total_issues; ?>"><?php echo $total_issues; ?></div>
        <div class="stat-sub"><?php echo $critical_open; ?> critical priority tickets</div>
        <a href="admin_issues.php" class="stat-link">Manage Tickets &rarr;</a>
    </div>

    <div class="stat-card amber">
        <div class="stat-icon amber"><?php echo get_svg_icon('stock'); ?></div>
        <div class="stat-label">Low Stock Alerts</div>
        <div class="stat-value" data-count="<?php echo $low_stock_cnt; ?>"><?php echo $low_stock_cnt; ?></div>
        <div class="stat-sub">Parts below safety buffer</div>
        <a href="admin_stock.php" class="stat-link">Inventory Stock &rarr;</a>
    </div>

    <div class="stat-card green">
        <div class="stat-icon green"><?php echo get_svg_icon('analytics'); ?></div>
        <div class="stat-label">Asset Valuation (TCO)</div>
        <div class="stat-value" style="font-size: 1.45rem; margin-top: 5px;"><?php echo fmt_inr($asset_val); ?></div>
        <div class="stat-sub">Active lifecycle book valuation</div>
        <span class="stat-link" style="color:var(--success);">PHP WDV Engine</span>
    </div>
</div>

<!-- Pipeline Status Tracker -->
<div class="card mb-4" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3>Support Pipeline Stages</h3>
        <a href="admin_issues.php" class="btn btn-ghost btn-sm">All issues &rarr;</a>
    </div>
    <div class="card-body" style="padding: 18px 24px;">
        <div style="display:grid;grid-template-columns:repeat(5,1fr);">
            <?php
            $pipe_colors = array('raised'=>'#f59e0b', 'assigned'=>'#3b82f6', 'in-progress'=>'#8b5cf6', 'testing'=>'#06b6d4', 'resolved'=>'#10b981');
            $pipe_labels = array('raised'=>'Raised', 'assigned'=>'Assigned', 'in-progress'=>'In Progress', 'testing'=>'Testing', 'resolved'=>'Resolved');
            foreach ($pipeline_counts as $st => $cnt):
                $col = $pipe_colors[$st];
            ?>
                <div style="flex:1; text-align:center; padding:16px 8px; border-right:1px solid var(--border); background: <?php echo $cnt > 0 ? 'rgba('.implode(',',sscanf($col,'#%02x%02x%02x')).',0.06)' : 'rgba(0,0,0,0.08)'; ?>;">
                    <div style="font-size:1.6rem; font-weight:800; color:<?php echo $cnt > 0 ? $col : 'var(--text-darker)'; ?>;"><?php echo $cnt; ?></div>
                    <div style="font-size:0.72rem; font-weight:700; color:var(--text-muted); margin-top:4px; text-transform:uppercase;"><?php echo $pipe_labels[$st]; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="main-grid-layout">
    <!-- Left Panel: Recent Fault Reports -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('issues'); ?> Recent Reported Faults</h3>
                <a href="admin_issues.php" class="btn btn-ghost btn-sm">View all</a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Device details</th>
                            <th>Issue details</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_issues) > 0): ?>
                            <?php foreach ($recent_issues as $iss): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:var(--text);"><?php echo htmlspecialchars($iss['gname']); ?></div>
                                        <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($iss['asset_tag']); ?></div>
                                    </td>
                                    <td>
                                        <a href="issue_detail.php?id=<?php echo $iss['id']; ?>" style="font-weight:600; color:var(--text);">
                                            <?php echo htmlspecialchars(substr($iss['title'], 0, 42)); ?><?php echo strlen($iss['title']) > 42 ? '...' : ''; ?>
                                        </a>
                                        <div style="font-size:0.75rem; color:var(--text-darker);"><?php echo htmlspecialchars($iss['raised_name']); ?> &bull; <?php echo date('d M', strtotime($iss['raised_at'])); ?></div>
                                    </td>
                                    <td><?php echo priority_badge($iss['priority']); ?></td>
                                    <td><?php echo issue_status_badge($iss['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No open issues logged.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Panel: Alerts & Audit Logs -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        
        <!-- Warranties expiring & Low stock -->
        <div class="card">
            <div class="card-header">
                <h3>System Notifications</h3>
            </div>
            <div class="card-body" style="padding:0;">
                
                <!-- Stock Notifications -->
                <?php if (count($low_stock_list) > 0): ?>
                    <div style="padding:14px 20px; border-bottom:1px solid var(--border); font-size:0.76rem; font-weight:700; text-transform:uppercase; color:var(--text-darker); letter-spacing:0.5px;">Low Stock Spare Parts</div>
                    <?php foreach ($low_stock_list as $part): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid var(--border); font-size:0.84rem;">
                            <div>
                                <span style="font-weight:600; color:var(--text);"><?php echo htmlspecialchars($part['name']); ?></span>
                                <div style="font-size:0.74rem; color:var(--text-muted);">Stock Category: <?php echo htmlspecialchars($part['category']); ?></div>
                            </div>
                            <span class="badge badge-red" style="font-size:0.7rem;">Only <?php echo $part['quantity']; ?> left</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Expiring Warranties -->
                <?php if (count($expiring_list) > 0): ?>
                    <div style="padding:14px 20px; border-bottom:1px solid var(--border); font-size:0.76rem; font-weight:700; text-transform:uppercase; color:var(--text-darker); letter-spacing:0.5px;">Expiring warranties (60 days)</div>
                    <?php foreach ($expiring_list as $w): 
                        $days = warranty_days_left($w['end_date']);
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid var(--border); font-size:0.84rem;">
                            <div>
                                <span style="font-weight:600; color:var(--text);"><?php echo htmlspecialchars($w['name']); ?></span>
                                <div style="font-size:0.74rem; color:var(--text-muted);"><?php echo htmlspecialchars($w['asset_tag']); ?></div>
                            </div>
                            <span class="badge <?php echo $days <= 30 ? 'badge-red' : 'badge-amber'; ?>" style="font-size:0.7rem;">
                                <?php echo $days <= 0 ? 'Expired' : $days . ' days left'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (count($low_stock_list) === 0 && count($expiring_list) === 0): ?>
                    <div class="empty-state" style="padding:32px;">No active alerts.</div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Recent Audit Logs -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('security'); ?> Security Audit Log</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (count($activities) > 0): ?>
                    <?php foreach ($activities as $act): ?>
                        <div style="padding:14px 20px; border-bottom:1px solid var(--border); font-size:0.82rem;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                <strong style="color:var(--text);"><?php echo htmlspecialchars($act['name']); ?></strong>
                                <span style="font-size:0.72rem; color:var(--text-darker);"><?php echo date('d M H:i', strtotime($act['created_at'])); ?></span>
                            </div>
                            <div style="color:var(--text-muted); font-size:0.78rem;"><?php echo htmlspecialchars($act['action']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding:32px;">Audit database is empty.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>
