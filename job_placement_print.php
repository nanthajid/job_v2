<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

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
               jp.IncomeDay, jp.IncomeMonth, jp.StartDate, jp.CreateDate,
               st.StName
        FROM job_placement jp
        LEFT JOIN educational_qualification edu ON edu.EqNo = jp.EduNo
        LEFT JOIN staff st ON st.StID = jp.StID
        WHERE 1=1 $where
        ORDER BY jp.JPNo DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$thaiDate = function ($iso) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    if (!$d) return $iso;
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

if ($dateFrom && $dateTo)      $rangeLabel = 'ช่วง ' . $thaiDate($dateFrom) . ' — ' . $thaiDate($dateTo);
elseif ($dateFrom)             $rangeLabel = 'ตั้งแต่ ' . $thaiDate($dateFrom);
elseif ($dateTo)               $rangeLabel = 'ถึง ' . $thaiDate($dateTo);
else                            $rangeLabel = 'ทั้งหมด (ไม่กรองวันที่)';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>พิมพ์รายงานการบรรจุงาน | สำนักงานจัดหางาน กทม. พื้นที่ 2</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <style>
    :root {
      --gov-navy: #002D62;
      --gov-gold: #D4AF37;
    }

    body {
      font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif;
      font-size: 14px;
      color: #1A1A1A;
      background: white;
      margin: 0;
      padding: 0;
    }

    .print-header {
      text-align: center;
      border-bottom: 3px solid #002D62;
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
    }

    .print-header h2 {
      font-family: 'Prompt', sans-serif;
      font-size: 1.5rem;
      color: var(--gov-navy);
      margin: 0 0 0.3rem;
    }

    .print-header .subtitle {
      font-size: 0.9rem;
      color: #64748B;
    }

    .print-header .range {
      display: inline-block;
      background: #FFF9E6;
      border: 1px solid var(--gov-gold);
      border-radius: 6px;
      padding: 0.3rem 1rem;
      margin-top: 0.5rem;
      font-weight: 600;
      color: var(--gov-navy);
    }

    .table-report {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    .table-report thead th {
      background: #E9ECEF;
      color: var(--gov-navy);
      font-weight: 600;
      padding: 0.6rem 0.5rem;
      border: 1px solid #DEE2E6;
      text-align: center;
      font-size: 0.8rem;
      text-transform: uppercase;
    }

    .table-report tbody td {
      padding: 0.5rem 0.5rem;
      border: 1px solid #E9ECEF;
      vertical-align: middle;
    }

    .table-report tbody tr:nth-child(even) {
      background: #F8F9FA;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-navy { color: var(--gov-navy); font-weight: 600; }
    .text-muted { color: #64748B; font-size: 0.8rem; }

    .badge-print {
      display: inline-block;
      background: #28a745;
      color: white;
      padding: 0.15rem 0.5rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .print-footer {
      margin-top: 2rem;
      border-top: 1px solid #DEE2E6;
      padding-top: 0.5rem;
      text-align: center;
      font-size: 0.8rem;
      color: #64748B;
    }

    .no-print { }

    @media print {
      .no-print { display: none !important; }
      body { padding: 1cm; }
      @page { size: A4 landscape; margin: 1cm; }
    }

    @media (max-width: 768px) {
      .table-report { font-size: 0.75rem; }
    }
  </style>
</head>
<body>

<div class="print-header">
  <h2>รายงานการบรรจุงาน</h2>
  <div class="subtitle">สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</div>
  <div class="range"><i class="far fa-calendar-check mr-2"></i> <?= htmlspecialchars($rangeLabel) ?></div>
  <br>
  <span class="text-muted">วันที่พิมพ์: <?= $thaiDate(date('Y-m-d')) ?></span>
</div>

<div class="no-print mb-4 text-center">
  <div class="row align-items-end justify-content-center">
    <div class="col-auto">
      <label class="small text-muted d-block mb-1">จากวันที่</label>
      <input type="text" id="filterDateFrom" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="เลือกวันที่" readonly style="width: 140px; display: inline-block;">
    </div>
    <div class="col-auto">
      <label class="small text-muted d-block mb-1">ถึงวันที่</label>
      <input type="text" id="filterDateTo" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>" placeholder="เลือกวันที่" readonly style="width: 140px; display: inline-block;">
    </div>
    <div class="col-auto">
      <button type="button" id="btnFilter" class="btn btn-primary btn-sm px-3" style="background: var(--gov-navy); border-color: var(--gov-navy); border-radius: 8px;">
        <i class="fas fa-search mr-1"></i> แสดง
      </button>
      <button type="button" id="btnPrint" class="btn btn-warning btn-sm px-3" style="border-radius: 8px; font-weight: 600;">
        <i class="fas fa-print mr-1"></i> พิมพ์
      </button>
      <a href="job_placement_print.php" class="btn btn-outline-secondary btn-sm px-3" style="border-radius: 8px;">ล้างตัวกรอง</a>
    </div>
  </div>
</div>

<table class="table-report">
  <thead>
    <tr>
      <th style="width: 50px;">ลำดับ</th>
      <th style="width: 140px;">เลขบัตรประชาชน</th>
      <th>ชื่อ-นามสกุล</th>
      <th style="width: 50px;">อายุ</th>
      <th>วุฒิการศึกษา</th>
      <th>สถานประกอบการ</th>
      <th>ตำแหน่ง</th>
      <th style="width: 90px;">รายได้/วัน</th>
      <th style="width: 90px;">รายได้/เดือน</th>
      <th style="width: 100px;">วันที่เริ่มงาน</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="10" class="text-center text-muted py-4">ไม่พบข้อมูลการบรรจุงานในช่วงเวลาที่เลือก</td></tr>
    <?php else: $idx = 0; foreach ($rows as $row): $idx++; ?>
      <tr>
        <td class="text-center"><?= $idx ?></td>
        <td class="text-center text-navy" style="font-family: monospace;"><?= htmlspecialchars($row['EmpID']) ?></td>
        <td><?= htmlspecialchars($row['EmpName']) ?></td>
        <td class="text-center"><?= $row['Age'] ? $row['Age'] : '—' ?></td>
        <td><?= htmlspecialchars($row['EduName'] ?? '—') ?></td>
        <td><?= htmlspecialchars($row['CompanyName']) ?></td>
        <td><?= htmlspecialchars($row['Position'] ?? '—') ?></td>
        <td class="text-right"><?= $row['IncomeDay'] ? number_format((float)$row['IncomeDay'], 2) : '—' ?></td>
        <td class="text-right"><?= $row['IncomeMonth'] ? number_format((float)$row['IncomeMonth'], 2) : '—' ?></td>
        <td class="text-center"><?= $row['StartDate'] ? '<span class="badge-print">' . $thaiDate($row['StartDate']) . '</span>' : '—' ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

<div class="text-right mt-3" style="font-weight: 600; color: var(--gov-navy);">
  จำนวนทั้งหมด: <?= count($rows) ?> ราย
</div>

<div class="print-footer">
  © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Develop By Nanthajd sawasri
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
(function () {
  var common = {
    dateFormat: 'Y-m-d',
    locale: 'th',
    allowInput: false
  };
  var fpFrom = flatpickr('#filterDateFrom', Object.assign({}, common, {
    onChange: function (sel) {
      if (sel[0]) fpTo.set('minDate', sel[0]);
    }
  }));
  var fpTo = flatpickr('#filterDateTo', Object.assign({}, common, {
    onChange: function (sel) {
      if (sel[0]) fpFrom.set('maxDate', sel[0]);
    }
  }));

  $('#btnFilter').on('click', function() {
    var from = $('#filterDateFrom').val();
    var to   = $('#filterDateTo').val();
    var params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to)   params.push('to='   + encodeURIComponent(to));
    window.location = 'job_placement_print.php' + (params.length ? '?' + params.join('&') : '');
  });

  $('#btnPrint').on('click', function() {
    window.print();
  });
})();
</script>
</body>
</html>