<?php
require_once __DIR__ . '/session_slot.php';


define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'techpulse369');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("
    <div style='font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;background:#0b0f1a;color:#ef4444;
                min-height:100vh;display:flex;align-items:center;justify-content:center;
                flex-direction:column;gap:12px;padding:40px;text-align:center;'>
        <div style='width:56px;height:56px;background:rgba(239,68,68,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;'><svg width='28' height='28' fill='none' stroke='#ef4444' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'/></svg></div>
        <div style='font-size:1.3rem;font-weight:700;color:#f1f5f9;'>Database Connection Failed</div>
        <div style='font-size:0.92rem;color:#94a3b8;max-width:420px;line-height:1.6;'>
            Could not connect to MySQL.<br>
            Please make sure WampServer is running and the database
            <strong style='color:#60a5fa;'>hp_techpulse</strong> has been created and imported in phpMyAdmin.
        </div>
        <div style='font-size:0.8rem;color:#64748b;margin-top:12px;background:#1e293b;padding:8px 16px;border-radius:6px;border:1px solid rgba(255,255,255,0.06);'>
            Error Details: " . htmlspecialchars(mysqli_connect_error()) . "
        </div>
    </div>");
}


mysqli_set_charset($conn, 'utf8');

if (!function_exists('esc')) {
    function esc($conn, $val) {
        return mysqli_real_escape_string($conn, trim($val));
    }
}


require_once __DIR__ . '/auth_token.php';
tp_bootstrap_session($conn);
?>
