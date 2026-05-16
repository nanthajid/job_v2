<?php
require_once __DIR__ . '/config/database.php';

$pdo = getDB();

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
    $conds[] = 'r.RDate >= :from';
    $params[':from'] = $dateFrom;
} else {
    $dateFrom = '';
}
if ($validDate($dateTo)) {
    $conds[] = 'r.RDate <= :to';
    $params[':to'] = $dateTo;
} else {
    $dateTo = '';
}
$where = $conds ? ' AND ' . implode(' AND ', $conds) : '';

$sql = "SELECT
        k.KNo,
        k.KName,
        SUM(CASE WHEN r.QNo = 1 AND r.SexNo = 1 THEN 1 ELSE 0 END) AS quit_male,
        SUM(CASE WHEN r.QNo = 1 AND r.SexNo = 2 THEN 1 ELSE 0 END) AS quit_female,
        SUM(CASE WHEN r.QNo = 2 AND r.SexNo = 1 THEN 1 ELSE 0 END) AS fire_male,
        SUM(CASE WHEN r.QNo = 2 AND r.SexNo = 2 THEN 1 ELSE 0 END) AS fire_female
     FROM kate k
     LEFT JOIN register r ON r.KNo = k.KNo$where
     GROUP BY k.KNo, k.KName
     ORDER BY k.KNo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sumQuitM = 0; $sumQuitF = 0; $sumFireM = 0; $sumFireF = 0;
foreach ($rows as $r) {
    $sumQuitM += (int)$r['quit_male'];
    $sumQuitF += (int)$r['quit_female'];
    $sumFireM += (int)$r['fire_male'];
    $sumFireF += (int)$r['fire_female'];
}
$sumQuitTotal = $sumQuitM + $sumQuitF;
$sumFireTotal = $sumFireM + $sumFireF;
$grandTotal   = $sumQuitTotal + $sumFireTotal;

$thaiDate = function ($iso) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    if (!$d) return $iso;
    $thMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};
if ($dateFrom && $dateTo)      $rangeLabel = 'ช่วงวันที่ ' . $thaiDate($dateFrom) . ' ถึง ' . $thaiDate($dateTo);
elseif ($dateFrom)             $rangeLabel = 'ตั้งแต่วันที่ ' . $thaiDate($dateFrom) . ' เป็นต้นไป';
elseif ($dateTo)               $rangeLabel = 'ถึงวันที่ ' . $thaiDate($dateTo);
else                            $rangeLabel = 'ข้อมูลทั้งหมด (ไม่ระบุช่วงเวลา)';

$printedAt = (int)date('j') . ' ' .
    ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'][(int)date('n')] .
    ' ' . (date('Y') + 543) . ' เวลา ' . date('H:i') . ' น.';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>พิมพ์ตารางสรุปผู้มาขึ้นทะเบียนว่างงาน</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Sarabun', sans-serif;
      margin: 0;
      padding: 24px 32px;
      color: #000;
      background: #fff;
      font-size: 14px;
    }
    .toolbar {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      margin-bottom: 18px;
    }
    .toolbar button {
      font-family: inherit;
      font-size: 14px;
      padding: 8px 18px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-print { background:#28a745; color:#fff; }
    .btn-close { background:#6c757d; color:#fff; }
    .doc-header { text-align: center; margin-bottom: 16px; }
    .doc-header h1 {
      font-size: 18pt;
      font-weight: 700;
      margin: 0 0 4px;
    }
    .doc-header h2 {
      font-size: 14pt;
      font-weight: 600;
      margin: 0 0 4px;
    }
    .doc-header .range {
      font-size: 13pt;
      font-weight: 500;
      margin-top: 6px;
    }
    table.report {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
    }
    table.report th, table.report td {
      border: 1px solid #000;
      padding: 8px 6px;
      text-align: center;
      vertical-align: middle;
    }
    table.report thead th {
      background: #f0f0f0;
      font-weight: 700;
    }
    table.report tbody td.col-name {
      text-align: left;
      padding-left: 12px;
      font-weight: 500;
    }
    table.report tfoot td {
      background: #f0f0f0;
      font-weight: 700;
    }
    table.report tfoot td.col-name { text-align: right; padding-right: 12px; }
    .total-cell { background:#fff8e1; font-weight: 700; }
    .doc-footer {
      margin-top: 22px;
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: #444;
    }
    @page { size: A4 landscape; margin: 12mm; }
    @media print {
      body { padding: 0; }
      .toolbar { display: none !important; }
      .total-cell { -webkit-print-color-adjust: exact; print-color-adjust: exact; background:#fff8e1 !important; }
      table.report thead th,
      table.report tfoot td { -webkit-print-color-adjust: exact; print-color-adjust: exact; background:#f0f0f0 !important; }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button class="btn-print" onclick="window.print()">พิมพ์</button>
    <button class="btn-close" onclick="window.close()">ปิดหน้านี้</button>
  </div>

  <div class="doc-header">
    <h1>สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</h1>
    <h2>ตารางสรุปผู้มาขึ้นทะเบียนว่างงาน แยกตามเขตและสาเหตุออกจากงาน</h2>
    <div class="range"><?= htmlspecialchars($rangeLabel) ?></div>
  </div>

  <table class="report">
    <thead>
      <tr>
        <th rowspan="3" style="width:24%;">เขต</th>
        <th colspan="6">สาเหตุออกจากงาน</th>
      </tr>
      <tr>
        <th colspan="3">ลาออก</th>
        <th colspan="3">เลิกจ้าง</th>
      </tr>
      <tr>
        <th>ชาย</th><th>หญิง</th><th>รวม</th>
        <th>ชาย</th><th>หญิง</th><th>รวม</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" style="padding:20px;">ไม่พบข้อมูล</td></tr>
      <?php else: foreach ($rows as $row):
        $a = (int)$row['quit_male'];
        $b = (int)$row['quit_female'];
        $c = $a + $b;
        $d = (int)$row['fire_male'];
        $e = (int)$row['fire_female'];
        $f = $d + $e;
      ?>
        <tr>
          <td class="col-name"><?= htmlspecialchars($row['KName']) ?></td>
          <td><?= number_format($a) ?></td>
          <td><?= number_format($b) ?></td>
          <td class="total-cell"><?= number_format($c) ?></td>
          <td><?= number_format($d) ?></td>
          <td><?= number_format($e) ?></td>
          <td class="total-cell"><?= number_format($f) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
    <tfoot>
      <tr>
        <td class="col-name">รวมทั้งหมด</td>
        <td><?= number_format($sumQuitM) ?></td>
        <td><?= number_format($sumQuitF) ?></td>
        <td class="total-cell"><?= number_format($sumQuitTotal) ?></td>
        <td><?= number_format($sumFireM) ?></td>
        <td><?= number_format($sumFireF) ?></td>
        <td class="total-cell"><?= number_format($sumFireTotal) ?></td>
      </tr>
      <tr>
        <td class="col-name">รวมทั้งสิ้น</td>
        <td colspan="6" class="total-cell"><?= number_format($grandTotal) ?> ราย</td>
      </tr>
    </tfoot>
  </table>

  <div class="doc-footer">
    <span>ผู้พิมพ์: เจ้าหน้าที่</span>
    <span>วันที่พิมพ์: <?= htmlspecialchars($printedAt) ?></span>
  </div>

  <script>
    window.addEventListener('load', function () {
      setTimeout(function () { window.print(); }, 350);
    });
  </script>
</body>
</html>
