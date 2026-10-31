<?php


date_default_timezone_set('Asia/Kolkata');

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


if (!function_exists('tp_redirect')) {
    function tp_redirect($url) {
        $token = isset($GLOBALS['tp_auth_token']) ? $GLOBALS['tp_auth_token'] : (isset($_SESSION['tp_token']) ? $_SESSION['tp_token'] : null);
        if ($token) {
            $clean = tp_clean_url($url);
            $sep = (strpos($clean, '?') !== false) ? '&' : '?';
            $url = $clean . $sep . '_t=' . $token;
        }
        header('Location: ' . $url);
        exit();
    }
}
