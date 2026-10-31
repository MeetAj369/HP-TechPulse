<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_admin(); // Guard Admin only

$uid = current_uid();
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$error = '';
$success = '';

if ($action === 'delete' && isset($_GET['id'])) {
    $target_uid = (int)$_GET['id'];
    
    if ($target_uid === $uid) {
        $error = 'Self-protection: You cannot delete your own administrative account.';
    } else {
        $del = mysqli_query($conn, "DELETE FROM users WHERE id=$target_uid");
        if ($del) {
            audit_log($conn, "Deleted user account ID: $target_uid", "users", $target_uid);
            tp_redirect('admin_users.php?msg=deleted');
        } else {
            $error = 'Failed to delete user account.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_uid = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name       = esc($conn, $_POST['name']);
    $email      = esc($conn, $_POST['email']);
    $password   = isset($_POST['password']) ? trim($_POST['password']) : '';
    $role       = esc($conn, $_POST['role']);
    $is_support = isset($_POST['is_support']) ? 1 : 0;
    $department = esc($conn, $_POST['department']);
    $phone      = esc($conn, $_POST['phone']);

    // Validation
    $email_chk_sql = ($target_uid === 0) ? 
        "SELECT id FROM users WHERE email='$email' LIMIT 1" : 
        "SELECT id FROM users WHERE email='$email' AND id != $target_uid LIMIT 1";
    $email_chk = mysqli_query($conn, $email_chk_sql);

    if (empty($name) || empty($email) || empty($role)) {
        $error = 'Name, email, and role are required.';
    } elseif ($target_uid === 0 && empty($password)) {
        $error = 'Password is required for new accounts.';
    } elseif (mysqli_num_rows($email_chk) > 0) {
        $error = 'Email address is already in use by another account.';
    } else {
        if ($target_uid === 0) {
            // Secure Hashed Password
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $ins = mysqli_query($conn,
                "INSERT INTO users (name, email, password, role, is_support, department, phone, created_at)
                 VALUES ('$name', '$email', '$hashed_pass', '$role', $is_support, '$department', '$phone', NOW())"
            );
            if ($ins) {
                $new_id = mysqli_insert_id($conn);
                audit_log($conn, "Created user account: $email (Role: $role)", "users", $new_id);
                tp_redirect('admin_users.php?msg=added');
            } else {
                $error = 'Database write failed.';
            }
        } else {
            // Update details
            $pass_clause = "";
            if (!empty($password)) {
                $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
                $pass_clause = ", password = '$hashed_pass'";
            }

            // Enforce that support flag is 0 if role is set to admin
            if ($role === 'admin') {
                $is_support = 0;
            }

            $upd = mysqli_query($conn,
                "UPDATE users SET 
                    name = '$name', 
                    email = '$email', 
                    role = '$role', 
                    is_support = $is_support, 
                    department = '$department', 
                    phone = '$phone'
                    $pass_clause
                 WHERE id = $target_uid"
            );

            if ($upd) {
                // If updating self, update active sessions
                if ($target_uid === $uid) {
                    $_SESSION['tp_name'] = $name;
                    $_SESSION['tp_role'] = $role;
                }
                
                audit_log($conn, "Updated user account details for: $email", "users", $target_uid);
                tp_redirect('admin_users.php?msg=updated');
            } else {
                $error = 'Database write failed.';
            }
        }
    }
}

// Fetch user data for form edit
$u_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $target_uid = (int)$_GET['id'];
    $uq = mysqli_query($conn, "SELECT * FROM users WHERE id=$target_uid LIMIT 1");
    $u_data = mysqli_fetch_assoc($uq);
}

// Fetch all users
$users = array();
$uq = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
if ($uq) {
    while($row = mysqli_fetch_assoc($uq)) { $users[] = $row; }
}

$page_title = 'User Directory';
$active_nav = 'users';
include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>
            <?php 
                if ($action === 'add') echo 'Register New User';
                elseif ($action === 'edit') echo 'Modify User account';
                else echo 'System User Accounts';
            ?>
        </h1>
        <p>Grant support technician privileges, manage corporate roles, and update credentials.</p>
    </div>
    <?php if ($action !== 'list'): ?>
        <a href="admin_users.php" class="btn btn-outline">&larr; Back to Directory</a>
    <?php else: ?>
        <a href="admin_users.php?action=add" class="btn btn-primary">
            <?php echo get_svg_icon('plus'); ?> Add User Account
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($error)): ?>
    <div style="background:var(--danger-bg); color:var(--danger); padding:12px; border-radius:var(--radius-sm); border:1px solid rgba(239,68,68,0.15); margin-bottom:20px; font-weight:600; display:flex; align-items:center; gap:8px;">
        <?php echo get_svg_icon('issues'); ?>
        <span><?php echo htmlspecialchars($error); ?></span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <div class="flash-msg flash-success">
        <?php echo get_svg_icon('check'); ?>
        <span>
            <?php 
                if ($_GET['msg'] === 'added') echo 'User account created successfully!';
                elseif ($_GET['msg'] === 'updated') echo 'User account updated successfully!';
                elseif ($_GET['msg'] === 'deleted') echo 'User account removed successfully.';
            ?>
        </span>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- USER DIRECTORY TABLE -->
    <div class="card">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Work Email</th>
                        <th>Role / Level</th>
                        <th>Department</th>
                        <th>Phone</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $u): 
                            $is_self = $u['id'] === $uid;
                            $u_role_color = ($u['role'] === 'admin') ? 'var(--primary)' : ($u['is_support'] ? 'var(--purple)' : 'var(--success)');
                            $u_role_bg = ($u['role'] === 'admin') ? 'var(--primary-bg)' : ($u['is_support'] ? 'var(--purple-bg)' : 'var(--success-bg)');
                        ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <!-- Avatar Initials -->
                                        <div style="width:34px; height:34px; border-radius:50%; background:<?php echo $u_role_bg; ?>; color:<?php echo $u_role_color; ?>; border:1px solid <?php echo $u_role_color; ?>22; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.88rem;">
                                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; color:var(--text);"><?php echo htmlspecialchars($u['name']); ?></div>
                                            <?php if ($is_self): ?>
                                                <span class="badge badge-blue" style="font-size:0.65rem; margin-top:2px;">Logged In</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge" style="background:<?php echo $u_role_bg; ?>; color:<?php echo $u_role_color; ?>;">
                                        <?php echo ($u['role'] === 'admin') ? 'Admin' : ($u['is_support'] ? 'IT Support' : 'Employee'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($u['department'] ? $u['department'] : '—'); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ? $u['phone'] : '—'); ?></td>
                                <td style="font-size:0.8rem; color:var(--text-muted);">
                                    <?php echo $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : '<span style="color:var(--text-darker);">Never</span>'; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="admin_users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-secondary btn-sm" title="Edit account details"><?php echo get_svg_icon('edit'); ?></a>
                                        <?php if (!$is_self): ?>
                                            <a href="admin_users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" title="Delete account" onclick="return confirm('Are you sure you want to permanently delete this user account?');"><?php echo get_svg_icon('delete'); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="empty-state">No users in database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- USER ADD / EDIT FORM -->
    <div class="card" style="max-width:650px; margin:0 auto;">
        <div class="card-header">
            <h3>User Account Specifications</h3>
        </div>
        <div class="card-body">
            <form action="admin_users.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $u_data ? $u_data['id'] : 0; ?>">

                <div class="form-group">
                    <label for="name">Full User Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Raj Patel" required value="<?php echo $u_data ? htmlspecialchars($u_data['name']) : ''; ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">Work Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required value="<?php echo $u_data ? htmlspecialchars($u_data['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Account Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="<?php echo $u_data ? '•••••••• (leave blank to keep current)' : 'Set default password'; ?>" <?php echo $u_data ? '' : 'required'; ?>>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="role">Corporate Security Role</label>
                        <select name="role" id="role" class="form-control" required onchange="toggleSupportCheckbox(this.value)">
                            <option value="user" <?php echo ($u_data && $u_data['role'] === 'user') ? 'selected' : ''; ?>>Standard User / Employee</option>
                            <option value="admin" <?php echo ($u_data && $u_data['role'] === 'admin') ? 'selected' : ''; ?>>System Administrator</option>
                        </select>
                    </div>

                    <div class="form-group" id="supportCheckboxWrapper" style="<?php echo ($u_data && $u_data['role'] === 'admin') ? 'display:none;' : ''; ?>">
                        <label style="opacity: 0;">Support Flag</label>
                        <div style="display:flex; align-items:center; gap:8px; height:40px;">
                            <input type="checkbox" name="is_support" id="is_support" style="width:18px; height:18px; cursor:pointer;" <?php echo ($u_data && $u_data['is_support'] == 1) ? 'checked' : ''; ?>>
                            <label for="is_support" style="margin-bottom:0; cursor:pointer; font-weight:700; color:var(--purple);">IT Support Desk Handler</label>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="department">Corporate Department</label>
                        <input type="text" name="department" id="department" class="form-control" placeholder="e.g. Finance, HR, IT Support" value="<?php echo $u_data ? htmlspecialchars($u_data['department']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Line Extension</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="Internal desk extension" value="<?php echo $u_data ? htmlspecialchars($u_data['phone']) : ''; ?>">
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:28px;">
                    <a href="admin_users.php" style="font-weight:600; color:var(--text-muted);">&larr; Back to Directory</a>
                    <button type="submit" class="btn btn-primary">
                        Save Account Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleSupportCheckbox(roleVal) {
        var wrapper = document.getElementById('supportCheckboxWrapper');
        var check = document.getElementById('is_support');
        if (roleVal === 'admin') {
            wrapper.style.display = 'none';
            check.checked = false; // Admin cannot be support role
        } else {
            wrapper.style.display = 'block';
        }
    }
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>
