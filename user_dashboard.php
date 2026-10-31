<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_user(); // User guard (both employee and support tech)

$uid = current_uid();
$is_support = current_is_support();
$is_admin   = false; // users are never admins — admin side has its own files

// Determine view mode
$view = isset($_GET['view']) ? trim($_GET['view']) : 'dashboard';
if ($view !== 'dashboard' && $view !== 'support_queue' && $view !== 'maintenance_jobs') {
    $view = 'dashboard';
}

// Redirect standard employee attempting to access support views
if (($view === 'support_queue' || $view === 'maintenance_jobs') && !$is_support) {
    tp_redirect('user_dashboard.php');
}

$page_title = 'My Dashboard';
$active_nav = 'dashboard';
if ($view === 'support_queue') {
    $page_title = 'Support Ticket Queue';
    $active_nav = 'issues';
} elseif ($view === 'maintenance_jobs') {
    $page_title = 'Maintenance Work Orders';
    $active_nav = 'maintenance';
}

// 1. My assigned gadgets
$my_gadgets_q = mysqli_query($conn, 
    "SELECT g.* FROM gadgets g WHERE g.assigned_to = $uid AND g.status != 'retired' ORDER BY g.id DESC"
);
$my_gadgets = array();
$total_co2_kg = 0.0;
$average_health = 0;
$active_gadgets_count = 0;

if ($my_gadgets_q) {
    while ($row = mysqli_fetch_assoc($my_gadgets_q)) {
        $my_gadgets[] = $row;
        // Calculate health score & aggregate for stats
        $hs = calculate_health_score($conn, $row['id']);
        $average_health += $hs['score'];
        $active_gadgets_count++;
        
        // Green Pulse CO2 Calculations:
        // CO2 Manufacture base + repair penalties
        $rep_q = mysqli_query($conn, "SELECT COUNT(*) FROM issues WHERE gadget_id = " . (int)$row['id'] . " AND status = 'resolved'");
        $rep_count = 0;
        if ($rep_q) { $rep_count = (int)mysqli_fetch_row($rep_q)[0]; }
        
        $row_co2 = (float)$row['co2_manufacture'] + ($rep_count * (float)$row['co2_repair']);
        $total_co2_kg += $row_co2;
    }
}
if ($active_gadgets_count > 0) {
    $average_health = round($average_health / $active_gadgets_count);
} else {
    $average_health = 100;
}

// Calculate Eco Score Rating
// Target: keep repair penalties low and gadget age high. Default Eco grade is A.
$eco_grade = 'A';
$eco_color = '#10b981';
if ($total_co2_kg > 800) {
    $eco_grade = 'D'; $eco_color = '#ef4444';
} elseif ($total_co2_kg > 500) {
    $eco_grade = 'C'; $eco_color = '#f59e0b';
} elseif ($total_co2_kg > 250) {
    $eco_grade = 'B'; $eco_color = '#3b82f6';
}

// 2. My raised issues (active & resolved)
$my_issues_q = mysqli_query($conn,
    "SELECT i.*, g.name as gname, g.asset_tag FROM issues i 
     JOIN gadgets g ON i.gadget_id = g.id 
     WHERE i.raised_by = $uid 
     ORDER BY i.raised_at DESC"
);
$my_issues = array();
$my_open_count = 0;
$my_resolved_count = 0;
if ($my_issues_q) {
    while ($row = mysqli_fetch_assoc($my_issues_q)) {
        $my_issues[] = $row;
        if ($row['status'] === 'resolved') {
            $my_resolved_count++;
        } else {
            $my_open_count++;
        }
    }
}

// 3. Support Queue (Tickets assigned to me)
$assigned_issues = array();
if ($is_support) {
    $assigned_q = mysqli_query($conn,
        "SELECT i.*, g.name as gname, g.asset_tag, u.name as raised_name FROM issues i 
         JOIN gadgets g ON i.gadget_id = g.id 
         JOIN users u ON i.raised_by = u.id 
         WHERE i.assigned_to = $uid AND i.status != 'resolved' 
         ORDER BY i.raised_at DESC"
    );
    if ($assigned_q) {
        while ($row = mysqli_fetch_assoc($assigned_q)) {
            $assigned_issues[] = $row;
        }
    }
}

// 4. Maintenance Jobs assigned to me
$maintenance_jobs = array();
if ($is_support) {
    $maint_q = mysqli_query($conn,
        "SELECT m.*, g.name as gname, g.asset_tag FROM maintenance m 
         JOIN gadgets g ON m.gadget_id = g.id 
         WHERE m.technician_id = $uid AND m.completed_date IS NULL 
         ORDER BY m.scheduled_date ASC"
    );
    if ($maint_q) {
        while ($row = mysqli_fetch_assoc($maint_q)) {
            $maintenance_jobs[] = $row;
        }
    }
}

// Handle Maintenance Job updates via POST
if ($is_support && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_maintenance') {
    $maint_id = (int)$_POST['maintenance_id'];
    $cost = (float)$_POST['cost'];
    $notes = esc($conn, $_POST['notes']);
    
    // Update maintenance schedule
    $update_maint = mysqli_query($conn,
        "UPDATE maintenance SET completed_date = CURDATE(), cost = $cost, notes = '$notes' WHERE id = $maint_id AND technician_id = $uid"
    );
    
    if ($update_maint) {
        // Fetch gadget_id from maintenance to log audit and reset gadget repair state if needed
        $g_fetch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT gadget_id FROM maintenance WHERE id=$maint_id"));
        if ($g_fetch) {
            $gadget_id = (int)$g_fetch['gadget_id'];
            audit_log($conn, "Completed scheduled maintenance job ID: $maint_id", "maintenance", $maint_id);
            mysqli_query($conn, "UPDATE gadgets SET status='active' WHERE id=$gadget_id AND status='in-repair'");
        }
        tp_redirect('user_dashboard.php?view=maintenance_jobs&msg=maint_success');
    }
}

include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>
            <?php 
                if ($view === 'support_queue') echo 'Support Ticket Queue';
                elseif ($view === 'maintenance_jobs') echo 'Maintenance Work Orders';
                else echo 'Welcome, ' . htmlspecialchars(current_name()) . '!';
            ?>
        </h1>
        <p><?php echo date('l, d F Y'); ?> &nbsp;&bull;&nbsp; <?php echo $is_admin?'Administrator':($is_support?'IT Support Technician':'Employee Portal'); ?></p>
    </div>
    <?php if ($view === 'dashboard'): ?>
        <a href="raise_issue.php" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Report an Issue
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'maint_success'): ?>
    <div class="alert alert-success">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Maintenance job completed and logged successfully!
    </div>
<?php endif; ?>

<?php if ($view === 'dashboard'): ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card cyan">
            <div class="stat-icon cyan"><?php echo get_svg_icon('gadgets'); ?></div>
            <div class="stat-label">Assigned Devices</div>
            <div class="stat-value" data-count="<?php echo $active_gadgets_count; ?>"><?php echo $active_gadgets_count; ?></div>
            <div class="stat-sub">Active gadgets in use</div>
        </div>
        
        <div class="stat-card red">
            <div class="stat-icon red"><?php echo get_svg_icon('issues'); ?></div>
            <div class="stat-label">My Open Tickets</div>
            <div class="stat-value" data-count="<?php echo $my_open_count; ?>"><?php echo $my_open_count; ?></div>
            <div class="stat-sub">Pending technical repair</div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon green"><?php echo get_svg_icon('check'); ?></div>
            <div class="stat-label">Resolved Faults</div>
            <div class="stat-value" data-count="<?php echo $my_resolved_count; ?>"><?php echo $my_resolved_count; ?></div>
            <div class="stat-sub">Resolved issues history</div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon purple"><?php echo get_svg_icon('sustainability'); ?></div>
            <div class="stat-label">Average Health Grade</div>
            <div class="stat-value"><?php echo $average_health; ?>%</div>
            <div class="stat-sub">Overall health score</div>
        </div>
    </div>

    <!-- Green Pulse Sustainability Panel -->
    <div class="eco-banner">
        <div class="eco-banner-icon">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c.132 0 .263 0 .393.007a7.5 7.5 0 017.92 12.446A9 9 0 1112 3zm0 5v8m-3-5l3-3 3 3"/></svg>
        </div>
        <div class="eco-banner-text" style="flex:1;">
            <h3>Green Pulse Summary</h3>
            <p>Your devices have a combined carbon footprint of <strong><?php echo number_format($total_co2_kg, 1); ?> kg CO2</strong>. Extending device life reduces e-waste and improves your eco grade.</p>
        </div>
        <div style="text-align:right; flex-shrink:0;">
            <div style="font-size:2.4rem; font-weight:900; color:<?php echo $eco_color; ?>; line-height:1;"><?php echo $eco_grade; ?></div>
            <div style="font-size:0.72rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-top:3px;">Eco Grade</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- List of Assigned Devices -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('gadgets'); ?> My Assigned Devices</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($my_gadgets) > 0): ?>
                    <?php foreach ($my_gadgets as $g): 
                        $hs = calculate_health_score($conn, $g['id']);
                        // Fetch closest active warranty
                        $war_q = mysqli_query($conn, 
                            "SELECT start_date, end_date FROM warranties WHERE gadget_id = " . (int)$g['id'] . " AND end_date >= CURDATE() ORDER BY end_date ASC LIMIT 1"
                        );
                        $war = ($war_q) ? mysqli_fetch_assoc($war_q) : null;
                    ?>
                        <div style="display:flex; align-items:center; gap:18px; padding:16px 24px; border-bottom:1px solid var(--border); transition:background 0.15s;" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            <!-- Device Photo Thumbnail — real-product style -->
                            <?php if (!empty($g['image']) && file_exists('uploads/' . $g['image'])): ?>
                                <div style="width:64px; height:64px; border-radius:12px; background:var(--bg-card,#f8fafc); border:1px solid var(--border); flex-shrink:0; display:flex; align-items:center; justify-content:center; overflow:hidden; padding:4px;">
                                    <img src="/HP TechPulse/uploads/<?php echo htmlspecialchars($g['image']); ?>"
                                         alt="<?php echo htmlspecialchars($g['name']); ?>"
                                         style="width:100%; height:100%; object-fit:contain; display:block; border-radius:8px;">
                                </div>
                            <?php else: ?>
                                <div style="width:64px; height:64px; border-radius:12px; background:var(--red-dim); color:var(--red); display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid var(--border-r);">
                                    <?php echo get_category_icon($g['category'], 32); ?>
                                </div>
                            <?php endif; ?>

                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:700; font-size:0.95rem; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:3px;">
                                    <?php echo htmlspecialchars($g['name']); ?>
                                </div>
                                <div style="font-size:0.78rem; color:var(--text-muted); margin-bottom:7px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                    <span style="font-weight:600; color:var(--text-sub);"><?php echo htmlspecialchars($g['asset_tag']); ?></span>
                                    <span style="color:var(--border);">|</span>
                                    <span><?php echo htmlspecialchars($g['category']); ?></span>
                                    <?php if ($g['brand']): ?>
                                    <span style="color:var(--border);">|</span>
                                    <span><?php echo htmlspecialchars($g['brand']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Health Bar progress -->
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex:1; max-width:120px;">
                                        <div class="health-bar-bg">
                                            <div class="health-bar-fill" data-pct="<?php echo $hs['score']; ?>" style="width:<?php echo $hs['score']; ?>%; background:<?php echo $hs['color']; ?>;"></div>
                                        </div>
                                    </div>
                                    <span style="font-size:0.74rem; font-weight:700; color:<?php echo $hs['color']; ?>;">
                                        <?php echo $hs['score']; ?>/100 (<?php echo $hs['verdict']; ?>)
                                    </span>
                                </div>
                            </div>

                            <!-- Warranty Countdown Circle -->
                            <div>
                                <?php if ($war): ?>
                                    <?php echo warranty_ring_html($war['end_date'], $war['start_date']); ?>
                                <?php else: ?>
                                    <div style="font-size:0.72rem; color:var(--text-muted); text-align:center; width:56px; line-height:1.3;">No Active<br>Warranty</div>
                                <?php endif; ?>
                            </div>

                            <a href="gadget_profile.php?id=<?php echo $g['id']; ?>" class="btn btn-ghost btn-sm">Profile</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <?php echo get_svg_icon('gadgets'); ?>
                        <p>No active company gadgets are currently assigned to your account.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Raised Tickets Timeline -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('issues'); ?> My Open Support Tickets</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($my_issues) > 0): ?>
                    <?php foreach ($my_issues as $iss): ?>
                        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display:flex; flex-direction:column; gap:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                                <div style="font-weight:700; font-size:0.88rem; min-width:0;">
                                    <a href="issue_detail.php?id=<?php echo $iss['id']; ?>" style="color:var(--text);">
                                        <?php echo htmlspecialchars($iss['title']); ?>
                                    </a>
                                </div>
                                <?php echo issue_status_badge($iss['status']); ?>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.76rem; color:var(--text-muted);">
                                <span><?php echo htmlspecialchars($iss['gname']); ?> &bull; <?php echo date('d M Y H:i', strtotime($iss['raised_at'])); ?></span>
                                <?php echo priority_badge($iss['priority']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <?php echo get_svg_icon('issues'); ?>
                        <p>You have not logged any technical issues.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /.grid-2 -->

<?php elseif ($view === 'support_queue' && $is_support): ?>
    
    <div class="card">
        <div class="card-header">
            <h3><?php echo get_svg_icon('list'); ?> Active Assigned Support Tickets</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Device details</th>
                        <th>Issue summary</th>
                        <th>Reporter</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($assigned_issues) > 0): ?>
                        <?php foreach ($assigned_issues as $iss): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--text);"><?php echo htmlspecialchars($iss['gname']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($iss['asset_tag']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:var(--text);"><?php echo htmlspecialchars($iss['title']); ?></div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo date('d M Y, H:i', strtotime($iss['raised_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($iss['raised_name']); ?></td>
                                <td><?php echo priority_badge($iss['priority']); ?></td>
                                <td><?php echo issue_status_badge($iss['status']); ?></td>
                                <td>
                                    <a href="issue_detail.php?id=<?php echo $iss['id']; ?>" class="btn btn-outline btn-sm">View Ticket</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <?php echo get_svg_icon('check'); ?>
                                <p>All clear! No pending issues are assigned to you.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($view === 'maintenance_jobs' && $is_support): ?>
    
    <div class="card">
        <div class="card-header">
            <h3><?php echo get_svg_icon('maintenance'); ?> Assigned Maintenance Work orders</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (count($maintenance_jobs) > 0): ?>
                <?php foreach ($maintenance_jobs as $job): ?>
                    <div style="padding: 24px; border-bottom:1px solid var(--border); display:flex; flex-wrap:wrap; gap:20px; justify-content:space-between; align-items:flex-start;">
                        <div style="flex:1; min-width:250px;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                                <span class="badge badge-purple"><?php echo htmlspecialchars($job['service_type']); ?></span>
                                <span style="font-size:0.78rem; font-weight:700; color:var(--text-muted);"><?php echo htmlspecialchars($job['asset_tag']); ?></span>
                            </div>
                            <h4 style="font-size:1.05rem; font-weight:700; color:var(--text); margin-bottom:4px;"><?php echo htmlspecialchars($job['gname']); ?></h4>
                            <p style="font-size:0.84rem; color:var(--text-muted); margin-bottom:10px;">Scheduled Date: <strong><?php echo date('d M Y', strtotime($job['scheduled_date'])); ?></strong></p>
                            <?php if (!empty($job['notes'])): ?>
                                <div style="background:var(--bg); border: 1px solid var(--border); padding:10px 14px; border-radius:var(--radius-sm); font-size:0.8rem; color:var(--text-muted);">
                                    <strong>Admin Notes:</strong> <?php echo htmlspecialchars($job['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Completion Action Box -->
                        <div style="width:100%; max-width:320px; background:var(--bg-alt); border: 1px solid var(--border); border-radius:var(--radius); padding:16px;">
                            <h5 style="font-size:0.82rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); margin-bottom:12px; border-bottom:1px solid var(--border); padding-bottom:6px;">Mark Job Completed</h5>
                            
                            <form action="user_dashboard.php?view=maintenance_jobs" method="POST">
                                <input type="hidden" name="action" value="complete_maintenance">
                                <input type="hidden" name="maintenance_id" value="<?php echo $job['id']; ?>">
                                
                                <div class="form-group" style="margin-bottom:10px;">
                                    <label for="cost_<?php echo $job['id']; ?>" style="font-size:0.74rem;">Repair/Service Cost (INR)</label>
                                    <input type="number" step="0.01" name="cost" id="cost_<?php echo $job['id']; ?>" class="form-control" style="padding:6px 10px; font-size:0.82rem;" placeholder="0.00" required>
                                </div>

                                <div class="form-group" style="margin-bottom:12px;">
                                    <label for="notes_<?php echo $job['id']; ?>" style="font-size:0.74rem;">Technician Notes</label>
                                    <textarea name="notes" id="notes_<?php echo $job['id']; ?>" class="form-control" style="padding:6px 10px; font-size:0.82rem; height:60px; resize:none;" placeholder="Enter details of work done..." required></textarea>
                                </div>

                                <button type="submit" class="btn btn-success btn-sm btn-full">
                                    <?php echo get_svg_icon('check'); ?> Complete Job
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <?php echo get_svg_icon('maintenance'); ?>
                    <p>No pending maintenance work orders assigned to you.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
