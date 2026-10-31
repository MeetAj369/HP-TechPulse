<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_user(); // User guard

$uid = current_uid();
$error = '';
$success = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gadget_id   = isset($_POST['gadget_id']) ? (int)$_POST['gadget_id'] : 0;
    $title       = esc($conn, $_POST['title']);
    $description = esc($conn, $_POST['description']);
    $priority    = esc($conn, $_POST['priority']);

    // Validate that the gadget is assigned to this user
    $chk = mysqli_query($conn, "SELECT id FROM gadgets WHERE id=$gadget_id AND assigned_to=$uid AND status != 'retired' LIMIT 1");
    
    if (empty($title) || empty($description) || empty($priority) || $gadget_id === 0) {
        $error = 'All fields are required.';
    } elseif (mysqli_num_rows($chk) !== 1) {
        $error = 'Invalid device selected. You can only report issues for your assigned gadgets.';
    } else {
        // Create support ticket
        $ins = mysqli_query($conn,
            "INSERT INTO issues (gadget_id, raised_by, title, description, priority, status, raised_at)
             VALUES ($gadget_id, $uid, '$title', '$description', '$priority', 'raised', NOW())"
        );

        if ($ins) {
            $issue_id = mysqli_insert_id($conn);
            
            // Create timeline log entry
            mysqli_query($conn,
                "INSERT INTO issue_updates (issue_id, updated_by, old_status, new_status, note, updated_at)
                 VALUES ($issue_id, $uid, NULL, 'raised', 'Issue logged by employee.', NOW())"
            );

            // Update gadget status to in-repair temporarily if priority is critical/high
            if ($priority === 'critical' || $priority === 'high') {
                mysqli_query($conn, "UPDATE gadgets SET status='in-repair' WHERE id=$gadget_id");
            }

            audit_log($conn, "Logged support ticket ID: $issue_id", "issues", $issue_id);
            $success = 'Your support ticket has been successfully registered. IT Support will assign a technician shortly.';
        } else {
            $error = 'Failed to log the issue. Please try again or contact support.';
        }
    }
}

// Fetch user's assigned gadgets
$devices_q = mysqli_query($conn, "SELECT id, name, asset_tag, category FROM gadgets WHERE assigned_to=$uid AND status != 'retired' ORDER BY name ASC");
$devices = array();
if ($devices_q) {
    while ($row = mysqli_fetch_assoc($devices_q)) {
        $devices[] = $row;
    }
}

$page_title = 'Report Device Fault';
$active_nav = 'raise';
include 'header.php';
?>

<div style="max-width: 650px; margin: 0 auto;">
    <div class="card anim-fadeup">
        <div class="card-header">
            <h3><?php echo get_svg_icon('issues'); ?> Report a Device Fault</h3>
        </div>
        <div class="card-body">
            
            <?php if (!empty($error)): ?>
                <div style="background:var(--danger-bg); color:var(--danger); padding:12px; border-radius:var(--radius-sm); border:1px solid rgba(239,68,68,0.15); margin-bottom:20px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <?php echo get_svg_icon('issues'); ?>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="flash-msg flash-success">
                    <?php echo get_svg_icon('check'); ?>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <div style="text-align: center; margin-top:10px;">
                    <a href="user_dashboard.php" class="btn btn-outline">Go to Dashboard</a>
                </div>
            <?php else: ?>

                <form action="raise_issue.php" method="POST">
                    
                    <div class="form-group">
                        <label for="gadget_id">Select Affected Device</label>
                        <select name="gadget_id" id="gadget_id" class="form-control" required>
                            <option value="">-- Choose one of your gadgets --</option>
                            <?php foreach ($devices as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo (isset($_POST['gadget_id']) && $_POST['gadget_id'] == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['name'] . ' [' . $d['asset_tag'] . ']'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="font-size:0.75rem; color:var(--text-darker); margin-top:4px; display:block;">Only devices currently assigned to you are listed.</span>
                    </div>

                    <div class="form-group">
                        <label for="title">Issue Subject / Summary</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Screen flickering, Keyboard keys stuck" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="priority">Issue Priority</label>
                        <select name="priority" id="priority" class="form-control" required>
                            <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>Low (General question / minor glitch)</option>
                            <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] === 'medium') ? 'selected' : 'selected'; ?>>Medium (Device usable, but has issues)</option>
                            <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>High (Hard to work / frequent crashes)</option>
                            <option value="critical" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'critical') ? 'selected' : ''; ?>>Critical (System completely dead / blocked)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Detailed Fault Description</label>
                        <textarea name="description" id="description" class="form-control" style="height:140px;" placeholder="Please describe exactly what is happening, when it started, and any troubleshooting steps you attempted..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:28px;">
                        <a href="user_dashboard.php" style="font-weight:600; color:var(--text-muted);">&larr; Back to Dashboard</a>
                        <button type="submit" class="btn btn-primary">
                            Submit Support Ticket
                        </button>
                    </div>

                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
