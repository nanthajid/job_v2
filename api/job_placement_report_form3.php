<?php
/**
 * API: Generate PDF monthly report - คลินิกอาชีพ-แบบ3
 * รายงาน: ทะเบียนผู้มาใช้บริการ กิจกรรมคลินิกอาชีพ แนะแนวทาง สร้างโอกาสการมีงานทํา
 * GET /api/job_placement_report_form3.php?from=YYYY-MM-DD&to=YYYY-MM-DD
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

// ช่วงข้อมูลอ้างอิง "วันที่บันทึกข้อมูล" = job_placement.CreateDate
// CreateDate เป็น datetime จึงเทียบขอบบนแบบ < วันถัดไป เพื่อให้รวมข้อมูลที่บันทึกในวันสุดท้ายทั้งวัน
if ($validDate($dateFrom)) {
    $conds[] = 'jp.CreateDate >= :from';
    $params[':from'] = $dateFrom . ' 00:00:00';
} else {
    $dateFrom = '';
}
if ($validDate($dateTo)) {
    $conds[] = 'jp.CreateDate < :to';
    $params[':to'] = (new DateTime($dateTo))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
} else {
    $dateTo = '';
}

$where = $conds ? ' AND ' . implode(' AND ', $conds) : '';

// Query: Pull data from job_placement for the "คลินิกอาชีพ-แบบ3" report
// Includes: EmpID, EmpName, Address, Phone, EduName, Position, CompanyName, StartDate
// Additional columns for service tracking (การให้บริการของเจ้าหน้าที่) will be placeholders
$sql = "SELECT jp.JPNo, jp.EmpID, jp.EmpName, jp.Address, jp.Phone,
               jp.Age, jp.Gender, edu.EqName AS EduName,
               jp.Position, jp.CompanyName,
               jp.StartDate, jp.ServiceType, jp.StaffService,
               jp.StID, st.StName
        FROM job_placement jp
        LEFT JOIN educational_qualification edu ON edu.EqNo = jp.EduNo
        LEFT JOIN staff st ON st.StID = jp.StID
        WHERE 1=1 $where
        ORDER BY jp.EmpID ASC, jp.JPNo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thai date helpers
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

// Determine report month label
if ($dateFrom) {
    $monthLabel = $thaiMonthYear($dateFrom);
} elseif ($dateTo) {
    $monthLabel = $thaiMonthYear($dateTo);
} else {
    $monthLabel = $thaiMonthYear(date('Y-m-d'));
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
    'margin_left'     => 5,
    'margin_right'    => 5,
    'margin_top'      => 10,
    'margin_bottom'   => 10,
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

$mpdf->SetTitle('ทะเบียนผู้มาใช้บริการ - คลินิกอาชีพ-แบบ3 - ' . $monthLabel);

// ========== Build HTML ==========
// Column layout for "คลินิกอาชีพ-แบบ3"
// ลำดับ:3% | ชื่อ-สกุล/เลขบัตร/ที่อยู่/โทรศัพท์:18% | เพศ:5%(ชาย2.5/หญิง2.5) | การศึกษาสูงสุด:7% |
// ความต้องการด้านอาชีพ:14% | การให้บริการของเจ้าหน้าที่:18%(แนะแนว4.5/ส่งตัว4.5/ส่งฝึก4.5/จัดฝึก4.5) |
// ผู้ใช้บริการใหม่:5% | ผู้ใช้บริการเดิม:5% | ผู้ประกันตนว่างงาน:7% | สมัครงาน:5% | ทดสอบฯ:5%
// Footer: คลินิกอาชีพ-แบบ3 (ส่งให้กองส่งเสริมฯ)

$cellPadding = 'padding: 3px 2px;';
$headerFontSize = '8px;';
$bodyFontSize = '10px;';
$tinyFontSize = '7px;';

$html = '<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: sarabun, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
  .form-title { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 1px; }
  .form-subtitle { text-align: center; font-size: 11px; margin-bottom: 1px; }
  .form-office { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 1px; }
  .form-period { text-align: center; font-size: 12px; font-weight: bold; margin-bottom: 6px; }
  .form-code { text-align: right; font-size: 9px; font-style: italic; margin-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 8px; table-layout: fixed; }
  thead th { border: 1px solid #000; padding: 2px 1px; text-align: center; font-weight: bold;
    font-size: 7px; background: #fff; vertical-align: middle; word-wrap: break-word; }
  tbody td { border: 1px solid #000; padding: 3px 2px; vertical-align: top; font-size: 8px; word-wrap: break-word; }
  .text-center { text-align: center; }
  .text-right { text-align: right; }
  .v-middle { vertical-align: middle !important; }
  .bold { font-weight: bold; }
  .col-seq { width: 2.5%; }
  .col-person { width: 14%; }
  .col-age { width: 2.8%; }
  .col-gender-m { width: 2%; }
  .col-gender-f { width: 2%; }
  .col-edu { width: 5.5%; }
  .col-addr { width: 10%; }
  .col-career { width: 10%; }
  .col-svc-guide { width: 3.8%; }
  .col-svc-refer { width: 3.8%; }
  .col-svc-train { width: 3.8%; }
  .col-svc-workshop { width: 3.8%; }
  .col-new { width: 4%; }
  .col-old { width: 4%; }
  .col-insured { width: 5.5%; }
  .col-apply { width: 4%; }
  .col-test { width: 4%; }
  .check-col { text-align: center; font-size: 9px; }
</style>
</head>
<body>';

// ===== Header =====
$html .= '<div class="form-title">ทะเบียนผู้มาใช้บริการ</div>';
$html .= '<div class="form-subtitle">กิจกรรมคลินิกอาชีพ แนะแนวทาง สร้างโอกาสการมีงานทํา</div>';
$html .= '<div class="form-office">สํานักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</div>';
$html .= '<div class="form-period">ประจําเดือน ' . $monthLabel . '</div>';
$html .= '<div class="form-code">คลินิกอาชีพ-แบบ3 (ส่งให้กองส่งเสริมฯ)</div>';

// ===== Table =====
$html .= '<table>
<thead>
  <tr>
    <th rowspan="2" class="col-seq">ลําดับ</th>
    <th rowspan="2" class="col-person">ชื่อ-สกุล<br>เลขบัตรประจําตัว<br>ประชาชน</th>
    <th rowspan="2" class="col-age">อายุ</th>
    <th colspan="2" style="width:4%;">เพศ</th>
    <th rowspan="2" class="col-edu">การศึกษา<br>สูงสุด</th>
    <th rowspan="2" class="col-addr">ที่อยู่ปัจจุบัน</th>
    <th rowspan="2" class="col-career">ความต้องการ<br>ด้านอาชีพ</th>
    <th colspan="4" style="width:15.2%;">การให้บริการของเจ้าหน้าที่</th>
    <th rowspan="2" class="col-new">ผู้ใช้<br>บริการใหม่</th>
    <th rowspan="2" class="col-old">ผู้ใช้<br>บริการเดิม</th>
    <th rowspan="2" class="col-insured">ผู้ประกันตน<br>กรณีว่างงาน</th>
    <th rowspan="2" class="col-apply">สมัคร<br>งาน</th>
    <th rowspan="2" class="col-test">ทดสอบฯ</th>
  </tr>
  <tr>
    <th class="col-gender-m">ชาย</th>
    <th class="col-gender-f">หญิง</th>
    <th class="col-svc-guide">แนะแนว/<br>ให้คําปรึกษา</th>
    <th class="col-svc-refer">ส่งตัวพบ<br>นายจ้าง</th>
    <th class="col-svc-train">ส่งฝึก<br>ทักษะ</th>
    <th class="col-svc-workshop">จัดฝึก<br>อบรม</th>
  </tr>
</thead>
<tbody>';

if (empty($rows)) {
    $html .= '<tr><td colspan="17" class="text-center" style="padding:20px;">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>';
} else {
    $idx = 0;
    foreach ($rows as $row) {
        $idx++;

        // Personal info block
        $personal  = '<span class="bold">' . htmlspecialchars($row['EmpName'] ?: '—') . '</span><br>';
        $personal .= '<span style="font-size:7.5px;">' . htmlspecialchars($row['EmpID']) . '</span>';

        $gender  = $row['Gender'] ?? '';
        $age     = $row['Age'] ? (int)$row['Age'] : '';
        $edu     = htmlspecialchars($row['EduName'] ?? '—');
        $addr    = htmlspecialchars($row['Address'] ?? '');
        $career  = htmlspecialchars($row['Position'] ?: ($row['CompanyName'] ?: '—'));

        // Parse StaffService (comma-separated codes) into array
        $staffSvcArr = [];
        if (!empty($row['StaffService'])) {
            $staffSvcArr = array_map('trim', explode(',', $row['StaffService']));
        }

        // ServiceType
        $svcType = $row['ServiceType'] ?? '';

        // Helper: show ✔ or empty
        $check = function ($has) { return $has ? '<span style="font-family: DejaVu Sans, sans-serif; font-size: 11px;">✔</span>' : ''; };

        // Column mapping (17 columns total):
        // 1:ลำดับ, 2:ชื่อ-สกุล/เลขบัตร, 3:อายุ, 4:ชาย, 5:หญิง, 6:การศึกษาสูงสุด, 7:ที่อยู่ปัจจุบัน,
        // 8:ความต้องการด้านอาชีพ, 9:แนะแนว, 10:ส่งตัว, 11:ส่งฝึก, 12:จัดฝึก,
        // 13:ผู้ใช้ใหม่, 14:ผู้ใช้เดิม, 15:ผู้ประกันตน, 16:สมัครงาน, 17:ทดสอบ

        $html .= '<tr>
          <td class="text-center v-middle">' . $idx . '</td>
          <td>' . $personal . '</td>
          <td class="text-center v-middle">' . $age . '</td>
          <td class="check-col v-middle">' . $check($gender === 'Male') . '</td>
          <td class="check-col v-middle">' . $check($gender === 'Female') . '</td>
          <td class="text-center v-middle">' . $edu . '</td>
          <td class="v-middle" style="font-size:7px;">' . $addr . '</td>
          <td class="v-middle" style="font-size:8px;">' . $career . '</td>
          <td class="check-col v-middle">' . $check(in_array('Guide', $staffSvcArr)) . '</td>
          <td class="check-col v-middle">' . $check(in_array('Refer', $staffSvcArr)) . '</td>
          <td class="check-col v-middle">' . $check(in_array('Train', $staffSvcArr)) . '</td>
          <td class="check-col v-middle">' . $check(in_array('Workshop', $staffSvcArr)) . '</td>
          <td class="check-col v-middle">' . $check($svcType === 'ServiceNew') . '</td>
          <td class="check-col v-middle">' . $check($svcType === 'ServiceOld') . '</td>
          <td class="check-col v-middle">' . $check($svcType === 'Insured') . '</td>
          <td class="check-col v-middle">' . $check(in_array('ApplyWork', $staffSvcArr)) . '</td>
          <td class="check-col v-middle">' . $check(in_array('Test', $staffSvcArr)) . '</td>
        </tr>';
    }
}

$html .= '</tbody></table>';

$html .= '</body></html>';

$mpdf->WriteHTML($html);

// Output PDF
$filename = 'Report_ClinicCareer_Form3_' . ($dateFrom ?: 'all') . '_' . ($dateTo ?: 'all') . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);