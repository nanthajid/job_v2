<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo  = getDB();

$totalRegistered   = (int) $pdo->query('SELECT COUNT(EmpID) FROM register')->fetchColumn();
$totalCheckin      = (int) $pdo->query('SELECT COUNT(EmpID) FROM selft_rep')->fetchColumn();
$registeredThisMonth = (int) $pdo->query(
    "SELECT COUNT(EmpID) FROM register
     WHERE DATE_FORMAT(RDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
)->fetchColumn();
$checkinThisMonth = (int) $pdo->query(
    "SELECT COUNT(EmpID) FROM selft_rep
     WHERE DATE_FORMAT(RDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
)->fetchColumn();
$gotJob = (int) $pdo->query(
    "SELECT COUNT(EmpID) FROM selft_rep WHERE JNo = 2"
)->fetchColumn();
$gotJobThisMonth = (int) $pdo->query(
    "SELECT COUNT(EmpID) FROM selft_rep
     WHERE JNo = 2
       AND DATE_FORMAT(RDate, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
)->fetchColumn();

// --- District Register Filter ---
$distDateFrom = (isset($_GET['dist_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dist_from'])) ? $_GET['dist_from'] : null;
$distDateTo   = (isset($_GET['dist_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dist_to'])) ? $_GET['dist_to'] : null;
$distWhere    = "";
$distParams   = [];
if ($distDateFrom && $distDateTo) {
    $distWhere = " AND r.RDate BETWEEN :from AND :to ";
    $distParams = ['from' => $distDateFrom, 'to' => $distDateTo];
}

$stmt = $pdo->prepare(
    "SELECT k.KNo, k.KName, COUNT(r.EmpID) AS cnt
     FROM kate k
     LEFT JOIN register r ON r.KNo = k.KNo $distWhere
     GROUP BY k.KNo, k.KName
     ORDER BY k.KNo"
);
$stmt->execute($distParams);
$districtRows = $stmt->fetchAll();

$totalRegisteredInPeriod = $totalRegistered;
if ($distDateFrom && $distDateTo) {
    $stmtTotal = $pdo->prepare("SELECT COUNT(EmpID) FROM register WHERE RDate BETWEEN :from AND :to");
    $stmtTotal->execute($distParams);
    $totalRegisteredInPeriod = (int)$stmtTotal->fetchColumn();
}
// --------------------------------

// --- District Check-in Filter ---
$ciDistFrom = (isset($_GET['ci_dist_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['ci_dist_from'])) ? $_GET['ci_dist_from'] : null;
$ciDistTo   = (isset($_GET['ci_dist_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['ci_dist_to'])) ? $_GET['ci_dist_to'] : null;
$ciDistWhere = "";
$ciDistParams = [];
if ($ciDistFrom && $ciDistTo) {
    $ciDistWhere = " AND s.RDate BETWEEN :from AND :to ";
    $ciDistParams = ['from' => $ciDistFrom, 'to' => $ciDistTo];
}

$stmtCi = $pdo->prepare(
    "SELECT k.KNo, k.KName, COUNT(s.EmpID) AS cnt
     FROM kate k
     LEFT JOIN selft_rep s ON s.KNo = k.KNo $ciDistWhere
     GROUP BY k.KNo, k.KName
     ORDER BY k.KNo"
);
$stmtCi->execute($ciDistParams);
$checkinDistrictRows = $stmtCi->fetchAll();

$totalCheckinInPeriod = $totalCheckin;
if ($ciDistFrom && $ciDistTo) {
    $stmtCiTotal = $pdo->prepare("SELECT COUNT(EmpID) FROM selft_rep WHERE RDate BETWEEN :from AND :to");
    $stmtCiTotal->execute($ciDistParams);
    $totalCheckinInPeriod = (int)$stmtCiTotal->fetchColumn();
}
// --------------------------------

$districtColors = [
    ['class' => 'text-primary', 'hex' => '#3498db', 'rgba' => 'rgba(52,152,219,.85)'],
    ['class' => 'text-success', 'hex' => '#2ecc71', 'rgba' => 'rgba(46,204,113,.85)'],
    ['class' => 'text-danger',  'hex' => '#e74c3c', 'rgba' => 'rgba(231,76,60,.85)'],
    ['class' => 'text-warning', 'hex' => '#f39c12', 'rgba' => 'rgba(243,156,18,.85)'],
    ['class' => '',             'hex' => '#9b59b6', 'rgba' => 'rgba(155,89,182,.85)'],
];

$stmtQuit = $pdo->prepare(
    "SELECT q.QNo, q.QName, COUNT(r.EmpID) AS cnt
     FROM quit q
     LEFT JOIN register r ON r.QNo = q.QNo $distWhere
     GROUP BY q.QNo, q.QName
     ORDER BY q.QNo"
);
$stmtQuit->execute($distParams);
$quitRows = $stmtQuit->fetchAll();

$stmtReason = $pdo->prepare(
    "SELECT j.JNo, j.JName, COUNT(s.EmpID) AS cnt
     FROM job j
     LEFT JOIN selft_rep s ON s.JNo = j.JNo $ciDistWhere
     GROUP BY j.JNo, j.JName
     ORDER BY j.JNo"
);
$stmtReason->execute($ciDistParams);
$reasonRows = $stmtReason->fetchAll();

function getThaiMonthYear($dateStr = null) {
    $months = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    if ($dateStr) {
        $time = strtotime($dateStr);
        return $months[(int)date('m', $time)] . " " . (date('Y', $time) + 543);
    }
    return $months[(int)date('m')] . " " . (date('Y') + 543);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard | สำนักงานจัดหางาน กทม. พื้นที่ 2</title>

  <!-- Google Fonts Thai -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <!-- Bootstrap 4 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <!-- DataTables Bootstrap 4 -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">

  <!-- AdminLTE 3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

  <!-- Flatpickr -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <!-- Custom -->
  <link rel="stylesheet" href="assets/css/custom.css?v=<?= time() ?>">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- ===== Navbar ===== -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
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
          <i class="far fa-calendar-alt mr-2"></i>
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

  <!-- ===== Sidebar ===== -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- ===== Content Wrapper ===== -->
  <div class="content-wrapper">

    <!-- Page Header -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2 align-items-center">
          <div class="col-sm-8">
            <h1 class="m-0" style="font-size:1.3rem;">
              <i class="fas fa-tachometer-alt mr-2 text-primary"></i>
              Dashboard — ภาพรวมผู้มาลงทะเบียนว่างงาน
            </h1>
            <small class="text-muted">สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</small>
          </div>
          <div class="col-sm-4 d-flex align-items-center justify-content-sm-end">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary mr-2 no-print">
              <i class="fas fa-print mr-1"></i>พิมพ์สรุปข้อมูล
            </button>
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item active"><i class="fas fa-home mr-1"></i>Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid">

        <!-- ==============================
             ROW 1 : STAT CARDS
             ============================== -->
        <div class="row">

          <!-- Total registered -->
          <div class="col-12 col-sm-6 col-xl mb-3">
            <div class="small-box bg-primary stat-card">
              <div class="inner">
                <h3 id="total-count" data-value="<?= $totalRegistered ?>">0</h3>
                <p><i class="fas fa-users mr-1"></i>ผู้มาขึ้นทะเบียนว่างงานทั้งหมด</p>
              </div>
              <div class="icon"><i class="fas fa-id-card-alt"></i></div>
              <a href="list.php" class="small-box-footer">
                ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <!-- Total check-in -->
          <div class="col-12 col-sm-6 col-xl mb-3">
            <div class="small-box bg-teal stat-card">
              <div class="inner">
                <h3 id="checkin-count" data-value="<?= $totalCheckin ?>">0</h3>
                <p><i class="fas fa-clipboard-check mr-1"></i>ผู้มารายงานตัวว่างงานทั้งหมด</p>
              </div>
              <div class="icon"><i class="fas fa-user-check"></i></div>
              <a href="checkin.php" class="small-box-footer">
                ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <!-- Registered this month -->
          <div class="col-12 col-sm-6 col-xl mb-3">
            <div class="small-box bg-success stat-card">
              <div class="inner">
                <h3><?= number_format($registeredThisMonth) ?></h3>
                <p><i class="fas fa-calendar-check mr-1"></i>ผู้มาขึ้นทะเบียนว่างงานเดือนนี้</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-plus"></i></div>
              <a href="list.php?filter=month" class="small-box-footer">
                ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <!-- Check-in this month -->
          <div class="col-12 col-sm-6 col-xl mb-3">
            <div class="small-box bg-info stat-card">
              <div class="inner">
                <h3><?= number_format($checkinThisMonth) ?></h3>
                <p><i class="fas fa-calendar-check mr-1"></i>ผู้มารายงานว่างงานตัวเดือนนี้</p>
              </div>
              <div class="icon"><i class="fas fa-clipboard-list"></i></div>
              <a href="checkin.php?filter=month" class="small-box-footer">
                ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <!-- Got job -->
          <div class="col-12 col-sm-6 col-xl mb-3">
            <div class="small-box bg-warning stat-card">
              <div class="inner">
                <h3><?= number_format($gotJob) ?></h3>
                <p><i class="fas fa-briefcase mr-1"></i>ได้งานแล้ว</p>
              </div>
              <div class="icon"><i class="fas fa-handshake"></i></div>
              <a href="gotjob.php" class="small-box-footer">
                ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

          <!-- Got job this month -->
          <div class="col-12 col-sm-6 col-xl mb-3">
            <div class="small-box bg-orange stat-card">
              <div class="inner">
                <h3><?= number_format($gotJobThisMonth) ?></h3>
                <p><i class="fas fa-briefcase mr-1"></i>ผู้ได้งานแล้วประจำเดือนนี้</p>
              </div>
              <div class="icon"><i class="fas fa-trophy"></i></div>
              <a href="gotjob.php?filter=month" class="small-box-footer">
                ดูรายละเอียด <i class="fas fa-arrow-circle-right"></i>
              </a>
            </div>
          </div>

        </div><!-- /ROW 1 -->

        <!-- ==============================
             ROW 1B : DAILY LINE CHART
             ============================== -->
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card card-outline card-success">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title mr-3">
                  <i class="fas fa-chart-line mr-2 text-success"></i>จำนวนผู้มาขึ้นทะเบียนว่างงานประจำเดือน 
                  <span id="titleRegMonth"><?= getThaiMonthYear() ?></span>
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-success"></i></span>
                    </div>
                    <input type="text" id="filterDateFrom" class="form-control" style="width:155px;" readonly>
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-2">ถึง</span>
                    </div>
                    <input type="text" id="filterDateTo" class="form-control" style="width:155px;" readonly>
                    <div class="input-group-append">
                      <button id="btnApplyFilter" class="btn btn-success btn-sm">
                        <i class="fas fa-search mr-1"></i>แสดง
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-wrapper" style="min-height:300px;">
                  <canvas id="chartDailyReg"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div><!-- /ROW 1B -->

        <!-- ==============================
             ROW 1C : DAILY CHECKIN LINE CHART
             ============================== -->
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card card-outline card-teal">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title mr-3">
                  <i class="fas fa-chart-line mr-2 text-teal"></i>จำนวนผู้มารายงานตัวว่างงานประจำเดือน 
                  <span id="titleCiMonth"><?= getThaiMonthYear() ?></span>
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-white"><i class="far fa-calendar-alt text-teal"></i></span>
                    </div>
                    <input type="text" id="ciFilterDateFrom" class="form-control" style="width:155px;" readonly>
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-2">ถึง</span>
                    </div>
                    <input type="text" id="ciFilterDateTo" class="form-control" style="width:155px;" readonly>
                    <div class="input-group-append">
                      <button id="btnApplyCheckin" class="btn btn-teal btn-sm" style="background:#20c997;color:#fff;">
                        <i class="fas fa-search mr-1"></i>แสดง
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-wrapper" style="min-height:300px;">
                  <canvas id="chartDailyCheckin"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div><!-- /ROW 1C -->

        <!-- ==============================
             ROW 2 : DISTRICT CARDS + BAR CHART
             ============================== -->
        <div class="row" id="section-dist-reg">

          <!-- District mini cards -->
          <div class="col-12 col-xl-4 mb-3">
            <div class="card card-outline card-primary h-100">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-map-marker-alt mr-2 text-primary"></i>จำนวนผู้มาขึ้นทะเบียนว่างงานแยกตามเขต
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap mt-2 mt-sm-0" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <input type="text" id="distFilterDateFrom" class="form-control" style="width:100px;cursor:pointer;" placeholder="เริ่ม" readonly value="<?= htmlspecialchars($distDateFrom ?: '') ?>">
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-1">ถึง</span>
                    </div>
                    <input type="text" id="distFilterDateTo" class="form-control" style="width:100px;cursor:pointer;" placeholder="สิ้นสุด" readonly value="<?= htmlspecialchars($distDateTo ?: '') ?>">
                    <div class="input-group-append">
                      <button id="btnApplyDistFilter" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <?php foreach ($districtRows as $i => $row):
                  $ci  = ($i % 5) + 1;
                  $col = $districtColors[$i % 5];
                ?>
                <div class="district-card c<?= $ci ?> card mb-2">
                  <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                      <div class="district-name"><?= htmlspecialchars($row['KName']) ?></div>
                      <div class="district-count <?= $col['class'] ?>"
                           <?= $col['class'] ? '' : 'style="color:' . $col['hex'] . ';"' ?>
                           id="district-count-<?= $i ?>">0</div>
                    </div>
                    <i class="fas fa-map-marker-alt fa-2x <?= $col['class'] ?> opacity-25"
                       <?= $col['class'] ? '' : 'style="color:' . $col['hex'] . ';opacity:.25;"' ?>></i>
                  </div>
                </div>
                <?php endforeach; ?>

                <div class="d-flex align-items-center justify-content-between px-2 pt-2 border-top mt-1">
                  <span class="font-weight-bold text-dark" style="font-size:.9rem;">
                    <i class="fas fa-users mr-1 text-primary"></i>รวม<?= ($distDateFrom && $distDateTo) ? ' (ในช่วงที่เลือก)' : 'ทั้งหมด' ?>
                  </span>
                  <span class="font-weight-bold text-primary" style="font-size:1.2rem;">
                    <?= number_format($totalRegisteredInPeriod) ?> <small class="text-muted" style="font-size:.75rem;">ราย</small>
                  </span>
                </div>
              </div>
            </div>
          </div><!-- /district cards -->

          <!-- Bar chart -->
          <div class="col-12 col-xl-8 mb-3">
            <div class="card card-outline card-primary h-100">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-chart-bar mr-2 text-primary"></i>กราฟผู้ลงทะเบียนว่างงานแยกตามเขต
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap mt-2 mt-sm-0" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <input type="text" id="gDistFilterDateFrom" class="form-control" style="width:100px;cursor:pointer;" placeholder="เริ่ม" readonly value="<?= htmlspecialchars($distDateFrom ?: '') ?>">
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-1">ถึง</span>
                    </div>
                    <input type="text" id="gDistFilterDateTo" class="form-control" style="width:100px;cursor:pointer;" placeholder="สิ้นสุด" readonly value="<?= htmlspecialchars($distDateTo ?: '') ?>">
                    <div class="input-group-append">
                      <button id="btnApplyGDistFilter" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-wrapper">
                  <canvas id="chartDistrict"></canvas>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /ROW 2 -->

        <!-- ==============================
             ROW 2B : CHECK-IN BY DISTRICT
             ============================== -->
        <div class="row" id="section-dist-ci">

          <!-- Check-in district mini cards -->
          <div class="col-12 col-xl-4 mb-3">
            <div class="card card-outline card-teal h-100">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-clipboard-check mr-2 text-teal"></i>จำนวนผู้มารายงานตัวว่างงานแยกตามเขต
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap mt-2 mt-sm-0" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <input type="text" id="ciDistFilterDateFrom" class="form-control" style="width:100px;cursor:pointer;" placeholder="เริ่ม" readonly value="<?= htmlspecialchars($ciDistFrom ?: '') ?>">
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-1">ถึง</span>
                    </div>
                    <input type="text" id="ciDistFilterDateTo" class="form-control" style="width:100px;cursor:pointer;" placeholder="สิ้นสุด" readonly value="<?= htmlspecialchars($ciDistTo ?: '') ?>">
                    <div class="input-group-append">
                      <button id="btnApplyCiDistFilter" class="btn btn-teal btn-sm" style="background:#20c997;color:#fff;">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <?php foreach ($checkinDistrictRows as $i => $row):
                  $ci  = ($i % 5) + 1;
                  $col = $districtColors[$i % 5];
                ?>
                <div class="district-card c<?= $ci ?> card mb-2">
                  <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                      <div class="district-name"><?= htmlspecialchars($row['KName']) ?></div>
                      <div class="district-count <?= $col['class'] ?>"
                           <?= $col['class'] ? '' : 'style="color:' . $col['hex'] . ';"' ?>
                           id="checkin-district-<?= $i ?>">0</div>
                    </div>
                    <i class="fas fa-user-check fa-2x <?= $col['class'] ?>"
                       style="opacity:.2;<?= $col['class'] ? '' : 'color:' . $col['hex'] . ';' ?>"></i>
                  </div>
                </div>
                <?php endforeach; ?>

                <div class="d-flex align-items-center justify-content-between px-2 pt-2 border-top mt-1">
                  <span class="font-weight-bold text-dark" style="font-size:.9rem;">
                    <i class="fas fa-clipboard-check mr-1 text-teal"></i>รวม<?= ($ciDistFrom && $ciDistTo) ? ' (ในช่วงที่เลือก)' : 'ทั้งหมด' ?>
                  </span>
                  <span class="font-weight-bold text-teal" style="font-size:1.2rem;">
                    <?= number_format($totalCheckinInPeriod) ?> <small class="text-muted" style="font-size:.75rem;">ครั้ง</small>
                  </span>
                </div>
              </div>
            </div>
          </div><!-- /checkin district cards -->
<!-- Check-in bar chart -->
<div class="col-12 col-xl-8 mb-3">
  <div class="card card-outline card-teal h-100">
    <div class="card-header d-flex flex-wrap align-items-center">
      <h3 class="card-title">
        <i class="fas fa-chart-bar mr-2 text-teal"></i>กราฟผู้มารายงานตัวเปรียบเทียบกับผู้มาลงทะเบียนแยกตามเขต
      </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap mt-2 mt-sm-0" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <input type="text" id="gCiDistFilterDateFrom" class="form-control" style="width:100px;cursor:pointer;" placeholder="เริ่ม" readonly value="<?= htmlspecialchars($ciDistFrom ?: '') ?>">
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-1">ถึง</span>
                    </div>
                    <input type="text" id="gCiDistFilterDateTo" class="form-control" style="width:100px;cursor:pointer;" placeholder="สิ้นสุด" readonly value="<?= htmlspecialchars($ciDistTo ?: '') ?>">
                    <div class="input-group-append">
                      <button id="btnApplyGCiDistFilter" class="btn btn-teal btn-sm" style="background:#20c997;color:#fff;">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-wrapper">
                  <canvas id="chartCheckinDistrict"></canvas>
                </div>
              </div>
            </div>
          </div>

        </div><!-- /ROW 2B -->

        <!-- ==============================
             ROW 3 : REASON PROGRESS + DOUGHNUT
             ============================== -->
        <div class="row" id="section-reason">

          <!-- Doughnut chart -->
          <div class="col-12 col-xl-5 mb-3">
            <div class="card card-outline card-danger h-100">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-chart-pie mr-2 text-danger"></i>สัดส่วนสาเหตุออกจากงาน<?= ($distDateFrom && $distDateTo) ? ' (ในช่วงที่เลือก)' : '' ?>
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap mt-2 mt-sm-0" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <input type="text" id="qFilterDateFrom" class="form-control" style="width:100px;cursor:pointer;" placeholder="เริ่ม" readonly value="<?= htmlspecialchars($distDateFrom ?: '') ?>">
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-1">ถึง</span>
                    </div>
                    <input type="text" id="qFilterDateTo" class="form-control" style="width:100px;cursor:pointer;" placeholder="สิ้นสุด" readonly value="<?= htmlspecialchars($distDateTo ?: '') ?>">
                    <div class="input-group-append">
                      <button id="btnApplyQFilter" class="btn btn-danger btn-sm">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-wrapper">
                  <canvas id="chartReason"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress bars -->
          <div class="col-12 col-xl-7 mb-3">
            <div class="card card-outline card-danger h-100">
              <div class="card-header d-flex flex-wrap align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-list-ol mr-2 text-danger"></i>สถานะการได้งาน
                </h3>
                <div class="d-flex align-items-center ml-auto no-print flex-wrap mt-2 mt-sm-0" style="gap:.5rem;">
                  <div class="input-group input-group-sm">
                    <input type="text" id="rFilterDateFrom" class="form-control" style="width:100px;cursor:pointer;" placeholder="เริ่ม" readonly value="<?= htmlspecialchars($ciDistFrom ?: '') ?>">
                    <div class="input-group-prepend input-group-append">
                      <span class="input-group-text bg-white px-1">ถึง</span>
                    </div>
                    <input type="text" id="rFilterDateTo" class="form-control" style="width:100px;cursor:pointer;" placeholder="สิ้นสุด" readonly value="<?= htmlspecialchars($ciDistTo ?: '') ?>">
                    <div class="input-group-append">
                      <button id="btnApplyRFilter" class="btn btn-danger btn-sm">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body" id="reason-progress-list">
                <!-- Built by dashboard.js -->
              </div>
            </div>
          </div>

        </div><!-- /ROW 3 -->


      </div><!-- /container-fluid -->
    </section>
  </div><!-- /content-wrapper -->

  <!-- ===== Footer ===== -->
  <footer class="main-footer">
    <strong>สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</strong>
    <div class="float-right d-none d-sm-inline-block">
      ระบบจัดการผู้ว่างงาน <b>v1.0</b>
    </div>
  </footer>

  <a href="#" id="backToTop" class="back-to-top-btn no-print" title="Back to Top">
    <i class="fas fa-arrow-up"></i>
  </a>

</div><!-- /wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
$(function() {
    flatpickr('#distFilterDateFrom', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#distFilterDateTo', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });

    flatpickr('#ciDistFilterDateFrom', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#ciDistFilterDateTo', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });

    // Graph Filters
    flatpickr('#gDistFilterDateFrom', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#gDistFilterDateTo', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#gCiDistFilterDateFrom', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#gCiDistFilterDateTo', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });

    // Bottom Row Filters
    flatpickr('#qFilterDateFrom', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#qFilterDateTo', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#rFilterDateFrom', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
    flatpickr('#rFilterDateTo', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        onReady: function(selectedDates, dateStr, instance) {
            $(instance.element).css('background-color', '#fff');
        }
    });
});

var phpDistrictData = <?= json_encode(array_map(function($i, $r) use ($districtColors) {
    $col = $districtColors[$i % 5];
    return ['label' => $r['KName'], 'count' => (int)$r['cnt'], 'color' => $col['hex']];
}, array_keys($districtRows), $districtRows), JSON_UNESCAPED_UNICODE) ?>;

var phpQuitData = <?= json_encode((function() use ($quitRows) {
    $colors = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c','#95a5a6'];
    return array_map(function($i, $r) use ($colors) {
        return ['label' => $r['QName'], 'count' => (int)$r['cnt'], 'color' => $colors[$i % count($colors)]];
    }, array_keys($quitRows), $quitRows);
})(), JSON_UNESCAPED_UNICODE) ?>;

var phpReasonData = <?= json_encode((function() use ($reasonRows) {
    $colors = ['#e74c3c','#3498db','#2ecc71','#27ae60','#f39c12','#9b59b6','#95a5a6'];
    return array_map(function($i, $r) use ($colors) {
        return ['jno' => (int)$r['JNo'], 'label' => $r['JName'], 'count' => (int)$r['cnt'], 'color' => $colors[$i % count($colors)]];
    }, array_keys($reasonRows), $reasonRows);
})(), JSON_UNESCAPED_UNICODE) ?>;

var totalRegisteredInPeriod = <?= $totalRegisteredInPeriod ?>;
var totalCheckinInPeriod    = <?= $totalCheckinInPeriod ?>;
var isRegisteredFiltered    = <?= ($distDateFrom && $distDateTo) ? 'true' : 'false' ?>;
var isCheckinFiltered       = <?= ($ciDistFrom && $ciDistTo) ? 'true' : 'false' ?>;

var phpCheckinDistrictData = <?= json_encode(array_map(function($i, $r) use ($districtColors) {
    $col = $districtColors[$i % 5];
    return ['label' => $r['KName'], 'count' => (int)$r['cnt'], 'color' => $col['rgba']];
}, array_keys($checkinDistrictRows), $checkinDistrictRows), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/dashboard.js?v=<?= time() ?>"></script>
</body>
</html>