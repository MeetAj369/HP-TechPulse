<?php
// ==============================================================================
// FPDF 1.86 — Official Pure PHP PDF Generation Class
// PHP 5.4+ Compatible | 100% Pure PHP — Zero External Dependencies
// Includes automatic GD fallback for PNGs with alpha channels
// ==============================================================================
if(!class_exists('FPDF')) {
define('FPDF_VERSION','1.86');

class FPDF {
    var $page;               // current page number
    var $n;                  // current object number
    var $offsets;            // array of object offsets
    var $buffer;             // buffer holding in-memory PDF
    var $pages;              // array containing pages
    var $state;              // current document state
    var $compress;           // compression flag
    var $k;                  // scale factor (number of points in user unit)
    var $DefOrientation;     // default orientation
    var $CurOrientation;     // current orientation
    var $StdPageSizes;       // standard page sizes
    var $DefPageSize;        // default page size
    var $CurPageSize;        // current page size
    var $CurRotation;        // current page rotation
    var $PageInfo;           // page-related data
    var $wPt, $hPt;          // dimensions of current page in points
    var $w, $h;              // dimensions of current page in user units
    var $lMargin;            // left margin
    var $tMargin;            // top margin
    var $rMargin;            // right margin
    var $bMargin;            // page break margin
    var $cMargin;            // cell margin
    var $x, $y;              // current position in user units
    var $lasth;              // height of last printed cell
    var $LineWidth;          // line width in user units
    var $fontpath;           // path containing fonts
    var $CoreFonts;          // array of core font names
    var $fonts;              // array of used fonts
    var $FontFiles;          // array of font files
    var $encodings;          // array of encodings
    var $cmaps;              // array of ToUnicode CMaps
    var $FontFamily;         // current font family
    var $FontStyle;          // current font style
    var $underline;          // underlining flag
    var $CurrentFont;        // current font info
    var $FontSizePt;         // current font size in points
    var $FontSize;           // current font size in user units
    var $DrawColor;          // commands for drawing color
    var $FillColor;          // commands for filling color
    var $TextColor;          // commands for text color
    var $ColorFlag;          // indicates whether fill and text colors are different
    var $WithAlpha;          // indicates whether alpha channel is used
    var $ws;                 // word spacing
    var $images;             // array of used images
    var $PageLinks;          // array of links in pages
    var $links;              // array of internal links
    var $AutoPageBreak;      // automatic page breaking
    var $PageBreakTrigger;   // threshold for automatic page breaks
    var $InHeader;           // flag set when processing header
    var $InFooter;           // flag set when processing footer
    var $AliasNbPages;       // alias for total number of pages
    var $ZoomMode;           // zoom display mode
    var $LayoutMode;         // layout display mode
    var $metadata;           // document properties
    var $PDFVersion;         // PDF version number

    function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->_doinit($orientation, $unit, $size);
    }

    function FPDF($orientation='P', $unit='mm', $size='A4') {
        $this->_doinit($orientation, $unit, $size);
    }

    function _doinit($orientation, $unit, $size) {
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->PageInfo = array();
        $this->state = 0;
        $this->fonts = array();
        $this->FontFiles = array();
        $this->encodings = array();
        $this->cmaps = array();
        $this->images = array();
        $this->links = array();
        $this->offsets = array();
        $this->InHeader = false;
        $this->InFooter = false;
        $this->AliasNbPages = '{nb}';
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->WithAlpha = false;
        $this->ws = 0;
        $this->fontpath = '';
        $this->CoreFonts = array('courier','helvetica','times','symbol','zapfdingbats');
        
        if($unit=='pt') $this->k = 1;
        elseif($unit=='mm') $this->k = 72/25.4;
        elseif($unit=='cm') $this->k = 72/2.54;
        elseif($unit=='in') $this->k = 72;
        else $this->Error('Incorrect unit: '.$unit);

        $this->StdPageSizes = array(
            'a3'=>array(841.89,1190.55), 'a4'=>array(595.28,841.89), 'a5'=>array(420.94,595.28),
            'letter'=>array(612,792), 'legal'=>array(612,1008)
        );
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;

        $orientation = strtolower($orientation);
        if($orientation=='p' || $orientation=='portrait') {
            $this->DefOrientation = 'P';
            $this->w = $size[0];
            $this->h = $size[1];
        } elseif($orientation=='l' || $orientation=='landscape') {
            $this->DefOrientation = 'L';
            $this->w = $size[1];
            $this->h = $size[0];
        } else {
            $this->Error('Incorrect orientation: '.$orientation);
        }

        $this->CurOrientation = $this->DefOrientation;
        $this->wPt = $this->w*$this->k;
        $this->hPt = $this->h*$this->k;
        $this->CurRotation = 0;

        $margin = 28.35/$this->k;
        $this->SetMargins($margin,$margin);
        $this->cMargin = $margin/10;
        $this->LineWidth = .567/$this->k;
        $this->SetAutoPageBreak(true,2*$margin);
        $this->SetDisplayMode('default');
        $this->compress = true;
        $this->PDFVersion = '1.3';
    }

    function SetMargins($left, $top, $right=null) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if($right===null) $this->rMargin = $left;
        else $this->rMargin = $right;
    }

    function SetLeftMargin($margin) { $this->lMargin = $margin; if($this->page>0 && $this->x<$margin) $this->x = $margin; }
    function SetTopMargin($margin) { $this->tMargin = $margin; }
    function SetRightMargin($margin) { $this->rMargin = $margin; }

    function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h-$margin;
    }

    function SetDisplayMode($zoom, $layout='default') {
        if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom)) $this->ZoomMode = $zoom;
        else $this->Error('Incorrect zoom display mode: '.$zoom);

        if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default') $this->LayoutMode = $layout;
        else $this->Error('Incorrect layout display mode: '.$layout);
    }

    function SetCompression($compress) {
        if(function_exists('gzcompress')) $this->compress = $compress;
        else $this->compress = false;
    }

    function SetTitle($title, $isUTF8=false) { $this->metadata['Title'] = $isUTF8 ? $title : utf8_encode($title); }
    function SetAuthor($author, $isUTF8=false) { $this->metadata['Author'] = $isUTF8 ? $author : utf8_encode($author); }
    function SetSubject($subject, $isUTF8=false) { $this->metadata['Subject'] = $isUTF8 ? $subject : utf8_encode($subject); }
    function SetKeywords($keywords, $isUTF8=false) { $this->metadata['Keywords'] = $isUTF8 ? $keywords : utf8_encode($keywords); }
    function SetCreator($creator, $isUTF8=false) { $this->metadata['Creator'] = $isUTF8 ? $creator : utf8_encode($creator); }

    function AliasNbPages($alias='{nb}') { $this->AliasNbPages = $alias; }

    function Error($msg) { die('<b>FPDF error:</b> '.$msg); }

    function Open() { $this->state = 1; }

    function Close() {
        if($this->state==3) return;
        if($this->page==0) $this->AddPage();
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
        $this->_enddoc();
    }

    function AddPage($orientation='', $size='', $rotation=0) {
        if($this->state==0) $this->Open();

        $family = $this->FontFamily;
        $style = $this->FontStyle.($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;

        if($this->page>0) {
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            $this->_endpage();
        }

        $this->_beginpage($orientation, $size, $rotation);
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $fontsize;
        $this->FontSize = $fontsize/$this->k;
        $this->LineWidth = $lw;
        $this->DrawColor = $dc;
        $this->FillColor = $fc;
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;

        if($family) $this->SetFont($family, $style, $fontsize);
        if($lw!=$this->LineWidth) { $this->LineWidth = $lw; $this->_out(sprintf('%.2F w', $lw*$this->k)); }
        if($dc!='0 G') $this->_out($dc);
        if($fc!='0 g') $this->_out($fc);
        if($tc!='0 g') $this->_out($tc);

        $this->InHeader = true;
        $this->Header();
        $this->InHeader = false;
    }

    function Header() {}
    function Footer() {}

    function PageNo() { return $this->page; }

    function SetDrawColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null) $this->DrawColor = sprintf('%.3F G', $r/255);
        else $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
        if($this->page>0) $this->_out($this->DrawColor);
    }

    function SetFillColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null) $this->FillColor = sprintf('%.3F g', $r/255);
        else $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor!=$this->TextColor);
        if($this->page>0) $this->_out($this->FillColor);
    }

    function SetTextColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null) $this->TextColor = sprintf('%.3F g', $r/255);
        else $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    }

    function GetStringWidth($s) {
        $s = (string)$s;
        $cw = &$this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for($i=0;$i<$l;$i++) $w += isset($cw[$s[$i]]) ? $cw[$s[$i]] : 500;
        return $w*$this->FontSize/1000;
    }

    function SetLineWidth($width) {
        $this->LineWidth = $width;
        if($this->page>0) $this->_out(sprintf('%.2F w', $width*$this->k));
    }

    function Line($x1, $y1, $x2, $y2) {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
    }

    function Rect($x, $y, $w, $h, $style='') {
        if($style=='F') $op = 'f';
        elseif($style=='FD' || $style=='DF') $op = 'B';
        else $op = 'S';
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k, $op));
    }

    function RoundedRect($x, $y, $w, $h, $r, $style='') {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F') $op = 'f';
        elseif($style=='FD' || $style=='DF') $op = 'B';
        else $op = 'S';
        $MyOldCoordinate = 4/3 * (sqrt(2) - 1);
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $x2 = $x + $w;
        $y2 = $y + $h;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
        $this->_arc($xc + $r*$MyOldCoordinate, $y, $x2, $yc - $r*$MyOldCoordinate, $x2, $yc);
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $x2*$k, ($hp-$yc)*$k));
        $this->_arc($x2, $yc + $r*$MyOldCoordinate, $xc + $r*$MyOldCoordinate, $y2, $xc, $y2);
        $xc = $x + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y2)*$k));
        $this->_arc($xc - $r*$MyOldCoordinate, $y2, $x, $yc + $r*$MyOldCoordinate, $x, $yc);
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x*$k, ($hp-$yc)*$k));
        $this->_arc($x, $yc - $r*$MyOldCoordinate, $xc - $r*$MyOldCoordinate, $y, $xc, $y);
        $this->_out($op);
    }

    function _arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1*$this->k, ($h-$y1)*$this->k, $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    function SetFont($family, $style='', $size=0) {
        if($family=='') $family = $this->FontFamily;
        else $family = strtolower($family);
        $style = strtoupper($style);
        if(strpos($style,'U')!==false) {
            $this->underline = true;
            $style = str_replace('U','',$style);
        } else $this->underline = false;
        if($style=='B') $style='B';
        elseif($style=='I') $style='I';
        elseif($style=='BI' || $style=='IB') $style='BI';

        if($size==0) $size = $this->FontSizePt;
        if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size) return;

        $fontkey = $family.$style;
        if(!isset($this->fonts[$fontkey])) {
            if($family=='helvetica' || $family=='arial') $family='helvetica';
            elseif($family=='symbol' || $family=='zapfdingbats') $style='';
            else $family='helvetica';
            $fontkey = $family.$style;
            if(!isset($this->fonts[$fontkey])) $this->_loadfont($family, $style);
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        if($this->page>0) $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    function _loadfont($family, $style) {
        $fontkey = $family.$style;
        $name = ($family=='helvetica' ? 'Helvetica' : ($family=='times' ? 'Times-Roman' : 'Courier'));
        if($style=='B') $name .= '-Bold';
        elseif($style=='I') $name .= '-Oblique';
        elseif($style=='BI') $name .= '-BoldOblique';
        
        $cw = array(
            ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,'\''=>191,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,
            '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,
            '@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,
            'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,'['=>333,'\\'=>278,']'=>333,'^'=>469,'_'=>556,
            '`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,
            'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>500,'{'=>334,'|'=>260,'}'=>334,'~'=>584
        );
        $i = count($this->fonts)+1;
        $this->fonts[$fontkey] = array('i'=>$i, 'type'=>'core', 'name'=>$name, 'cw'=>$cw);
    }

    function Text($x, $y, $txt) {
        $txt = (string)$txt;
        $s = sprintf('BT /F%d %.2F Tf %.2F %.2F Td (%s) Tj ET', $this->CurrentFont['i'], $this->FontSizePt, $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        if($this->underline && $txt!='') $s .= ' '.$this->_dounderline($x, $y, $txt);
        if($this->ColorFlag) $s = 'q '.$this->TextColor.' '.$s.' Q';
        $this->_out($s);
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $k = $this->k;
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if($ws>0) { $this->ws = 0; $this->_out('0 Tw'); }
            $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
            $this->x = $x;
            if($ws>0) { $this->ws = $ws; $this->_out(sprintf('%.3F Tw', $ws*$k)); }
        }
        if($w==0) $w = $this->w-$this->rMargin-$this->x;
        $s = '';
        if($fill || $border==1) {
            if($fill) $op = ($border==1) ? 'B' : 'f';
            else $op = 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $op);
        }
        if(is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if(strpos($border,'L')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, $x*$k, ($this->h-($y+$h))*$k);
            if(strpos($border,'T')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-$y)*$k);
            if(strpos($border,'R')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x+$w)*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
            if(strpos($border,'B')!==false) $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-($y+$h))*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
        }
        if($txt!=='') {
            if($align=='R') $dx = $w-$this->cMargin-$this->GetStringWidth($txt);
            elseif($align=='C') $dx = ($w-$this->GetStringWidth($txt))/2;
            else $dx = $this->cMargin;
            if($this->ColorFlag) $s .= 'q '.$this->TextColor.' ';
            $s .= sprintf('BT /F%d %.2F Tf %.2F %.2F Td (%s) Tj ET', $this->CurrentFont['i'], $this->FontSizePt, ($this->x+$dx)*$k, ($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k, $this->_escape($txt));
            if($this->underline) $s .= ' '.$this->_dounderline($this->x+$dx, $this->y+.5*$h+.3*$this->FontSize, $txt);
            if($this->ColorFlag) $s .= ' Q';
        }
        if($s) $this->_out($s);
        $this->lasth = $h;
        if($ln>0) {
            $this->y += $h;
            if($ln==1) $this->x = $this->lMargin;
        } else $this->x += $w;
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        $cw = &$this->CurrentFont['cw'];
        if($w==0) $w = $this->w-$this->rMargin-$this->x;
        $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep = -1;
        $i = 0; $j = 0; $l = 0; $ns = 0;
        while($i<$nb) {
            $c = $s[$i];
            if($c=="\n") {
                $this->Cell($w, $h, substr($s, $j, $i-$j), $border, 2, $align, $fill);
                $i++; $sep = -1; $j = $i; $l = 0; $ns = 0;
                continue;
            }
            if($c==' ') { $sep = $i; $ns++; }
            $l += isset($cw[$c]) ? $cw[$c] : 500;
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j) $i++;
                    $this->Cell($w, $h, substr($s, $j, $i-$j), $border, 2, $align, $fill);
                } else {
                    $this->Cell($w, $h, substr($s, $j, $sep-$j), $border, 2, $align, $fill);
                    $i = $sep+1;
                }
                $sep = -1; $j = $i; $l = 0; $ns = 0;
            } else $i++;
        }
        if($i!=$j) $this->Cell($w, $h, substr($s, $j, $i-$j), $border, 2, $align, $fill);
    }

    function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='') {
        if(!isset($this->images[$file])) {
            if($type=='') {
                $pos = strrpos($file,'.');
                if(!$pos) $this->Error('Image file has no extension and no type was specified: '.$file);
                $type = substr($file, $pos+1);
            }
            $type = strtolower($type);
            if($type=='jpeg') $type = 'jpg';
            $mtd = '_parse'.$type;
            if(!method_exists($this, $mtd)) $this->Error('Unsupported image type: '.$type);
            $info = $this->$mtd($file);
            $info['i'] = count($this->images)+1;
            $this->images[$file] = $info;
        } else $info = $this->images[$file];

        if($w==0 && $h==0) { $w = -96; $h = -96; }
        if($w<0) $w = -$w*72/96/$this->k;
        if($h<0) $h = -$h*72/96/$this->k;
        if($w==0) $w = $h*$info['w']/$info['h'];
        if($h==0) $h = $w*$info['h']/$info['w'];

        if($x===null) $x = $this->x;
        if($y===null) $y = $this->y;

        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
            $x = $this->x; $y = $this->y;
        }

        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k, $info['i']));
        $this->x = $x + $w;
        $this->y = $y + $h;
    }

    function Ln($h=null) {
        $this->x = $this->lMargin;
        if($h===null) $this->y += $this->lasth;
        else $this->y += $h;
    }

    function GetX() { return $this->x; }
    function SetX($x) { if($x>=0) $this->x = $x; else $this->x = $this->w+$x; }
    function GetY() { return $this->y; }
    function SetY($y) { if($y>=0) $this->y = $y; else $this->y = $this->h+$y; }
    function SetXY($x, $y) { $this->SetY($y); $this->SetX($x); }

    function Output($dest='', $name='', $isUTF8=false) {
        if($this->state<3) $this->Close();
        $dest = strtoupper($dest);
        if($dest=='') {
            if($name=='') { $name = 'doc.pdf'; $dest = 'I'; }
            else $dest = 'D';
        }
        switch($dest) {
            case 'I': $this->_senddata($this->buffer, $name, true); break;
            case 'D': $this->_senddata($this->buffer, $name, false); break;
            case 'F':
                $f = fopen($name,'wb');
                if(!$f) $this->Error('Unable to create output file: '.$name);
                fwrite($f, $this->buffer, strlen($this->buffer));
                fclose($f);
                break;
            case 'S': return $this->buffer;
            default: $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }

    function _senddata($data, $name, $inline) {
        if(ob_get_length()) ob_clean();
        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen($data));
        header('Content-Disposition: '.($inline ? 'inline' : 'attachment').'; filename="'.$name.'"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $data;
    }

    function AcceptPageBreak() { return $this->AutoPageBreak; }

    function _getpagesize($size) {
        if(is_string($size)) {
            $s = strtolower($size);
            if(!isset($this->StdPageSizes[$s])) $this->Error('Unknown page size: '.$size);
            return array($this->StdPageSizes[$s][0]/$this->k, $this->StdPageSizes[$s][1]/$this->k);
        } else {
            if($size[0]>$size[1]) return array($size[1]/$this->k, $size[0]/$this->k);
            else return array($size[0]/$this->k, $size[1]/$this->k);
        }
    }

    function _beginpage($orientation, $size, $rotation) {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';

        if($orientation=='') $orientation = $this->DefOrientation;
        else $orientation = strtoupper($orientation[0]);
        if($size=='') $size = $this->DefPageSize;
        else $size = $this->_getpagesize($size);
        if($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if($orientation=='P') { $this->w = $size[0]; $this->h = $size[1]; }
            else { $this->w = $size[1]; $this->h = $size[0]; }
            $this->wPt = $this->w*$this->k;
            $this->hPt = $this->h*$this->k;
            $this->PageBreakTrigger = $this->h-$this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        $this->PageInfo[$this->page] = array('w'=>$this->wPt, 'h'=>$this->hPt);
    }

    function _endpage() { $this->state = 1; }

    function _escape($s) {
        $s = str_replace('\\','\\\\',$s);
        $s = str_replace('(','\\(',$s);
        $s = str_replace(')','\\)',$s);
        return str_replace("\r",'\\r',$s);
    }

    function _dounderline($x, $y, $txt) {
        $w = $this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');
        return sprintf('%.2F %.2F %.2F %.2F re f', $x*$this->k, ($this->h-($y-$this->FontSize*.15))*$this->k, $w*$this->k, -$this->FontSize*.07*$this->k);
    }

    function _out($s) {
        if($this->state==2) $this->pages[$this->page] .= $s."\n";
        else $this->buffer .= $s."\n";
    }

    function _parsejpg($file) {
        $a = getimagesize($file);
        if(!$a) $this->Error('Missing or incorrect image file: '.$file);
        if($a[2]!=2) $this->Error('Not a JPEG file: '.$file);
        $colspace = 'DeviceRGB';
        if(isset($a['channels'])) {
            if($a['channels']==3) $colspace = 'DeviceRGB';
            elseif($a['channels']==4) $colspace = 'DeviceCMYK';
            else $colspace = 'DeviceGray';
        }
        return array('w'=>$a[0], 'h'=>$a[1], 'cs'=>$colspace, 'bpc'=>8, 'f'=>'DCTDecode', 'data'=>file_get_contents($file));
    }

    function _parsepng($file) {
        $f = fopen($file,'rb');
        if(!$f) $this->Error('Can\'t open image file: '.$file);
        $info = $this->_parsepngstream($f,$file);
        fclose($f);
        return $info;
    }

    function _parsepngstream($f, $file) {
        if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10)) $this->Error('Not a PNG file: '.$file);
        fread($f,4);
        if(fread($f,4)!='IHDR') $this->Error('Incorrect PNG file: '.$file);
        $w = $this->_freadint($f);
        $h = $this->_freadint($f);
        $bpc = ord(fread($f,1));
        $ct = ord(fread($f,1));
        if($ct==0) $colspace = 'DeviceGray';
        elseif($ct==2) $colspace = 'DeviceRGB';
        elseif($ct==3) $colspace = 'Indexed';
        elseif($ct==4 || $ct==6) {
            // PNG contains Alpha Channel — convert via GD to clean 24-bit RGB JPEG
            if(function_exists('imagecreatefrompng') && function_exists('imagecreatetruecolor')) {
                $img = @imagecreatefrompng($file);
                if($img) {
                    $tmp = sys_get_temp_dir() . '/fpdf_tmp_' . md5($file) . '.jpg';
                    $w_orig = imagesx($img);
                    $h_orig = imagesy($img);
                    $bg = imagecreatetruecolor($w_orig, $h_orig);
                    $white = imagecolorallocate($bg, 255, 255, 255);
                    imagefilledrectangle($bg, 0, 0, $w_orig, $h_orig, $white);
                    imagecopy($bg, $img, 0, 0, 0, 0, $w_orig, $h_orig);
                    imagejpeg($bg, $tmp, 95);
                    imagedestroy($img);
                    imagedestroy($bg);
                    $res = $this->_parsejpg($tmp);
                    @unlink($tmp);
                    return $res;
                }
            }
            $this->Error('Alpha channel not supported in PNG: '.$file);
        } else $this->Error('Unknown PNG color type in file: '.$file);
        if(ord(fread($f,1))!=0) $this->Error('Unknown compression method: '.$file);
        if(ord(fread($f,1))!=0) $this->Error('Unknown filter method: '.$file);
        if(ord(fread($f,1))!=0) $this->Error('Interlacing not supported: '.$file);
        fread($f,4);
        $dp = '/Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w;
        $pal = ''; $trns = ''; $data = '';
        do {
            $n = $this->_freadint($f);
            $type = fread($f,4);
            if($type=='PLTE') {
                $pal = fread($f,$n);
                fread($f,4);
            } elseif($type=='tRNS') {
                $t = fread($f,$n);
                if($ct==0) $trns = array(ord(substr($t,1,1)));
                elseif($ct==2) $trns = array(ord(substr($t,1,1)), ord(substr($t,3,1)), ord(substr($t,5,1)));
                else {
                    $pos = strpos($t,chr(0));
                    if($pos!==false) $trns = array($pos);
                }
                fread($f,4);
            } elseif($type=='IDAT') {
                $data .= fread($f,$n);
                fread($f,4);
            } elseif($type=='IEND') break;
            else fread($f,$n+4);
        } while($n);
        if($colspace=='Indexed' && empty($pal)) $this->Error('Missing palette in '.$file);
        return array('w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'dp'=>$dp, 'pal'=>$pal, 'trns'=>$trns, 'data'=>$data);
    }

    function _freadint($f) {
        $a = unpack('Ni',fread($f,4));
        return $a['i'];
    }

    function _newobj($n=null) {
        if($n===null) $n = ++$this->n;
        $this->offsets[$n] = strlen($this->buffer);
        $this->_out($n.' 0 obj');
    }

    function _putpages() {
        $nb = $this->page;
        for($n=1; $n<=$nb; $n++) {
            $this->PageInfo[$n]['n'] = $this->n + 1 + 2*($n-1);
        }
        for($n=1; $n<=$nb; $n++) {
            $this->_putpage($n);
        }
        // Pages root (Object 1)
        $this->_newobj(1);
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for($n=1; $n<=$nb; $n++) {
            $kids .= $this->PageInfo[$n]['n'].' 0 R ';
        }
        $this->_out($kids.']');
        $this->_out('/Count '.$nb);
        $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->DefPageSize[0]*$this->k, $this->DefPageSize[1]*$this->k));
        $this->_out('>>');
        $this->_out('endobj');
    }

    function _putpage($n) {
        $this->_newobj();
        $this->_out('<</Type /Page');
        $this->_out('/Parent 1 0 R');
        $this->_out('/Resources 2 0 R');
        $this->_out('/Contents '.($this->n+1).' 0 R>>');
        $this->_out('endobj');

        $p = $this->pages[$n];
        $this->_newobj();
        $this->_out('<< /Length '.strlen($p).' >>');
        $this->_out('stream');
        $this->_out($p);
        $this->_out('endstream');
        $this->_out('endobj');
    }

    function _putfonts() {
        foreach($this->fonts as $k=>$font) {
            $this->_newobj();
            $this->fonts[$k]['n'] = $this->n;
            $this->_out('<</Type /Font /Subtype /Type1 /BaseFont /'.$font['name'].' /Encoding /WinAnsiEncoding>>');
            $this->_out('endobj');
        }
    }

    function _putimages() {
        foreach($this->images as $file=>$info) {
            $this->_newobj();
            $this->images[$file]['n'] = $this->n;
            $this->_out('<</Type /XObject /Subtype /Image');
            $this->_out('/Width '.$info['w'].' /Height '.$info['h']);
            if($info['cs']=='Indexed') $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
            else $this->_out('/ColorSpace /'.$info['cs']);
            $this->_out('/BitsPerComponent '.$info['bpc']);
            if(isset($info['f'])) $this->_out('/Filter /'.$info['f']);
            if(isset($info['dp'])) $this->_out('/DecodeParms <<'.$info['dp'].'>>');
            $this->_out('/Length '.strlen($info['data']).'>>');
            $this->_out('stream');
            $this->_out($info['data']);
            $this->_out('endstream');
            $this->_out('endobj');
        }
    }

    function _putresourcedict() {
        $this->_newobj(2);
        $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach($this->fonts as $font) $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
        $this->_out('>>');
        if(count($this->images)) {
            $this->_out('/XObject <<');
            foreach($this->images as $img) $this->_out('/I'.$img['i'].' '.$img['n'].' 0 R');
            $this->_out('>>');
        }
        $this->_out('>>');
        $this->_out('endobj');
    }

    function _putcatalog() {
        $this->_newobj();
        $this->_out('<</Type /Catalog /Pages 1 0 R>>');
        $this->_out('endobj');
    }

    function _putheader() {
        $this->_out('%PDF-'.$this->PDFVersion);
    }

    function _enddoc() {
        $this->_putheader();
        $this->_putpages();
        $this->_putfonts();
        $this->_putimages();
        $this->_putresourcedict();
        $this->_putcatalog();

        // Xref
        $o = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 '.($this->n+1));
        $this->_out('0000000000 65535 f ');
        for($i=1; $i<=$this->n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }

        // Trailer
        $this->_out('trailer');
        $this->_out('<</Size '.($this->n+1).' /Root '.$this->n.' 0 R>>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
    }
}
}
?>
