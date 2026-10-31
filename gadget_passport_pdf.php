<?php
require_once __DIR__ . '/session_slot.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

ob_start();

require_once 'db.php';
require_once 'auth.php';
require_once 'functions.php';
require_once 'fpdf.php';

if (!is_logged_in()) {
    if (ob_get_length()) ob_end_clean();
    tp_redirect('login.php?msg=login_required');
    exit;
}

if (!isset($_GET['id'])) { die('Error: No device ID specified.'); }
$gid  = (int)$_GET['id'];
$role = current_role();
$uid  = current_uid();
$is_support = current_is_support();

$gad_q = mysqli_query($conn,
    "SELECT g.*, v.name AS vendor_name, u.name AS assigned_name, u.email AS assigned_email, u.department AS assigned_dept
     FROM gadgets g
     LEFT JOIN vendors v ON g.vendor_id = v.id
     LEFT JOIN users u ON g.assigned_to = u.id
     WHERE g.id = $gid"
);
$gad = mysqli_fetch_assoc($gad_q);
if (!$gad) { die('Error: Device record not found.'); }
if ($role === 'user' && !$is_support && (int)$gad['assigned_to'] !== $uid) { die('Access denied.'); }

$hs       = calculate_health_score($conn, $gid);
$dep_rows = calculate_depreciation($gad['purchase_price'], $gad['depreciation_rate'], $gad['useful_life_yrs']);

$wars = array();
$wq = mysqli_query($conn, "SELECT * FROM warranties WHERE gadget_id = $gid ORDER BY end_date ASC");
if ($wq) { while ($w = mysqli_fetch_assoc($wq)) { $wars[] = $w; } }

$issues = array();
$iq = mysqli_query($conn, "SELECT i.*, u.name AS raised_name FROM issues i JOIN users u ON i.raised_by = u.id WHERE i.gadget_id = $gid ORDER BY i.raised_at DESC LIMIT 5");
if ($iq) { while ($i = mysqli_fetch_assoc($iq)) { $issues[] = $i; } }

$total_rep = 0.0;
$cq = mysqli_query($conn, "SELECT SUM(cost) FROM maintenance WHERE gadget_id = $gid AND completed_date IS NOT NULL");
if ($cq) { $total_rep = (float)mysqli_fetch_row($cq)[0]; }
$tco = (float)$gad['purchase_price'] + $total_rep;

$doc_num   = 'TP-' . strtoupper(substr(md5($gid . $gad['asset_tag']), 0, 8));
$clean_tag = str_replace(array(' ', '/'), '-', $gad['asset_tag']);
$filename  = 'HP_TechPulse_Device_Passport_' . $clean_tag . '.pdf';

class DevicePassportPDF extends FPDF {
    var $gad;
    var $doc_num;

    function Header() {
        // ── Navy header bar ──
        $this->SetFillColor(15, 23, 42);
        $this->Rect(0, 0, 210, 26, 'F');

        // Red accent strip
        $this->SetFillColor(220, 38, 38);
        $this->Rect(0, 26, 210, 1, 'F');

        // ── LEFT: Logo + Brand title ──
        $logo = __DIR__ . '/uploads/HP TechPulse logo.png';
        if (file_exists($logo)) {
            $this->Image($logo, 10, 4, 16);
            $this->SetXY(28, 6);
        } else {
            $this->SetXY(10, 6);
        }

        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(50, 6, 'HP TechPulse', 0, 0, 'L');

        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetTextColor(252, 165, 165);
        $this->Cell(0, 6, '  DEVICE PASSPORT', 0, 1, 'L');

        // ── RIGHT: Doc info ──
        $this->SetFont('Helvetica', '', 6.5);
        $this->SetTextColor(203, 213, 225);
        $this->SetXY(110, 6);
        $this->Cell(90, 4, 'Doc: ' . $this->doc_num, 0, 1, 'R');
        $this->SetX(110);
        $this->Cell(90, 4, 'Generated: ' . date('d M Y, H:i'), 0, 1, 'R');
        $this->SetX(110);
        $this->Cell(90, 4, 'HP TechPulse Enterprise Asset Management', 0, 1, 'R');

        $this->Ln(8);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetDrawColor(220, 220, 220);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(95, 5, 'HP TechPulse Enterprise Asset Management', 0, 0, 'L');
        $this->Cell(95, 5, 'Page ' . $this->PageNo(), 0, 0, 'R');
    }

    function SectionTitle($title) {
        $this->Ln(4);
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(220, 38, 38);
        $this->Cell(186, 6, strtoupper($title), 0, 1, 'L');
        $this->SetDrawColor(220, 38, 38);
        $this->SetLineWidth(0.3);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->Ln(3);
    }

    function SpecRow($label, $value, $label2, $value2) {
        // Label col 1
        $this->SetFillColor(245, 247, 250);
        $this->SetDrawColor(215, 220, 230);
        $this->SetTextColor(80, 90, 110);
        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell(35, 6, '  ' . $label, 1, 0, 'L', true);

        // Value col 1
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(20, 25, 40);
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(58, 6, '  ' . $value, 1, 0, 'L', true);

        // Label col 2
        $this->SetFillColor(245, 247, 250);
        $this->SetTextColor(80, 90, 110);
        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell(35, 6, '  ' . $label2, 1, 0, 'L', true);

        // Value col 2
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(20, 25, 40);
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(58, 6, '  ' . $value2, 1, 1, 'L', true);
    }
}

$pdf = new DevicePassportPDF('P', 'mm', 'A4');
$pdf->gad = $gad;
$pdf->doc_num = $doc_num;
$pdf->AliasNbPages();
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 14);
$pdf->AddPage();

$card_y = $pdf->GetY();
$pdf->SetFillColor(250, 251, 252);
$pdf->SetDrawColor(200, 210, 225);
$pdf->RoundedRect(12, $card_y, 186, 24, 2, 'DF');

// Device name
$pdf->SetXY(16, $card_y + 2);
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->SetTextColor(15, 23, 42);
$pdf->Cell(120, 6, $gad['name'], 0, 1, 'L');

// Meta line
$pdf->SetX(16);
$pdf->SetFont('Helvetica', '', 7);
$pdf->SetTextColor(80, 90, 110);
$meta = 'Asset: ' . $gad['asset_tag'];
$meta .= '   |   Category: ' . $gad['category'];
$meta .= '   |   Brand: ' . ($gad['brand'] ? $gad['brand'] : 'N/A');
$meta .= '   |   Custodian: ' . ($gad['assigned_name'] ? $gad['assigned_name'] : 'Unassigned');
$pdf->Cell(140, 4.5, $meta, 0, 1, 'L');

// Status badge
$st = strtolower($gad['status']);
if ($st === 'active') { $pdf->SetFillColor(22, 163, 74); }
elseif ($st === 'repair') { $pdf->SetFillColor(37, 99, 235); }
elseif ($st === 'inactive') { $pdf->SetFillColor(217, 119, 6); }
else { $pdf->SetFillColor(220, 38, 38); }
$pdf->SetXY(16, $card_y + 16);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 7);
$pdf->Cell(22, 4.5, '  ' . strtoupper($gad['status']) . '  ', 0, 0, 'C', true);

// Device photo
if (!empty($gad['image'])) {
    $img_path = __DIR__ . '/uploads/' . $gad['image'];
    if (file_exists($img_path)) {
        $ext = strtolower(pathinfo($img_path, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png') {
            $pdf->Image($img_path, 164, $card_y + 1, 22, 22);
        }
    }
}

$pdf->SetY($card_y + 27);

$pdf->SectionTitle('Device Specifications');

$pdf->SpecRow('Brand / Manufacturer', $gad['brand'] ? $gad['brand'] : '--', 'Model', $gad['model'] ? $gad['model'] : '--');
$pdf->SpecRow('Serial Number', $gad['serial_number'] ? $gad['serial_number'] : '--', 'Asset Tag', $gad['asset_tag']);
$pdf->SpecRow('Category', $gad['category'] ? $gad['category'] : '--', 'Department', $gad['assigned_dept'] ? $gad['assigned_dept'] : '--');
$pdf->SpecRow('Assigned Custodian', $gad['assigned_name'] ? $gad['assigned_name'] : 'Unassigned', 'Preferred Supplier', $gad['vendor_name'] ? $gad['vendor_name'] : '--');
$pdf->SpecRow('Purchase Date', $gad['purchase_date'] ? date('d M Y', strtotime($gad['purchase_date'])) : '--', 'Purchase Price', 'Rs. ' . number_format($gad['purchase_price'], 2));

$pdf->SectionTitle('Smart Health Score Analysis');

$hy = $pdf->GetY();
$pdf->SetFillColor(250, 251, 252);
$pdf->SetDrawColor(200, 210, 225);
$pdf->RoundedRect(12, $hy, 186, 20, 2, 'DF');

// Score number
$pdf->SetXY(18, $hy + 2);
$pdf->SetFont('Helvetica', 'B', 18);
$sc = $hs['score'];
if ($sc >= 80) { $pdf->SetTextColor(22, 163, 74); }
elseif ($sc >= 60) { $pdf->SetTextColor(37, 99, 235); }
elseif ($sc >= 40) { $pdf->SetTextColor(217, 119, 6); }
else { $pdf->SetTextColor(220, 38, 38); }
$pdf->Cell(28, 8, $sc . '/100', 0, 1, 'L');

// Verdict
$pdf->SetX(18);
$pdf->SetFont('Helvetica', 'B', 7);
$pdf->SetTextColor(60, 70, 90);
$pdf->Cell(28, 4, strtoupper($hs['verdict']), 0, 0, 'L');

// Breakdown items
$pdf->SetFont('Helvetica', '', 6.5);
$pdf->SetTextColor(60, 70, 90);
$bi = 0;
foreach ($hs['breakdown'] as $b) {
    if ($bi >= 4) break;
    $pdf->SetXY(55, $hy + 2 + ($bi * 4.5));
    $delta_str = ($b['delta'] > 0 ? '+' : '') . $b['delta'] . ' pts';
    $pdf->Cell(100, 4, $b['label'] . ' (' . $b['note'] . ')', 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 6.5);
    if ($b['delta'] < 0) { $pdf->SetTextColor(220, 38, 38); }
    else { $pdf->SetTextColor(22, 163, 74); }
    $pdf->Cell(20, 4, $delta_str, 0, 0, 'R');
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor(60, 70, 90);
    $bi++;
}

$pdf->SetY($hy + 22);

$pdf->SectionTitle('Financial Valuation (WDV Method)');

// Summary row
$pdf->SetDrawColor(215, 220, 230);
$pdf->SetFillColor(245, 247, 250);
$pdf->SetFont('Helvetica', 'B', 7);
$pdf->SetTextColor(80, 90, 110);
$pdf->Cell(62, 4.5, '  ORIGINAL COST', 1, 0, 'L', true);
$pdf->Cell(62, 4.5, '  REPAIR OVERHEAD', 1, 0, 'L', true);
$pdf->Cell(62, 4.5, '  TOTAL COST OF OWNERSHIP', 1, 1, 'L', true);

$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(20, 25, 40);
$pdf->SetFillColor(255, 255, 255);
$pdf->Cell(62, 6.5, '  Rs. ' . number_format($gad['purchase_price']), 1, 0, 'L', true);
$pdf->SetTextColor(220, 38, 38);
$pdf->Cell(62, 6.5, '  Rs. ' . number_format($total_rep), 1, 0, 'L', true);
$pdf->SetTextColor(37, 99, 235);
$pdf->Cell(62, 6.5, '  Rs. ' . number_format($tco), 1, 1, 'L', true);

$pdf->Ln(2);

// Depreciation table
if (!empty($dep_rows)) {
    // Table header
    $pdf->SetFillColor(37, 99, 235);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 6.5);
    $pdf->Cell(28, 5, '  Year', 1, 0, 'L', true);
    $pdf->Cell(44, 5, '  Opening Value', 1, 0, 'R', true);
    $pdf->Cell(44, 5, '  Depreciation (' . $gad['depreciation_rate'] . '%)', 1, 0, 'R', true);
    $pdf->Cell(44, 5, '  Closing Valuation', 1, 0, 'R', true);
    $pdf->Cell(26, 5, '  Salvage', 1, 1, 'C', true);

    // Table rows
    $row_i = 0;
    foreach (array_slice($dep_rows, 0, 6) as $dr) {
        $bg = ($row_i % 2 === 0) ? array(255,255,255) : array(248,250,252);
        $pdf->SetFillColor($bg[0], $bg[1], $bg[2]);
        $pdf->SetDrawColor(215, 220, 230);
        $pdf->SetTextColor(40, 50, 70);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(28, 4.5, '  Year ' . $dr['year'], 1, 0, 'L', true);
        $pdf->Cell(44, 4.5, 'Rs. ' . number_format($dr['opening']) . '  ', 1, 0, 'R', true);
        $pdf->SetTextColor(220, 38, 38);
        $pdf->Cell(44, 4.5, '-Rs. ' . number_format($dr['dep']) . '  ', 1, 0, 'R', true);
        $pdf->SetTextColor(20, 25, 40);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(44, 4.5, 'Rs. ' . number_format($dr['closing']) . '  ', 1, 0, 'R', true);
        $pdf->SetFont('Helvetica', '', 6.5);
        $pdf->SetTextColor(100, 110, 130);
        $pdf->Cell(26, 4.5, $dr['pct_left'] . '%', 1, 1, 'C', true);
        $row_i++;
    }
}

$pdf->SectionTitle('Warranty & Support Status');

$pdf->SetFont('Helvetica', '', 7.5);
$pdf->SetTextColor(40, 50, 70);
if (count($wars) > 0) {
    foreach ($wars as $w) {
        $days = warranty_days_left($w['end_date']);
        if ($days <= 0) { $w_status = 'EXPIRED'; }
        elseif ($days <= 90) { $w_status = $days . ' days left'; }
        else { $w_status = $days . ' days left'; }
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(40, 50, 70);
        $pdf->Cell(130, 5, '  ' . ucfirst($w['type']) . ' (' . $w['provider'] . ') -- Ends: ' . date('d M Y', strtotime($w['end_date'])), 0, 0, 'L');
        $pdf->SetFont('Helvetica', 'B', 7.5);
        if ($days <= 0) { $pdf->SetTextColor(220, 38, 38); }
        elseif ($days <= 90) { $pdf->SetTextColor(217, 119, 6); }
        else { $pdf->SetTextColor(22, 163, 74); }
        $pdf->Cell(56, 5, $w_status, 0, 1, 'R');
    }
} else {
    $pdf->SetTextColor(120, 130, 150);
    $pdf->Cell(186, 5, '  No active warranty or AMC contract records.', 0, 1, 'L');
}

$pdf->Ln(4);

$sig_y = $pdf->GetY() + 3;
// Only add new page if truly no space — signatures take ~28mm + 14mm footer = 42mm from bottom
if ($sig_y > 250) {
    $pdf->AddPage();
    $sig_y = $pdf->GetY() + 3;
}

// Official Stamp Badge (placed cleanly on right above signature 3)
$pdf->SetDrawColor(220, 38, 38);
$pdf->SetLineWidth(0.4);
$pdf->RoundedRect(138, $sig_y, 48, 12, 2, 'D');
$pdf->SetLineWidth(0.2);

$pdf->SetXY(138, $sig_y + 1.5);
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(220, 38, 38);
$pdf->Cell(48, 4, 'HP TECHPULSE', 0, 1, 'C');
$pdf->SetX(138);
$pdf->SetFont('Helvetica', '', 6);
$pdf->SetTextColor(100, 110, 130);
$pdf->Cell(48, 3, 'CERTIFIED ASSET RECORD', 0, 1, 'C');

// Signature lines (placed at sig_y + 18)
$pdf->SetDrawColor(160, 170, 185);
$pdf->SetLineWidth(0.2);
$pdf->Line(14, $sig_y + 18, 58, $sig_y + 18);
$pdf->Line(76, $sig_y + 18, 120, $sig_y + 18);
$pdf->Line(138, $sig_y + 18, 186, $sig_y + 18);

$pdf->SetXY(14, $sig_y + 19);
$pdf->SetFont('Helvetica', 'B', 7);
$pdf->SetTextColor(100, 110, 130);
$pdf->Cell(44, 4, 'IT ASSET MANAGER', 0, 0, 'C');
$pdf->SetX(76);
$pdf->Cell(44, 4, 'DEPARTMENT HEAD', 0, 0, 'C');
$pdf->SetX(138);
$pdf->Cell(48, 4, 'AUTHORIZED CUSTODIAN', 0, 1, 'C');

// Explicitly move Y cursor 8mm past the signature labels
$pdf->SetY($sig_y + 28);

$pdf->SetDrawColor(226, 232, 240);
$pdf->Line(12, $pdf->GetY(), 198, $pdf->GetY());
$pdf->Ln(2);
$pdf->SetFont('Helvetica', '', 7);
$pdf->SetTextColor(140, 150, 170);
$pdf->Cell(186, 4, 'Generated on ' . date('l, d F Y \a\t H:i:s') . '  |  HP TechPulse  |  Document Ref: ' . $doc_num, 0, 1, 'C');

if (ob_get_length()) ob_end_clean();
$pdf->Output('D', $filename);
exit;
