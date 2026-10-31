<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_login(); // Requires login

if (!isset($_GET['id'])) {
    die("Error: No gadget ID provided.");
}
$gid  = (int)$_GET['id'];
$role = current_role();
$uid  = current_uid();
$is_support = current_is_support();

// Fetch gadget details
$gad_q = mysqli_query($conn,
    "SELECT g.*, v.name as vendor_name, u.name as assigned_name 
     FROM gadgets g
     LEFT JOIN vendors v ON g.vendor_id = v.id
     LEFT JOIN users u ON g.assigned_to = u.id
     WHERE g.id = $gid"
);
$g = mysqli_fetch_assoc($gad_q);
if (!$g) {
    die("Error: Device not found.");
}

// Access Control check
// Regular users can only see their own assigned gadgets. Admins & Support Staff can view any gadget.
if ($role === 'user' && !$is_support && (int)$g['assigned_to'] !== (int)$uid) {
    tp_redirect('user_dashboard.php?msg=access_denied');
}

// Calculate Health Score
$hs = calculate_health_score($conn, $gid);

// Calculate Depreciation
$dep_rows = calculate_depreciation($g['purchase_price'], $g['depreciation_rate'], $g['useful_life_yrs']);

// Fetch warranties
$wars = array();
$wq = mysqli_query($conn, "SELECT * FROM warranties WHERE gadget_id = $gid ORDER BY end_date ASC");
if ($wq) {
    while ($w = mysqli_fetch_assoc($wq)) { $wars[] = $w; }
}

// Fetch issue history
$issues = array();
$iq = mysqli_query($conn, "SELECT i.*, u.name as raised_name FROM issues i JOIN users u ON i.raised_by = u.id WHERE i.gadget_id = $gid ORDER BY i.raised_at DESC LIMIT 5");
if ($iq) {
    while ($iss = mysqli_fetch_assoc($iq)) { $issues[] = $iss; }
}

// Fetch total repair costs
$total_repairs_cost = 0;
$cost_q = mysqli_query($conn, "SELECT SUM(cost) FROM maintenance WHERE gadget_id = $gid AND completed_date IS NOT NULL");
if ($cost_q) {
    $total_repairs_cost = (float)mysqli_fetch_row($cost_q)[0];
}

$total_co2_base = (float)$g['co2_manufacture'];
$co2_per_repair = (float)$g['co2_repair'];
$resolved_repairs_count = count(array_filter($issues, function($x){ return $x['status'] === 'resolved'; }));
$total_co2_actual = $total_co2_base + ($resolved_repairs_count * $co2_per_repair);

$page_title = 'Device Profile: ' . htmlspecialchars($g['name']);
$active_nav = ($role === 'admin') ? 'gadgets' : 'dashboard';
include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?php echo htmlspecialchars($g['name']); ?></h1>
        <p><?php echo htmlspecialchars($g['asset_tag']); ?> &bull; <?php echo htmlspecialchars($g['category']); ?> &bull; Serial: <?php echo htmlspecialchars($g['serial_number'] ? $g['serial_number'] : 'N/A'); ?></p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="gadget_passport.php?id=<?php echo $g['id']; ?>" class="btn btn-outline">
            <?php echo get_svg_icon('passport'); ?> View Passport
        </a>
        <?php if ($role === 'admin'): ?>
            <a href="admin_gadgets.php?action=edit&id=<?php echo $g['id']; ?>" class="btn btn-primary">
                <?php echo get_svg_icon('edit'); ?> Edit Gadget
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom:24px; overflow:hidden;">
    <div style="display:flex; align-items:stretch; gap:0; flex-wrap:wrap;">

        <!-- Photo Panel -->
        <div style="width:280px; flex-shrink:0; background:var(--bg); border-right:1px solid var(--border); display:flex; align-items:center; justify-content:center; padding:24px; min-height:220px;">
            <?php if (!empty($g['image']) && file_exists('uploads/' . $g['image'])): ?>
                <img src="/HP TechPulse/uploads/<?php echo htmlspecialchars($g['image']); ?>"
                     alt="<?php echo htmlspecialchars($g['name']); ?>"
                     style="max-width:232px; max-height:200px; width:100%; height:auto; object-fit:contain; border-radius:12px; display:block;">
            <?php else: ?>
                <!-- Placeholder: pure CSS+SVG, no emoji -->
                <div style="width:200px; height:190px; border-radius:16px; background:linear-gradient(135deg,var(--red-dim),var(--blue-dim)); display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed var(--border-r); gap:12px;">
                    <div style="color:var(--red); opacity:0.7;">
                        <?php echo get_category_icon($g['category'], 52); ?>
                    </div>
                    <div style="font-size:0.72rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.06em; text-align:center; padding:0 10px; line-height:1.4;">
                        <?php echo htmlspecialchars($g['brand'] ? $g['brand'] . ' ' . $g['category'] : $g['category']); ?>
                        <?php if ($role === 'admin'): ?>
                        <div style="margin-top:6px; color:var(--red); font-size:0.65rem; font-weight:600;">No photo &mdash; upload via Edit</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Panel -->
        <div style="flex:1; padding:28px 32px; display:flex; flex-direction:column; justify-content:center; gap:16px; min-width:0;">
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px; flex-wrap:wrap;">
                    <span style="font-size:1.35rem; font-weight:900; letter-spacing:-0.02em; color:var(--text);"><?php echo htmlspecialchars($g['name']); ?></span>
                    <?php echo gadget_status_badge($g['status']); ?>
                </div>
                <div style="font-size:0.85rem; color:var(--text-sub); line-height:1.5;">
                    <?php if ($g['brand']): ?>
                        <strong style="color:var(--text);"><?php echo htmlspecialchars($g['brand']); ?></strong> &bull;
                    <?php endif; ?>
                    <?php if ($g['model']): ?>
                        <?php echo htmlspecialchars($g['model']); ?> &bull;
                    <?php endif; ?>
                    <?php echo htmlspecialchars($g['category']); ?>
                </div>
            </div>

            <!-- Quick spec pills -->
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <div style="padding:6px 14px; background:var(--bg); border:1px solid var(--border); border-radius:20px; font-size:0.78rem; font-weight:600; color:var(--text-sub);">
                    <span style="color:var(--text-muted); margin-right:4px;">Asset</span><?php echo htmlspecialchars($g['asset_tag']); ?>
                </div>
                <?php if ($g['serial_number']): ?>
                <div style="padding:6px 14px; background:var(--bg); border:1px solid var(--border); border-radius:20px; font-size:0.78rem; font-weight:600; color:var(--text-sub);">
                    <span style="color:var(--text-muted); margin-right:4px;">S/N</span><?php echo htmlspecialchars($g['serial_number']); ?>
                </div>
                <?php endif; ?>
                <?php if ($g['purchase_date']): ?>
                <div style="padding:6px 14px; background:var(--bg); border:1px solid var(--border); border-radius:20px; font-size:0.78rem; font-weight:600; color:var(--text-sub);">
                    <span style="color:var(--text-muted); margin-right:4px;">Purchased</span><?php echo date('d M Y', strtotime($g['purchase_date'])); ?>
                </div>
                <?php endif; ?>
                <?php if ($g['purchase_price'] > 0): ?>
                <div style="padding:6px 14px; background:var(--bg); border:1px solid var(--border); border-radius:20px; font-size:0.78rem; font-weight:600; color:var(--text-sub);">
                    <span style="color:var(--text-muted); margin-right:4px;">Value</span><?php echo fmt_inr($g['purchase_price']); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Assigned User -->
            <div style="display:flex; align-items:center; gap:10px; padding:12px 16px; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius); margin-top:4px;">
                <div style="width:34px; height:34px; border-radius:50%; background:var(--grad); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:0.82rem; flex-shrink:0;">
                    <?php echo $g['assigned_name'] ? strtoupper(substr($g['assigned_name'], 0, 1)) : '?'; ?>
                </div>
                <div>
                    <div style="font-size:0.8rem; font-weight:700; color:var(--text);">
                        <?php echo $g['assigned_name'] ? htmlspecialchars($g['assigned_name']) : 'Unassigned'; ?>
                    </div>
                    <div style="font-size:0.72rem; color:var(--text-muted);">
                        <?php echo $g['assigned_name'] ? 'Device Custodian' : 'Not assigned to any employee'; ?>
                    </div>
                </div>
                <?php if ($g['vendor_name']): ?>
                <div style="margin-left:auto; padding:4px 12px; background:var(--blue-dim); color:var(--blue); border-radius:20px; font-size:0.72rem; font-weight:700;">
                    <?php echo htmlspecialchars($g['vendor_name']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<div class="main-grid-layout">
    <!-- Left Column: Specs, Health Score & Depreciation -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        
        <!-- Smart Health Score Card -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('dashboard'); ?> Smart Health Analyzer</h3>
                <span class="badge" style="background:<?php echo $hs['color']; ?>22; color:<?php echo $hs['color']; ?>; border:1px solid <?php echo $hs['color']; ?>44; font-size:0.88rem; font-weight:700;">
                    <?php echo $hs['score']; ?>/100 &bull; <?php echo $hs['verdict']; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="health-bar-bg" style="height:12px; margin-bottom:20px;">
                    <div class="health-bar-fill" style="width:<?php echo $hs['score']; ?>%; background:<?php echo $hs['color']; ?>;"></div>
                </div>
                
                <h4 style="font-size:0.86rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:12px; letter-spacing:0.5px;">Health Deduction Breakdown</h4>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($hs['breakdown'] as $b): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:8px; font-size:0.84rem;">
                            <div>
                                <strong style="color:var(--text);"><?php echo htmlspecialchars($b['label']); ?></strong>
                                <div style="font-size:0.75rem; color:var(--text-muted);"><?php echo htmlspecialchars($b['note']); ?></div>
                            </div>
                            <span style="font-weight:700; color:<?php echo $b['delta'] > 0 ? 'var(--success)' : ($b['delta'] < 0 ? 'var(--danger)' : 'var(--text-muted)'); ?>;">
                                <?php echo ($b['delta'] > 0 ? '+' : '') . $b['delta']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Green Pulse Sustainability Forecaster (Redesigned) -->
        <div class="card forecaster-panel" style="overflow:hidden;">

            <!-- Card Header -->
            <div style="background:linear-gradient(135deg,rgba(16,185,129,0.08),rgba(16,185,129,0.03)); border-bottom:1px solid rgba(16,185,129,0.2); padding:20px 24px; margin:-24px -24px 24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:rgba(16,185,129,0.15); color:var(--success); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <?php echo get_svg_icon('leaf'); ?>
                        </div>
                        <div>
                            <h3 style="color:var(--success); font-size:0.98rem; font-weight:800; margin:0;">Green Pulse Carbon Calculator</h3>
                            <div style="font-size:0.72rem; color:var(--text-muted); margin-top:1px;">Environmental impact analysis for this asset</div>
                        </div>
                    </div>
                    <span class="badge badge-green" style="font-size:0.72rem; letter-spacing:0.04em;">Eco-Metrics Engine</span>
                </div>
            </div>

            <!-- CO2 Stat Cards Row -->
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px;">

                <!-- Manufacture CO2 -->
                <div style="background:var(--bg); border:1px solid var(--border); border-top:3px solid var(--text-muted); border-radius:var(--radius); padding:16px 14px; text-align:center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:6px; margin-bottom:8px;">
                        <svg width="14" height="14" fill="none" stroke="var(--text-muted)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted);">Manufacture</span>
                    </div>
                    <div style="font-size:1.55rem; font-weight:900; color:var(--text); line-height:1;"><?php echo number_format($total_co2_base, 0); ?></div>
                    <div style="font-size:0.7rem; font-weight:700; color:var(--text-muted); margin-top:2px;">kg CO&#8322;</div>
                </div>

                <!-- Repairs CO2 -->
                <div style="background:var(--bg); border:1px solid var(--border); border-top:3px solid var(--warning,#f59e0b); border-radius:var(--radius); padding:16px 14px; text-align:center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:6px; margin-bottom:8px;">
                        <svg width="14" height="14" fill="none" stroke="#f59e0b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                        <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted);">Repairs</span>
                    </div>
                    <div style="font-size:1.55rem; font-weight:900; color:#f59e0b; line-height:1;"><?php echo number_format($resolved_repairs_count * $co2_per_repair, 1); ?></div>
                    <div style="font-size:0.7rem; font-weight:700; color:var(--text-muted); margin-top:2px;">kg CO&#8322;</div>
                </div>

                <!-- Net Footprint -->
                <div style="background:linear-gradient(135deg,rgba(16,185,129,0.07),rgba(16,185,129,0.02)); border:1px solid rgba(16,185,129,0.3); border-top:3px solid var(--success); border-radius:var(--radius); padding:16px 14px; text-align:center;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:6px; margin-bottom:8px;">
                        <svg width="14" height="14" fill="none" stroke="var(--success)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/></svg>
                        <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:var(--success);">Net Footprint</span>
                    </div>
                    <div style="font-size:1.55rem; font-weight:900; color:var(--success); line-height:1;"><?php echo number_format($total_co2_actual, 1); ?></div>
                    <div style="font-size:0.7rem; font-weight:700; color:var(--success); opacity:0.7; margin-top:2px;">kg CO&#8322;</div>
                </div>

            </div>

            <!-- Lifecycle Extension Simulator -->
            <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius); padding:22px;">

                <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
                    <div>
                        <h4 style="font-size:0.92rem; font-weight:800; color:var(--text); margin:0 0 3px;">Lifecycle Extension Simulator</h4>
                        <p style="font-size:0.78rem; color:var(--text-muted); line-height:1.5; margin:0;">Slide to calculate the environmental offset and financial savings from extending this device beyond its standard <strong style="color:var(--text);"><?php echo $g['useful_life_yrs']; ?>-year</strong> lifespan.</p>
                    </div>
                    <div style="padding:4px 12px; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.25); border-radius:20px; font-size:0.72rem; font-weight:700; color:var(--success); white-space:nowrap;" id="sliderYearBadge">+1 Year</div>
                </div>

                <!-- Slider -->
                <div style="position:relative; margin-bottom:8px;">
                    <input type="range" class="simulator-slider" id="ecoSlider" min="1" max="5" value="1" style="width:100%;">
                </div>
                <!-- Tick labels -->
                <div style="display:flex; justify-content:space-between; font-size:0.72rem; color:var(--text-darker); font-weight:700; margin-bottom:22px; padding:0 2px;">
                    <span>+1 Yr</span><span>+2 Yrs</span><span>+3 Yrs</span><span>+4 Yrs</span><span>+5 Yrs</span>
                </div>

                <!-- Result Cards -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">

                    <!-- CO2 Prevented -->
                    <div style="background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(16,185,129,0.03)); border:1px solid rgba(16,185,129,0.25); border-radius:var(--radius); padding:18px; text-align:center;">
                        <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px;">
                            <div style="width:28px; height:28px; border-radius:8px; background:rgba(16,185,129,0.15); display:flex; align-items:center; justify-content:center;">
                                <svg width="15" height="15" fill="none" stroke="var(--success)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c.132 0 .263 0 .393.007a7.5 7.5 0 017.92 12.446A9 9 0 1112 3z"/></svg>
                            </div>
                            <span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--success);">CO&#8322; Prevented</span>
                        </div>
                        <div class="sim-val green" id="simCo2" style="font-size:1.6rem; font-weight:900; color:var(--success); line-height:1;">0 kg</div>
                        <div style="font-size:0.72rem; color:var(--text-muted); margin-top:4px;">Carbon emissions saved</div>
                    </div>

                    <!-- Asset Value -->
                    <div style="background:linear-gradient(135deg,rgba(26,107,255,0.1),rgba(26,107,255,0.03)); border:1px solid rgba(26,107,255,0.25); border-radius:var(--radius); padding:18px; text-align:center;">
                        <div style="display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px;">
                            <div style="width:28px; height:28px; border-radius:8px; background:rgba(26,107,255,0.12); display:flex; align-items:center; justify-content:center;">
                                <svg width="15" height="15" fill="none" stroke="var(--blue)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span style="font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:var(--blue);">Asset Utility</span>
                        </div>
                        <div class="sim-val blue" id="simCost" style="font-size:1.6rem; font-weight:900; color:var(--blue); line-height:1;">Rs. 0</div>
                        <div style="font-size:0.72rem; color:var(--text-muted); margin-top:4px;">Extended value saved</div>
                    </div>

                </div>

                <!-- Eco Achievement Badge Row -->
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:var(--bg-card); border:1px solid rgba(16,185,129,0.2); border-radius:var(--radius-sm); flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <svg width="16" height="16" fill="none" stroke="var(--success)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        <span style="font-size:0.8rem; color:var(--text-muted);">Green Achievement Level</span>
                    </div>
                    <strong style="color:var(--success); font-size:0.85rem; font-weight:800;" id="simBadge">Eco Conservator</strong>
                </div>

            </div>
        </div>

        <!-- Depreciation Table -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('analytics'); ?> Financial Valuation (WDV Method)</h3>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Opening Value</th>
                            <th>Depreciation (<?php echo $g['depreciation_rate']; ?>%)</th>
                            <th>Closing Valuation</th>
                            <th>Salvage %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dep_rows as $row): ?>
                            <tr>
                                <td>Year <?php echo $row['year']; ?></td>
                                <td><?php echo fmt_inr($row['opening']); ?></td>
                                <td><?php echo fmt_inr($row['dep']); ?></td>
                                <td><strong style="color:var(--text);"><?php echo fmt_inr($row['closing']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background:var(--border); color:var(--text-muted); font-size:0.74rem;">
                                        <?php echo $row['pct_left']; ?>% left
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Specs, Warranties, and History -->
    <div style="display:flex; flex-direction:column; gap:24px;">
        
        <!-- Asset Specifications -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('passport'); ?> Device Specifications</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php
                $specs = array(
                    array('Brand & Model', $g['brand'] . ' ' . $g['model']),
                    array('Asset Tag', $g['asset_tag']),
                    array('Serial Number', $g['serial_number'] ? $g['serial_number'] : '—'),
                    array('Status', gadget_status_badge($g['status'])),
                    array('Assigned Custodian', $g['assigned_name'] ? $g['assigned_name'] : '<span style="color:var(--text-darker);">Unassigned</span>'),
                    array('Purchase Date', $g['purchase_date'] ? date('d M Y', strtotime($g['purchase_date'])) : '—'),
                    array('Purchase Value', fmt_inr($g['purchase_price'])),
                    array('Maintenance Costs', fmt_inr($total_repairs_cost)),
                    array('Supplier / Vendor', $g['vendor_name'] ? $g['vendor_name'] : '—'),
                );
                foreach ($specs as $spec):
                ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid var(--border); font-size:0.84rem;">
                        <span style="color:var(--text-muted); font-weight:600;"><?php echo $spec[0]; ?></span>
                        <strong style="color:var(--text);"><?php echo $spec[1]; ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (!empty($g['description'])): ?>
                    <div style="padding:16px 20px; font-size:0.84rem; line-height:1.5; color:var(--text-muted);">
                        <strong style="color:var(--text); display:block; margin-bottom:4px;">Technical Notes:</strong>
                        <?php echo nl2br(htmlspecialchars($g['description'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Warranty Coverage -->
        <div class="card">
            <div class="card-header">
                <h3><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:middle; margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> Warranty &amp; AMC Contracts</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($wars) > 0): ?>
                    <?php foreach ($wars as $w): 
                        $days = warranty_days_left($w['end_date']);
                        $is_amc = $w['type'] === 'amc';
                    ?>
                        <div style="padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; gap:10px;">
                            <div>
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                    <span class="badge <?php echo $is_amc ? 'badge-purple' : 'badge-blue'; ?>"><?php echo $is_amc ? 'AMC Contract' : 'Standard Warranty'; ?></span>
                                    <strong style="font-size:0.84rem; color:var(--text);"><?php echo htmlspecialchars($w['provider']); ?></strong>
                                </div>
                                <div style="font-size:0.75rem; color:var(--text-muted);">
                                    Ends: <?php echo date('d M Y', strtotime($w['end_date'])); ?>
                                </div>
                            </div>
                            <span class="badge <?php echo $days <= 0 ? 'badge-red' : ($days <= 30 ? 'badge-amber' : 'badge-green'); ?>">
                                <?php echo $days <= 0 ? 'Expired' : $days . 'd left'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding:30px;">
                        <p>No warranty or AMC details have been registered for this device.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Issue Tickets -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo get_svg_icon('issues'); ?> Recent Fault Reports</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (count($issues) > 0): ?>
                    <?php foreach ($issues as $iss): ?>
                        <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; flex-direction:column; gap:6px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                <strong style="font-size:0.84rem; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;">
                                    <a href="issue_detail.php?id=<?php echo $iss['id']; ?>" style="color:var(--text);">
                                        <?php echo htmlspecialchars($iss['title']); ?>
                                    </a>
                                </strong>
                                <?php echo issue_status_badge($iss['status']); ?>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.72rem; color:var(--text-muted);">
                                <span>Reported: <?php echo date('d M Y', strtotime($iss['raised_at'])); ?></span>
                                <span>Priority: <?php echo priority_badge($iss['priority']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="padding:30px;">
                        <p>This device has a clean repair history. No faults reported.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var slider = document.getElementById('ecoSlider');
    
    // Core parameters from database (PHP variables injected)
    var co2Manufacture = <?php echo (float)$g['co2_manufacture']; ?>;
    var usefulLifeYrs = <?php echo (int)$g['useful_life_yrs']; ?>;
    var purchasePrice = <?php echo (float)$g['purchase_price']; ?>;
    var currentDepreciationRate = <?php echo (float)$g['depreciation_rate']; ?>;

    function updateSimulation() {
        var yrs = parseInt(slider.value, 10);

        // Update year badge
        var badge = document.getElementById('sliderYearBadge');
        if (badge) badge.textContent = '+' + yrs + ' Year' + (yrs > 1 ? 's' : '');
        
        // 1. Carbon Savings Calculation
        var co2Avoided = Math.round(co2Manufacture * (yrs / usefulLifeYrs));
        document.getElementById('simCo2').textContent = co2Avoided + ' kg CO2';

        // 2. Financial Utility Savings
        var financialUtilityGained = Math.round(purchasePrice * (yrs / usefulLifeYrs));
        document.getElementById('simCost').textContent = 'Rs. ' + financialUtilityGained.toLocaleString();

        // 3. Sustainability Achievement Badge Level
        var badgeText = 'Eco Supporter';
        if (yrs >= 5) badgeText = 'Net Zero Vanguard';
        else if (yrs >= 4) badgeText = 'Carbon Neutral Champion';
        else if (yrs >= 3) badgeText = 'Resource Custodian';
        else if (yrs >= 2) badgeText = 'Green Steward';

        document.getElementById('simBadge').textContent = badgeText;
    }

    if (slider) {
        slider.addEventListener('input', updateSimulation);
        updateSimulation(); // Run once on startup
    }
});
</script>

<?php include 'footer.php'; ?>
