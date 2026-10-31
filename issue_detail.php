<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_login(); // Verify login

if (!isset($_GET['id'])) {
    die("Error: No ticket ID provided.");
}
$issue_id = (int)$_GET['id'];
$uid  = current_uid();
$role = current_role();
$is_support = current_is_support();

// Fetch ticket details
$issue_q = mysqli_query($conn,
    "SELECT i.*, g.name as gname, g.asset_tag, g.assigned_to as g_assigned,
            u.name as raised_name, t.name as tech_name
     FROM issues i
     JOIN gadgets g ON i.gadget_id = g.id
     JOIN users u ON i.raised_by = u.id
     LEFT JOIN users t ON i.assigned_to = t.id
     WHERE i.id = $issue_id"
);
$iss = mysqli_fetch_assoc($issue_q);
if (!$iss) {
    die("Error: Ticket not found.");
}

// Access Control
// Employees can only view issues they raised themselves or issues for gadgets assigned to them
if ($role === 'user' && !$is_support) {
    if ($iss['raised_by'] !== $uid && $iss['g_assigned'] !== $uid) {
        tp_redirect('user_dashboard.php?msg=access_denied');
    }
}

$error = '';
$success = '';

// Handle Status Updates (Admins and Assigned Support Users)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    // Only admin or the assigned support tech can update status
    if ($role === 'admin' || ($is_support && $iss['assigned_to'] == $uid)) {
        $new_status = esc($conn, $_POST['status']);
        $note       = esc($conn, $_POST['note']);
        $old_status = $iss['status'];

        $valid_stages = array('raised', 'assigned', 'in-progress', 'testing', 'resolved');
        
        if (!in_array($new_status, $valid_stages)) {
            $error = 'Invalid status selected.';
        } elseif (empty($note)) {
            $error = 'An update note is required.';
        } else {
            // Update ticket status
            $resolved_clause = ($new_status === 'resolved') ? ", resolved_at=NOW()" : "";
            
            $upd = mysqli_query($conn, 
                "UPDATE issues SET status='$new_status' $resolved_clause WHERE id=$issue_id"
            );

            if ($upd) {
                // Log timeline change
                mysqli_query($conn,
                    "INSERT INTO issue_updates (issue_id, updated_by, old_status, new_status, note, updated_at)
                     VALUES ($issue_id, $uid, '$old_status', '$new_status', '$note', NOW())"
                );

                // If resolved, set gadget state back to active
                if ($new_status === 'resolved') {
                    mysqli_query($conn, "UPDATE gadgets SET status='active' WHERE id=" . (int)$iss['gadget_id'] . " AND status='in-repair'");
                }

                audit_log($conn, "Updated ticket ID $issue_id status: $old_status -> $new_status", "issues", $issue_id);
                
                // Refresh details
                tp_redirect("issue_detail.php?id=$issue_id&msg=updated");
            } else {
                $error = 'Failed to update ticket. Please try again.';
            }
        }
    } else {
        $error = 'Permission denied: You cannot update this ticket.';
    }
}

// Fetch Timeline updates
$updates_q = mysqli_query($conn,
    "SELECT iu.*, u.name as updater_name, u.role as updater_role, u.is_support as updater_support
     FROM issue_updates iu
     JOIN users u ON iu.updated_by = u.id
     WHERE iu.issue_id = $issue_id
     ORDER BY iu.updated_at DESC"
);
$updates = array();
if ($updates_q) {
    while ($row = mysqli_fetch_assoc($updates_q)) { $updates[] = $row; }
}

$page_title = 'Ticket #' . $iss['id'] . ' Details';
$active_nav = ($role === 'admin') ? 'issues' : 'dashboard';
include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Support Ticket #<?php echo $iss['id']; ?></h1>
        <p><?php echo htmlspecialchars($iss['title']); ?></p>
    </div>
    <a href="<?php echo ($role === 'admin') ? 'admin_issues.php' : 'user_dashboard.php'; ?>" class="btn btn-outline">
        &larr; Back to List
    </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="flash-msg flash-success">
        <?php echo get_svg_icon('check'); ?> Support ticket updated successfully!
    </div>
<?php endif; ?>

<div class="main-grid-layout">
    
    <!-- Left Column: Timeline & Comments -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        
        <!-- Ticket Information Summary -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('issues'); ?> Ticket Overview</h3>
                <div>
                    <?php echo priority_badge($iss['priority']); ?>
                    <?php echo issue_status_badge($iss['status']); ?>
                </div>
            </div>
            <div class="card-body">
                <div style="background:var(--bg-alt); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border); font-size:0.88rem; color:var(--text); margin-bottom:20px; line-height:1.6;">
                    <strong style="display:block; color:var(--text-muted); font-size:0.75rem; text-transform:uppercase; margin-bottom:6px;">Fault Description:</strong>
                    <?php echo nl2br(htmlspecialchars($iss['description'])); ?>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; font-size:0.82rem;">
                    <div>
                        <span style="color:var(--text-muted); display:block;">Reported Device:</span>
                        <strong>
                            <a href="gadget_profile.php?id=<?php echo $iss['gadget_id']; ?>">
                                <?php echo htmlspecialchars($iss['gname']); ?> [<?php echo htmlspecialchars($iss['asset_tag']); ?>]
                            </a>
                        </strong>
                    </div>
                    <div>
                        <span style="color:var(--text-muted); display:block;">Raised By:</span>
                        <strong><?php echo htmlspecialchars($iss['raised_name']); ?></strong>
                    </div>
                    <div>
                        <span style="color:var(--text-muted); display:block;">Assigned Handler:</span>
                        <strong><?php echo $iss['tech_name'] ? htmlspecialchars($iss['tech_name']) : '<span style="color:var(--warning);">Unassigned</span>'; ?></strong>
                    </div>
                    <div>
                        <span style="color:var(--text-muted); display:block;">Reported Timestamp:</span>
                        <strong><?php echo date('d M Y, H:i', strtotime($iss['raised_at'])); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="overflow:visible;">
            <div class="card-header" style="border-bottom:1px solid var(--border);">
                <h3>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-3px;margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Pipeline Tracking
                </h3>
                <?php
                $pipe_badge_map = array(
                    'raised'      => array('label'=>'Raised',      'color'=>'#f97316','bg'=>'rgba(249,115,22,0.12)'),
                    'assigned'    => array('label'=>'Assigned',    'color'=>'#3b82f6','bg'=>'rgba(59,130,246,0.12)'),
                    'in-progress' => array('label'=>'In Progress', 'color'=>'#a855f7','bg'=>'rgba(168,85,247,0.12)'),
                    'testing'     => array('label'=>'Testing',     'color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.12)'),
                    'resolved'    => array('label'=>'Resolved',    'color'=>'#22c55e','bg'=>'rgba(34,197,94,0.12)'),
                );
                $pb = isset($pipe_badge_map[$iss['status']]) ? $pipe_badge_map[$iss['status']] : array('label'=>ucfirst($iss['status']),'color'=>'#94a3b8','bg'=>'rgba(148,163,184,0.1)');
                echo '<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;background:'.$pb['bg'].';color:'.$pb['color'].';border:1px solid '.$pb['color'].'33;">';
                echo '<span style="width:6px;height:6px;border-radius:50%;background:'.$pb['color'].';display:inline-block;"></span>';
                echo htmlspecialchars($pb['label']);
                echo '</span>';
                ?>
            </div>
            <div class="card-body" style="padding:32px 28px 28px;">
                <?php
                $stages       = array('raised', 'assigned', 'in-progress', 'testing', 'resolved');
                $stage_labels = array('Raised', 'Assigned', 'In Progress', 'Testing', 'Resolved');
                $stage_icons  = array(
                    /* raised */      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
                    /* assigned */    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                    /* in-progress */ '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                    /* testing */     '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                    /* resolved */    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                );
                $stage_colors = array(
                    'raised'      => array('node'=>'#f97316','glow'=>'rgba(249,115,22,0.35)','track'=>'#f97316'),
                    'assigned'    => array('node'=>'#3b82f6','glow'=>'rgba(59,130,246,0.35)', 'track'=>'#3b82f6'),
                    'in-progress' => array('node'=>'#a855f7','glow'=>'rgba(168,85,247,0.35)', 'track'=>'#a855f7'),
                    'testing'     => array('node'=>'#f59e0b','glow'=>'rgba(245,158,11,0.35)', 'track'=>'#f59e0b'),
                    'resolved'    => array('node'=>'#22c55e','glow'=>'rgba(34,197,94,0.35)',  'track'=>'#22c55e'),
                );
                $current_idx = array_search($iss['status'], $stages);
                if ($current_idx === false) $current_idx = 0;

                // Progress percentage for the filled track bar
                $track_pct = ($current_idx / (count($stages) - 1)) * 100;
                $cur_color = isset($stage_colors[$iss['status']]) ? $stage_colors[$iss['status']] : array('node'=>'#94a3b8','glow'=>'rgba(148,163,184,0.3)','track'=>'#94a3b8');
                ?>

                <!-- Pipeline wrapper -->
                <div class="tp-pipeline">
                    <!-- Background track -->
                    <div class="tp-track">
                        <div class="tp-track-fill" style="width:<?php echo $track_pct; ?>%;background:linear-gradient(to right,<?php echo $stage_colors['raised']['track']; ?>,<?php echo $cur_color['track']; ?>);"></div>
                    </div>

                    <!-- Stage nodes -->
                    <div class="tp-stages">
                    <?php foreach ($stages as $idx => $st):
                        $done    = $idx < $current_idx;
                        $current = $idx === $current_idx;
                        $future  = $idx > $current_idx;
                        $sc      = isset($stage_colors[$st]) ? $stage_colors[$st] : array('node'=>'#94a3b8','glow'=>'rgba(148,163,184,0.3)','track'=>'#94a3b8');
                    ?>
                    <div class="tp-stage <?php echo $done?'tp-done':($current?'tp-current':'tp-future'); ?>">

                        <!-- Node circle -->
                        <div class="tp-node"
                             style="<?php if($done||$current): ?>background:<?php echo $sc['node']; ?>;border-color:<?php echo $sc['node']; ?>;<?php if($current): ?>box-shadow:0 0 0 4px <?php echo $sc['glow']; ?>,0 0 18px <?php echo $sc['glow']; ?>;<?php endif; ?><?php else: ?>background:var(--bg-alt);border-color:var(--border);<?php endif; ?>">
                            <?php if ($done): ?>
                                <!-- Completed checkmark -->
                                <svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            <?php elseif ($current): ?>
                                <!-- Current stage icon -->
                                <svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24">
                                    <?php echo $stage_icons[$idx]; ?>
                                </svg>
                            <?php else: ?>
                                <!-- Future step number -->
                                <span style="font-size:0.68rem;font-weight:800;color:var(--text-muted);"><?php echo ($idx+1); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Label -->
                        <div class="tp-label">
                            <span class="tp-label-name" style="<?php if($done||$current): ?>color:<?php echo $sc['node']; ?>;<?php endif; ?>">
                                <?php echo $stage_labels[$idx]; ?>
                            </span>
                            <?php if ($current): ?>
                            <span class="tp-label-badge" style="background:<?php echo $sc['glow']; ?>;color:<?php echo $sc['node']; ?>;">Active</span>
                            <?php elseif ($done): ?>
                            <span class="tp-label-badge" style="background:rgba(34,197,94,0.1);color:#22c55e;">Done</span>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                    </div><!-- /.tp-stages -->
                </div><!-- /.tp-pipeline -->

            </div>
        </div>

        <style>
        .tp-pipeline {
            position: relative;
            padding: 8px 0 4px;
        }
        /* Connecting track */
        .tp-track {
            position: absolute;
            top: 26px;
            left: calc(10% + 18px);
            right: calc(10% + 18px);
            height: 4px;
            background: var(--border);
            border-radius: 99px;
            z-index: 1;
        }
        .tp-track-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.6s cubic-bezier(0.4,0,0.2,1);
        }
        /* Stages row */
        .tp-stages {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            z-index: 2;
        }
        /* Individual stage */
        .tp-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }
        /* Node circle */
        .tp-node {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2.5px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.35s ease;
            position: relative;
            flex-shrink: 0;
        }
        /* Animated pulse on current */
        .tp-current .tp-node::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 2px solid currentColor;
            animation: tp-ping 1.8s cubic-bezier(0,0,0.2,1) infinite;
            opacity: 0.35;
        }
        @keyframes tp-ping {
            0%   { transform: scale(0.85); opacity: 0.4; }
            70%  { transform: scale(1.25); opacity: 0;   }
            100% { transform: scale(1.25); opacity: 0;   }
        }
        /* Labels */
        .tp-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            text-align: center;
        }
        .tp-label-name {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-muted);
            white-space: nowrap;
            letter-spacing: 0.02em;
            transition: color 0.3s;
        }
        .tp-future .tp-label-name { color: var(--text-darker); font-weight: 500; }
        .tp-label-badge {
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 2px 7px;
            border-radius: 20px;
        }
        @media (max-width: 600px) {
            .tp-label-name  { font-size: 0.62rem; }
            .tp-node        { width: 36px; height: 36px; }
            .tp-track       { top: 22px; }
        }
        </style>

        <!-- Ticket Update logs timeline -->
        <div class="card">
            <div class="card-header">
                <h3>Timeline Action History</h3>
            </div>
            <div class="card-body" style="padding: 24px 32px;">
                <?php if (count($updates) > 0): ?>
                    <div style="position:relative; border-left:2px solid var(--border); padding-left:20px; display:flex; flex-direction:column; gap:20px;">
                        <?php foreach ($updates as $up): 
                            $stage_label = isset($stage_labels[array_search($up['new_status'], $stages)]) ? $stage_labels[array_search($up['new_status'], $stages)] : ucfirst($up['new_status']);
                            $up_role = ($up['updater_role'] === 'admin') ? 'Admin' : ($up['updater_support'] ? 'Support' : 'Employee');
                            $up_color = ($up['updater_role'] === 'admin') ? 'var(--primary)' : ($up['updater_support'] ? 'var(--purple)' : 'var(--success)');
                        ?>
                            <div style="position:relative;">
                                <!-- Timeline bullet -->
                                <div style="position:absolute; top:4px; left:-27px; width:12px; height:12px; border-radius:50%; background:var(--bg); border:3px solid <?php echo $up_color; ?>;"></div>
                                
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                    <span style="font-size:0.84rem; font-weight:700; color:var(--text);">
                                        Status set to <span style="color:<?php echo $up_color; ?>;"><?php echo $stage_label; ?></span>
                                    </span>
                                    <span style="font-size:0.74rem; color:var(--text-darker);"><?php echo date('d M Y, H:i', strtotime($up['updated_at'])); ?></span>
                                </div>
                                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px;">
                                    Updated by <strong><?php echo htmlspecialchars($up['updater_name']); ?></strong> (<?php echo $up_role; ?>)
                                </div>
                                <div style="background:var(--bg-alt); padding:8px 12px; border-radius:var(--radius-sm); font-size:0.82rem; color:var(--text); border:1px solid var(--border);">
                                    <?php echo htmlspecialchars($up['note']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; color:var(--text-darker);">No history logs logged yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right Column: Status Editor (Admins & Assigned Techs) -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        
        <?php 
        $can_update = ($role === 'admin' || ($is_support && $iss['assigned_to'] == $uid));
        if ($can_update && $iss['status'] !== 'resolved'): 
        ?>
            <!-- Update Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Update Ticket Status</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div style="background:var(--danger-bg); color:var(--danger); padding:10px; border-radius:var(--radius-sm); margin-bottom:16px; font-weight:600; font-size:0.8rem;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="issue_detail.php?id=<?php echo $issue_id; ?>" method="POST">
                        <input type="hidden" name="action" value="update_status">

                        <div class="form-group">
                            <label for="status">Next Pipeline Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="raised" <?php echo $iss['status'] === 'raised' ? 'selected' : ''; ?>>Raised</option>
                                <option value="assigned" <?php echo $iss['status'] === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                <option value="in-progress" <?php echo $iss['status'] === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="testing" <?php echo $iss['status'] === 'testing' ? 'selected' : ''; ?>>Testing</option>
                                <option value="resolved" <?php echo $iss['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved (Closes Ticket)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="note">Update Description / Action Note</label>
                            <textarea name="note" id="note" class="form-control" style="height:100px;" placeholder="Describe details of the update, actions taken, or parts replaced..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">
                            Submit Log Update
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Read-only panel for users/handlers -->
            <div class="card">
                <div class="card-header">
                    <h3>Ticket Handling Status</h3>
                </div>
                <div class="card-body">
                    <?php if ($iss['status'] === 'resolved'): ?>
                        <div style="text-align:center; padding:10px 0;">
                            <div style="font-size:2rem; color:var(--success); margin-bottom:8px;">✓</div>
                            <h4 style="color:var(--success); font-weight:700;">Issue Resolved</h4>
                            <p style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;">
                                This ticket has been marked completed.<br>
                                Resolved on: <strong><?php echo date('d M Y, H:i', strtotime($iss['resolved_at'])); ?></strong>
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:10px 0; color:var(--text-muted);">
                            <div style="font-size:1.8rem; margin-bottom:8px;">🔧</div>
                            <h4 style="font-weight:700; color:var(--text);">Awaiting Action</h4>
                            <p style="font-size:0.78rem; margin-top:4px; line-height:1.45;">
                                <?php if ($is_support && $iss['assigned_to'] != $uid): ?>
                                    This ticket is assigned to another support technician: <strong><?php echo htmlspecialchars($iss['tech_name']); ?></strong>.
                                <?php else: ?>
                                    IT Support is currently diagnosing this fault. Updates will appear in the timeline below.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>
