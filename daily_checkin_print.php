<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$pdo = getDB();
$user = currentUser();

$dateFrom = isset($_GET['from']) ? trim($_GET['from']) : date('Y-m-d');
$dateTo   = isset($_GET['to'])   ? trim($_GET['to'])   : date('Y-m-d');

$validDate = function ($s) {
    if ($s === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
};

$params = [];
$conds  = [];
if ($validDate($dateFrom)) {
    $conds[] = 's.RDate >= :from';
    $params[':from'] = $dateFrom;
}
if ($validDate($dateTo)) {
    $conds[] = 's.RDate <= :to';
    $params[':to'] = $dateTo;
}
$where = $conds ? ' AND ' . implode(' AND ', $conds) : '';

$sql = "SELECT s.DocID, s.EmpID, t.Title AS TitleName, e.EmpName, s.RDate, k.KName, q.QName, j.JName, pot.PotName
        FROM selft_rep s
        LEFT JOIN employee e ON e.EmpID = s.EmpID
        LEFT JOIN titles   t ON t.TitleNo = e.Titles
        LEFT JOIN kate     k ON k.KNo   = s.KNo
        LEFT JOIN quit     q ON q.QNo   = s.QNo
        LEFT JOIN job      j ON j.JNo   = s.JNo
        LEFT JOIN emp_position pot ON pot.PotNo = s.PotNo
        WHERE 1=1 $where
        ORDER BY s.RDate ASC, s.DocNo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$thMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$thaiDate = function ($iso) use ($thMonths) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    if (!$d) return $iso;
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

if ($dateFrom && $dateTo && $dateFrom === $dateTo) {
    $rangeLabel = 'ประจำวันที่ ' . $thaiDate($dateFrom);
} elseif ($dateFrom && $dateTo) {
    $rangeLabel = 'ช่วงวันที่ ' . $thaiDate($dateFrom) . ' ถึง ' . $thaiDate($dateTo);
} elseif ($dateFrom) {
    $rangeLabel = 'ตั้งแต่วันที่ ' . $thaiDate($dateFrom) . ' เป็นต้นไป';
} elseif ($dateTo) {
    $rangeLabel = 'ถึงวันที่ ' . $thaiDate($dateTo);
} else {
    $rangeLabel = 'ข้อมูลทั้งหมด (ไม่ระบุช่วงเวลา)';
}

$printedAt = (int)date('j') . ' ' . $thMonths[(int)date('n')] . ' ' . (date('Y') + 543) . ' เวลา ' . date('H:i') . ' น.';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายงานการรายงานตัวว่างงานประจำวัน | สำนักงานจัดหางาน กทม. พื้นที่ 2</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    :root {
      --gov-navy: #002D62;
      --gov-royal: #005EB8;
      --gov-gold: #D4AF37;
      --gov-bg: #F0F2F5;
      --gov-white: #FFFFFF;
      --gov-gray: #E9ECEF;
      --gov-text-dark: #1A1A1A;
      --gov-text-muted: #64748B;
      --gov-border: #DEE2E6;
      --gov-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    body {
      font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif;
      background-color: var(--gov-bg);
      color: var(--gov-text-dark);
      font-size: 16px;
    }

    h1, h2, h3, h4, .brand-text, .nav-link, .btn {
      font-family: 'Prompt', sans-serif;
    }

    .content-wrapper {
      background-color: var(--gov-bg);
      padding-bottom: 3rem;
    }

    .main-header {
      border-bottom: 3px solid var(--gov-gold) !important;
      box-shadow: var(--gov-shadow);
    }

    .gov-card {
      background: var(--gov-white);
      border: none;
      border-radius: 12px;
      box-shadow: var(--gov-shadow);
      margin-bottom: 1.5rem;
      overflow: hidden;
    }

    .gov-card-header {
      background-color: transparent;
      border-bottom: 1px solid var(--gov-gray);
      padding: 1.25rem 1.5rem;
    }

    .gov-card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gov-navy);
      margin: 0;
    }

    .gov-page-header {
      background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%);
      padding: 2.5rem 0;
      margin-bottom: 2rem;
      color: white;
      box-shadow: var(--gov-shadow);
    }

    .gov-page-title {
      font-size: 2rem;
      font-weight: 600;
    }

    /* Print Paper Styles */
    .report-paper {
      background: #fff;
      padding: 15mm;
      min-height: 297mm;
      margin: 0 auto;
      box-shadow: var(--gov-shadow);
      color: #000;
      border-radius: 8px;
    }
    
    .doc-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid var(--gov-navy); padding-bottom: 20px; }
    .doc-header h1 { font-size: 20pt; font-weight: 700; color: var(--gov-navy); margin-bottom: 5px; }
    .doc-header h2 { font-size: 16pt; font-weight: 600; color: var(--gov-royal); margin-bottom: 5px; }
    .doc-header .range { font-size: 13pt; color: var(--gov-text-dark); }
    
    table.table-report { width: 100%; border-collapse: collapse; margin-top: 15px; }
    table.table-report th, table.table-report td {
      border: 1px dashed #dee2e6 !important;
      padding: 10px 6px;
      text-align: center;
      vertical-align: middle;
      color: #000 !important;
    }
    table.table-report thead th { 
      background: var(--gov-gray) !important; 
      color: var(--gov-navy) !important;
      font-weight: 700; 
      font-size: 13px;
      border-bottom: 2px dashed #dee2e6 !important;
    }
    table.table-report tbody td.text-left { text-align: left; padding-left: 10px; }

    .doc-footer {
      margin-top: 30px;
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      color: var(--gov-text-muted);
      border-top: 1px dashed #dee2e6;
      padding-top: 15px;
    }

    .form-label {
      font-weight: 500;
      color: var(--gov-navy);
      margin-bottom: 0.5rem;
    }
    .form-control {
      border-radius: 8px;
      padding: 0.6rem 1rem;
    }

    @media print {
      .no-print, .main-sidebar, .main-header, .main-footer { display: none !important; }
      .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: #fff !important; }
      body { background: #fff !important; }
      .report-paper {
        box-shadow: none;
        padding: 0;
        margin: 0;
        min-height: auto;
        border-radius: 0;
      }
      .doc-header h1, .doc-header h2 { color: #000; }
      table.table-report th, table.table-report td { border: 1px dashed #bbb !important; }
      table.table-report thead th { background: #f8f9fa !important; color: #000 !important; }
      @page { size: A4 portrait; margin: 15mm 10mm; }
    }

    @media (max-width: 768px) {
      .gov-page-title { font-size: 1.5rem; }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- ===== Navbar ===== -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light no-print">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button">
          <i class="fas fa-bars text-navy"></i>
        </a>
      </li>
      <li class="nav-item d-none d-lg-block">
        <span class="nav-link text-navy font-weight-bold">
          <i class="fas fa-desktop mr-2"></i>ระบบจัดการคนว่างงาน สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2
        </span>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-none d-md-block">
        <span class="nav-link text-muted font-weight-light">
           พุทธศักราช <?php echo (date('Y') + 543); ?>
        </span>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <div class="d-flex align-items-center">
            <div class="text-right mr-2 d-none d-sm-block">
              <div class="font-weight-bold" style="line-height:1;"><?= htmlspecialchars($user['StName'] ?: $user['UserName']) ?></div>
              <small class="text-muted"><?= htmlspecialchars($user['StPostName'] ?: ($user['StPost'] ?: 'เจ้าหน้าที่')) ?></small>
            </div>
            <i class="fas fa-user-circle fa-2x text-navy"></i>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right shadow border-0">
          <a href="logout.php" class="dropdown-item text-danger">
            <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
          </a>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <?php $current_page = 'daily_checkin_print'; include __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">

    <!-- Page Header -->
    <div class="gov-page-header no-print">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">พิมพ์รายงานการรายงานตัว</h1>
            <p class="mb-0 opacity-9">รายงานรายละเอียดการรายงานตัวว่างงานประจำวันแยกตามช่วงเวลา</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-print fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid px-lg-5">
        
        <!-- UI Card: Filter -->
        <div class="gov-card no-print">
          <div class="gov-card-header">
            <h3 class="gov-card-title"><i class="fas fa-filter mr-2"></i> ตัวกรองข้อมูล</h3>
          </div>
          <div class="gov-card-body p-4">
            <form method="get" id="filterForm">
              <div class="row align-items-end">
                <div class="col-md-6 mb-3 mb-md-0">
                  <label class="form-label">ช่วงวันที่มารายงานตัว</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-light border-right-0"><i class="far fa-calendar-alt text-royal"></i></span>
                    </div>
                    <input type="text" id="filterDateFrom" name="from" class="form-control" 
                           placeholder="จากวันที่" value="<?= htmlspecialchars($dateFrom) ?>" readonly>
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-light border-left-0 border-right-0">ถึง</span>
                    </div>
                    <input type="text" id="filterDateTo" name="to" class="form-control" 
                           placeholder="ถึงวันที่" value="<?= htmlspecialchars($dateTo) ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6 text-md-right">
                  <button type="submit" class="btn btn-primary px-4 shadow-sm" style="background-color: var(--gov-navy); border-color: var(--gov-navy); border-radius: 8px; padding: 0.6rem 1.5rem;">
                    <i class="fas fa-search mr-2"></i>แสดงข้อมูล
                  </button>
                  <button type="button" class="btn btn-success px-4 shadow-sm ml-2" onclick="window.print()" style="background-color: #28a745; border-color: #28a745; border-radius: 8px; padding: 0.6rem 1.5rem;">
                    <i class="fas fa-print mr-2"></i>พิมพ์รายงาน
                  </button>
                  <a href="daily_checkin_print.php" class="btn btn-secondary ml-2" style="padding: 0.6rem 1.5rem;">
                    <i class="fas fa-redo mr-2"></i>ล้าง
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Report Content -->
        <div class="report-paper">
          <div class="doc-header">
            <h1>สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</h1>
            <h2>รายงานรายละเอียดการรายงานตัวว่างงาน</h2>
            <div class="range"><?= htmlspecialchars($rangeLabel) ?></div>
          </div>

          <table class="table-report">
            <thead>
              <tr>
                <th style="width: 5%;">ลำดับ</th>
                <th style="width: 13%;">วันที่รายงาน</th>
                <th style="width: 14%;">เลขบัตรประชาชน</th>
                <th style="width: 20%;">ชื่อ-นามสกุล</th>
                <th style="width: 12%;">ตำแหน่งล่าสุด</th>
                <th style="width: 11%;">เขต</th>
                <th style="width: 11%;">สาเหตุที่ออก</th>
                <th style="width: 14%;">สถานะงาน</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr><td colspan="8" style="padding:40px;">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>
              <?php else: $i = 1; foreach ($rows as $row): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= date('d/m/', strtotime($row['RDate'])) . (date('Y', strtotime($row['RDate'])) + 543) ?></td>
                  <td style="font-size: 0.9rem;"><?= htmlspecialchars($row['EmpID'] ?? '') ?></td>
                  <td class="text-left"><?= htmlspecialchars(($row['TitleName'] ? $row['TitleName'] . ' ' : '') . ($row['EmpName'] ?? '')) ?></td>
                  <td><?= htmlspecialchars($row['PotName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['KName'] ?? '') ?></td>
                  <td class="text-left" style="font-size: 0.85rem;"><?= htmlspecialchars($row['QName'] ?? '') ?></td>
                  <td class="text-left" style="font-size: 0.85rem;"><?= htmlspecialchars($row['JName'] ?? '') ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>

          <div class="doc-footer">
            <span>จำนวนรายการรายงานตัวทั้งสิ้น: <?= number_format(count($rows)) ?> รายการ</span>
            <span>วันที่พิมพ์: <?= htmlspecialchars($printedAt) ?></span>
          </div>
        </div>

      </div>
    </section>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

<script>
  $(function () {
    const commonConfig = {
      dateFormat: "Y-m-d",
      locale: "th",
      allowInput: true
    };
    flatpickr("#filterDateFrom", commonConfig);
    flatpickr("#filterDateTo", commonConfig);
  });
</script>
</body>
</html>
