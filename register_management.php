<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user     = currentUser();
$pdo      = getDB();
$kateRows = $pdo->query("SELECT KNo, KName FROM kate ORDER BY KNo")->fetchAll();
$sexRows  = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll();
$titlesRows = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll();
$quitRows  = $pdo->query("SELECT QNo, QName FROM quit ORDER BY QNo")->fetchAll();
$staffRows = $pdo->query("SELECT StID, StName FROM staff ORDER BY StName")->fetchAll();
$eduRows  = $pdo->query("SELECT EqNo, EqName FROM educational_qualification ORDER BY EqNo")->fetchAll();
$potRows  = $pdo->query("SELECT PotNo, PotName FROM emp_position ORDER BY PotNo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายชื่อผู้ลงทะเบียน | Government Digital Service</title>

  <!-- Fonts: Modern Thai GovTech Stack -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
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

    .nav-tabs-gov {
      border-bottom: 2px solid var(--gov-gray);
      padding: 0 1.5rem;
    }
    .nav-tabs-gov .nav-link {
      border: none;
      color: var(--gov-text-muted);
      padding: 1rem 1.5rem;      font-weight: 500;
      position: relative;
    }
    .nav-tabs-gov .nav-link.active {
      color: var(--gov-navy);
      background: transparent;
    }
    .nav-tabs-gov .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 3px;
      background-color: var(--gov-royal);
    }

    .table thead th {
      background-color: var(--gov-gray);
      color: var(--gov-navy);
      font-weight: 600;
      border-bottom: 2px solid var(--gov-border);
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.025em;
      padding: 1rem;
    }
    .table td {
      padding: 1rem;
      vertical-align: middle;
      border-top: 1px solid var(--gov-gray);
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--gov-royal) !important;
      color: white !important;
      border: none !important;
      border-radius: 4px;
    }
    .dataTables_filter input {
      height: auto !important;
      padding: 0.4rem 0.75rem !important;
      font-size: 1rem !important;
      border-radius: 6px !important;
      border: 1px solid var(--gov-border) !important;
      margin-left: 0.5rem !important;
    }
    .dataTables_length select {
      height: auto !important;
      padding: 0.4rem 2rem 0.4rem 0.75rem !important;
      font-size: 1rem !important;
      border-radius: 6px !important;
      border: 1px solid var(--gov-border) !important;
    }

    .btn-action {
      width: 32px;
      height: 32px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: all 0.2s;
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

    /* Select2 Gov Customization */
    .select2-container--bootstrap4 .select2-selection {
      border-radius: 8px;
      border: 1px solid var(--gov-border);
      min-height: calc(1.5em + 1.1rem + 2px);
      padding: 0.375rem 0.75rem;
      display: flex;
      align-items: center;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
      padding-left: 0;
      line-height: 1.5;
      color: var(--gov-text-dark);
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
      height: 100%;
      top: 0;
    }
    .select2-container--bootstrap4.select2-container--focus .select2-selection {
      border-color: var(--gov-royal);
      box-shadow: 0 0 0 3px rgba(0, 94, 184, 0.15);
    }
    .input-group > .select2-container--bootstrap4 {
      flex: 1 1 auto;
      width: auto !important;
    }
    .select2-dropdown {
      border-radius: 8px;
      border: 1px solid var(--gov-border);
      box-shadow: var(--gov-shadow);
      z-index: 1060;
    }
    .select2-search--dropdown .select2-search__field {
      border-radius: 6px;
      border: 1px solid var(--gov-border);
    }
    .select2-results__option {
      padding: 0.75rem 1rem;
    }
    .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] {
      background-color: var(--gov-navy);
    }

    .border-bottom-dotted {
      border-bottom: 1.5px dotted #666 !important;
    }

    @media (max-width: 768px) {
      .gov-page-title { font-size: 1.5rem; }
    }
  </style>
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
    <div class="gov-page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">รายชื่อผู้ลงทะเบียน</h1>
            <p class="mb-0 opacity-9">ระบบจัดการฐานข้อมูลผู้ประกันตนว่างงานและการลงทะเบียน</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-users-viewfinder fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid px-lg-5">

        <div class="gov-card">
          <div class="card-header p-0 border-bottom-0 bg-white">
            <ul class="nav nav-tabs nav-tabs-gov" id="regMgmtTabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="tab-emp-tab" data-toggle="tab" href="#tab-emp" role="tab">
                  <i class="fas fa-user-group mr-2"></i> ข้อมูลผู้ว่างงาน
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="tab-reg-tab" data-toggle="tab" href="#tab-reg" role="tab">
                  <i class="fas fa-file-invoice mr-2"></i> รายการลงทะเบียน
                </a>
              </li>
            </ul>
          </div>

          <div class="gov-card-body p-4">
            <div class="tab-content" id="regMgmtTabsContent">

              <!-- TAB: Employees -->
              <div class="tab-pane fade show active" id="tab-emp" role="tabpanel">
                <div class="table-responsive">
                  <table id="empTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th style="width: 50px;">ลำดับ</th>
                        <th>เลขบัตรประชาชน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th class="text-center">เพศ</th>
                        <th>เขตพื้นที่</th>
                        <th class="text-center" style="width: 80px;">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>

              <!-- TAB: Registrations -->
              <div class="tab-pane fade" id="tab-reg" role="tabpanel">
                <div class="table-responsive">
                  <table id="regTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th style="width: 50px;">ลำดับ</th>
                        <th>เลขบัตรประชาชน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th class="text-center">วันลงทะเบียน</th>
                        <th>เขตพื้นที่</th>
                        <th>สาเหตุที่ออก</th>
                        <th class="text-center" style="width: 80px;">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- ===== Modals: Modernized style ===== -->
  
  <!-- View Employee Modal -->
  <div class="modal fade" id="viewEmpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header bg-navy text-white py-3 px-4" style="border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white"><i class="fas fa-id-card-clip mr-2"></i> ข้อมูลผู้ลงทะเบียน</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <div id="viewEmpLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x text-royal"></i></div>
          <div id="viewEmpContent" class="d-none">
             <div class="row">
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เลขบัตรประชาชน</label>
                   <div id="vw-empid" class="h5 font-weight-bold text-navy text-monospace mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">ชื่อ-นามสกุล</label>
                   <div id="vw-empname" class="h5 font-weight-bold mb-0"></div>
                </div>
                <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เพศ</label>
                   <div id="vw-sex" class="mb-0"></div>
                </div>
                <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เขตพื้นที่</label>
                   <div id="vw-kate" class="mb-0"></div>
                </div>
                <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เบอร์โทรศัพท์</label>
                   <div id="vw-phone" class="mb-0"></div>
                </div>
                <div class="col-md-12 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">ที่อยู่ปัจจุบัน</label>
                   <div id="vw-address" class="mt-1"></div>
                </div>
                <div class="col-md-12 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">วุฒิการศึกษาล่าสุด</label>
                   <div id="vw-edu" class="mb-0"></div>
                </div>
             </div>

             <div class="gov-card shadow-none border mt-4">
                <div class="gov-card-header bg-navy text-white py-2" style="border-bottom: 2px solid var(--gov-gold) !important;">
                   <h6 class="gov-card-title mb-0 text-white" style="font-size: 1rem;"><i class="fas fa-history mr-2"></i> ประวัติการขึ้นทะเบียน</h6>
                </div>
                <div class="gov-card-body p-0">
                   <div class="table-responsive">
                      <table class="table table-sm table-striped mb-0">
                         <thead class="bg-white">
                            <tr>
                               <th class="text-center" style="width:120px">วันที่</th>
                               <th style="width:180px">เลขที่เอกสาร</th>
                               <th>สาเหตุที่ออกจากงาน</th>
                            </tr>
                         </thead>
                         <tbody id="vw-history-body">
                            <!-- JS loops through data here -->
                         </tbody>
                      </table>
                   </div>
                </div>
             </div>

             <div class="gov-card shadow-none border mt-4">
                <div class="gov-card-header bg-navy text-white py-2" style="border-bottom: 2px solid var(--gov-gold) !important;">
                   <h6 class="gov-card-title mb-0 text-white" style="font-size: 1rem;"><i class="fas fa-clipboard-check mr-2"></i> ประวัติการรายงานตัวว่างงาน</h6>
                </div>
                <div class="gov-card-body p-0">
                   <div class="table-responsive">
                      <table class="table table-sm table-striped mb-0">
                         <thead class="bg-white">
                            <tr>
                               <th class="text-center" style="width:120px">วันที่รายงาน</th>
                               <th>เขตพื้นที่</th>
                               <th>สาเหตุที่ออก</th>
                               <th>สถานะงาน</th>
                            </tr>
                         </thead>
                         <tbody id="vw-rep-history-body">
                            <!-- JS loops through data here -->
                         </tbody>
                      </table>
                   </div>
                </div>
             </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4">
          <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">ปิดหน้าต่าง</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Registration Modal -->
  <div class="modal fade" id="viewRegModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header bg-navy text-white py-3 px-4" style="border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white"><i class="fas fa-file-invoice mr-2"></i> รายละเอียดการลงทะเบียน</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <div id="viewRegLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x text-royal"></i></div>
          <div id="viewRegContent" class="d-none">
             <div class="row">
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เลขที่เอกสาร</label>
                   <div id="vr-docid" class="h5 font-weight-bold text-navy text-monospace mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">วันที่มาขึ้นทะเบียน</label>
                   <div id="vr-rdate" class="h5 font-weight-bold mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">ผู้ลงทะเบียน</label>
                   <div id="vr-empname" class="h6 mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">สาเหตุที่ออกจากงาน</label>
                   <div id="vr-quit" class="h6 text-danger mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">วุฒิการศึกษาล่าสุด</label>
                   <div id="vr-edu" class="h6 mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">ตำแหน่งล่าสุด</label>
                   <div id="vr-pot" class="h6 mb-0"></div>
                </div>
                <div class="col-md-12 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เจ้าหน้าที่ผู้บันทึก</label>
                   <div id="vr-staff" class="small text-muted mb-0"></div>
                </div>
             </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4">
          <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">ปิดหน้าต่าง</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Educational Qualification (Add) Modal for Management -->
  <div class="modal fade" id="eduModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4 bg-navy text-white">
          <h5 class="gov-card-title text-white">
            <i class="fas fa-graduation-cap mr-2"></i> เพิ่มวุฒิการศึกษาใหม่
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="eduModalForm" autocomplete="off">
          <div class="modal-body px-4 pb-4">
            <div class="form-group">
              <label class="form-label" for="modalEqName">ชื่อวุฒิการศึกษา <span class="text-danger">*</span></label>
              <input type="text" id="modalEqName" name="EqName" class="form-control" placeholder="เช่น ปริญญาตรี, ปวส. เทคโนโลยีสารสนเทศ" required>
            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary px-4" style="background-color: var(--gov-royal); border-color: var(--gov-royal); border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;" id="btnSaveModalEdu">บันทึกวุฒิการศึกษา</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Position (Add) Modal for Management -->
  <div class="modal fade" id="potModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4 bg-navy text-white">
          <h5 class="gov-card-title text-white">
            <i class="fas fa-briefcase mr-2"></i> เพิ่มตำแหน่งใหม่
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="potModalForm" autocomplete="off">
          <div class="modal-body px-4 pb-4">
            <div class="form-group">
              <label class="form-label" for="modalPotName">ชื่อตำแหน่ง <span class="text-danger">*</span></label>
              <input type="text" id="modalPotName" name="PotName" class="form-control" placeholder="เช่น พนักงานขาย, บัญชี, วิศวกรซอฟต์แวร์" required>
            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary px-4" style="background-color: var(--gov-royal); border-color: var(--gov-royal); border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;" id="btnSaveModalPot">บันทึกตำแหน่ง</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="main-footer border-top-0 bg-transparent text-center py-4">
    <div class="text-muted small">
      © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Develop By Nanthajd sawasri
    </div>
  </footer>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
  // Initialize Select2
  $('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
  });

  var thaiLang = {
    sProcessing: "กำลังประมวลผล...",
    sLengthMenu: "แสดง _MENU_ รายการ",
    sZeroRecords: "ไม่พบข้อมูล",
    sInfo: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
    sSearch: "ค้นหา:",
    oPaginate: { sFirst: "หน้าแรก", sPrevious: "ก่อนหน้า", sNext: "ถัดไป", sLast: "หน้าสุดท้าย" }
  };

  function escapeHtml(s) { return $('<div>').text(s == null ? '' : s).html(); }

  function dashIfEmpty(s) {
    s = (s == null ? '' : String(s)).trim();
    return s === '' ? '<span class="text-muted">—</span>' : escapeHtml(s);
  }

  function formatThaiDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '';
    var parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    var year = parseInt(parts[0]) + 543;
    var month = parseInt(parts[1]);
    var day = parseInt(parts[2]);
    var monthNames = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return day + ' ' + monthNames[month] + ' ' + year;
  }

  // Employee DataTable
  var dtEmp = $('#empTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: 'api/employee_list.php', type: 'GET' },
    language: thaiLang,
    columns: [
      {
        data: null,
        className: 'text-center',
        orderable: false,
        render: function(data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        }
      },
      { data: 'EmpID', render: function(d) { return '<span class="text-monospace font-weight-bold text-navy">' + d + '</span>'; } },
      { data: 'EmpName' },
      { data: 'SexName', className: 'text-center' },
      { data: 'KName' },
      {
        data: null,
        className: 'text-center',
        orderable: false,
        width: '80px',
        render: function(data, type, row) {
          return '<button class="btn btn-sm btn-info" data-action="view" data-id="' + encodeURIComponent(row.EmpID) + '" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>';
        }
      }
    ]
  });

  // Registration DataTable
  var dtReg = $('#regTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: 'api/register_list.php', type: 'GET' },
    language: thaiLang,
    order: [[3, 'desc']],
    columns: [
      {
        data: null,
        className: 'text-center',
        orderable: false,
        render: function(data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        }
      },
      { data: 'EmpID', className: 'text-monospace' },
      { data: 'EmpName' },
      { data: 'RDate', className: 'text-center' },
      { data: 'KName' },
      { data: 'QName' },
      {
        data: null,
        className: 'text-center',
        orderable: false,
        width: '80px',
        render: function(data, type, row) {
          return '<button class="btn btn-sm btn-info" data-action="view" data-doc="' + encodeURIComponent(row.DocNo) + '" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>';
        }
      }
    ]
  });

  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    dtEmp.columns.adjust();
    dtReg.columns.adjust();
  });

  // --- Employee Handlers ---
  $('#empTable tbody').on('click', 'button[data-action="view"]', function() {
    var id = decodeURIComponent($(this).data('id'));
    $('#viewEmpLoading').removeClass('d-none');
    $('#viewEmpContent').addClass('d-none');
    $('#viewEmpModal').modal('show');
    $.getJSON('api/employee_detail.php', { id: id }).done(function(res) {
      if(res.success) {
        var d = res.data;
        $('#vw-empid').text(d.EmpID);
        $('#vw-empname').text((d.TitleName ? d.TitleName + ' ' : '') + d.EmpName);
        $('#vw-sex').text(d.SexName || '—');
        $('#vw-kate').text(d.KName || '—');
        $('#vw-phone').text(d.Phone || '—');
        $('#vw-address').text(d.Address || '—');
        $('#vw-edu').text((d.latest_education && d.latest_education.EqName) ? d.latest_education.EqName : '—');

        // Populate history
        var $hist = $('#vw-history-body').empty();
        if (d.history && d.history.length > 0) {
          d.history.forEach(function(h) {
            var docParts = (h.DocID || '').split('/');
            var seq = docParts.length > 1 ? docParts[1] : h.DocID;
            $hist.append(
              '<tr>' +
              '<td class="text-center">' + dashIfEmpty(formatThaiDate(h.RDate)) + '</td>' +
              '<td class="font-weight-bold text-navy" style="font-size: 1.1rem;">' + seq + '</td>' +
              '<td>' + (h.QName || '—') + '</td>' +
              '</tr>'
            );
          });
        } else {
          $hist.append('<tr><td colspan="3" class="text-center text-muted py-3">ไม่พบประวัติการขึ้นทะเบียน</td></tr>');
        }

        // Populate reporting history
        var $repHist = $('#vw-rep-history-body').empty();
        if (d.reporting_history && d.reporting_history.length > 0) {
          d.reporting_history.forEach(function(rh) {
            $repHist.append(
              '<tr>' +
              '<td class="text-center">' + dashIfEmpty(formatThaiDate(rh.RDate)) + '</td>' +
              '<td>' + dashIfEmpty(rh.KName) + '</td>' +
              '<td>' + dashIfEmpty(rh.QName) + '</td>' +
              '<td>' + dashIfEmpty(rh.JName) + '</td>' +
              '</tr>'
            );
          });
        } else {
          $repHist.append('<tr><td colspan="4" class="text-center text-muted py-3">ไม่พบประวัติการรายงานตัว</td></tr>');
        }

        $('#viewEmpLoading').addClass('d-none');
        $('#viewEmpContent').removeClass('d-none');
      }
    });
  });

  // --- Registration Handlers ---
  $('#regTable tbody').on('click', 'button[data-action="view"]', function() {
    var doc = $(this).data('doc');
    $('#viewRegLoading').removeClass('d-none');
    $('#viewRegContent').addClass('d-none');
    $('#viewRegModal').modal('show');
    $.getJSON('api/register_detail.php', { doc: doc }).done(function(res) {
      if(res.success) {
        var d = res.data;
        $('#vr-docid').text(d.DocID);
        $('#vr-rdate').text(formatThaiDate(d.RDate));
        $('#vr-empname').text((d.TitleName ? d.TitleName + ' ' : '') + d.EmpName);
        $('#vr-quit').text(d.QName);
        $('#vr-edu').text(d.EqName || '—');
        $('#vr-pot').text(d.PotName || '—');
        $('#vr-staff').text(d.StName + ' (' + d.StID + ')');
        $('#viewRegLoading').addClass('d-none');
        $('#viewRegContent').removeClass('d-none');
      }
    });
  });

  // Educational Qualification Modal for Management
  $('#btnAddEduMgmt').on('click', function() {
    $('#eduModalForm')[0].reset();
    $('#eduModal').modal('show');
  });

  $('#eduModalForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $('#btnSaveModalEdu');
    var eqName = $('#modalEqName').val().trim();
    if (eqName === '') return;

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');
    
    $.ajax({
      url: 'api/edu_add.php',
      type: 'POST',
      dataType: 'json',
      data: { EqName: eqName }
    })
    .done(function(res) {
      if (res && res.success) {
        var newOption = new Option(res.data.EqName, res.data.EqNo, true, true);
        $('#editRegEqNo').append(newOption).trigger('change');
        $('#eduModal').modal('hide');
        alert('เพิ่มวุฒิการศึกษาเรียบร้อย');
      } else {
        alert(res.message || 'กรุณาลองใหม่อีกครั้ง');
      }
    })
    .fail(function() {
      alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    })
    .always(function() {
      $btn.prop('disabled', false).html('บันทึกวุฒิการศึกษา');
    });
  });

  // Position Modal for Management
  $('#btnAddPotMgmt').on('click', function() {
    $('#potModalForm')[0].reset();
    $('#potModal').modal('show');
  });

  $('#potModalForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $('#btnSaveModalPot');
    var potName = $('#modalPotName').val().trim();
    if (potName === '') return;

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');
    
    $.ajax({
      url: 'api/pot_add.php',
      type: 'POST',
      dataType: 'json',
      data: { PotName: potName }
    })
    .done(function(res) {
      if (res && res.success) {
        var newOption = new Option(res.data.PotName, res.data.PotNo, true, true);
        $('#editRegPotNo').append(newOption).trigger('change');
        $('#potModal').modal('hide');
        alert('เพิ่มตำแหน่งเรียบร้อย');
      } else {
        alert(res.message || 'กรุณาลองใหม่อีกครั้ง');
      }
    })
    .fail(function() {
      alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
    })
    .always(function() {
      $btn.prop('disabled', false).html('บันทึกตำแหน่ง');
    });
  });

});
</script>
</body>
</html>
