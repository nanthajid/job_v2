<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user     = currentUser();
$pdo      = getDB();
$kateRows = $pdo->query("SELECT KNo, KName FROM kate ORDER BY KNo")->fetchAll();
$sexRows  = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll();
$titlesRows = $pdo->query("SELECT DocNo, Title FROM titles ORDER BY DocNo")->fetchAll();
$quitRows  = $pdo->query("SELECT QNo, QName FROM quit ORDER BY QNo")->fetchAll();
$staftRows = $pdo->query("SELECT StID, StName FROM staft ORDER BY StName")->fetchAll();
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
      padding: 1rem 1.5rem;
      font-weight: 500;
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
              <small class="text-muted"><?= htmlspecialchars($user['StPost'] ?: 'เจ้าหน้าที่') ?></small>
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
                        <th>เลขบัตรประชาชน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th class="text-center">เพศ</th>
                        <th>เขตพื้นที่</th>
                        <th class="text-center no-print">จัดการ</th>
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
                        <th>เลขที่เอกสาร</th>
                        <th>เลขบัตรประชาชน</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th class="text-center">วันลงทะเบียน</th>
                        <th>เขตพื้นที่</th>
                        <th>สาเหตุที่ออก</th>
                        <th class="text-center no-print">จัดการ</th>
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
                <div class="col-md-6 mb-3">
                   <label class="small text-muted mb-0">เลขบัตรประชาชน</label>
                   <div id="vw-empid" class="h5 font-weight-bold text-navy text-monospace"></div>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="small text-muted mb-0">ชื่อ-นามสกุล</label>
                   <div id="vw-empname" class="h5 font-weight-bold"></div>
                </div>
                <div class="col-md-4 mb-3">
                   <label class="small text-muted mb-0">เพศ</label>
                   <div id="vw-sex"></div>
                </div>
                <div class="col-md-4 mb-3">
                   <label class="small text-muted mb-0">เขตพื้นที่</label>
                   <div id="vw-kate"></div>
                </div>
                <div class="col-md-4 mb-3">
                   <label class="small text-muted mb-0">เบอร์โทรศัพท์</label>
                   <div id="vw-phone"></div>
                </div>
                <div class="col-md-12">
                   <label class="small text-muted mb-0">ที่อยู่ปัจจุบัน</label>
                   <div id="vw-address" class="border rounded p-3 bg-light mt-1"></div>
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
      </div>
    </div>
  </div>

  <!-- Edit Employee Modal -->
  <div class="modal fade" id="editEmpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4">
          <h5 class="gov-card-title text-navy"><i class="fas fa-user-pen mr-2"></i> แก้ไขข้อมูลผู้ลงทะเบียน</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <form id="editEmpForm">
          <div class="modal-body px-4 pb-4">
             <div id="editEmpAlert" class="alert alert-danger d-none"></div>
             <div class="row">
                <div class="col-md-6 mb-3">
                   <label class="form-label">เลขบัตรประชาชน</label>
                   <input type="text" id="editEmpID" name="EmpID" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">คำนำหน้า</label>
                   <div class="d-flex pt-2">
                      <?php foreach ($titlesRows as $t): ?>
                        <div class="custom-control custom-radio custom-gov-radio mr-3">
                          <input type="radio" id="edit_title_<?= $t['DocNo'] ?>" name="Titles" class="custom-control-input" value="<?= $t['DocNo'] ?>" required>
                          <label class="custom-control-label" for="edit_title_<?= $t['DocNo'] ?>"><?= $t['Title'] ?></label>
                        </div>
                      <?php endforeach; ?>
                   </div>
                </div>
                <div class="col-md-12 mb-3">
                   <label class="form-label">ชื่อ-นามสกุล</label>
                   <input type="text" id="editEmpName" name="EmpName" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">เขตพื้นที่</label>
                   <select id="editKNo" name="KNo" class="form-control" style="height: auto; font-size: 1rem; padding: 0.75rem 1rem;" required>
                     <?php foreach ($kateRows as $r): ?>
                       <option value="<?= $r['KNo'] ?>"><?= $r['KName'] ?></option>
                     <?php endforeach; ?>
                   </select>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">เพศ</label>
                   <div class="d-flex pt-2">
                     <?php foreach ($sexRows as $s): ?>
                       <div class="custom-control custom-radio custom-gov-radio mr-4">
                         <input type="radio" id="edit_sex_<?= $s['SexNo'] ?>" name="SexNo" class="custom-control-input" value="<?= $s['SexNo'] ?>" required>
                         <label class="custom-control-label" for="edit_sex_<?= $s['SexNo'] ?>"><?= $s['SexName'] ?></label>
                       </div>
                     <?php endforeach; ?>
                   </div>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">เบอร์โทรศัพท์</label>
                   <input type="text" id="editPhone" name="Phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">Line ID</label>
                   <input type="text" id="editLineID" name="lineID" class="form-control">
                </div>
                <div class="col-md-12">
                   <label class="form-label">ที่อยู่</label>
                   <textarea id="editAddress" name="Address" class="form-control" rows="3"></textarea>
                </div>
             </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-gov-outline" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary px-4" style="background-color: var(--gov-royal); border-color: var(--gov-royal); border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;">บันทึกการแก้ไข</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal (Employee) -->
  <div class="modal fade" id="deleteEmpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4 bg-danger text-white">
          <h5 class="gov-card-title text-white"><i class="fas fa-trash-can mr-2"></i> ยืนยันการลบข้อมูล</h5>
        </div>
        <div class="modal-body p-4 text-center">
           <div class="mb-3"><i class="fas fa-circle-exclamation fa-4x text-danger opacity-5"></i></div>
           <p>คุณต้องการลบข้อมูลของ <strong id="del-empname"></strong> ใช่หรือไม่?</p>
           <p class="small text-danger">ข้อมูลการลงทะเบียนและการรายงานตัวทั้งหมดของบุคคลนี้จะถูกลบถาวร</p>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4 justify-content-center">
          <button type="button" class="btn btn-gov-outline" data-dismiss="modal">ยกเลิก</button>
          <button type="button" class="btn btn-danger px-4" id="deleteEmpConfirm">ยืนยันลบข้อมูล</button>
        </div>
      </div>
    </div>
  </div>

  <!-- View Registration Modal -->
  <div class="modal fade" id="viewRegModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4">
          <h5 class="gov-card-title text-navy"><i class="fas fa-file-invoice mr-2"></i> รายละเอียดการลงทะเบียน</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <div id="viewRegLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x text-royal"></i></div>
          <div id="viewRegContent" class="d-none">
             <div class="row">
                <div class="col-md-6 mb-3">
                   <label class="small text-muted mb-0">เลขที่เอกสาร</label>
                   <div id="vr-docid" class="h5 font-weight-bold text-navy text-monospace"></div>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="small text-muted mb-0">วันที่มาขึ้นทะเบียน</label>
                   <div id="vr-rdate" class="h5 font-weight-bold"></div>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="small text-muted mb-0">ผู้ลงทะเบียน</label>
                   <div id="vr-empname" class="h6"></div>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="small text-muted mb-0">สาเหตุที่ออกจากงาน</label>
                   <div id="vr-quit" class="h6 text-danger"></div>
                </div>
                <div class="col-md-12 mb-3">
                   <label class="small text-muted mb-0">เจ้าหน้าที่ผู้บันทึก</label>
                   <div id="vr-staff" class="small text-muted"></div>
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Registration Modal -->
  <div class="modal fade" id="editRegModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4">
          <h5 class="gov-card-title text-navy"><i class="fas fa-file-pen mr-2"></i> แก้ไขข้อมูลการลงทะเบียน</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <form id="editRegForm">
          <input type="hidden" id="editRegDocNo" name="DocNo">
          <div class="modal-body px-4 pb-4">
             <div id="editRegAlert" class="alert alert-danger d-none"></div>
             <div class="row">
                <div class="col-md-6 mb-3">
                   <label class="form-label">เลขที่เอกสาร</label>
                   <input type="text" id="editRegDocID" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">วันที่มาขึ้นทะเบียน <span class="text-danger">*</span></label>
                   <input type="text" id="editRegRDate" name="RDate" class="form-control" required readonly>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">เขตพื้นที่ <span class="text-danger">*</span></label>
                   <select id="editRegKNo" name="KNo" class="form-control" style="height: auto; font-size: 1rem; padding: 0.75rem 1rem;" required>
                     <?php foreach ($kateRows as $r): ?>
                       <option value="<?= $r['KNo'] ?>"><?= $r['KName'] ?></option>
                     <?php endforeach; ?>
                   </select>
                </div>
                <div class="col-md-6 mb-3">
                   <label class="form-label">เพศ <span class="text-danger">*</span></label>
                   <div class="d-flex pt-2">
                     <?php foreach ($sexRows as $s): ?>
                       <div class="custom-control custom-radio custom-gov-radio mr-4">
                         <input type="radio" id="edit_reg_sex_<?= $s['SexNo'] ?>" name="SexNo" class="custom-control-input" value="<?= $s['SexNo'] ?>" required>
                         <label class="custom-control-label" for="edit_reg_sex_<?= $s['SexNo'] ?>"><?= $s['SexName'] ?></label>
                       </div>
                     <?php endforeach; ?>
                   </div>
                </div>
                <div class="col-md-12 mb-3">
                   <label class="form-label">สาเหตุที่ออกจากงาน <span class="text-danger">*</span></label>
                   <div class="d-flex flex-wrap pt-2">
                     <?php foreach ($quitRows as $r): ?>
                       <div class="custom-control custom-radio custom-gov-radio mr-4 mb-2">
                         <input type="radio" id="edit_reg_quit_<?= $r['QNo'] ?>" name="QNo" class="custom-control-input" value="<?= $r['QNo'] ?>" required>
                         <label class="custom-control-label" for="edit_reg_quit_<?= $r['QNo'] ?>"><?= $r['QName'] ?></label>
                       </div>
                     <?php endforeach; ?>
                   </div>
                </div>
             </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-gov-outline" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary px-4" style="background-color: var(--gov-royal); border-color: var(--gov-royal); border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;">บันทึกการแก้ไข</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Registration Confirmation -->
  <div class="modal fade" id="deleteRegModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4 bg-danger text-white">
          <h5 class="gov-card-title text-white"><i class="fas fa-trash-can mr-2"></i> ยืนยันการลบเอกสาร</h5>
        </div>
        <div class="modal-body p-4 text-center">
           <p>คุณต้องการลบเอกสารเลขที่ <strong id="del-reg-docid"></strong> ใช่หรือไม่?</p>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4 justify-content-center">
          <button type="button" class="btn btn-gov-outline" data-dismiss="modal">ยกเลิก</button>
          <button type="button" class="btn btn-danger px-4" id="deleteRegConfirm">ยืนยันลบเอกสาร</button>
        </div>
      </div>
    </div>
  </div>

  <footer class="main-footer border-top-0 bg-transparent text-center py-4">
    <div class="text-muted small">
      © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Government Digital Service Platform
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

<script>
$(function () {
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
      { data: 'EmpID', render: function(d) { return '<span class="text-monospace font-weight-bold text-navy">' + d + '</span>'; } },
      { data: 'EmpName' },
      { data: 'SexName', className: 'text-center' },
      { data: 'KName' },
      {
        data: null,
        orderable: false,
        className: 'text-center no-print',
        render: function(row) {
          var id = encodeURIComponent(row.EmpID);
          return '<button class="btn btn-action btn-outline-info mr-1" data-action="view" data-id="'+id+'"><i class="fas fa-eye"></i></button>' +
                 '<button class="btn btn-action btn-outline-primary mr-1" data-action="edit" data-id="'+id+'"><i class="fas fa-pen"></i></button>' +
                 '<button class="btn btn-action btn-outline-danger" data-action="delete" data-id="'+id+'"><i class="fas fa-trash"></i></button>';
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
      { data: 'DocID', className: 'text-monospace font-weight-bold text-navy' },
      { data: 'EmpID', className: 'text-monospace' },
      { data: 'EmpName' },
      { data: 'RDate', className: 'text-center' },
      { data: 'KName' },
      { data: 'QName' },
      {
        data: null,
        orderable: false,
        className: 'text-center no-print',
        render: function(row) {
          return '<button class="btn btn-action btn-outline-info mr-1" data-action="view" data-doc="'+row.DocNo+'"><i class="fas fa-eye"></i></button>' +
                 '<button class="btn btn-action btn-outline-primary mr-1" data-action="edit" data-doc="'+row.DocNo+'"><i class="fas fa-pen"></i></button>' +
                 '<button class="btn btn-action btn-outline-danger" data-action="delete" data-doc="'+row.DocNo+'"><i class="fas fa-trash"></i></button>';
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

  $('#empTable tbody').on('click', 'button[data-action="edit"]', function() {
    var id = decodeURIComponent($(this).data('id'));
    $('#editEmpForm')[0].reset();
    $('#editEmpModal').modal('show');
    $.getJSON('api/employee_detail.php', { id: id }).done(function(res) {
      if(res.success) {
        var d = res.data;
        $('#editEmpID').val(d.EmpID);
        $('#editEmpName').val(d.EmpName);
        if(d.Titles) $('input[name="Titles"]', '#editEmpForm').filter('[value="'+d.Titles+'"]').prop('checked', true);
        if(d.SexNo) $('input[name="SexNo"]', '#editEmpForm').filter('[value="'+d.SexNo+'"]').prop('checked', true);
        $('#editKNo').val(d.KNo);
        $('#editPhone').val(d.Phone);
        $('#editLineID').val(d.lineID);
        $('#editAddress').val(d.Address);
      }
    });
  });

  $('#editEmpForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');
    $.ajax({ url: 'api/employee_update.php', type: 'POST', data: $(this).serialize(), dataType: 'json' })
    .done(function(res) {
       if(res.success) { $('#editEmpModal').modal('hide'); dtEmp.ajax.reload(null, false); dtReg.ajax.reload(null, false); }
       else { alert(res.message); }
    }).always(function() { $btn.prop('disabled', false).html(originalHtml); });
  });

  var currentDelId = null;
  $('#empTable tbody').on('click', 'button[data-action="delete"]', function() {
    var data = dtEmp.row($(this).closest('tr')).data();
    currentDelId = data.EmpID;
    $('#del-empname').text(data.EmpName);
    $('#deleteEmpModal').modal('show');
  });

  $('#deleteEmpConfirm').on('click', function() {
    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังลบ...');
    $.post('api/employee_delete.php', { EmpID: currentDelId }, function(res) {
      if(res.success) { $('#deleteEmpModal').modal('hide'); dtEmp.ajax.reload(); dtReg.ajax.reload(); }
      else { alert(res.message || 'ลบไม่สำเร็จ'); }
    }, 'json').always(function() { $btn.prop('disabled', false).html(originalHtml); });
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
        $('#vr-rdate').text(d.RDate);
        $('#vr-empname').text(d.EmpName);
        $('#vr-quit').text(d.QName);
        $('#vr-staff').text(d.StName + ' (' + d.StID + ')');
        $('#viewRegLoading').addClass('d-none');
        $('#viewRegContent').removeClass('d-none');
      }
    });
  });

  var editRegFp = flatpickr('#editRegRDate', { locale: 'th', dateFormat: 'Y-m-d' });

  $('#regTable tbody').on('click', 'button[data-action="edit"]', function() {
    var doc = $(this).data('doc');
    $('#editRegForm')[0].reset();
    $('#editRegModal').modal('show');
    $.getJSON('api/register_detail.php', { doc: doc }).done(function(res) {
      if(res.success) {
        var d = res.data;
        $('#editRegDocNo').val(d.DocNo);
        $('#editRegDocID').val(d.DocID);
        editRegFp.setDate(d.RDate);
        $('#editRegKNo').val(d.KNo);
        if(d.SexNo) $('input[name="SexNo"]', '#editRegForm').filter('[value="'+d.SexNo+'"]').prop('checked', true);
        if(d.QNo)   $('input[name="QNo"]', '#editRegForm').filter('[value="'+d.QNo+'"]').prop('checked', true);
      }
    });
  });

  $('#editRegForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');
    $.ajax({ url: 'api/register_update.php', type: 'POST', data: $(this).serialize(), dataType: 'json' })
    .done(function(res) {
       if(res.success) { $('#editRegModal').modal('hide'); dtReg.ajax.reload(null, false); }
       else { alert(res.message); }
    }).always(function() { $btn.prop('disabled', false).html(originalHtml); });
  });

  var currentDelDoc = null;
  $('#regTable tbody').on('click', 'button[data-action="delete"]', function() {
    var data = dtReg.row($(this).closest('tr')).data();
    currentDelDoc = data.DocNo;
    $('#del-reg-docid').text(data.DocID);
    $('#deleteRegModal').modal('show');
  });

  $('#deleteRegConfirm').on('click', function() {
    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังลบ...');
    $.post('api/register_delete.php', { DocNo: currentDelDoc }, function(res) {
      if(res.success) { $('#deleteRegModal').modal('hide'); dtReg.ajax.reload(); dtEmp.ajax.reload(); }
      else { alert(res.message || 'ลบไม่สำเร็จ'); }
    }, 'json').always(function() { $btn.prop('disabled', false).html(originalHtml); });
  });

});
</script>
</body>
</html>
