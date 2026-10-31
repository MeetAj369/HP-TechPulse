<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_support(); // Guard Admin and Support users

$role = current_role();
$is_admin = ($role === 'admin');
$uid = current_uid();
$error = '';
$success = '';

// Handle Ticket Assignment (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_tech') {
    if (!$is_admin) {
        tp_redirect('admin_issues.php?msg=admin_required');
    }
    
    $issue_id = (int)$_POST['issue_id'];
    $tech_id  = (int)$_POST['technician_id'];

    if (empty($issue_id) || empty($tech_id)) {
        $error = 'Please select a valid technician.';
    } else {
        // Fetch current status of the issue
        $chk_q = mysqli_query($conn, "SELECT status FROM issues WHERE id=$issue_id LIMIT 1");
        $chk = mysqli_fetch_assoc($chk_q);
        
        if ($chk) {
            $old_status = $chk['status'];
            $new_status = ($old_status === 'raised') ? 'assigned' : $old_status; // Upgrade status to 'assigned' if it was just 'raised'

            $upd = mysqli_query($conn,
                "UPDATE issues SET assigned_to=$tech_id, status='$new_status' WHERE id=$issue_id"
            );

            if ($upd) {
                // Log timeline update
                $note = "Ticket assigned to technician ID: $tech_id.";
                mysqli_query($conn,
                    "INSERT INTO issue_updates (issue_id, updated_by, old_status, new_status, note, updated_at)
                     VALUES ($issue_id, $uid, '$old_status', '$new_status', '$note', NOW())"
                );

                audit_log($conn, "Assigned ticket ID $issue_id to tech ID: $tech_id", "issues", $issue_id);
                $success = 'Technician assigned successfully!';
            } else {
                $error = 'Failed to assign technician.';
            }
        }
    }
}

// Fetch technicians (users where is_support = 1 or role = admin)
$techs = array();
$tq = mysqli_query($conn, "SELECT id, name, email FROM users WHERE (role='user' AND is_support=1) OR role='admin' ORDER BY name ASC");
if ($tq) {
    while ($row = mysqli_fetch_assoc($tq)) { $techs[] = $row; }
}

// Build Search & Filter Conditions
$search_clause = "";
$status_filter = "";
$priority_filter = "";
$cat_filter = "";
$tech_filter = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $s = esc($conn, $_GET['search']);
    $search_clause = " AND (i.title LIKE '%$s%' OR g.name LIKE '%$s%' OR g.asset_tag LIKE '%$s%' OR u.name LIKE '%$s%' OR t.name LIKE '%$s%' OR CONCAT('#TK-', i.id) LIKE '%$s%') ";
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $st = esc($conn, $_GET['status']);
    if ($st === 'in-progress') {
        $status_filter = " AND i.status IN ('in-progress', 'assigned', 'testing') ";
    } else {
        $status_filter = " AND i.status='$st' ";
    }
}
if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $pr = esc($conn, $_GET['priority']);
    if ($pr === 'critical') {
        $priority_filter = " AND i.priority='critical' AND i.status != 'resolved' ";
    } else {
        $priority_filter = " AND i.priority='$pr' ";
    }
}
if (isset($_GET['cat']) && !empty($_GET['cat'])) {
    $c = esc($conn, $_GET['cat']);
    $cat_filter = " AND g.category='$c' ";
}
if (isset($_GET['tech_f']) && !empty($_GET['tech_f'])) {
    $tf = (int)$_GET['tech_f'];
    if ($tf > 0) {
        $tech_filter = " AND i.assigned_to=$tf ";
    } elseif ($_GET['tech_f'] === 'unassigned') {
        $tech_filter = " AND i.assigned_to IS NULL ";
    }
}

// Fetch issues list
$issues = array();
$list_q = mysqli_query($conn,
    "SELECT i.*, g.name as gname, g.asset_tag, g.category, g.image as gimage, u.name as raised_name, u.email as raised_email, t.name as tech_name
     FROM issues i
     JOIN gadgets g ON i.gadget_id = g.id
     JOIN users u ON i.raised_by = u.id
     LEFT JOIN users t ON i.assigned_to = t.id
     WHERE 1=1 $search_clause $status_filter $priority_filter $cat_filter $tech_filter
     ORDER BY i.status = 'resolved' ASC, CASE i.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END ASC, i.raised_at DESC"
);
if ($list_q) {
    while ($row = mysqli_fetch_assoc($list_q)) { $issues[] = $row; }
}

// Calculate Summary Statistics (Unfiltered for stat bar)
$st_total = 0; $st_raised = 0; $st_in_prog = 0; $st_critical = 0; $st_resolved = 0;
$sq = mysqli_query($conn, "SELECT status, priority FROM issues");
if ($sq) {
    while ($sr = mysqli_fetch_assoc($sq)) {
        $st_total++;
        if ($sr['status'] === 'raised')      $st_raised++;
        if ($sr['status'] === 'in-progress' || $sr['status'] === 'assigned' || $sr['status'] === 'testing') $st_in_prog++;
        if ($sr['status'] === 'resolved')    $st_resolved++;
        if ($sr['priority'] === 'critical' && $sr['status'] !== 'resolved')  $st_critical++;
    }
}

$page_title = 'Helpdesk Support Desk';
$active_nav = 'issues';
include 'header.php';
?>

<style>
.inv-stat-bar { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
@media(max-width:1100px) { .inv-stat-bar { grid-template-columns: repeat(3, 1fr); } }
@media(max-width:700px)  { .inv-stat-bar { grid-template-columns: 1fr 1fr; } }

.inv-stat {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  transition: all 0.25s var(--ease);
}
.inv-stat:hover {
  transform: translateY(-3px);
  border-color: var(--border-r);
  box-shadow: 0 10px 24px rgba(0,0,0,0.3);
}
.inv-stat-icon {
  width: 44px; height: 44px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.inv-stat-val { font-size: 1.45rem; font-weight: 900; line-height: 1; margin-bottom: 3px; }
.inv-stat-lbl { font-size: 0.76rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }

.inv-toolbar {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 18px;
  margin-bottom: 24px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}
.inv-search-wrap {
  position: relative;
  flex: 1;
  min-width: 240px;
}
.inv-search-wrap input {
  padding-left: 36px;
  height: 38px;
  font-size: 0.85rem;
}
.inv-search-wrap svg {
  position: absolute;
  left: 11px; top: 11px;
  color: var(--text-muted);
  pointer-events: none;
}

.tk-pill {
  font-family: monospace;
  font-weight: 800;
  font-size: 0.72rem;
  background: rgba(232, 19, 42, 0.12);
  color: var(--red);
  padding: 2px 7px;
  border-radius: 6px;
  border: 1px solid var(--border-r);
  display: inline-block;
}

.priority-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
  text-transform: capitalize;
}
.priority-critical { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
.priority-high     { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
.priority-medium   { background: rgba(59, 130, 246, 0.15); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
.priority-low      { background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); }

.priority-dot {
  width: 6px; height: 6px; border-radius: 50%; display: inline-block;
}
.priority-critical .priority-dot { background: #ef4444; box-shadow: 0 0 8px #ef4444; animation: tp-badge-pop 1.5s infinite; }
.priority-high .priority-dot     { background: #f59e0b; }
.priority-medium .priority-dot   { background: #3b82f6; }
.priority-low .priority-dot      { background: #94a3b8; }

.data-table th { padding: 12px 10px; font-size: 0.72rem; }
.data-table td { padding: 12px 10px; font-size: 0.84rem; }
</style>

<!-- Page Header -->
<div class="page-header">
  <div class="page-header-left">
    <h1>Support Desk Tickets</h1>
    <p>Monitor real-time ticket queues, dispatch technicians, and resolve employee incidents.</p>
  </div>
  <div style="display:flex; gap:10px; align-items:center;">
    <a href="raise_issue.php" class="btn btn-primary">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Report New Issue
    </a>
  </div>
</div>

<!-- Alert messages -->
<?php if (!empty($error)): ?>
  <div style="background:var(--danger-bg); color:var(--danger); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid rgba(239,68,68,0.2); margin-bottom:20px; font-weight:600; display:flex; align-items:center; gap:10px;">
    <?php echo get_svg_icon('issues'); ?>
    <span><?php echo htmlspecialchars($error); ?></span>
  </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
  <div class="flash-msg flash-success" style="margin-bottom:20px;">
    <?php echo get_svg_icon('check'); ?>
    <span><?php echo htmlspecialchars($success); ?></span>
  </div>
<?php endif; ?>

<!-- ── Clickable Quick Stats Bar ── -->
<div class="inv-stat-bar">
  <a href="admin_issues.php" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(26,107,255,0.12);">
      <svg fill="none" stroke="var(--blue)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    </div>
    <div>
      <div class="inv-stat-val"><?php echo $st_total; ?></div>
      <div class="inv-stat-lbl">Total Tickets</div>
    </div>
  </a>

  <a href="admin_issues.php?status=raised" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(245,158,11,0.12);">
      <svg fill="none" stroke="var(--warning)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:var(--warning);"><?php echo $st_raised; ?></div>
      <div class="inv-stat-lbl">Action Required</div>
    </div>
  </a>

  <a href="admin_issues.php?status=in-progress" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(168,85,247,0.12);">
      <svg fill="none" stroke="#a855f7" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:#a855f7;"><?php echo $st_in_prog; ?></div>
      <div class="inv-stat-lbl">In Progress</div>
    </div>
  </a>

  <a href="admin_issues.php?priority=critical" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(239,68,68,0.12);">
      <svg fill="none" stroke="var(--red)" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:var(--red);"><?php echo $st_critical; ?></div>
      <div class="inv-stat-lbl">Critical</div>
    </div>
  </a>

  <a href="admin_issues.php?status=resolved" class="inv-stat" style="text-decoration:none;">
    <div class="inv-stat-icon" style="background:rgba(34,197,94,0.12);">
      <svg fill="none" stroke="#22c55e" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div>
      <div class="inv-stat-val" style="color:#22c55e;"><?php echo $st_resolved; ?></div>
      <div class="inv-stat-lbl">Resolved</div>
    </div>
  </a>
</div>

<!-- ── Multi-Parameter Toolbar ── -->
<form action="admin_issues.php" method="GET" class="inv-toolbar">
  <div class="inv-search-wrap">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" name="search" class="form-control" placeholder="Search by Ticket ID, summary, asset tag, reporter..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
  </div>

  <select name="status" class="form-control" style="width:145px;height:38px;">
    <option value="">All Statuses</option>
    <option value="raised"      <?php echo (isset($_GET['status']) && $_GET['status'] === 'raised')      ? 'selected' : ''; ?>>Raised</option>
    <option value="assigned"    <?php echo (isset($_GET['status']) && $_GET['status'] === 'assigned')    ? 'selected' : ''; ?>>Assigned</option>
    <option value="in-progress" <?php echo (isset($_GET['status']) && $_GET['status'] === 'in-progress') ? 'selected' : ''; ?>>In Progress</option>
    <option value="testing"     <?php echo (isset($_GET['status']) && $_GET['status'] === 'testing')     ? 'selected' : ''; ?>>Testing</option>
    <option value="resolved"    <?php echo (isset($_GET['status']) && $_GET['status'] === 'resolved')    ? 'selected' : ''; ?>>Resolved</option>
  </select>

  <select name="priority" class="form-control" style="width:135px;height:38px;">
    <option value="">All Priorities</option>
    <option value="critical" <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'critical') ? 'selected' : ''; ?>>Critical</option>
    <option value="high"     <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'high')     ? 'selected' : ''; ?>>High</option>
    <option value="medium"   <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'medium')   ? 'selected' : ''; ?>>Medium</option>
    <option value="low"      <?php echo (isset($_GET['priority']) && $_GET['priority'] === 'low')      ? 'selected' : ''; ?>>Low</option>
  </select>

  <select name="cat" class="form-control" style="width:140px;height:38px;">
    <option value="">All Categories</option>
    <?php foreach (array('Laptop','Mobile','Monitor','Tablet','Desktop','Printer','Accessory') as $c): ?>
      <option value="<?php echo $c; ?>" <?php echo (isset($_GET['cat']) && $_GET['cat'] === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
    <?php endforeach; ?>
  </select>

  <select name="tech_f" class="form-control" style="width:160px;height:38px;">
    <option value="">All Technicians</option>
    <option value="unassigned" <?php echo (isset($_GET['tech_f']) && $_GET['tech_f'] === 'unassigned') ? 'selected' : ''; ?>>Unassigned Only</option>
    <?php foreach ($techs as $t): ?>
      <option value="<?php echo $t['id']; ?>" <?php echo (isset($_GET['tech_f']) && $_GET['tech_f'] == $t['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit" class="btn btn-outline" style="height:38px;padding:0 18px;font-size:0.85rem;">Filter</button>

  <?php if (!empty($_GET['search']) || !empty($_GET['status']) || !empty($_GET['priority']) || !empty($_GET['cat']) || !empty($_GET['tech_f'])): ?>
    <a href="admin_issues.php" class="btn btn-ghost" style="height:38px;padding:0 14px;font-size:0.85rem;">Clear</a>
  <?php endif; ?>

  <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted);white-space:nowrap;"><?php echo count($issues); ?> ticket<?php echo count($issues) !== 1 ? 's' : ''; ?> found</span>
</form>

<!-- ── Tickets Data Table ── -->
<div class="card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Ticket Details</th>
          <th>Affected Hardware</th>
          <th>Reporter</th>
          <th>Priority</th>
          <th>Pipeline Status</th>
          <th>Assigned Technician</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($issues) > 0): ?>
          <?php foreach ($issues as $iss): 
            $pr_class = 'priority-' . strtolower($iss['priority']);
            $tk_code  = '#TK-' . str_pad($iss['id'], 4, '0', STR_PAD_LEFT);
          ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                  <span class="tk-pill"><?php echo $tk_code; ?></span>
                  <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo date('d M Y, H:i', strtotime($iss['raised_at'])); ?></span>
                </div>
                <div style="font-weight:700; color:var(--text); font-size:0.88rem; line-height:1.3; max-width:210px;">
                  <a href="issue_detail.php?id=<?php echo $iss['id']; ?>" style="color:var(--text); text-decoration:none;" onmouseover="this.style.color='var(--red)'" onmouseout="this.style.color='var(--text)'">
                    <?php echo htmlspecialchars($iss['title']); ?>
                  </a>
                </div>
              </td>

              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <?php if (!empty($iss['gimage']) && file_exists('uploads/' . $iss['gimage'])): ?>
                    <img src="/HP%20TechPulse/uploads/<?php echo htmlspecialchars($iss['gimage']); ?>" alt="Device" style="width:42px; height:42px; border-radius:8px; object-fit:cover; border:1px solid var(--border); flex-shrink:0;">
                  <?php else: ?>
                    <div style="width:42px; height:42px; border-radius:8px; background:var(--red-dim); color:var(--red); display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid var(--border-r);">
                      <?php echo get_category_icon($iss['category'], 22); ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div style="font-weight:700; color:var(--text); font-size:0.86rem;"><?php echo htmlspecialchars($iss['gname']); ?></div>
                    <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($iss['asset_tag']); ?> &bull; <?php echo htmlspecialchars($iss['category']); ?></div>
                  </div>
                </div>
              </td>

              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <div style="width:28px; height:28px; border-radius:50%; background:rgba(26,107,255,0.15); color:var(--blue); font-weight:800; font-size:0.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <?php echo strtoupper(substr($iss['raised_name'], 0, 1)); ?>
                  </div>
                  <div>
                    <div style="font-weight:600; color:var(--text); font-size:0.84rem;"><?php echo htmlspecialchars($iss['raised_name']); ?></div>
                    <div style="font-size:0.73rem; color:var(--text-muted);"><?php echo htmlspecialchars($iss['raised_email']); ?></div>
                  </div>
                </div>
              </td>

              <td>
                <span class="priority-chip <?php echo $pr_class; ?>">
                  <span class="priority-dot"></span>
                  <?php echo ucfirst($iss['priority']); ?>
                </span>
              </td>

              <td>
                <?php echo issue_status_badge($iss['status']); ?>
              </td>

              <td>
                <?php if ($iss['status'] === 'resolved'): ?>
                  <div style="font-size:0.82rem; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
                    <svg fill="none" stroke="#22c55e" viewBox="0 0 24 24" width="16" height="16" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span><?php echo htmlspecialchars($iss['tech_name'] ? $iss['tech_name'] : 'System'); ?></span>
                  </div>
                <?php elseif ($is_admin): ?>
                  <!-- Inline Quick Dispatch Form -->
                  <form action="admin_issues.php" method="POST" style="display:inline-flex; align-items:center; gap:6px;">
                    <input type="hidden" name="action" value="assign_tech">
                    <input type="hidden" name="issue_id" value="<?php echo $iss['id']; ?>">
                    
                    <select name="technician_id" class="form-control" style="padding:4px 8px; font-size:0.78rem; height:32px; max-width:145px; background:var(--bg);" required onchange="this.form.submit()">
                      <option value="">-- Assign Tech --</option>
                      <?php foreach ($techs as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo $iss['assigned_to'] == $t['id'] ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($t['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                <?php else: ?>
                  <span style="font-size:0.82rem; color:var(--text-muted);">
                    <?php echo $iss['tech_name'] ? htmlspecialchars($iss['tech_name']) : '<span style="color:var(--text-darker);">Unassigned</span>'; ?>
                  </span>
                <?php endif; ?>
              </td>

              <td>
                <div style="display:flex; gap:6px;">
                  <a href="issue_detail.php?id=<?php echo $iss['id']; ?>" class="btn btn-outline btn-sm">Timeline</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="empty-state">
              <?php echo get_svg_icon('issues'); ?>
              <p>No support desk tickets found matching your filter criteria.</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>

