<?php
require_once __DIR__ . '/session_slot.php';
// Backports PHP 5.5's password_hash & password_verify to PHP 5.4.x using Blowfish (CRYPT_BLOWFISH)
if (!function_exists('password_hash')) {
    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    function password_hash($password, $algo, $options = array()) {
        $cost = isset($options['cost']) ? (int)$options['cost'] : 10;
        if ($cost < 4 || $cost > 31) {
            $cost = 10;
        }
        // Generate secure 22 character salt for BCrypt
        $salt = "";
        $salt_chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9), array('.', '/'));
        for ($i = 0; $i < 22; $i++) {
            $salt .= $salt_chars[mt_rand(0, 63)];
        }
        $salt = sprintf("$2y$%02d$", $cost) . $salt;
        return crypt($password, $salt);
    }

    function password_verify($password, $hash) {
        if (!is_string($hash) || strlen($hash) < 60) {
            return false;
        }
        $test_hash = crypt($password, $hash);
        if (strlen($test_hash) !== strlen($hash)) {
            return false;
        }
        // Timing attack safe comparison
        $status = 0;
        for ($i = 0; $i < strlen($test_hash); $i++) {
            $status |= (ord($test_hash[$i]) ^ ord($hash[$i]));
        }
        return $status === 0;
    }
}


function calculate_health_score($conn, $gadget_id) {
    $gid = (int)$gadget_id;
    $g   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM gadgets WHERE id=$gid"));
    if (!$g) return array('score'=>0,'verdict'=>'Unknown','color'=>'#64748b','breakdown'=>array());

    $score = 100;
    $breakdown = array();

    // 1. Age penalty (max -30 pts)
    $age_months = 0;
    if (!empty($g['purchase_date'])) {
        $purchase = new DateTime($g['purchase_date']);
        $now      = new DateTime();
        $diff     = $now->diff($purchase);
        $age_months = ($diff->y * 12) + $diff->m;
    }
    $age_penalty = min(30, (int)floor($age_months / 2));
    $score -= $age_penalty;
    $breakdown[] = array('label' => 'Age (' . $age_months . ' months)', 'delta' => -$age_penalty, 'note' => 'Max penalty 30 pts after 5 years');

    // 2. Repairs penalty (-8 per repair, max -40)
    $rep_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM issues WHERE gadget_id=$gid AND status='resolved'");
    $rep_count = 0;
    if ($rep_q) { $r = mysqli_fetch_assoc($rep_q); $rep_count = (int)$r['c']; }
    $rep_penalty = min(40, $rep_count * 8);
    $score -= $rep_penalty;
    $breakdown[] = array('label' => 'Repairs logged (' . $rep_count . ')', 'delta' => -$rep_penalty, 'note' => '-8 pts per resolved issue');

    // 3. Open critical issue (-15 pts)
    $crit_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM issues WHERE gadget_id=$gid AND priority='critical' AND status != 'resolved'");
    $crit = 0;
    if ($crit_q) { $r = mysqli_fetch_assoc($crit_q); $crit = (int)$r['c']; }
    if ($crit > 0) {
        $score -= 15;
        $breakdown[] = array('label' => 'Critical open issue', 'delta' => -15, 'note' => 'Active critical fault detected');
    }

    // 4. Maintenance overdue penalty (max -10)
    $maint_q = mysqli_query($conn, "SELECT MIN(scheduled_date) as d FROM maintenance WHERE gadget_id=$gid AND completed_date IS NULL AND scheduled_date < CURDATE()");
    $maint_overdue = 0;
    if ($maint_q) {
        $mr = mysqli_fetch_assoc($maint_q);
        if (!empty($mr['d'])) {
            $sched = new DateTime($mr['d']);
            $now2  = new DateTime();
            $diff2 = $now2->diff($sched);
            $months_overdue = ($diff2->y * 12) + $diff2->m;
            $maint_overdue  = min(10, (int)floor($months_overdue / 2));
        }
    }
    $score -= $maint_overdue;
    if ($maint_overdue > 0) {
        $breakdown[] = array('label' => 'Maintenance overdue', 'delta' => -$maint_overdue, 'note' => 'Scheduled service not completed');
    }

    // 5. Warranty active bonus (+10)
    $war_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM warranties WHERE gadget_id=$gid AND type='warranty' AND end_date >= CURDATE()");
    $has_warranty = 0;
    if ($war_q) { $r = mysqli_fetch_assoc($war_q); $has_warranty = (int)$r['c'] > 0 ? 1 : 0; }
    $war_bonus = $has_warranty ? 10 : 0;
    $score += $war_bonus;
    $breakdown[] = array('label' => 'Warranty status', 'delta' => $war_bonus, 'note' => $has_warranty ? 'Active warranty +10 pts' : 'No active warranty');

    // 6. AMC active bonus (+8)
    $amc_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM warranties WHERE gadget_id=$gid AND type='amc' AND end_date >= CURDATE()");
    $has_amc = 0;
    if ($amc_q) { $r = mysqli_fetch_assoc($amc_q); $has_amc = (int)$r['c'] > 0 ? 1 : 0; }
    $amc_bonus = $has_amc ? 8 : 0;
    $score += $amc_bonus;
    $breakdown[] = array('label' => 'AMC coverage', 'delta' => $amc_bonus, 'note' => $has_amc ? 'Active AMC +8 pts' : 'No AMC contract');

    // 7. Status penalty
    $status_penalty = 0;
    if ($g['status'] === 'in-repair') { $status_penalty = 10; }
    if ($g['status'] === 'retired')   { $status_penalty = 50; }
    $score -= $status_penalty;
    if ($status_penalty > 0) {
        $breakdown[] = array('label' => 'Status: ' . $g['status'], 'delta' => -$status_penalty, 'note' => 'Current gadget status penalty');
    }

    // Clamp score
    $score = max(0, min(100, $score));

    // Verdict
    if ($score >= 80)      { $verdict = 'Excellent'; $color = '#10b981'; }
    elseif ($score >= 60)  { $verdict = 'Good';      $color = '#3b82f6'; }
    elseif ($score >= 40)  { $verdict = 'Needs Attention'; $color = '#f59e0b'; }
    else                   { $verdict = 'Replace Soon';    $color = '#ef4444'; }

    return array(
        'score'     => $score,
        'verdict'   => $verdict,
        'color'     => $color,
        'breakdown' => $breakdown,
    );
}


function calculate_depreciation($purchase_price, $rate_percent, $useful_life) {
    $rows  = array();
    $value = (float)$purchase_price;
    $rate  = (float)$rate_percent;
    for ($yr = 1; $yr <= (int)$useful_life; $yr++) {
        $dep    = round($value * ($rate / 100), 2);
        $close  = round($value - $dep, 2);
        $rows[] = array(
            'year'     => $yr,
            'opening'  => round($value, 2),
            'dep'      => $dep,
            'closing'  => $close,
            'pct_left' => round(($close / $purchase_price) * 100, 1),
        );
        $value = $close;
        if ($value <= 0) break;
    }
    return $rows;
}


function generate_asset_tag($conn) {
    $year = date('Y');
    $q    = mysqli_query($conn, "SELECT asset_tag FROM gadgets WHERE asset_tag LIKE 'TP-$year-%' ORDER BY id DESC LIMIT 1");
    $last_num = 0;
    if ($q && mysqli_num_rows($q) > 0) {
        $row      = mysqli_fetch_assoc($q);
        $parts    = explode('-', $row['asset_tag']);
        $last_num = isset($parts[2]) ? (int)$parts[2] : 0;
    }
    return 'TP-' . $year . '-' . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
}


function issue_status_badge($status) {
    $map = array(
        'raised'      => array('class'=>'badge-amber',  'label'=>'Raised'),
        'assigned'    => array('class'=>'badge-blue',   'label'=>'Assigned'),
        'in-progress' => array('class'=>'badge-purple', 'label'=>'In Progress'),
        'testing'     => array('class'=>'badge-cyan',   'label'=>'Testing'),
        'resolved'    => array('class'=>'badge-green',  'label'=>'Resolved'),
    );
    $b = isset($map[$status]) ? $map[$status] : array('class'=>'badge-gray','label'=>ucfirst($status));
    return '<span class="badge ' . $b['class'] . '">' . $b['label'] . '</span>';
}

function priority_badge($priority) {
    $map = array(
        'low'      => 'badge-gray',
        'medium'   => 'badge-blue',
        'high'     => 'badge-amber',
        'critical' => 'badge-red',
    );
    $cls = isset($map[$priority]) ? $map[$priority] : 'badge-gray';
    return '<span class="badge ' . $cls . '">' . ucfirst($priority) . '</span>';
}

function gadget_status_badge($status) {
    $map = array(
        'active'    => array('class'=>'badge-green','label'=>'Active'),
        'in-repair' => array('class'=>'badge-amber','label'=>'In Repair'),
        'retired'   => array('class'=>'badge-gray', 'label'=>'Retired'),
    );
    $b = isset($map[$status]) ? $map[$status] : array('class'=>'badge-gray','label'=>ucfirst($status));
    return '<span class="badge ' . $b['class'] . '">' . $b['label'] . '</span>';
}


function warranty_days_left($end_date) {
    $now  = new DateTime();
    $end  = new DateTime($end_date);
    $diff = $now->diff($end);
    if ($now > $end) return -1; // expired
    return ($diff->y * 365) + ($diff->m * 30) + $diff->d;
}

function warranty_ring_html($end_date, $start_date) {
    $days_left = warranty_days_left($end_date);
    $total_days = max(1, (new DateTime($start_date))->diff(new DateTime($end_date))->days);
    $pct = $days_left < 0 ? 0 : min(100, round(($days_left / $total_days) * 100));

    if ($days_left < 0)       { $color = '#ef4444'; $label = 'Expired'; $sub = ''; }
    elseif ($days_left <= 30) { $color = '#ef4444'; $label = $days_left . 'd'; $sub = 'left'; }
    elseif ($days_left <= 90) { $color = '#f59e0b'; $label = $days_left . 'd'; $sub = 'left'; }
    else {
        $months = round($days_left / 30);
        $color  = '#10b981';
        $label  = $months . 'mo';
        $sub    = 'left';
    }

    $r    = 22;
    $circ = 2 * M_PI * $r;
    $offset = $circ - ($pct / 100) * $circ;

    return '<div class="warranty-ring">
        <svg viewBox="0 0 56 56">
            <circle cx="28" cy="28" r="' . $r . '" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"/>
            <circle cx="28" cy="28" r="' . $r . '" fill="none" stroke="' . $color . '" stroke-width="5"
                stroke-dasharray="' . round($circ,2) . '" stroke-dashoffset="' . round($offset,2) . '"
                stroke-linecap="round" transform="rotate(-90 28 28)"/>
        </svg>
        <div class="warranty-ring-text" style="color:' . $color . '">
            <span>' . $label . '</span>
            ' . ($sub ? '<span>' . $sub . '</span>' : '') . '
        </div>
    </div>';
}


function fmt_inr($amount) {
    return 'Rs.' . number_format((float)$amount, 0, '.', ',');
}


function get_svg_icon($name, $class = '') {
    $icons = array(
        'dashboard' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>',
        'gadgets' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
        'issues' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        'maintenance' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>',
        'users' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
        'vendors' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        'stock' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
        'analytics' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>',
        'sustainability' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>', // Custom Eco Icon (Leafy feel represented by a clean sun-burst/cycle)
        'leaf' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c.132 0 .263 0 .393.007a7.5 7.5 0 007.92 12.446A9 9 0 1112 3zm0 5v8m-3-5l3-3 3 3"/></svg>',
        'passport' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        'plus' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
        'edit' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>',
        'delete' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
        'security' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>',
        'logout' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>',
        'arrow-right' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>',
        'check' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
        'sun-moon' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>',
        'calendar' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
        'list' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>'
    );
    return isset($icons[$name]) ? $icons[$name] : '';
}


function get_category_icon($category, $size = 24) {
    $s = (int)$size;
    $icons = array(
        'Laptop'    => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="14" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M2 19h20M8 19l-1 2M16 19l1 2"/></svg>',
        'Mobile'    => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="3" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor" stroke="none"/></svg>',
        'Monitor'   => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="15" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8 21h8M12 18v3"/></svg>',
        'Tablet'    => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor" stroke="none"/></svg>',
        'Desktop'   => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8 21h8M12 17v4M6 21h12"/></svg>',
        'Printer'   => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2v4H6v-4z"/><line x1="6" y1="13" x2="6.01" y2="13" stroke-width="2" stroke-linecap="round"/></svg>',
        'Accessory' => '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9l-7-7zm0 0v7h7"/></svg>',
    );
    $default = '<svg width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="18" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M16 3v4H8V3"/></svg>';
    return isset($icons[$category]) ? $icons[$category] : $default;
}


function get_upload_icon() {
    return '<svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity:0.45;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4" stroke-width="1.6"/></svg>';
}


function render_svg_qr_matrix($text, $size = 100) {
    $len = strlen($text);
    $version = 2;
    if ($len > 32) $version = 3;
    if ($len > 54) $version = 4;

    $size_modules = 17 + 4 * $version;
    $matrix = array_fill(0, $size_modules, array_fill(0, $size_modules, 0));
    $reserved = array_fill(0, $size_modules, array_fill(0, $size_modules, false));

    // 1. Finder patterns (3 corners: top-left, top-right, bottom-left)
    $finders = array(array(0, 0), array(0, $size_modules - 7), array($size_modules - 7, 0));
    foreach ($finders as $f) {
        $fr = $f[0]; $fc = $f[1];
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $mr = $fr + $r; $mc = $fc + $c;
                if ($mr >= 0 && $mr < $size_modules && $mc >= 0 && $mc < $size_modules) {
                    $reserved[$mr][$mc] = true;
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        if ($r == 0 || $r == 6 || $c == 0 || $c == 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                            $matrix[$mr][$mc] = 1;
                        } else {
                            $matrix[$mr][$mc] = 0;
                        }
                    } else {
                        $matrix[$mr][$mc] = 0;
                    }
                }
            }
        }
    }

    // 2. Alignment pattern for Version >= 2
    if ($version >= 2) {
        $align_pos = $version == 2 ? 18 : ($version == 3 ? 22 : 26);
        $ar = $align_pos; $ac = $align_pos;
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $mr = $ar + $r; $mc = $ac + $c;
                if (!$reserved[$mr][$mc]) {
                    $reserved[$mr][$mc] = true;
                    if (abs($r) == 2 || abs($c) == 2 || ($r == 0 && $c == 0)) {
                        $matrix[$mr][$mc] = 1;
                    } else {
                        $matrix[$mr][$mc] = 0;
                    }
                }
            }
        }
    }

    // 3. Timing patterns (row 6 and col 6)
    for ($i = 8; $i < $size_modules - 8; $i++) {
        if (!$reserved[6][$i]) {
            $reserved[6][$i] = true;
            $matrix[6][$i] = ($i % 2 == 0) ? 1 : 0;
        }
        if (!$reserved[$i][6]) {
            $reserved[$i][6] = true;
            $matrix[$i][6] = ($i % 2 == 0) ? 1 : 0;
        }
    }

    // 4. Format info reservation (around finders)
    for ($i = 0; $i < 9; $i++) {
        if ($i < $size_modules) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
            $reserved[$size_modules - 1 - $i][8] = true;
            $reserved[8][$size_modules - 1 - $i] = true;
        }
    }
    $matrix[8][8] = 0; // Dark module

    // 5. Data encoding (Byte Mode + Length + Text + Padding)
    $bits = '0100'; // Byte Mode
    $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
    for ($i = 0; $i < $len; $i++) {
        $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
    }
    $bits .= '0000';
    while (strlen($bits) % 8 !== 0) { $bits .= '0'; }

    $pad = false;
    $max_data_bits = ($version == 2 ? 34 : ($version == 3 ? 55 : 80)) * 8;
    while (strlen($bits) < $max_data_bits) {
        $bits .= $pad ? '00010001' : '11101100';
        $pad = !$pad;
    }

    // 6. GF(256) Reed-Solomon Error Correction Codewords computation
    $ec_count = $version == 2 ? 10 : ($version == 3 ? 15 : 20);
    $data_bytes = array();
    for ($i = 0; $i < strlen($bits); $i += 8) {
        $data_bytes[] = bindec(substr($bits, $i, 8));
    }
    
    $exp = array_fill(0, 512, 0);
    $log = array_fill(0, 256, 0);
    $x = 1;
    for ($i = 0; $i < 255; $i++) {
        $exp[$i] = $x;
        $exp[$i + 255] = $x;
        $log[$x] = $i;
        $x = ($x << 1) ^ (($x & 0x80) ? 0x11D : 0);
    }

    $poly = array(1);
    for ($i = 0; $i < $ec_count; $i++) {
        $next = array_fill(0, count($poly) + 1, 0);
        for ($j = 0; $j < count($poly); $j++) {
            $next[$j] ^= $poly[$j];
            $term = $exp[($log[$poly[$j]] + $exp[$i]) % 255];
            $next[$j + 1] ^= $term;
        }
        $poly = $next;
    }

    $ec = array_fill(0, $ec_count, 0);
    foreach ($data_bytes as $byte) {
        $factor = $byte ^ $ec[0];
        array_shift($ec);
        $ec[] = 0;
        if ($factor !== 0) {
            for ($j = 0; $j < $ec_count; $j++) {
                if ($poly[$j + 1] !== 0) {
                    $ec[$j] ^= $exp[($log[$factor] + $log[$poly[$j + 1]]) % 255];
                }
            }
        }
    }

    $all_bits = $bits;
    foreach ($ec as $e) {
        $all_bits .= str_pad(decbin($e), 8, '0', STR_PAD_LEFT);
    }

    // 7. Place bits into matrix in standard QR zig-zag order
    $bit_idx = 0;
    $total_bits = strlen($all_bits);
    $dir = -1;
    for ($col = $size_modules - 1; $col > 0; $col -= 2) {
        if ($col == 6) $col--;
        $row_start = ($dir == -1) ? ($size_modules - 1) : 0;
        $row_end   = ($dir == -1) ? -1 : $size_modules;
        for ($r = $row_start; $r != $row_end; $r += $dir) {
            for ($c = 0; $c < 2; $c++) {
                $mc = $col - $c;
                if (!$reserved[$r][$mc]) {
                    $val = ($bit_idx < $total_bits) ? (int)$all_bits[$bit_idx++] : 0;
                    if (($r + $mc) % 2 === 0) { $val ^= 1; }
                    $matrix[$r][$mc] = $val;
                }
            }
        }
        $dir = -$dir;
    }

    // 8. Draw Format Info (Mask 0, Low EC) -> 0x77C4 format string
    $format_bits = '111011111000100';
    for ($i = 0; $i < 15; $i++) {
        $bit = (int)$format_bits[$i];
        if ($i < 6) $matrix[8][$i] = $bit;
        elseif ($i < 8) $matrix[8][$i + 1] = $bit;
        else $matrix[$size_modules - 15 + $i][8] = $bit;

        if ($i < 8) $matrix[$size_modules - 1 - $i][8] = $bit;
        else $matrix[8][15 - $i] = $bit;
    }

    // 9. Build SVG output
    $cell_sz = round($size / $size_modules, 2);
    $rects = '';
    for ($r = 0; $r < $size_modules; $r++) {
        for ($c = 0; $c < $size_modules; $c++) {
            if ($matrix[$r][$c] === 1) {
                $x = round($c * $cell_sz, 2);
                $y = round($r * $cell_sz, 2);
                $rects .= '<rect x="'.$x.'" y="'.$y.'" width="'.ceil($cell_sz).'" height="'.ceil($cell_sz).'" fill="#0f172a"/>';
            }
        }
    }

    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'" xmlns="http://www.w3.org/2000/svg" style="background:#ffffff;padding:4px;border-radius:8px;display:block;">'.$rects.'</svg>';
}
?>
