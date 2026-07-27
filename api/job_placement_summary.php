<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();

    $dateFrom = isset($_GET['from']) ? trim($_GET['from']) : '';
    $dateTo   = isset($_GET['to'])   ? trim($_GET['to'])   : '';

    $validDate = function ($s) {
        if ($s === '') return false;
        $d = DateTime::createFromFormat('Y-m-d', $s);
        return $d && $d->format('Y-m-d') === $s;
    };

    $dateParams = [];
    $dateConds  = [];

    if ($validDate($dateFrom)) {
        $dateConds[] = 'PeriodDate >= :from';
        $dateParams[':from'] = $dateFrom;
    }
    if ($validDate($dateTo)) {
        $dateConds[] = 'PeriodDate <= :to';
        $dateParams[':to'] = $dateTo;
    }

    $where = $dateConds ? 'WHERE ' . implode(' AND ', $dateConds) : '';

    // ประชากรของรายงาน = 1) คนที่บันทึกข้อมูลบรรจุงานแล้ว (job_placement) รวมกับ
    // 2) คนที่รายงานตัวว่าได้งานเดือนนี้ (selft_rep.JNo=2) แต่ยังไม่มีใครกดเพิ่มข้อมูลบรรจุงานให้
    // เพื่อให้ตรงกับรายชื่อที่แสดงในตารางของหน้า job_placement.php (api/job_placement_list.php ใช้ UNION เดียวกันนี้)
    $unionSql = "
        SELECT jp.Gender AS Gender, jp.Age AS Age, jp.EduNo AS EduNo, jp.StartDate AS PeriodDate
        FROM job_placement jp

        UNION ALL

        SELECT
            CASE e.SexNo WHEN 1 THEN 'Male' WHEN 2 THEN 'Female' ELSE NULL END AS Gender,
            NULL AS Age,
            sr.EqNo AS EduNo,
            sr.RDate AS PeriodDate
        FROM selft_rep sr
        INNER JOIN employee e ON e.EmpID = sr.EmpID
        WHERE sr.JNo = 2
          AND sr.RDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND sr.RDate < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
          AND NOT EXISTS (SELECT 1 FROM job_placement jp2 WHERE jp2.EmpID = sr.EmpID)
    ";

    $stmtSrc = $pdo->prepare("SELECT Gender, Age, EduNo FROM ($unionSql) src $where");
    $stmtSrc->execute($dateParams);
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

    // ========== 2) Age Stats ==========
    $ageRanges = [
        ['label' => '15-19 ปี',     'min' => 15, 'max' => 19],
        ['label' => '20-29 ปี',     'min' => 20, 'max' => 29],
        ['label' => '30-39 ปี',     'min' => 30, 'max' => 39],
        ['label' => '40-49 ปี',     'min' => 40, 'max' => 49],
        ['label' => '50-59 ปี',     'min' => 50, 'max' => 59],
        ['label' => '60 ปี ขึ้นไป', 'min' => 60, 'max' => 200],
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
        $ageData[] = [
            'label'  => $range['label'],
            'male'   => $range['male'],
            'female' => $range['female'],
            'total'  => $range['male'] + $range['female'],
        ];
    }
    if ($ageUnspecTotal > 0) {
        $ageData[] = [
            'label'  => 'ไม่ระบุอายุ',
            'male'   => $ageUnspecMale,
            'female' => $ageUnspecFemale,
            'total'  => $ageUnspecTotal,
        ];
    }
    $ageSummaryMale   += $ageUnspecMale;
    $ageSummaryFemale += $ageUnspecFemale;
    $ageSummaryTotal   = $totalRows;

    // ========== 3) Education Stats ==========
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
        $eduData[] = [
            'label'  => $stat['label'],
            'male'   => $stat['male'],
            'female' => $stat['female'],
            'total'  => $stat['male'] + $stat['female'],
        ];
    }
    if ($eduUnspecTotal > 0) {
        $eduData[] = [
            'label'  => 'ไม่ระบุวุฒิการศึกษา',
            'male'   => $eduUnspecMale,
            'female' => $eduUnspecFemale,
            'total'  => $eduUnspecTotal,
        ];
    }
    $eduSummaryMale   += $eduUnspecMale;
    $eduSummaryFemale += $eduUnspecFemale;
    $eduSummaryTotal   = $totalRows;

    echo json_encode([
        'success'   => true,
        'gender'    => [
            'male'        => $genderMale,
            'female'      => $genderFemale,
            'unspecified' => $genderUnspecified,
            'total'       => $genderTotal,
        ],
        'age'       => [
            'data'   => $ageData,
            'male'   => $ageSummaryMale,
            'female' => $ageSummaryFemale,
            'total'  => $ageSummaryTotal,
        ],
        'education' => [
            'data'   => $eduData,
            'male'   => $eduSummaryMale,
            'female' => $eduSummaryFemale,
            'total'  => $eduSummaryTotal,
        ],
        'dateFrom' => $dateFrom,
        'dateTo'   => $dateTo,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
