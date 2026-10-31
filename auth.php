<?php
require_once __DIR__ . '/session_slot.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!function_exists('tp_clean_url')) {
    function tp_clean_url($url) {
        while (strpos($url, '&amp;') !== false) {
            $url = str_replace('&amp;', '&', $url);
        }
        $url = preg_replace('/([?&])_t=[^&]*/i', '', $url);
        $url = preg_replace('/[?&]+$/', '', $url);
        if (strpos($url, '?') === false && strpos($url, '&') !== false) {
            $first_amp = strpos($url, '&');
            $url = substr($url, 0, $first_amp) . '?' . substr($url, $first_amp + 1);
        }
        $url = preg_replace('/\?&/', '?', $url);
        $url = preg_replace('/&&+/', '&', $url);
        return $url;
    }
}



function is_logged_in() {
    if (isset($GLOBALS['tp_auth_session']) && !empty($GLOBALS['tp_auth_session']['user_id'])) {
        return true;
    }
    return isset($_SESSION['tp_user_id']) && !empty($_SESSION['tp_user_id']);
}

function current_role() {
    if (isset($GLOBALS['tp_auth_session']) && isset($GLOBALS['tp_auth_session']['user_role'])) {
        return $GLOBALS['tp_auth_session']['user_role'];
    }
    return isset($_SESSION['tp_role']) ? $_SESSION['tp_role'] : '';
}

function current_is_support() {
    if (isset($GLOBALS['tp_auth_session']) && isset($GLOBALS['tp_auth_session']['is_support'])) {
        return (int)$GLOBALS['tp_auth_session']['is_support'] === 1;
    }
    return (isset($_SESSION['tp_is_support']) && (int)$_SESSION['tp_is_support'] === 1)
        || (isset($_SESSION['tp_support'])    && (int)$_SESSION['tp_support']    === 1);
}

function current_name() {
    if (isset($GLOBALS['tp_auth_session']) && isset($GLOBALS['tp_auth_session']['user_name'])) {
        return $GLOBALS['tp_auth_session']['user_name'];
    }
    return isset($_SESSION['tp_name']) ? $_SESSION['tp_name'] : 'User';
}

function current_uid() {
    if (isset($GLOBALS['tp_auth_session']) && isset($GLOBALS['tp_auth_session']['user_id'])) {
        return (int)$GLOBALS['tp_auth_session']['user_id'];
    }
    return isset($_SESSION['tp_user_id']) ? (int)$_SESSION['tp_user_id'] : 0;
}


function _tp_auth_js_bridge($reason = 'login_required') {
    $login = '/HP TechPulse/login.php?msg=' . urlencode($reason);
    $clean_uri = tp_clean_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/HP TechPulse/');
    $sep = (strpos($clean_uri, '?') !== false) ? '&' : '?';
    ?><!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>body{margin:0;background:#0b0f1a;}</style>
<script>
(function(){
  var t = sessionStorage.getItem('tp_auth_token');
  var hasTokenInUrl = /[?&]_t=/.test(window.location.search);
  if (t && t.length === 64 && !hasTokenInUrl) {
    var target = '<?php echo addslashes($clean_uri); ?>' + '<?php echo $sep; ?>_t=' + encodeURIComponent(t);
    window.location.replace(target);
  } else {
    window.location.replace('<?php echo addslashes($login); ?>');
  }
})();
</script>
</head><body></body></html><?php
    exit();
}



function require_login() {
    if (!is_logged_in()) {
        _tp_auth_js_bridge('login_required');
    }
}

function require_admin() {
    if (!is_logged_in()) {
        _tp_auth_js_bridge('login_required');
    }
    if (current_role() !== 'admin') {
        tp_redirect('user_dashboard.php');
    }
}

function require_user() {
    if (!is_logged_in()) {
        _tp_auth_js_bridge('login_required');
    }
    // Admins and employees can both access user dashboard views
    $role = current_role();
    if ($role !== 'user' && $role !== 'admin') {
        tp_redirect('admin_dashboard.php');
    }
}

function require_support() {
    if (!is_logged_in()) {
        _tp_auth_js_bridge('login_required');
    }
    if (current_role() !== 'admin' && !current_is_support()) {
        tp_redirect('user_dashboard.php');
    }
}

// Redirect away from login/register if already authenticated
function redirect_if_logged_in() {
    if (is_logged_in()) {
        $role = current_role();
        if ($role === 'admin') {
            tp_redirect('admin_dashboard.php');
        } else {
            tp_redirect('user_dashboard.php');
        }
    }
}


function audit_log($conn, $action, $table = '', $record_id = 0) {
    if (!is_logged_in()) return;
    $uid    = current_uid();
    $action = mysqli_real_escape_string($conn, $action);
    $table  = mysqli_real_escape_string($conn, $table);
    $rid    = (int)$record_id;
    mysqli_query($conn,
        "INSERT INTO audit_log (user_id, action, table_affected, record_id, created_at)
         VALUES ($uid, '$action', '$table', $rid, NOW())"
    );
}
