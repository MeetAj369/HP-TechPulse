<?php
require_once __DIR__ . '/session_slot.php';

include 'db.php'; // bootstraps session and validates token


$token = isset($GLOBALS['tp_auth_token']) ? $GLOBALS['tp_auth_token'] : null;

if (!$token) {
    // Also check URL directly in case bootstrap didn't find it
    $token = tp_get_request_token();
}

if ($token) {
    tp_destroy_token($conn, $token);
}

// Clear PHP session data for this request
$_SESSION = array();
session_destroy();


echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
echo '<style>body{margin:0;background:#0b0f1a;}</style>';
echo '<script>';
echo 'sessionStorage.removeItem("tp_auth_token");';
echo 'window.location.replace("/HP TechPulse/login.php?msg=logged_out");';
echo '</script>';
echo '</head><body></body></html>';
exit();
?>
