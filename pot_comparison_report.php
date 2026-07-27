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

$paramsReg = [];
$condsReg  = [];

if ($validDate($dateFrom)) {
    $condsReg[] = 'RDate >= :fromReg';
    $paramsReg[':fromReg'] = $dateFrom;
}
if ($validDate($dateTo)) {
    $condsReg[] = 'RDate <= :toReg';
    $paramsReg[':toReg'] = $dateTo;
}

$whereReg = $condsReg ? ' WHERE ' . implode(' AND ', $condsReg) : '';

$sql = "SELECT
            pot.PotNo,
            pot.PotName,
            COALESCE(reg.reg_male, 0) AS reg_male,
            COALESCE(reg.reg_female, 0) AS reg_female
        FROM emp_position pot
        LEFT JOIN (
            SELECT 
                PotNo, 
                SUM(CASE WHEN SexNo = 1 THEN 1 ELSE 0 END) AS reg_male,
                SUM(CASE WHEN SexNo = 2 THEN 1 ELSE 0 END) AS reg_female
            FROM register
            $whereReg
            GROUP BY PotNo
        ) reg ON reg.PotNo = pot.PotNo
        ORDER BY pot.PotNo";

$stmt = $pdo->prepare($sql);
$stmt->execute($paramsReg);
$rows = $stmt->fetchAll();

$thaiDate = function ($iso) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', $iso);
    if (!$d) return $iso;
    $thMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

$rangeLabel = '';
if ($dateFrom && $dateTo)      $rangeLabel = 'ช่วง ' . $thaiDate($dateFrom) . ' — ' . $thaiDate($dateTo);
elseif ($dateFrom)             $rangeLabel = 'ตั้งแต่ ' . $thaiDate($dateFrom);
elseif ($dateTo)               $rangeLabel = 'ถึง ' . $thaiDate($dateTo);
else                            $rangeLabel = 'ทั้งหมด';

$sumRegM = 0; $sumRegF = 0;
foreach ($rows as $r) {
    $sumRegM += (int)$r['reg_male'];
    $sumRegF += (int)$r['reg_female'];
}
$sumRegTotal = $sumRegM + $sumRegF;
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายงานเปรียบเทียบตามตำแหน่งงาน | สำนักงานจัดหางาน กทม. พื้นที่ 2</title>

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

    .table-report thead th {
      vertical-align: middle;
      text-align: center;
      background: var(--gov-gray);
      color: var(--gov-navy);
      font-weight: 600;
      border: 1px solid var(--gov-border) !important;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.025em;
    }
    .table-report tbody td {
      text-align: center;
      vertical-align: middle;
      border: 1px solid var(--gov-gray) !important;
      padding: 1rem;
    }
    .table-report tbody td.col-name {
      text-align: left;
      font-weight: 500;
      color: var(--gov-navy);
    }
    .table-report tfoot td {
      text-align: center;
      font-weight: 700;
      background: #FFF9E6;
      border: 1px solid var(--gov-border) !important;
      color: var(--gov-navy);
    }
    .table-report tfoot td.col-name { text-align: right; }
    
    .total-cell { background: #F8F9FA; font-weight: 700; color: var(--gov-royal); }

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
      .no-print, .main-sidebar, .main-header, .main-footer { display:none !important; }
      .content-wrapper { margin-left:0 !important; background: white !important; }
      .gov-page-header { display: none !important; }
      .gov-card { box-shadow: none !important; border: 1px solid #eee !important; }
      @page { size: A4 landscape; margin: 10mm; }
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

  <?php include 'includes/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">

    <!-- Page Header -->
    <div class="gov-page-header no-print">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">รายงานสรุปจำนวนผู้ขึ้นทะเบียน (ตำแหน่งงาน)</h1>
            <p class="mb-0 opacity-9">สรุปจำนวนผู้มาขึ้นทะเบียนว่างงาน แยกตามตำแหน่งงานล่าสุดและเพศ</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-briefcase fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid px-lg-5">

        <div class="row mb-4 no-print">
          <div class="col-12">
            <div class="gov-card p-4 h-100 border-0 shadow-sm" style="background: white; border-left: 5px solid #28a745 !important;">
               <div class="d-flex align-items-center">
                  <div class="mr-3 text-success"><i class="fas fa-user-plus fa-3x opacity-2"></i></div>
                  <div>
                     <div class="text-muted small">ผู้ขึ้นทะเบียนว่างงานทั้งหมด</div>
                     <div class="h3 font-weight-bold mb-2 text-navy"><?= number_format($sumRegTotal) ?> <small>ราย</small></div>
                     <div class="text-muted small" style="font-size: 0.9rem;"><i class="fas fa-calendar-alt mr-1"></i><?= htmlspecialchars($rangeLabel) ?></div>
                  </div>
               </div>
            </div>
          </div>
        </div>

        <div class="gov-card">
          <div class="gov-card-header d-flex align-items-center flex-wrap">
            <h3 class="gov-card-title"><i class="fas fa-table mr-2"></i> ตารางสรุปข้อมูลการขึ้นทะเบียนตามตำแหน่งงาน</h3>
            <span class="badge badge-light border ml-3 px-3 py-2" style="font-size: 0.9rem;"><i class="far fa-calendar-check mr-2 text-royal"></i><?= htmlspecialchars($rangeLabel) ?></span>
            <div class="ml-auto no-print">
              <button type="button" class="btn btn-success shadow-sm mr-2" onclick="window.print()" style="background-color: #28a745; border-color: #28a745; border-radius: 8px;">
                <i class="fas fa-print mr-2"></i>พิมพ์รายงาน
              </button>
              <button type="button" id="exportExcel" class="btn btn-outline-success shadow-sm" style="border-radius: 8px;">
                <i class="fas fa-file-excel mr-2"></i>ส่งออก Excel
              </button>
            </div>
          </div>

          <div class="card-body border-bottom no-print p-4 bg-light">
            <form method="get" id="filterForm">
              <div class="row align-items-end">
                <div class="col-md-6">
                   <label class="form-label">ช่วงวันที่</label>
                   <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i class="far fa-calendar-alt text-royal"></i></span>
                      </div>
                      <input type="text" id="filterDateFrom" name="from" class="form-control" 
                             placeholder="จากวันที่" value="<?= htmlspecialchars($dateFrom) ?>" readonly>
                      <div class="input-group-prepend input-group-append">
                        <span class="input-group-text bg-white border-left-0 border-right-0">ถึง</span>
                      </div>
                      <input type="text" id="filterDateTo" name="to" class="form-control" 
                             placeholder="ถึงวันที่" value="<?= htmlspecialchars($dateTo) ?>" readonly>
                   </div>
                </div>
                <div class="col-md-6 text-md-right mt-3 mt-md-0">
                  <button type="submit" class="btn btn-primary px-4 shadow-sm" style="background-color: var(--gov-navy); border-color: var(--gov-navy); border-radius: 8px; padding: 0.6rem 1.5rem;">
                    <i class="fas fa-search mr-2"></i>แสดงข้อมูล
                  </button>
                  <a href="pot_comparison_report.php" class="btn btn-gov-outline ml-2" style="padding: 0.6rem 1.5rem;">
                    <i class="fas fa-redo mr-2"></i>ล้างตัวกรอง
                  </a>
                </div>
              </div>
            </form>
          </div>

          <div class="gov-card-body p-0">
            <div class="table-responsive">
              <table class="table table-report mb-0" id="comparisonTable">
                <thead>
                  <tr>
                    <th rowspan="2" style="min-width:250px;">ตำแหน่งงาน</th>
                    <th colspan="3" class="bg-white"><span class="text-navy">จำนวนผู้ขึ้นทะเบียนว่างงาน</span></th>
                  </tr>
                  <tr>
                    <th style="min-width:120px;">ชาย (ราย)</th>
                    <th style="min-width:120px;">หญิง (ราย)</th>
                    <th class="total-cell" style="min-width:120px;">รวม (ราย)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($rows)): ?>
                    <tr><td colspan="4" class="text-muted py-5">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>
                  <?php else: foreach ($rows as $row):
                    $a = (int)$row['reg_male'];
                    $b = (int)$row['reg_female'];
                    $c = $a + $b;
                  ?>
                    <tr>
                      <td class="col-name text-navy font-weight-bold">
                        <i class="fas fa-briefcase text-royal mr-2 opacity-5"></i>
                        <?= htmlspecialchars($row['PotName']) ?>
                      </td>
                      <td><?= number_format($a) ?></td>
                      <td><?= number_format($b) ?></td>
                      <td class="total-cell"><?= number_format($c) ?></td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td class="col-name text-navy">รวมทั้งสิ้น</td>
                    <td><?= number_format($sumRegM) ?></td>
                    <td><?= number_format($sumRegF) ?></td>
                    <td class="total-cell" style="background: #E6F0FF;"><?= number_format($sumRegTotal) ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div class="card-footer bg-white text-muted small py-3">
            <i class="fas fa-info-circle mr-1 text-royal"></i>
            ข้อมูลสรุปจำนวนผู้ขึ้นทะเบียนว่างงานแยกตามตำแหน่งงานล่าสุดและเพศ
          </div>
        </div>

      </div>
    </section>
  </div>

  <footer class="main-footer border-top-0 bg-transparent text-center py-4">
    <div class="text-muted small">
      © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Developed By Nanthajid Sawasri Computer Science Officer
    </div>
  </footer>

</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
  $(function () {
    var common = {
      dateFormat: 'Y-m-d',
      locale: 'th',
      allowInput: false
    };
    flatpickr('#filterDateFrom', common);
    flatpickr('#filterDateTo', common);

    // Export to Excel
    $('#exportExcel').on('click', function() {
      const table = document.getElementById('comparisonTable');
      const wb = XLSX.utils.table_to_book(table, { sheet: "รายงานเปรียบเทียบ_ตำแหน่งงาน" });
      const from = $('#filterDateFrom').val() || 'all';
      const to = $('#filterDateTo').val() || 'all';
      XLSX.writeFile(wb, `รายงานเปรียบเทียบ_ตำแหน่งงาน_${from}_ถึง_${to}.xlsx`);
    });
  });
</script>
</body>
</html>
