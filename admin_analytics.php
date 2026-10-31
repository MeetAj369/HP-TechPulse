<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db.php';
include 'auth.php';
include 'functions.php';

require_support(); // Guard Admin and Support users

$page_title = 'System Analytics';
$active_nav = 'analytics';


// 1. Gadgets by Category
$cat_labels = array();
$cat_values = array();
$cat_q = mysqli_query($conn, "SELECT category, COUNT(*) as c FROM gadgets WHERE status != 'retired' GROUP BY category ORDER BY c DESC");
if ($cat_q) {
    while ($row = mysqli_fetch_assoc($cat_q)) {
        $cat_labels[] = $row['category'];
        $cat_values[] = (int)$row['c'];
    }
}

// 2. Ticket Status breakdown
$ticket_labels = array('Raised', 'Assigned', 'In Progress', 'Testing', 'Resolved');
$ticket_keys = array('raised', 'assigned', 'in-progress', 'testing', 'resolved');
$ticket_values = array(0, 0, 0, 0, 0);
$t_q = mysqli_query($conn, "SELECT status, COUNT(*) as c FROM issues GROUP BY status");
if ($t_q) {
    while ($row = mysqli_fetch_assoc($t_q)) {
        $idx = array_search($row['status'], $ticket_keys);
        if ($idx !== false) {
            $ticket_values[$idx] = (int)$row['c'];
        }
    }
}

// 3. Green Pulse CO2 Carbon footprint by Category
$eco_labels = array();
$eco_values = array();
$eco_q = mysqli_query($conn, "SELECT category, SUM(co2_manufacture) as total_co2 FROM gadgets WHERE status != 'retired' GROUP BY category ORDER BY total_co2 DESC");
if ($eco_q) {
    while ($row = mysqli_fetch_assoc($eco_q)) {
        $eco_labels[] = $row['category'];
        $eco_values[] = round((float)$row['total_co2'], 1);
    }
}

// 4. Financial Depreciation stats
$total_purchase_val = 0.0;
$current_book_val = 0.0;
$g_val_q = mysqli_query($conn, "SELECT purchase_price, depreciation_rate, useful_life_yrs, purchase_date FROM gadgets WHERE status != 'retired'");
if ($g_val_q) {
    while ($g = mysqli_fetch_assoc($g_val_q)) {
        $price = (float)$g['purchase_price'];
        $total_purchase_val += $price;
        
        // Calculate WDV book value based on age
        $age_yrs = 0;
        if (!empty($g['purchase_date'])) {
            $purchase = new DateTime($g['purchase_date']);
            $now = new DateTime();
            $diff = $now->diff($purchase);
            $age_yrs = $diff->y + ($diff->m / 12);
        }
        
        $val = $price;
        $rate = (float)$g['depreciation_rate'] / 100;
        for ($i = 0; $i < (int)floor($age_yrs); $i++) {
            $val -= $val * $rate;
        }
        // Partial year WDV
        $fraction = $age_yrs - floor($age_yrs);
        if ($fraction > 0) {
            $val -= $val * $rate * $fraction;
        }
        $current_book_val += max(0, $val);
    }
}

include 'header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Analytics Console</h1>
        <p>Live database graphical reports. Pure HTML5 Canvas rendering (100% offline compatible).</p>
    </div>
</div>

<!-- Financial Summary Strip -->
<div class="stats-grid">
    <div class="stat-card green" style="grid-column: span 2;">
        <div class="stat-label">Total Historical Asset Investment</div>
        <div class="stat-value"><?php echo fmt_inr($total_purchase_val); ?></div>
        <div class="stat-sub">Sum of purchase price of all active/in-repair devices</div>
    </div>
    <div class="stat-card cyan" style="grid-column: span 2;">
        <div class="stat-label">Calculated Residual Asset Book Value</div>
        <div class="stat-value"><?php echo fmt_inr($current_book_val); ?></div>
        <div class="stat-sub">Current amortized net worth of company assets (WDV method)</div>
    </div>
</div>

<div class="main-grid-layout" style="margin-bottom: 24px;">
    <!-- Chart 1: Gadgets by Category -->
    <div class="card">
        <div class="card-header">
            <h3>Inventory Share by Category</h3>
        </div>
        <div class="card-body" style="text-align: center;">
            <canvas id="chartCategory" width="380" height="240" style="max-width:100%; height:auto;"></canvas>
        </div>
    </div>

    <!-- Chart 2: Ticket Status Pipeline -->
    <div class="card">
        <div class="card-header">
            <h3>Support Tickets status Share</h3>
        </div>
        <div class="card-body" style="text-align: center;">
            <canvas id="chartTickets" width="380" height="240" style="max-width:100%; height:auto;"></canvas>
        </div>
    </div>
</div>

<div class="main-grid-layout">
    <!-- Chart 3: Carbon Footprint (Green Pulse) -->
    <div class="card" style="grid-column: span 2;">
        <div class="card-header">
            <h3>Green Pulse: Manufacturing Carbon Footprint (kg CO2) by Category</h3>
        </div>
        <div class="card-body" style="text-align: center; display: flex; justify-content: center;">
            <canvas id="chartCarbon" width="700" height="250" style="max-width:100%; height:auto;"></canvas>
        </div>
    </div>
</div>

<!-- HTML5 Pure Canvas Drawing Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Core parameters from PHP
    var catLabels  = <?php echo json_encode($cat_labels); ?>;
    var catValues  = <?php echo json_encode($cat_values); ?>;
    
    var ticketLabels = <?php echo json_encode($ticket_labels); ?>;
    var ticketValues = <?php echo json_encode($ticket_values); ?>;
    
    var ecoLabels  = <?php echo json_encode($eco_labels); ?>;
    var ecoValues  = <?php echo json_encode($eco_values); ?>;

    var themeDark = !document.documentElement.classList.contains('light-mode');
    var textColor = themeDark ? '#f1f5f9' : '#1f2937';
    var gridColor = themeDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

    // Palette
    var colors = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#06b6d4', '#64748b', '#ec4899'];

    (function drawDonutChart() {
        var canvas = document.getElementById('chartCategory');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var cx = canvas.width / 2;
        var cy = canvas.height / 2 - 10;
        var radius = Math.min(cx, cy) - 30;

        var total = catValues.reduce(function(a, b){ return a + b; }, 0);
        if (total === 0) {
            ctx.fillStyle = textColor;
            ctx.font = '14px Arial';
            ctx.fillText('No data available', cx - 50, cy);
            return;
        }

        var startAngle = -Math.PI / 2;
        for (var i = 0; i < catValues.length; i++) {
            var sliceAngle = (catValues[i] / total) * 2 * Math.PI;
            ctx.beginPath();
            ctx.arc(cx, cy, radius, startAngle, startAngle + sliceAngle);
            ctx.lineTo(cx, cy);
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            startAngle += sliceAngle;
        }

        // Inner circle (Donut cut)
        ctx.beginPath();
        ctx.arc(cx, cy, radius * 0.55, 0, 2 * Math.PI);
        ctx.fillStyle = themeDark ? '#0e131f' : '#ffffff';
        ctx.fill();

        // Draw Labels / Legend at bottom
        var lx = 10, ly = canvas.height - 24;
        ctx.font = '10px sans-serif';
        for (var i = 0; i < catLabels.length; i++) {
            ctx.fillStyle = colors[i % colors.length];
            ctx.fillRect(lx, ly, 10, 10);
            ctx.fillStyle = textColor;
            var pct = Math.round((catValues[i] / total) * 100);
            ctx.fillText(catLabels[i] + ' (' + catValues[i] + ')', lx + 14, ly + 8);
            lx += 85;
            if (lx > canvas.width - 80) { lx = 10; ly += 14; }
        }
    })();

    (function drawPieChart() {
        var canvas = document.getElementById('chartTickets');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var cx = canvas.width / 2;
        var cy = canvas.height / 2 - 10;
        var radius = Math.min(cx, cy) - 30;

        var total = ticketValues.reduce(function(a, b){ return a + b; }, 0);
        if (total === 0) {
            ctx.fillStyle = textColor;
            ctx.font = '14px Arial';
            ctx.fillText('No active tickets log', cx - 60, cy);
            return;
        }

        var startAngle = -Math.PI / 2;
        var ticketColors = ['#f59e0b', '#3b82f6', '#8b5cf6', '#06b6d4', '#10b981']; // matching badge colors

        for (var i = 0; i < ticketValues.length; i++) {
            if (ticketValues[i] === 0) continue;
            var sliceAngle = (ticketValues[i] / total) * 2 * Math.PI;
            ctx.beginPath();
            ctx.arc(cx, cy, radius, startAngle, startAngle + sliceAngle);
            ctx.lineTo(cx, cy);
            ctx.fillStyle = ticketColors[i];
            ctx.fill();
            startAngle += sliceAngle;
        }

        // Draw Legend
        var lx = 10, ly = canvas.height - 24;
        ctx.font = '10px sans-serif';
        for (var i = 0; i < ticketLabels.length; i++) {
            ctx.fillStyle = ticketColors[i];
            ctx.fillRect(lx, ly, 10, 10);
            ctx.fillStyle = textColor;
            ctx.fillText(ticketLabels[i] + ' (' + ticketValues[i] + ')', lx + 14, ly + 8);
            lx += 78;
        }
    })();

    (function drawBarChart() {
        var canvas = document.getElementById('chartCarbon');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');

        var paddingLeft = 60;
        var paddingBottom = 40;
        var paddingTop = 30;
        var paddingRight = 30;

        var chartW = canvas.width - paddingLeft - paddingRight;
        var chartH = canvas.height - paddingTop - paddingBottom;

        var maxVal = Math.max.apply(null, ecoValues);
        if (maxVal === 0 || isFinite(maxVal) === false) maxVal = 100;
        // round maxVal to neat grid
        maxVal = Math.ceil(maxVal / 100) * 100;

        // Draw Axes
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;
        ctx.beginPath();
        // y axis line
        ctx.moveTo(paddingLeft, paddingTop);
        ctx.lineTo(paddingLeft, canvas.height - paddingBottom);
        // x axis line
        ctx.lineTo(canvas.width - paddingRight, canvas.height - paddingBottom);
        ctx.stroke();

        // Draw Y ticks & Grid Lines
        ctx.fillStyle = textColor;
        ctx.font = '10px sans-serif';
        ctx.textAlign = 'right';
        var ticksCount = 4;
        for (var i = 0; i <= ticksCount; i++) {
            var yVal = (maxVal / ticksCount) * i;
            var yPos = canvas.height - paddingBottom - (chartH * (i / ticksCount));
            ctx.fillText(yVal + ' kg', paddingLeft - 8, yPos + 3);
            
            if (i > 0) {
                ctx.strokeStyle = gridColor;
                ctx.beginPath();
                ctx.moveTo(paddingLeft, yPos);
                ctx.lineTo(canvas.width - paddingRight, yPos);
                ctx.stroke();
            }
        }

        // Draw Bars
        var barWidth = Math.round((chartW / ecoValues.length) * 0.5);
        var spacing = Math.round((chartW / ecoValues.length) * 0.5);
        
        ctx.textAlign = 'center';
        for (var i = 0; i < ecoValues.length; i++) {
            var barH = (ecoValues[i] / maxVal) * chartH;
            var xPos = paddingLeft + spacing/2 + i * (barWidth + spacing);
            var yPos = canvas.height - paddingBottom - barH;

            // Bar drawing
            ctx.fillStyle = '#10b981'; // Green footprint color
            ctx.fillRect(xPos, yPos, barWidth, barH);
            
            // Draw Value on top
            ctx.fillStyle = textColor;
            ctx.fillText(ecoValues[i] + ' kg', xPos + barWidth/2, yPos - 6);

            // Draw Category name under bar
            ctx.fillText(ecoLabels[i], xPos + barWidth/2, canvas.height - paddingBottom + 16);
        }
    })();

});
</script>

<?php include 'footer.php'; ?>
