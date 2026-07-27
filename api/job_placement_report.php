<?php
/**
 * API: Generate PDF monthly report for job placements
 * รายงาน: ทะเบียนผู้ใช้บริการที่ได้รับการบรรจุงาน / ประกอบอาชีพอิสระ (คลินิกอาชีพฯ-แบบ4)
 * GET /api/job_placement_report.php?from=YYYY-MM-DD&to=YYYY-MM-DD
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$user = currentUser();
$pdo  = getDB();

// Parse date parameters
$dateFrom = isset($_GET['from']) ? trim($_GET['from']) : '';
$dateTo   = isset($_GET['to'])   ? trim($_GET['to'])   : '';

$validDate = function ($s) {
    if ($s === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
};

$params = [];
$conds  = [];

if ($validDate($dateFrom)) {
    $conds[] = 'jp.StartDate >= :from';
    $params[':from'] = $dateFrom;
} else {
    $dateFrom = '';
}
if ($validDate($dateTo)) {
    $conds[] = 'jp.StartDate <= :to';
    $params[':to'] = $dateTo;
} else {
    $dateTo = '';
}

$where = $conds ? ' AND ' . implode(' AND ', $conds) : '';

$sql = "SELECT jp.JPNo, jp.EmpID, jp.EmpName, jp.Age, edu.EqName AS EduName,
               jp.Address, jp.Phone,
               jp.CompanyName, jp.CompanyAddress, jp.Position,
               jp.IncomeDay, jp.IncomeMonth, jp.StartDate,
               st.StName
        FROM job_placement jp
        LEFT JOIN educational_qualification edu ON edu.EqNo = jp.EduNo
        LEFT JOIN staff st ON st.StID = jp.StID
        WHERE 1=1 $where
        ORDER BY jp.StartDate ASC, jp.JPNo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thai date helpers
$thaiDate = function ($iso) {
    if (!$iso || $iso === '0000-00-00') return '';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    if (!$d) return $iso;
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

$thaiMonthYear = function ($iso) {
    // Return e.g. "มิถุนายน 2569"
    if (!$iso) {
        // Use current date
        $d = new DateTime();
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $iso);
        if (!$d) $d = new DateTime();
    }
    $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    return $thMonthsFull[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

// Determine report month label
if ($dateFrom && $dateTo) {
    $monthLabel = $thaiMonthYear($dateFrom);
} elseif ($dateFrom) {
    $monthLabel = $thaiMonthYear($dateFrom);
} elseif ($dateTo) {
    $monthLabel = $thaiMonthYear($dateTo);
} else {
    $monthLabel = $thaiMonthYear(date('Y-m-d'));
}

// Calculate totals
$totalIncomeDay   = 0;
$totalIncomeMonth = 0;
$totalCount       = count($rows);
foreach ($rows as $r) {
    $totalIncomeDay   += (float)$r['IncomeDay'];
    $totalIncomeMonth += (float)$r['IncomeMonth'];
}

// ========== Configure mPDF for Thai language ==========
$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode'            => '+aCJK',
    'format'          => 'A4-L',            // Landscape
    'default_font_size' => 12,
    'default_font'    => 'sarabun',
    'margin_left'     => 8,
    'margin_right'    => 8,
    'margin_top'      => 12,
    'margin_bottom'   => 12,
    'fontDir'         => array_merge($fontDirs, [
        __DIR__ . '/../assets/fonts',
    ]),
    'fontdata'        => $fontData + [
        'sarabun' => [
            'R' => 'THSarabunNew.ttf',
            'B' => 'THSarabunNew.ttf',
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
    ],
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
]);

$mpdf->SetTitle('ทะเบียนผู้ใช้บริการที่ได้รับการบรรจุงาน - ' . $monthLabel);

// ========== Build HTML matching report_form.pdf ==========
// Column widths in % for A4-Landscape (total printable ~277mm)
// ลำดับ:4% | ชื่อ-สกุล+ที่อยู่:23% | อายุ:4% | วุฒิ:8% | สถานประกอบการ:24% | ตำแหน่ง:10% | ต่อวัน:8% | ต่อเดือน:8% | เริ่มงาน:11%
$html = '<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: sarabun, sans-serif; font-size: 14px; color: #000; margin: 0; padding: 0; }
  .form-title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 3px; }
  .form-subtitle { text-align: center; font-size: 14px; margin-bottom: 3px; }
  .form-office { text-align: center; font-size: 15px; font-weight: bold; margin-bottom: 3px; }
  .form-period { text-align: center; font-size: 15px; font-weight: bold; margin-bottom: 10px; }
  .form-footer-note { text-align: left; font-size: 12px; margin-top: 12px; }
  .form-footer-right { text-align: right; font-size: 12px; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; table-layout: fixed; }
  thead th { border: 1px solid #000; padding: 5px 4px; text-align: center; font-weight: bold;
    font-size: 11px; background: #fff; vertical-align: middle; word-wrap: break-word; }
  tbody td { border: 1px solid #000; padding: 5px 4px; vertical-align: top; font-size: 12px; word-wrap: break-word; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .v-middle { vertical-align: middle !important; }
  .col-seq { width: 4%; }
  .col-person { width: 23%; }
  .col-age { width: 4%; }
  .col-edu { width: 8%; }
  .col-company { width: 24%; }
  .col-position { width: 10%; }
  .col-income-day { width: 8%; }
  .col-income-month { width: 8%; }
  .col-startdate { width: 11%; }
  .personal-name { font-weight: bold; }
  .personal-id { font-family: Courier, monospace; font-size: 11px; }
  .personal-addr { font-size: 11px; }
  .personal-phone { font-size: 11px; }
  .company-name { font-weight: bold; }
  .company-addr { font-size: 10.5px; }
  .summary-row td { border: 1px solid #000; padding: 6px 4px; font-weight: bold; font-size: 12px; }
</style>
</head>
<body>';

// ===== Header =====
$html .= '<div class="form-title">ทะเบียนผู้ใช้บริการที่ได้รับการบรรจุงาน / ประกอบอาชีพอิสระ</div>';
$html .= '<div class="form-subtitle">กิจกรรมคลินิกอาชีพ แนะแนวทาง สร้างโอกาสการมีงานทํา</div>';
$html .= '<div class="form-office">สํานักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</div>';
$html .= '<div class="form-period">ประจําเดือน ' . $monthLabel . '</div>';

// ===== Table with fixed column widths (table-layout:fixed) =====
$html .= '<table>
<thead>
  <tr>
    <th rowspan="2" class="col-seq">ลําดับ</th>
    <th rowspan="2" class="col-person">ชื่อ-นามสกุล<br>(เลขบัตรประจําตัวประชาชน)</th>
    <th rowspan="2" class="col-age">อายุ</th>
    <th rowspan="2" class="col-edu">วุฒิ<br>การศึกษา</th>
    <th rowspan="2" class="col-company">ชื่อที่อยู่สถานประกอบการ / ประกอบอาชีพอิสระ</th>
    <th rowspan="2" class="col-position">ตําแหน่ง</th>
    <th colspan="2" style="width:16%;">รายได้</th>
    <th rowspan="2" class="col-startdate">วันที่<br>เริ่มงาน</th>
  </tr>
  <tr>
    <th class="col-income-day">ต่อวัน</th>
    <th class="col-income-month">ต่อเดือน</th>
  </tr>
</thead>
<tbody>';

if (empty($rows)) {
    $html .= '<tr><td colspan="9" class="text-center" style="padding:20px;">ไม่พบข้อมูลการบรรจุงานในช่วงเวลาที่เลือก</td></tr>';
} else {
    $idx = 0;
    foreach ($rows as $row) {
        $idx++;

        // Format personal info block: Name + ID only (no address/phone)
        $personalInfo  = '<span class="personal-name">' . htmlspecialchars($row['EmpName'] ?: '—') . '</span><br>';
        $personalInfo .= '<span class="personal-id">' . htmlspecialchars($row['EmpID']) . '</span>';

        $age          = $row['Age'] ? $row['Age'] : '—';
        $edu          = htmlspecialchars($row['EduName'] ?? '—');
        $companyInfo  = '<span class="company-name">' . htmlspecialchars($row['CompanyName']) . '</span>';
        if ($row['CompanyAddress']) {
            $companyInfo .= '<br><span class="company-addr">' . htmlspecialchars($row['CompanyAddress']) . '</span>';
        }
        $position     = htmlspecialchars($row['Position'] ?? '—');
        $incomeDay    = $row['IncomeDay']   ? number_format((float)$row['IncomeDay'], 0) : '';
        $incomeMonth  = $row['IncomeMonth'] ? number_format((float)$row['IncomeMonth'], 0) : '';
        $startDate    = $row['StartDate'] && $row['StartDate'] !== '0000-00-00' ? $thaiDate($row['StartDate']) : '';

        $html .= '<tr>
          <td class="text-center v-middle">' . $idx . '</td>
          <td>' . $personalInfo . '</td>
          <td class="text-center v-middle">' . $age . '</td>
          <td class="text-center v-middle">' . $edu . '</td>
          <td>' . $companyInfo . '</td>
          <td class="text-center v-middle">' . $position . '</td>
          <td class="text-right v-middle">' . $incomeDay . '</td>
          <td class="text-right v-middle">' . $incomeMonth . '</td>
          <td class="text-center v-middle">' . $startDate . '</td>
        </tr>';
    }
}

$html .= '</tbody></table>';

// ===== Footer: หมายเหตุ =====
$noteMonth = $monthLabel; // e.g. "มิถุนายน 2569"
$html .= '<div style="margin-top: 12px; padding: 8px 0; font-size: 11px; text-align: left;">
  <strong>หมายเหตุ</strong> รายชื่อผู้ที่รับการบรรจุงานเป็นรายชื่อผู้มารับบริการในเดือน ' . $noteMonth . '
</div>';

$html .= '</body></html>';

$mpdf->WriteHTML($html);

// Output PDF
$filename = 'Report_JobPlacement_Form4_' . ($dateFrom ?: 'all') . '_' . ($dateTo ?: 'all') . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);