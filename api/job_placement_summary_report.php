<?php
/**
 * API: Generate PDF summary report for job placements
 * ตารางสถิติผู้มาใช้บริการกิจกรรมคลินิกอาชีพ (สรุปจำนวนผู้รับบริการ)
 * GET /api/job_placement_summary_report.php?from=YYYY-MM-DD&to=YYYY-MM-DD
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$user = currentUser();
$pdo  = getDB();

$dateFrom = isset($_GET['from']) ? trim($_GET['from']) : '';
$dateTo   = isset($_GET['to'])   ? trim($_GET['to'])   : '';

$validDate = function ($s) {
    if ($s === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
};

$params = [];
$conds  = [];

// ช่วงข้อมูลอ้างอิง "วันที่บันทึกข้อมูล" = job_placement.CreateDate
// CreateDate เป็น datetime จึงเทียบขอบบนแบบ < วันถัดไป เพื่อให้รวมข้อมูลที่บันทึกในวันสุดท้ายทั้งวัน
if ($validDate($dateFrom)) {
    $conds[] = 'PeriodDate >= :from';
    $params[':from'] = $dateFrom . ' 00:00:00';
}
if ($validDate($dateTo)) {
    $conds[] = 'PeriodDate < :to';
    $params[':to'] = (new DateTime($dateTo))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
}

$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

// Thai date helpers
$thaiDate = function ($iso) {
    if (!$iso || $iso === '0000-00-00') return '';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    if (!$d) return $iso;
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

$thaiMonthYear = function ($iso) {
    if (!$iso) {
        $d = new DateTime();
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $iso);
        if (!$d) $d = new DateTime();
    }
    $thMonthsFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    return $thMonthsFull[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

// Determine period label
if ($dateFrom && $dateTo) {
    $periodLabel = 'ประจำเดือน ' . $thaiMonthYear($dateFrom);
} elseif ($dateFrom) {
    $periodLabel = 'ตั้งแต่ ' . $thaiDate($dateFrom);
} elseif ($dateTo) {
    $periodLabel = 'ถึง ' . $thaiDate($dateTo);
} else {
    $periodLabel = 'ประจำเดือน ' . $thaiMonthYear(date('Y-m-d'));
}

// ประชากรของรายงาน = คนที่บันทึกข้อมูลบรรจุงานไว้ในตาราง job_placement เท่านั้น
// ให้ตรงกับรายชื่อที่แสดงในตารางของหน้า job_placement.php (api/job_placement_list.php ใช้แหล่งข้อมูลเดียวกัน)
$srcSql = "
    SELECT jp.Gender AS Gender, jp.Age AS Age, jp.EduNo AS EduNo, jp.CreateDate AS PeriodDate
    FROM job_placement jp
";

$stmtSrc = $pdo->prepare("SELECT Gender, Age, EduNo FROM ($srcSql) src $where");
$stmtSrc->execute($params);
$srcRows = $stmtSrc->fetchAll(PDO::FETCH_ASSOC);

$totalRows = count($srcRows);

// ========== 1) Gender Stats ==========
$genderMale = $genderFemale = $genderUnspecified = 0;
foreach ($srcRows as $r) {
    if ($r['Gender'] === 'Male') {
        $genderMale++;
    } elseif ($r['Gender'] === 'Female') {
        $genderFemale++;
    } else {
        $genderUnspecified++;
    }
}
$genderTotal = $totalRows;

// ========== 2) Age Range Stats (แยกเพศ) ==========
$ageRanges = [
    ['label' => '15-19 ปี',         'min' => 15, 'max' => 19],
    ['label' => '20-29 ปี',         'min' => 20, 'max' => 29],
    ['label' => '30-39 ปี',         'min' => 30, 'max' => 39],
    ['label' => '40-49 ปี',         'min' => 40, 'max' => 49],
    ['label' => '50-59 ปี',         'min' => 50, 'max' => 59],
    ['label' => '60 ปี ขึ้นไป',     'min' => 60, 'max' => 200],
];
foreach ($ageRanges as &$r) {
    $r['male']   = 0;
    $r['female'] = 0;
}
unset($r);

$ageUnspecMale = $ageUnspecFemale = $ageUnspecTotal = 0;

foreach ($srcRows as $row) {
    $age    = $row['Age'] !== null ? (int)$row['Age'] : null;
    $gender = $row['Gender'];

    if ($age === null) {
        $ageUnspecTotal++;
        if ($gender === 'Male')   $ageUnspecMale++;
        if ($gender === 'Female') $ageUnspecFemale++;
        continue;
    }
    foreach ($ageRanges as &$range) {
        if ($age >= $range['min'] && $age <= $range['max']) {
            if ($gender === 'Male')   $range['male']++;
            if ($gender === 'Female') $range['female']++;
            break;
        }
    }
    unset($range);
}

$ageData        = [];
$ageSummaryMale = $ageSummaryFemale = 0;
foreach ($ageRanges as $range) {
    $ageSummaryMale   += $range['male'];
    $ageSummaryFemale += $range['female'];
    $ageData[] = ['label' => $range['label'], 'male' => $range['male'], 'female' => $range['female'], 'total' => $range['male'] + $range['female']];
}
if ($ageUnspecTotal > 0) {
    $ageData[] = ['label' => 'ไม่ระบุอายุ', 'male' => $ageUnspecMale, 'female' => $ageUnspecFemale, 'total' => $ageUnspecTotal];
}
$ageSummaryMale   += $ageUnspecMale;
$ageSummaryFemale += $ageUnspecFemale;
$ageSummaryTotal   = $totalRows;

// ========== 3) Education Stats (แยกเพศ) ==========
$eduRows = $pdo->query("SELECT EqNo, EqName FROM educational_qualification ORDER BY EqNo")->fetchAll(PDO::FETCH_ASSOC);
$eduStats = [];
foreach ($eduRows as $e) {
    $eduStats[(int)$e['EqNo']] = ['label' => $e['EqName'], 'male' => 0, 'female' => 0];
}

$eduUnspecMale = $eduUnspecFemale = $eduUnspecTotal = 0;

foreach ($srcRows as $row) {
    $eduNo  = $row['EduNo'] !== null ? (int)$row['EduNo'] : null;
    $gender = $row['Gender'];

    if ($eduNo === null || !isset($eduStats[$eduNo])) {
        $eduUnspecTotal++;
        if ($gender === 'Male')   $eduUnspecMale++;
        if ($gender === 'Female') $eduUnspecFemale++;
        continue;
    }
    if ($gender === 'Male')   $eduStats[$eduNo]['male']++;
    if ($gender === 'Female') $eduStats[$eduNo]['female']++;
}

$eduData        = [];
$eduSummaryMale = $eduSummaryFemale = 0;
foreach ($eduStats as $stat) {
    $eduSummaryMale   += $stat['male'];
    $eduSummaryFemale += $stat['female'];
    $eduData[] = ['label' => $stat['label'], 'male' => $stat['male'], 'female' => $stat['female'], 'total' => $stat['male'] + $stat['female']];
}
if ($eduUnspecTotal > 0) {
    $eduData[] = ['label' => 'ไม่ระบุวุฒิการศึกษา', 'male' => $eduUnspecMale, 'female' => $eduUnspecFemale, 'total' => $eduUnspecTotal];
}
$eduSummaryMale   += $eduUnspecMale;
$eduSummaryFemale += $eduUnspecFemale;
$eduSummaryTotal   = $totalRows;

// ========== Configure mPDF ==========
$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new \Mpdf\Mpdf([
    'mode'              => '+aCJK',
    'format'            => 'A4',
    'default_font_size' => 14,
    'default_font'      => 'sarabun',
    'margin_left'       => 12,
    'margin_right'      => 12,
    'margin_top'        => 15,
    'margin_bottom'     => 15,
    'fontDir'           => array_merge($fontDirs, [
        __DIR__ . '/../assets/fonts',
    ]),
    'fontdata'          => $fontData + [
        'sarabun' => [
            'R'       => 'THSarabunNew.ttf',
            'B'       => 'THSarabunNew-Bold.ttf',
            'useOTL'  => 0xFF,
            'useKashida' => 75,
        ],
    ],
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
]);

$mpdf->SetTitle('ตารางสถิติผู้มาใช้บริการกิจกรรมคลินิกอาชีพ - ' . $periodLabel);

// ========== Build HTML ==========
$html = '<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: sarabun, sans-serif; font-size: 16px; color: #000; margin: 0; padding: 0; }
  .report-title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 2px; }
  .report-office { text-align: center; font-size: 16px; margin-bottom: 2px; }
  .report-period { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 12px; }
  .section-title { font-size: 16px; font-weight: bold; margin: 14px 0 6px 0; padding: 4px 8px; background: #E8F0FE; border-left: 4px solid #005EB8; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 10px; }
  thead th { border: 1px solid #000; padding: 5px 6px; text-align: center; font-weight: bold;
    font-size: 14px; background: #f0f0f0; vertical-align: middle; }
  tbody td { border: 1px solid #000; padding: 5px 6px; vertical-align: middle; font-size: 14px; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .col-label { font-weight: bold; color: #002D62; }
  .total-row td { background: #FFF9E6; font-weight: bold; }
  .col-w40 { width: 40%; }
  .col-w20 { width: 20%; }
  @page { margin: 12mm; }
</style>
</head>
<body>';

// Header
$html .= '<div class="report-title">ตารางสถิติผู้มาใช้บริการกิจกรรมคลินิกอาชีพ</div>';
$html .= '<div class="report-office">แนะแนวทาง สร้างโอกาสการมีงานทํา</div>';
$html .= '<div class="report-office">สํานักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</div>';
$html .= '<div class="report-period">' . htmlspecialchars($periodLabel) . '</div>';

// ===== Section 1: แยกตามเพศ =====
$html .= '<div class="section-title">แยกตามเพศ</div>';
$html .= '<table>
  <thead>
    <tr><th>เพศ</th><th class="text-center">จํานวน (คน)</th></tr>
  </thead>
  <tbody>
    <tr><td class="col-label">ชาย</td><td class="text-center">' . number_format($genderMale) . '</td></tr>
    <tr><td class="col-label">หญิง</td><td class="text-center">' . number_format($genderFemale) . '</td></tr>' .
    ($genderUnspecified > 0 ? '
    <tr><td class="col-label">ไม่ระบุเพศ</td><td class="text-center">' . number_format($genderUnspecified) . '</td></tr>' : '') . '
  </tbody>
  <tfoot>
    <tr class="total-row"><td class="text-right col-label">รวม</td><td class="text-center">' . number_format($genderTotal) . '</td></tr>
  </tfoot>
</table>';

// ===== Section 2: ช่วงอายุ (แยกเพศ) =====
$html .= '<div class="section-title">ช่วงอายุ</div>';
$html .= '<table>
  <thead>
    <tr>
      <th class="col-w40">ช่วงอายุ</th>
      <th class="text-center col-w20">ชาย (คน)</th>
      <th class="text-center col-w20">หญิง (คน)</th>
      <th class="text-center col-w20">รวม (คน)</th>
    </tr>
  </thead>
  <tbody>';
foreach ($ageData as $a) {
    $html .= '<tr>
      <td class="col-label">' . htmlspecialchars($a['label']) . '</td>
      <td class="text-center">' . number_format($a['male']) . '</td>
      <td class="text-center">' . number_format($a['female']) . '</td>
      <td class="text-center">' . number_format($a['total']) . '</td>
    </tr>';
}
$html .= '</tbody>
  <tfoot>
    <tr class="total-row">
      <td class="text-right col-label">รวม</td>
      <td class="text-center">' . number_format($ageSummaryMale) . '</td>
      <td class="text-center">' . number_format($ageSummaryFemale) . '</td>
      <td class="text-center">' . number_format($ageSummaryTotal) . '</td>
    </tr>
  </tfoot>
</table>';

// ===== Section 3: แยกตามวุฒิการศึกษา (แยกเพศ) =====
$html .= '<div class="section-title">แยกตามวุฒิการศึกษา</div>';
$html .= '<table>
  <thead>
    <tr>
      <th class="col-w40">ชื่อวุฒิการศึกษา</th>
      <th class="text-center col-w20">ชาย (คน)</th>
      <th class="text-center col-w20">หญิง (คน)</th>
      <th class="text-center col-w20">รวม (คน)</th>
    </tr>
  </thead>
  <tbody>';
foreach ($eduData as $e) {
    $html .= '<tr>
      <td class="col-label">' . htmlspecialchars($e['label']) . '</td>
      <td class="text-center">' . number_format($e['male']) . '</td>
      <td class="text-center">' . number_format($e['female']) . '</td>
      <td class="text-center">' . number_format($e['total']) . '</td>
    </tr>';
}
$html .= '</tbody>
  <tfoot>
    <tr class="total-row">
      <td class="text-right col-label">รวม</td>
      <td class="text-center">' . number_format($eduSummaryMale) . '</td>
      <td class="text-center">' . number_format($eduSummaryFemale) . '</td>
      <td class="text-center">' . number_format($eduSummaryTotal) . '</td>
    </tr>
  </tfoot>
</table>';

$html .= '</body></html>';

$mpdf->WriteHTML($html);

// Output PDF
$filename = 'Summary_Report_' . ($dateFrom ?: 'all') . '_' . ($dateTo ?: 'all') . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);