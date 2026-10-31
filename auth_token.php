<?php
if (!defined('TP_TOKEN_LEN')) define('TP_TOKEN_LEN', 64);
if (!defined('TP_TOKEN_TTL')) define('TP_TOKEN_TTL', 8);

function tp_ensure_sessions_table($conn) {
    mysqli_query($conn,
        "CREATE TABLE IF NOT EXISTS tp_sessions (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            token        CHAR(64)      NOT NULL,
            user_id      INT           NOT NULL,
            user_name    VARCHAR(255)  NOT NULL,
            user_role    VARCHAR(20)   NOT NULL DEFAULT 'user',
            is_support   TINYINT(1)   NOT NULL DEFAULT 0,
            created_at   DATETIME      NOT NULL,
            last_activity DATETIME     NOT NULL,
            expires_at   DATETIME      NOT NULL,
            ip_address   VARCHAR(45)   DEFAULT NULL,
            user_agent   VARCHAR(512)  DEFAULT NULL,
            UNIQUE KEY   idx_token  (token),
            INDEX        idx_expires (expires_at),
            INDEX        idx_user    (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    );
}

function tp_generate_token() {
    if (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
    // PHP 5.4 safe fallback
    $hex = '';
    for ($i = 0; $i < 64; $i++) {
        $hex .= sprintf('%x', mt_rand(0, 15));
    }
    return $hex;
}

function tp_create_session($conn, $user_id, $user_name, $role, $is_support) {
    // Clean up expired sessions first
    mysqli_query($conn, "DELETE FROM tp_sessions WHERE expires_at < NOW()");

    $token   = tp_generate_token();
    $expires = date('Y-m-d H:i:s', time() + TP_TOKEN_TTL * 3600);
    $ip      = isset($_SERVER['REMOTE_ADDR'])     ? mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'])                    : '';
    $ua      = isset($_SERVER['HTTP_USER_AGENT']) ? mysqli_real_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'], 0, 500)) : '';
    $name    = mysqli_real_escape_string($conn, $user_name);
    $role_e  = mysqli_real_escape_string($conn, $role);

    $ok = mysqli_query($conn,
        "INSERT INTO tp_sessions
            (token, user_id, user_name, user_role, is_support, created_at, last_activity, expires_at, ip_address, user_agent)
         VALUES
            ('$token', $user_id, '$name', '$role_e', $is_support, NOW(), NOW(), '$expires', '$ip', '$ua')"
    );

    return $ok ? $token : false;
}

function tp_validate_token($conn, $token) {
    if (!$token
        || strlen($token) !== TP_TOKEN_LEN
        || !ctype_xdigit($token)) {
        return false;
    }

    $t   = mysqli_real_escape_string($conn, $token);
    $q   = mysqli_query($conn,
        "SELECT * FROM tp_sessions
         WHERE token = '$t' AND expires_at > NOW()
         LIMIT 1"
    );

    if (!$q || mysqli_num_rows($q) === 0) {
        return false;
    }

    $row = mysqli_fetch_assoc($q);

    // Slide expiry window (rolling session keep-alive)
    $new_exp = date('Y-m-d H:i:s', time() + TP_TOKEN_TTL * 3600);
    mysqli_query($conn,
        "UPDATE tp_sessions SET last_activity = NOW(), expires_at = '$new_exp'
         WHERE token = '$t'"
    );

    return $row;
}

function tp_destroy_token($conn, $token) {
    if (!$token) return;
    $t = mysqli_real_escape_string($conn, $token);
    mysqli_query($conn, "DELETE FROM tp_sessions WHERE token = '$t'");
}

function tp_get_request_token() {
    if (isset($_GET['_t'])
        && strlen($_GET['_t'])  === TP_TOKEN_LEN
        && ctype_xdigit($_GET['_t'])) {
        return $_GET['_t'];
    }
    if (isset($_POST['_t'])
        && strlen($_POST['_t']) === TP_TOKEN_LEN
        && ctype_xdigit($_POST['_t'])) {
        return $_POST['_t'];
    }
    return null;
}

function tp_bootstrap_session($conn) {
    tp_ensure_sessions_table($conn);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = tp_get_request_token();

    if (!$token) {
        // No token in this request — appears logged-out to PHP.
        // JS bridge (in auth guard fallback) handles back-button case.
        $_SESSION = array();
        $GLOBALS['tp_auth_session'] = null;
        $GLOBALS['tp_auth_token']   = null;
        return false;
    }

    $sess = tp_validate_token($conn, $token);

    if (!$sess) {
        // Token invalid or expired
        $_SESSION = array();
        $GLOBALS['tp_auth_session'] = null;
        $GLOBALS['tp_auth_token']   = null;
        return false;
    }

    // ── Populate request identity & $_SESSION from DB data ────────
    $GLOBALS['tp_auth_session'] = $sess;
    $GLOBALS['tp_auth_token']   = $token;

    $_SESSION['tp_user_id']    = (int)$sess['user_id'];
    $_SESSION['tp_name']       = $sess['user_name'];
    $_SESSION['tp_role']       = $sess['user_role'];
    $_SESSION['tp_support']    = (int)$sess['is_support'];
    $_SESSION['tp_is_support'] = (int)$sess['is_support'];
    $_SESSION['tp_token']      = $token;

    return true;
}
