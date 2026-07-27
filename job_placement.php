<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user     = currentUser();
$pdo      = getDB();
$eduRows     = $pdo->query("SELECT EqNo, EqName FROM educational_qualification ORDER BY EqNo")->fetchAll();
$potRows     = $pdo->query("SELECT PotNo, PotName FROM emp_position ORDER BY PotNo")->fetchAll();
$svTypeRows  = $pdo->query("SELECT SvTypeID, SvTypeCode, SvTypeName FROM service_type ORDER BY SortOrder")->fetchAll();
$stfSvcRows  = $pdo->query("SELECT StfSvcID, StfSvcCode, StfSvcName FROM staff_service_type ORDER BY SortOrder")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>จัดการการบรรจุงาน | Government Digital Service</title>

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
      padding: 0.75rem 1rem;
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
      width: 36px;
      height: 36px;
      padding: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: all 0.2s;
    }

    .form-label {
      font-weight: 500;
      color: var(--gov-navy);
      margin-bottom: 0.5rem;
      display: block;
    }

    .form-control {
      border-radius: 8px;
      border: 1px solid var(--gov-border);
      padding: 0.75rem 1rem;
      height: auto;
      font-size: 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
      border-color: var(--gov-royal);
      box-shadow: 0 0 0 3px rgba(0, 94, 184, 0.15);
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


    .badge-income {
      background: #28a745;
      color: #fff;
      border-radius: 4px;
      padding: 0.3rem 0.6rem;
      font-size: 0.85rem;
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
      padding: 0.75rem;
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
            <h1 class="gov-page-title">จัดการการบรรจุงาน</h1>
            <p class="mb-0 opacity-9">ระบบบันทึกและจัดการข้อมูลผู้ได้รับการบรรจุงาน เข้าสู่สถานประกอบการหรือประกอบอาชีพอิสระ</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-user-check fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid px-lg-5">

        <div class="gov-card">
           <div class="gov-card-header d-flex align-items-center flex-wrap">
            <h3 class="gov-card-title"><i class="fas fa-list mr-2"></i> รายการบรรจุงาน</h3>
            <div class="ml-auto d-flex align-items-center flex-wrap">
              <!-- Monthly Report Module -->
              <div class="d-flex align-items-center mr-3" style="gap: 0.5rem;">
                <div class="input-group input-group-sm" style="width: 150px;">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; font-size: 0.8rem; color: var(--gov-navy);"><i class="far fa-calendar-alt mr-1"></i>จาก</span>
                  </div>
                  <input type="text" id="reportDateFrom" class="form-control form-control-sm border-left-0" placeholder="เลือกวันที่" readonly style="border-radius: 0 8px 8px 0; font-size: 0.85rem;">
                </div>
                <div class="input-group input-group-sm" style="width: 150px;">
                  <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0" style="border-radius: 8px 0 0 8px; font-size: 0.8rem; color: var(--gov-navy);"><i class="far fa-calendar-alt mr-1"></i>ถึง</span>
                  </div>
                  <input type="text" id="reportDateTo" class="form-control form-control-sm border-left-0" placeholder="เลือกวันที่" readonly style="border-radius: 0 8px 8px 0; font-size: 0.85rem;">
                </div>
                <button type="button" class="btn btn-sm px-3" id="btnGenPDF" style="background-color: #dc3545; color: white; border-radius: 8px; font-weight: 600; white-space: nowrap;">
                  <i class="fas fa-file-pdf mr-1"></i> คลินิกอาชีพ - แบบ4
                </button>
                <button type="button" class="btn btn-sm px-3" id="btnGenPDFForm3" style="background-color: #fd7e14; color: white; border-radius: 8px; font-weight: 600; white-space: nowrap;">
                  <i class="fas fa-file-pdf mr-1"></i> คลินิกอาชีพ - แบบ3
                </button>
              </div>
               <button type="button" class="btn btn-sm px-3" id="btnSummary" style="background-color: #6f42c1; color: white; border-radius: 8px; font-weight: 600; white-space: nowrap;">
                 <i class="fas fa-chart-bar mr-1"></i> สรุปจำนวนผู้รับบริการ
               </button>
               <button type="button" class="btn btn-primary px-4" id="btnAddJobPlacement" style="background-color: var(--gov-royal); border-color: var(--gov-royal); border-radius: 8px; padding: 0.6rem 1.5rem; font-weight: 600;">
                 <i class="fas fa-plus mr-2"></i> เพิ่มข้อมูลบรรจุงาน
               </button>
            </div>
          </div>

          <div class="gov-card-body p-4">
            <div class="table-responsive">
              <table id="jpTable" class="table table-hover w-100">
                <thead>
                  <tr>
                    <th style="width: 50px;">ลำดับ</th>
                    <th>เลขบัตรประชาชน</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th class="text-center">อายุ</th>
                    <th>สถานประกอบการ</th>
                    <th>ตำแหน่ง</th>
                    <th class="text-center">วันที่เริ่มงาน</th>
                    <th class="text-center" style="width: 100px;">จัดการ</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- ===== Modal: Add/Edit Job Placement ===== -->
  <div class="modal fade" id="jpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white" id="jpModalTitle" style="font-weight: 600;">
            <i class="fas fa-user-check mr-2"></i> เพิ่มข้อมูลการบรรจุงาน
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
        </div>
        <form id="jpForm" autocomplete="off">
          <input type="hidden" id="jpAction" value="add">
          <input type="hidden" id="jpNo" name="JPNo" value="">
          <div class="modal-body px-4 pt-4 pb-2" style="background: #F8FAFC;">
            <div id="jpFormAlert" class="alert alert-danger d-none mb-3" role="alert"></div>

            <div class="row">
              <!-- Left Column: Personal Information -->
              <div class="col-md-6">
                <div class="gov-card shadow-sm border mb-3" style="border-radius: 10px;">
                  <div class="gov-card-header d-flex align-items-center py-2 px-3" style="background: linear-gradient(135deg, #E8F0FE 0%, #F0F4FF 100%); border-bottom: 2px solid var(--gov-royal); border-radius: 10px 10px 0 0;">
                    <div style="width: 32px; height: 32px; background: var(--gov-royal); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                      <i class="fas fa-user text-white" style="font-size: 0.85rem;"></i>
                    </div>
                    <h6 class="gov-card-title mb-0" style="font-size: 0.95rem; font-weight: 600; color: var(--gov-navy);">ข้อมูลส่วนบุคคล</h6>
                  </div>
                  <div class="gov-card-body p-3">
                    <div class="form-group mb-3" style="position: relative;">
                      <label class="form-label mb-1" for="modalEmpID" style="font-size: 0.85rem;">เลขบัตรประจำตัวประชาชน <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-id-card text-muted"></i></span>
                        </div>
                        <input type="text" id="modalEmpID" name="EmpID" class="form-control border-left-0" maxlength="13" inputmode="numeric" placeholder="พิมพ์เลขบัตร" required style="border-radius: 0 8px 8px 0;">
                      </div>
                      <div id="empSuggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; display: none; z-index: 1061; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: -4px;"></div>
                    </div>
                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group mb-3">
                          <label class="form-label mb-1" for="modalTitle" style="font-size: 0.85rem;">คำนำหน้า</label>
                          <input type="text" id="modalTitle" name="Title" class="form-control" placeholder="เช่น นาย, นาง, นางสาว" style="border-radius: 8px;">
                        </div>
                      </div>
                      <div class="col-md-8">
                        <div class="form-group mb-3">
                          <label class="form-label mb-1" for="modalEmpName" style="font-size: 0.85rem;">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                          <input type="text" id="modalEmpName" name="EmpName" class="form-control" placeholder="กรอกชื่อ-นามสกุล" required style="border-radius: 8px;">
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        <div class="form-group mb-3">
                          <label class="form-label mb-1" for="modalAge" style="font-size: 0.85rem;">อายุ <span class="text-muted font-weight-normal">(ปี)</span></label>
                          <input type="number" id="modalAge" name="Age" class="form-control" min="15" max="120" placeholder="เช่น 25" style="border-radius: 8px;">
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="form-group mb-3">
                          <label class="form-label mb-1" style="font-size: 0.85rem;">เพศ</label>
                          <div class="d-flex" style="gap: 1.5rem; padding-top: 0.55rem;">
                            <div class="custom-control custom-radio">
                              <input type="radio" id="genderMale" name="Gender" class="custom-control-input" value="Male">
                              <label class="custom-control-label" for="genderMale">ชาย</label>
                            </div>
                            <div class="custom-control custom-radio">
                              <input type="radio" id="genderFemale" name="Gender" class="custom-control-input" value="Female">
                              <label class="custom-control-label" for="genderFemale">หญิง</label>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label mb-1" for="modalPhone" style="font-size: 0.85rem;">โทรศัพท์</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-phone-alt text-muted"></i></span>
                            </div>
                            <input type="text" id="modalPhone" name="Phone" class="form-control border-left-0" maxlength="15" placeholder="08XXXXXXXX" style="border-radius: 0 8px 8px 0;">
                          </div>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label mb-1" for="modalEduNo" style="font-size: 0.85rem;">วุฒิการศึกษา</label>
                      <select id="modalEduNo" name="EduNo" class="form-control select2" style="border-radius: 8px; width: 100%;">
                        <option value="">— เลือกวุฒิการศึกษา —</option>
                        <?php foreach ($eduRows as $r): ?>
                          <option value="<?= (int)$r['EqNo'] ?>"><?= htmlspecialchars($r['EqName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group mb-0">
                      <label class="form-label mb-1" for="modalAddress" style="font-size: 0.85rem;">ที่อยู่</label>
                      <textarea id="modalAddress" name="Address" class="form-control" rows="2" placeholder="ระบุที่อยู่ปัจจุบัน" style="border-radius: 8px; resize: none;"></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Job Information -->
              <div class="col-md-6">
                <div class="gov-card shadow-sm border mb-3" style="border-radius: 10px;">
                  <div class="gov-card-header d-flex align-items-center py-2 px-3" style="background: linear-gradient(135deg, #E8F8F0 0%, #F0FFF4 100%); border-bottom: 2px solid #28a745; border-radius: 10px 10px 0 0;">
                    <div style="width: 32px; height: 32px; background: #28a745; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                      <i class="fas fa-building text-white" style="font-size: 0.85rem;"></i>
                    </div>
                    <h6 class="gov-card-title mb-0" style="font-size: 0.95rem; font-weight: 600; color: #1B5E20;">ข้อมูลการบรรจุงาน</h6>
                  </div>
                  <div class="gov-card-body p-3">
                    <div class="form-group mb-3">
                      <label class="form-label mb-1" for="modalCompanyName" style="font-size: 0.85rem;">ชื่อสถานประกอบการ / ประกอบอาชีพอิสระ <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-industry text-muted"></i></span>
                        </div>
                        <input type="text" id="modalCompanyName" name="CompanyName" class="form-control border-left-0" placeholder="เช่น บริษัท ABC จำกัด" required style="border-radius: 0 8px 8px 0;">
                      </div>
                    </div>
                    <div class="form-group mb-3">
                      <label class="form-label mb-1" for="modalCompanyAddress" style="font-size: 0.85rem;">ที่อยู่สถานประกอบการ</label>
                      <textarea id="modalCompanyAddress" name="CompanyAddress" class="form-control" rows="2" placeholder="ระบุที่อยู่สถานประกอบการ" style="border-radius: 8px; resize: none;"></textarea>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        <div class="form-group mb-3">
                          <label class="form-label mb-1" for="modalPosition" style="font-size: 0.85rem;">ตำแหน่ง</label>
                          <select id="modalPosition" name="Position" class="form-control select2-position" style="border-radius: 8px; width: 100%;">
                            <option value="">— เลือกหรือพิมพ์ค้นหาตำแหน่ง —</option>
                            <?php foreach ($potRows as $r): ?>
                              <option value="<?= htmlspecialchars($r['PotName']) ?>"><?= htmlspecialchars($r['PotName']) ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="form-group mb-3">
                          <label class="form-label mb-1" for="modalStartDate" style="font-size: 0.85rem;">วันที่เริ่มงาน</label>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="far fa-calendar-alt text-navy"></i></span>
                            </div>
                            <input type="text" id="modalStartDate" name="StartDate" class="form-control border-left-0" placeholder="เลือกวันที่" readonly style="border-radius: 0 8px 8px 0;">
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        <div class="form-group mb-0">
                          <label class="form-label mb-1" for="modalIncomeDay" style="font-size: 0.85rem;">รายได้ต่อวัน <span class="text-muted font-weight-normal">(บาท)</span></label>
                          <div class="input-group">
                            <input type="number" id="modalIncomeDay" name="IncomeDay" class="form-control" min="0" step="0.01" placeholder="0.00" style="border-radius: 8px 0 0 8px;">
                            <div class="input-group-append">
                              <span class="input-group-text bg-light" style="border-radius: 0 8px 8px 0;">บาท</span>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="form-group mb-0">
                          <label class="form-label mb-1" for="modalIncomeMonth" style="font-size: 0.85rem;">รายได้ต่อเดือน <span class="text-muted font-weight-normal">(บาท)</span></label>
                          <div class="input-group">
                            <input type="number" id="modalIncomeMonth" name="IncomeMonth" class="form-control" min="0" step="0.01" placeholder="0.00" style="border-radius: 8px 0 0 8px;">
                            <div class="input-group-append">
                              <span class="input-group-text bg-light" style="border-radius: 0 8px 8px 0;">บาท</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Service Classification Card -->
                <div class="gov-card shadow-sm border mb-3" style="border-radius: 10px;">
                  <div class="gov-card-header d-flex align-items-center py-2 px-3" style="background: linear-gradient(135deg, #FFF9E6 0%, #FFFDF0 100%); border-bottom: 2px solid var(--gov-gold); border-radius: 10px 10px 0 0;">
                    <div style="width: 32px; height: 32px; background: var(--gov-gold); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                      <i class="fas fa-tags text-white" style="font-size: 0.85rem;"></i>
                    </div>
                    <h6 class="gov-card-title mb-0" style="font-size: 0.95rem; font-weight: 600; color: #5D4E37;">ประเภทและการให้บริการ</h6>
                  </div>
                  <div class="gov-card-body p-3">
                    <!-- Radio: ประเภทผู้รับบริการ (dynamic from service_type table) -->
                    <div class="mb-3">
                      <label class="form-label mb-2" style="font-size: 0.85rem;">ประเภทผู้รับบริการ</label>
                      <div class="d-flex flex-wrap">
                        <?php foreach ($svTypeRows as $svType): ?>
                        <div class="custom-control custom-radio custom-gov-radio mr-4 mb-2">
                          <input type="radio" id="svcType_<?= htmlspecialchars($svType['SvTypeCode']) ?>" name="ServiceType" class="custom-control-input" value="<?= htmlspecialchars($svType['SvTypeCode']) ?>">
                          <label class="custom-control-label" for="svcType_<?= htmlspecialchars($svType['SvTypeCode']) ?>"><?= htmlspecialchars($svType['SvTypeName']) ?></label>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    </div>

                    <!-- Checkbox: การให้บริการของเจ้าหน้าที่ (dynamic from staff_service_type table) -->
                    <div class="mb-0">
                      <label class="form-label mb-2" style="font-size: 0.85rem;">การให้บริการของเจ้าหน้าที่</label>
                      <div class="row">
                        <?php
                        $half = (int)ceil(count($stfSvcRows) / 2);
                        $col1 = array_slice($stfSvcRows, 0, $half);
                        $col2 = array_slice($stfSvcRows, $half);
                        ?>
                        <div class="col-md-6">
                          <?php foreach ($col1 as $stfSvc): ?>
                          <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" id="stfSvc_<?= htmlspecialchars($stfSvc['StfSvcCode']) ?>" name="StaffService[]" class="custom-control-input" value="<?= htmlspecialchars($stfSvc['StfSvcCode']) ?>">
                            <label class="custom-control-label" for="stfSvc_<?= htmlspecialchars($stfSvc['StfSvcCode']) ?>"><?= htmlspecialchars($stfSvc['StfSvcName']) ?></label>
                          </div>
                          <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                          <?php foreach ($col2 as $stfSvc): ?>
                          <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" id="stfSvc_<?= htmlspecialchars($stfSvc['StfSvcCode']) ?>" name="StaffService[]" class="custom-control-input" value="<?= htmlspecialchars($stfSvc['StfSvcCode']) ?>">
                            <label class="custom-control-label" for="stfSvc_<?= htmlspecialchars($stfSvc['StfSvcCode']) ?>"><?= htmlspecialchars($stfSvc['StfSvcName']) ?></label>
                          </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
          <div class="modal-footer border-top-0 px-4 pt-2 pb-4" style="background: #F8FAFC;">
            <button type="button" class="btn btn-light px-4" data-dismiss="modal" style="border-radius: 8px; font-weight: 500; border: 1px solid #DEE2E6;">
              <i class="fas fa-times mr-1"></i> ยกเลิก
            </button>
            <button type="submit" class="btn px-5" id="btnSaveJp" style="background: linear-gradient(135deg, var(--gov-royal) 0%, var(--gov-navy) 100%); color: white; border: none; border-radius: 8px; padding: 0.7rem 2rem; font-weight: 600; box-shadow: 0 2px 8px rgba(0,94,184,0.3);">
              <i class="fas fa-save mr-2"></i> บันทึกข้อมูล
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===== Modal: View Detail ===== -->
  <div class="modal fade" id="viewJpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header bg-navy text-white py-3 px-4" style="border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white"><i class="fas fa-eye mr-2"></i> รายละเอียดการบรรจุงาน</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <div id="viewJpLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x text-royal"></i></div>
          <div id="viewJpContent" class="d-none">
             <div class="row">
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">เลขบัตรประจำตัวประชาชน</label>
                   <div id="vw-empid" class="h5 font-weight-bold text-navy text-monospace mb-0"></div>
                </div>
                <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">ชื่อ-นามสกุล</label>
                   <div id="vw-empname" class="h5 font-weight-bold mb-0"></div>
                </div>
                <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">อายุ</label>
                   <div id="vw-age" class="mb-0"></div>
                </div>
                <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">วุฒิการศึกษา</label>
                   <div id="vw-edu" class="mb-0"></div>
                </div>
                <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">โทรศัพท์</label>
                   <div id="vw-phone" class="mb-0"></div>
                </div>
                <div class="col-md-12 mb-3 border-bottom-dotted pb-2">
                   <label class="small text-muted mb-0">ที่อยู่</label>
                   <div id="vw-address" class="mt-1"></div>
                </div>
             </div>

             <div class="gov-card shadow-none border mt-4">
                <div class="gov-card-header bg-light py-2" style="border-bottom: 2px solid var(--gov-border);">
                   <h6 class="gov-card-title mb-0" style="font-size: 1rem;"><i class="fas fa-building mr-2"></i> ข้อมูลการบรรจุงาน</h6>
                </div>
                <div class="gov-card-body">
                   <div class="row">
                      <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                         <label class="small text-muted mb-0">ชื่อสถานประกอบการ</label>
                         <div id="vw-company" class="h5 font-weight-bold text-navy mb-0"></div>
                      </div>
                      <div class="col-md-6 mb-3 border-bottom-dotted pb-2">
                         <label class="small text-muted mb-0">ตำแหน่ง</label>
                         <div id="vw-position" class="h5 mb-0"></div>
                      </div>
                      <div class="col-md-12 mb-3 border-bottom-dotted pb-2">
                         <label class="small text-muted mb-0">ที่อยู่สถานประกอบการ</label>
                         <div id="vw-compaddr" class="mt-1"></div>
                      </div>
                      <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                         <label class="small text-muted mb-0">วันที่เริ่มงาน</label>
                         <div id="vw-startdate" class="mb-0 font-weight-bold text-success"></div>
                      </div>
                      <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                         <label class="small text-muted mb-0">รายได้ต่อวัน</label>
                         <div id="vw-incomeday" class="mb-0 font-weight-bold"></div>
                      </div>
                      <div class="col-md-4 mb-3 border-bottom-dotted pb-2">
                         <label class="small text-muted mb-0">รายได้ต่อเดือน</label>
                         <div id="vw-incomemonth" class="mb-0 font-weight-bold"></div>
                      </div>
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

  <!-- ===== Modal: Summary Report ===== -->
  <div class="modal fade" id="summaryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white" style="font-weight: 600;">
            <i class="fas fa-chart-bar mr-2"></i> ตารางสถิติผู้มาใช้บริการกิจกรรมคลินิกอาชีพ
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <div id="summaryLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x" style="color: #6f42c1;"></i></div>
          <div id="summaryContent" class="d-none">
            <div class="text-center mb-3">
              <h6 class="font-weight-bold text-navy mb-1">สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</h6>
              <div class="text-muted" id="summaryPeriod"></div>
            </div>

            <!-- 1) แยกตามเพศ -->
            <div class="gov-card shadow-sm border mb-4">
              <div class="gov-card-header py-2 px-3" style="background: #E8F0FE; border-bottom: 2px solid var(--gov-royal);">
                <h6 class="gov-card-title mb-0" style="font-size: 0.95rem;"><i class="fas fa-venus-mars mr-2"></i>แยกตามเพศ</h6>
              </div>
              <div class="table-responsive p-0">
                <table class="table table-report mb-0">
                  <thead>
                    <tr>
                      <th>เพศ</th>
                      <th class="text-center">จำนวน (คน)</th>
                    </tr>
                  </thead>
                  <tbody id="summaryGenderTbody"></tbody>
                  <tfoot id="summaryGenderTfoot"></tfoot>
                </table>
              </div>
            </div>

            <!-- 2) แยกตามช่วงอายุ -->
            <div class="gov-card shadow-sm border mb-4">
              <div class="gov-card-header py-2 px-3" style="background: #FFF9E6; border-bottom: 2px solid var(--gov-gold);">
                <h6 class="gov-card-title mb-0" style="font-size: 0.95rem;"><i class="fas fa-birthday-cake mr-2"></i>ช่วงอายุ</h6>
              </div>
              <div class="table-responsive p-0">
                <table class="table table-report mb-0">
                  <thead>
                    <tr>
                      <th>ช่วงอายุ</th>
                      <th class="text-center">ชาย (คน)</th>
                      <th class="text-center">หญิง (คน)</th>
                      <th class="text-center total-cell">รวม (คน)</th>
                    </tr>
                  </thead>
                  <tbody id="summaryAgeTbody"></tbody>
                  <tfoot id="summaryAgeTfoot"></tfoot>
                </table>
              </div>
            </div>

            <!-- 3) แยกตามวุฒิการศึกษา -->
            <div class="gov-card shadow-sm border mb-0">
              <div class="gov-card-header py-2 px-3" style="background: #E8F8F0; border-bottom: 2px solid #28a745;">
                <h6 class="gov-card-title mb-0" style="font-size: 0.95rem;"><i class="fas fa-graduation-cap mr-2"></i>แยกตามวุฒิการศึกษา</h6>
              </div>
              <div class="table-responsive p-0">
                <table class="table table-report mb-0">
                  <thead>
                    <tr>
                      <th>ชื่อวุฒิการศึกษา</th>
                      <th class="text-center">ชาย (คน)</th>
                      <th class="text-center">หญิง (คน)</th>
                      <th class="text-center total-cell">รวม (คน)</th>
                    </tr>
                  </thead>
                  <tbody id="summaryEduTbody"></tbody>
                  <tfoot id="summaryEduTfoot"></tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4">
          <button type="button" class="btn btn-secondary px-4" data-dismiss="modal" style="border-radius: 8px;">ปิดหน้าต่าง</button>
          <button type="button" class="btn px-4" id="btnPrintSummary" style="background-color: #28a745; color: white; border-radius: 8px; font-weight: 600;">
            <i class="fas fa-print mr-2"></i> พิมพ์รายงาน
          </button>
        </div>
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
  // Helper functions
  function escapeHtml(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function dashIfEmpty(s) {
    s = (s == null ? '' : String(s)).trim();
    return s === '' ? '<span class="text-muted">—</span>' : escapeHtml(s);
  }

  // Initialize flatpickr
  var fpStartDate = flatpickr('#modalStartDate', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    allowInput: false
  });

  // Initialize Select2
  $('#modalEduNo').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#jpModal')
  });

  // Initialize Select2 for Position (searchable dropdown from emp_position)
  $('#modalPosition').select2({
    theme: 'bootstrap4',
    width: '100%',
    dropdownParent: $('#jpModal'),
    placeholder: '— เลือกหรือพิมพ์ค้นหาตำแหน่ง —',
    allowClear: true,
    language: {
      noResults: function() { return 'ไม่พบตำแหน่งที่ค้นหา'; },
      searching: function() { return 'กำลังค้นหา...'; }
    }
  });

  // Simple Employee ID Search
  var searchTimeout;
  $('#modalEmpID').on('input', function() {
    clearTimeout(searchTimeout);
    var query = $(this).val().trim();
    console.log('Input changed:', query);

    if (query.length < 1) {
      $('#empSuggestions').hide();
      return;
    }

    searchTimeout = setTimeout(function() {
      console.log('Searching for:', query);
      $.ajax({
        url: 'api/job_placement_emp_search.php',
        type: 'GET',
        dataType: 'json',
        data: { q: query },
        success: function(data) {
          console.log('API Response:', data, typeof data);
          var suggestions = $('#empSuggestions');
          suggestions.empty();

          try {
            if (Array.isArray(data) && data.length > 0) {
              console.log('Found', data.length, 'results');
              data.forEach(function(item) {
                var text = item.text || item.label || '';
                var parts = text.split(' - ');
                var name = parts.length > 1 ? parts[1] : '';

                var $div = $('<div class="emp-suggestion" style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #eee; background: white;"></div>')
                  .data('empid', item.id)
                  .data('empname', name)
                  .data('title', item.Title || '')
                  .data('age', item.Age || '')
                  .data('gender', item.SexName || '')
                  .data('phone', item.Phone || '')
                  .data('address', item.Address || '')
                  .html('<strong style="color: #002D62;">' + item.id + '</strong><br/><small style="color: #999;">' + name + '</small>');

                suggestions.append($div);
              });
              suggestions.show();
            } else {
              suggestions.html('<div style="padding: 10px 12px; color: #999;">ไม่พบพนักงาน</div>').show();
            }
          } catch (e) {
            console.error('Parse error:', e.message);
            suggestions.html('<div style="padding: 10px 12px; color: #d9534f;">ข้อมูลไม่ถูกต้อง</div>').show();
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', status, xhr.status, xhr.responseText);
          $('#empSuggestions').html('<div style="padding: 10px 12px; color: #d9534f;">ข้อผิดพลาด (' + xhr.status + ')</div>').show();
        }
      });
    }, 300);
  });

  // Handle suggestion click - Autofill data
  $(document).on('click', '.emp-suggestion', function(e) {
    e.stopPropagation();

    var $this = $(this);
    var empId = $this.data('empid');
    var empName = $this.data('empname');
    var title = $this.data('title');
    var gender = $this.data('gender');
    var phone = $this.data('phone');
    var address = $this.data('address');

    console.log('Selected:', empId, empName, 'Title:', title);

    // Autofill all available fields
    $('#modalEmpID').val(empId);
    $('#modalTitle').val(title || '');
    $('#modalEmpName').val(empName);
    if (phone) $('#modalPhone').val(phone);
    if (address) $('#modalAddress').val(address);

    // Set gender radio button
    $('input[name="Gender"]').prop('checked', false);
    if (gender === 'ชาย') {
      $('#genderMale').prop('checked', true);
    } else if (gender === 'หญิง') {
      $('#genderFemale').prop('checked', true);
    }

    $('#empSuggestions').hide();
  });

  // Hide suggestions when clicking outside
  $(document).on('click', function(e) {
    if (!$(e.target).closest('#modalEmpID, #empSuggestions').length) {
      $('#empSuggestions').hide();
    }
  });

  // Thai language for DataTable
  var thaiLang = {
    sProcessing: "กำลังประมวลผล...",
    sLengthMenu: "แสดง _MENU_ รายการ",
    sZeroRecords: "ไม่พบข้อมูล",
    sInfo: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
    sSearch: "ค้นหา:",
    oPaginate: { sFirst: "หน้าแรก", sPrevious: "ก่อนหน้า", sNext: "ถัดไป", sLast: "หน้าสุดท้าย" }
  };

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

  function formatMoney(val) {
    if (val == null || val === '' || val === 0) return '<span class="text-muted">—</span>';
    return Number(val).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท';
  }

  // ========== AUTOCOMPLETE EVENT HANDLERS ==========
  // (Autocomplete source and rendering is defined above)

  // ========== DataTable ==========
  var dtJp = $('#jpTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: { url: 'api/job_placement_list.php', type: 'GET' },
    language: thaiLang,
    order: [[5, 'desc']],
    columns: [
      {
        data: null,
        className: 'text-center',
        orderable: false,
        render: function(data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        }
      },
      { data: 'EmpID', className: 'text-monospace font-weight-bold text-navy' },
      { data: 'EmpName' },
      {
        data: 'Age',
        className: 'text-center',
        render: function(d) {
          return d ? d + ' ปี' : '<span class="text-muted">—</span>';
        }
      },
      { data: 'CompanyName' },
      { data: 'Position', render: function(d) { return dashIfEmpty(d); } },
      {
        data: 'StartDate',
        className: 'text-center',
        render: function(d) {
          return d ? '<span class="badge badge-success">' + formatThaiDate(d) + '</span>' : '<span class="text-muted">—</span>';
        }
      },
      {
        data: null,
        className: 'text-center',
        orderable: false,
        width: '100px',
        render: function(data, type, row) {
          if (!row.JPNo) {
            return '<button class="btn btn-sm btn-primary btn-action" data-action="add-placement" data-empid="' + row.EmpID + '" title="เพิ่มข้อมูลการบรรจุงาน"><i class="fas fa-plus"></i></button>';
          }
          return '<button class="btn btn-sm btn-info btn-action mr-1" data-action="view" data-id="' + row.JPNo + '" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>' +
                 '<button class="btn btn-sm btn-warning btn-action mr-1" data-action="edit" data-id="' + row.JPNo + '" title="แก้ไข"><i class="fas fa-edit"></i></button>' +
                 '<button class="btn btn-sm btn-danger btn-action" data-action="delete" data-id="' + row.JPNo + '" title="ลบ"><i class="fas fa-trash"></i></button>';
        }
      }
    ]
  });

  // ========== Add Button ==========
  $('#btnAddJobPlacement').on('click', function() {
    $('#jpForm')[0].reset();
    $('#jpAction').val('add');
    $('#jpNo').val('');
    $('#jpModalTitle').html('<i class="fas fa-user-check mr-2"></i> เพิ่มข้อมูลการบรรจุงาน');
    $('#jpFormAlert').addClass('d-none');
    $('#modalEduNo').val('').trigger('change');
    $('#empSuggestions').hide();
    fpStartDate.clear();
    $('#jpModal').modal('show');
  });

  // Add a placement record for a person reported as employed (selft_rep.JNo = 2).
  $('#jpTable tbody').on('click', 'button[data-action="add-placement"]', function() {
    var empID = String($(this).data('empid'));
    $('#btnAddJobPlacement').trigger('click');
    $('#modalEmpID').val(empID);

    $.getJSON('api/job_placement_emp_lookup.php', { id: empID }).done(function(res) {
      if (!res.success) return;
      var d = res.data;
      $('#modalEmpID').val(d.EmpID);
      $('#modalEmpName').val(d.EmpName || '');
      $('#modalTitle').val(d.TitleName || '');
      $('#modalPhone').val(d.Phone || '');
      $('#modalAddress').val(d.Address || '');
      $('#modalEduNo').val(d.EduNo || '').trigger('change');
      $('input[name="Gender"]').prop('checked', false);
      if (d.Gender) {
        $('input[name="Gender"][value="' + d.Gender + '"]').prop('checked', true);
      }
    });
  });
  // ========== Edit Button ==========
  $('#jpTable tbody').on('click', 'button[data-action="edit"]', function() {
    var id = $(this).data('id');
    $.getJSON('api/job_placement_detail.php', { id: id }).done(function(res) {
      if (res.success) {
        var d = res.data;
        $('#jpAction').val('edit');
        $('#jpNo').val(d.JPNo);
        $('#jpModalTitle').html('<i class="fas fa-edit mr-2"></i> แก้ไขข้อมูลการบรรจุงาน');
        $('#jpFormAlert').addClass('d-none');

        // Set employee ID and name
        $('#modalEmpID').val(d.EmpID);
        $('#modalEmpName').val(d.EmpName);
        $('#modalTitle').val(d.TitleName || '');
        $('#modalAge').val(d.Age || '');
        $('#modalPhone').val(d.Phone || '');
        $('#modalAddress').val(d.Address || '');
        $('#modalAge').val(d.Age || '');

        // Set Gender radio
        $('input[name="Gender"]').prop('checked', false);
        if (d.Gender) {
          $('input[name="Gender"][value="' + d.Gender + '"]').prop('checked', true);
        }

        $('#modalEduNo').val(d.EduNo || '').trigger('change');
        $('#modalPhone').val(d.Phone || '');
        $('#modalAddress').val(d.Address || '');
        $('#modalCompanyName').val(d.CompanyName);
        $('#modalCompanyAddress').val(d.CompanyAddress || '');
        $('#modalPosition').val(d.Position || '');
        $('#modalIncomeDay').val(d.IncomeDay || '');
        $('#modalIncomeMonth').val(d.IncomeMonth || '');

        if (d.StartDate) {
          fpStartDate.setDate(d.StartDate);
        } else {
          fpStartDate.clear();
        }

        // Set ServiceType radio
        $('input[name="ServiceType"]').prop('checked', false);
        if (d.ServiceType) {
          $('input[name="ServiceType"][value="' + d.ServiceType + '"]').prop('checked', true);
        }

        // Set StaffService checkboxes
        $('input[name="StaffService[]"]').prop('checked', false);
        if (d.StaffService) {
          var svcArr = d.StaffService.split(',');
          for (var i = 0; i < svcArr.length; i++) {
            $('input[name="StaffService[]"][value="' + svcArr[i].trim() + '"]').prop('checked', true);
          }
        }

        $('#jpModal').modal('show');
      }
    });
  });

  // ========== View Button ==========
  $('#jpTable tbody').on('click', 'button[data-action="view"]', function() {
    var id = $(this).data('id');
    $('#viewJpLoading').removeClass('d-none');
    $('#viewJpContent').addClass('d-none');
    $('#viewJpModal').modal('show');
    $.getJSON('api/job_placement_detail.php', { id: id }).done(function(res) {
      if (res.success) {
        var d = res.data;
        $('#vw-empid').text(d.EmpID);
        $('#vw-empname').text(d.EmpName);
        $('#vw-age').text(d.Age ? d.Age + ' ปี' : '—');
        $('#vw-edu').text(d.EduName || '—');
        $('#vw-phone').text(d.Phone || '—');
        $('#vw-address').text(d.Address || '—');
        $('#vw-company').text(d.CompanyName || '—');
        $('#vw-compaddr').text(d.CompanyAddress || '—');
        $('#vw-position').text(d.Position || '—');
        $('#vw-startdate').text(d.StartDate ? formatThaiDate(d.StartDate) : '—');
        $('#vw-incomeday').html(formatMoney(d.IncomeDay));
        $('#vw-incomemonth').html(formatMoney(d.IncomeMonth));
        $('#viewJpLoading').addClass('d-none');
        $('#viewJpContent').removeClass('d-none');
      }
    });
  });

  // ========== Delete Button ==========
  $('#jpTable tbody').on('click', 'button[data-action="delete"]', function() {
    var id = $(this).data('id');
    if (confirm('คุณแน่ใจว่าต้องการลบข้อมูลบรรจุงานนี้หรือไม่?\nการลบไม่สามารถกู้คืนได้')) {
      $.post('api/job_placement_delete.php', { JPNo: id }, function(res) {
        if (res.success) {
          dtJp.ajax.reload(null, false);
        } else {
          alert(res.message || 'เกิดข้อผิดพลาด');
        }
      }, 'json').fail(function() {
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
      });
    }
  });

  // ========== Form Submit (Add/Edit) ==========
  $('#jpForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $('#btnSaveJp');
    var $alert = $('#jpFormAlert');

    $alert.addClass('d-none');

    // Input validation
    var empID = $('#modalEmpID').val().trim();
    if (!/^\d{13}$/.test(empID)) {
      $alert.removeClass('d-none').text('กรุณากรอกเลขบัตรประจำตัวประชาชน 13 หลัก');
      $('#modalEmpID').focus();
      return;
    }
    if ($('#modalEmpName').val().trim() === '') {
      $alert.removeClass('d-none').text('กรุณากรอกชื่อ-นามสกุล');
      $('#modalEmpName').focus();
      return;
    }
    if ($('#modalCompanyName').val().trim() === '') {
      $alert.removeClass('d-none').text('กรุณากรอกชื่อสถานประกอบการ');
      $('#modalCompanyName').focus();
      return;
    }

    var ageVal = $('#modalAge').val().trim();
    if (ageVal !== '' && (!/^\d+$/.test(ageVal) || parseInt(ageVal) < 15 || parseInt(ageVal) > 120)) {
      $alert.removeClass('d-none').text('อายุต้องเป็นตัวเลขระหว่าง 15-120 ปี');
      $('#modalAge').focus();
      return;
    }

    var action = $('#jpAction').val();
    var url = action === 'add' ? 'api/job_placement_save.php' : 'api/job_placement_update.php';

    // Collect StaffService checkboxes
    var staffServices = [];
    $('input[name="StaffService[]"]:checked').each(function() {
      staffServices.push($(this).val());
    });

    var formData = {
      JPNo: $('#jpNo').val(),
      EmpID: empID,
      EmpName: $('#modalEmpName').val().trim(),
      Age: $('#modalAge').val().trim(),
      Gender: $('input[name="Gender"]:checked').val() || '',
      EduNo: $('#modalEduNo').val() || '',
      Address: $('#modalAddress').val().trim(),
      Phone: $('#modalPhone').val().trim(),
      CompanyName: $('#modalCompanyName').val().trim(),
      CompanyAddress: $('#modalCompanyAddress').val().trim(),
      Position: $('#modalPosition').val().trim(),
      IncomeDay: $('#modalIncomeDay').val().trim(),
      IncomeMonth: $('#modalIncomeMonth').val().trim(),
      StartDate: $('#modalStartDate').val().trim(),
      ServiceType: $('input[name="ServiceType"]:checked').val() || '',
      StaffService: staffServices.join(',')
    };

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');

    $.post(url, formData, function(res) {
      if (res.success) {
        $('#jpModal').modal('hide');
        dtJp.ajax.reload(null, false);
        // Optionally show toast
      } else {
        $alert.removeClass('d-none').text(res.message || 'เกิดข้อผิดพลาด');
      }
    }, 'json').fail(function(jqXHR) {
      var msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
      try {
        var err = JSON.parse(jqXHR.responseText);
        msg = err.message || msg;
      } catch(e) {}
      $alert.removeClass('d-none').text(msg);
    }).always(function() {
      $btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> บันทึกข้อมูล');
    });
  });

  // ========== Modal cleanup ==========
  $('#jpModal').on('hidden.bs.modal', function() {
    $('#jpFormAlert').addClass('d-none');
  });

  // ========== EmpID input: numeric only + auto-fill from employee DB ==========
  var empLookupTimer = null;
  $('#modalEmpID').on('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
    var val = this.value;

    // Only auto-fill when adding new record (not editing)
    if ($('#jpAction').val() !== 'add') return;

    // Clear previous timer
    if (empLookupTimer) clearTimeout(empLookupTimer);

    // When 13 digits entered, fetch employee data
    if (val.length === 13) {
      empLookupTimer = setTimeout(function() {
        $.getJSON('api/job_placement_emp_lookup.php', { id: val })
          .done(function(res) {
            if (res.success) {
              var d = res.data;
              // Fill name (only if empty or user hasn't manually typed)
              if ($('#modalEmpName').val().trim() === '' || $('#modalEmpName').data('autoFilled') !== false) {
                $('#modalEmpName').val(d.EmpName).data('autoFilled', true);
              }
              // Fill phone
              if ($('#modalPhone').val().trim() === '' || $('#modalPhone').data('autoFilled') !== false) {
                $('#modalPhone').val(d.Phone).data('autoFilled', true);
              }
              // Fill address
              if ($('#modalAddress').val().trim() === '' || $('#modalAddress').data('autoFilled') !== false) {
                $('#modalAddress').val(d.Address).data('autoFilled', true);
              }
              // Fill education
              if (d.EduNo && ($('#modalEduNo').val() === '' || $('#modalEduNo').data('autoFilled') !== false)) {
                $('#modalEduNo').val(d.EduNo).trigger('change').data('autoFilled', true);
              }
            }
          });
      }, 300);
    }
  });

  // Mark fields as manually edited (stop auto-fill from overwriting)
  $('#modalEmpName, #modalPhone, #modalAddress, #modalAge').on('input', function() {
    $(this).data('autoFilled', false);
  });
  $('#modalEduNo').on('change', function() {
    $(this).data('autoFilled', false);
  });

  // ========== Monthly Report PDF Module ==========
  var fpReportFrom = flatpickr('#reportDateFrom', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    allowInput: false,
    onChange: function(sel) {
      if (sel[0]) fpReportTo.set('minDate', sel[0]);
    }
  });

  var fpReportTo = flatpickr('#reportDateTo', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    allowInput: false,
    onChange: function(sel) {
      if (sel[0]) fpReportFrom.set('maxDate', sel[0]);
    }
  });

  $('#btnGenPDF').on('click', function() {
    var from = $('#reportDateFrom').val().trim();
    var to   = $('#reportDateTo').val().trim();

    if (!from && !to) {
      alert('กรุณาเลือกช่วงวันที่อย่างน้อยหนึ่งรายการ (จากวันที่ หรือ ถึงวันที่)');
      return;
    }

    var params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to)   params.push('to='   + encodeURIComponent(to));

    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> กำลังสร้าง PDF...');

    // Open PDF in new tab
    var url = 'api/job_placement_report.php?' + params.join('&');
    window.open(url, '_blank');

    // Re-enable button after short delay
    setTimeout(function() {
      $btn.prop('disabled', false).html(originalHtml);
    }, 1500);
  });

  // ===== คลินิกอาชีพ - แบบ3 (ทะเบียนผู้มาใช้บริการ) =====
  $('#btnGenPDFForm3').on('click', function() {
    var from = $('#reportDateFrom').val().trim();
    var to   = $('#reportDateTo').val().trim();

    if (!from && !to) {
      alert('กรุณาเลือกช่วงวันที่อย่างน้อยหนึ่งรายการ (จากวันที่ หรือ ถึงวันที่)');
      return;
    }

    var params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to)   params.push('to='   + encodeURIComponent(to));

    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> กำลังสร้าง PDF...');

    // Open PDF in new tab
    var url = 'api/job_placement_report_form3.php?' + params.join('&');
    window.open(url, '_blank');

    // Re-enable button after short delay
    setTimeout(function() {
      $btn.prop('disabled', false).html(originalHtml);
    }, 1500);
  });

  // ========== Summary Report Button ==========
  $('#btnSummary').on('click', function() {
    var from = $('#reportDateFrom').val().trim();
    var to   = $('#reportDateTo').val().trim();

    $('#summaryLoading').removeClass('d-none');
    $('#summaryContent').addClass('d-none');
    $('#summaryModal').modal('show');

    $.ajax({
      url: 'api/job_placement_summary.php',
      type: 'GET',
      data: { from: from, to: to },
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function(res) {
        if (!res.success) {
          $('#summaryLoading').html('<div class="alert alert-danger">' + escapeHtml(res.message || 'ไม่สามารถดึงข้อมูลได้') + '</div>');
          return;
        }

        var g = res.gender;
        var age = res.age;
        var edu = res.education;

        // Build period info
        var periodText = '';
        if (res.dateFrom && res.dateTo) {
          periodText = 'ประจำเดือน ' + formatThaiDate(res.dateFrom) + ' — ' + formatThaiDate(res.dateTo);
        } else if (res.dateFrom) {
          periodText = 'ตั้งแต่ ' + formatThaiDate(res.dateFrom);
        } else if (res.dateTo) {
          periodText = 'ถึง ' + formatThaiDate(res.dateTo);
        } else {
          periodText = 'แสดงข้อมูลทั้งหมด';
        }
        $('#summaryPeriod').html('<i class="far fa-calendar-check mr-1"></i>' + escapeHtml(periodText));

        // 1) Gender table
        var genderTbody = '';
        genderTbody += '<tr><td class="col-name text-navy font-weight-bold">ชาย</td><td class="text-center">' + g.male.toLocaleString('th-TH') + '</td></tr>';
        genderTbody += '<tr><td class="col-name text-navy font-weight-bold">หญิง</td><td class="text-center">' + g.female.toLocaleString('th-TH') + '</td></tr>';
        if (g.unspecified > 0) {
          genderTbody += '<tr><td class="col-name text-muted font-italic">ไม่ระบุเพศ</td><td class="text-center text-muted">' + g.unspecified.toLocaleString('th-TH') + '</td></tr>';
        }
        $('#summaryGenderTbody').html(genderTbody);

        var genderTfoot = '<tr><td class="col-name text-navy font-weight-bold text-right">รวม</td><td class="text-center font-weight-bold" style="background: #E6F0FF;">' + g.total.toLocaleString('th-TH') + '</td></tr>';
        $('#summaryGenderTfoot').html(genderTfoot);

        // 2) Age table
        var ageTbody = '';
        if (age.data.length === 0) {
          ageTbody = '<tr><td colspan="4" class="text-muted py-3 text-center">ไม่พบข้อมูล</td></tr>';
        } else {
          for (var i = 0; i < age.data.length; i++) {
            var a = age.data[i];
            ageTbody += '<tr>';
            ageTbody += '<td class="col-name text-navy font-weight-bold">' + escapeHtml(a.label) + '</td>';
            ageTbody += '<td class="text-center">' + a.male.toLocaleString('th-TH') + '</td>';
            ageTbody += '<td class="text-center">' + a.female.toLocaleString('th-TH') + '</td>';
            ageTbody += '<td class="total-cell text-center">' + a.total.toLocaleString('th-TH') + '</td>';
            ageTbody += '</tr>';
          }
        }
        $('#summaryAgeTbody').html(ageTbody);

        var ageTfoot = '<tr>';
        ageTfoot += '<td class="col-name text-navy font-weight-bold text-right">รวม</td>';
        ageTfoot += '<td class="text-center font-weight-bold">' + age.male.toLocaleString('th-TH') + '</td>';
        ageTfoot += '<td class="text-center font-weight-bold">' + age.female.toLocaleString('th-TH') + '</td>';
        ageTfoot += '<td class="total-cell text-center font-weight-bold" style="background: #E6F0FF;">' + age.total.toLocaleString('th-TH') + '</td>';
        ageTfoot += '</tr>';
        $('#summaryAgeTfoot').html(ageTfoot);

        // 3) Education table
        var eduTbody = '';
        if (edu.data.length === 0) {
          eduTbody = '<tr><td colspan="4" class="text-muted py-3 text-center">ไม่พบข้อมูล</td></tr>';
        } else {
          for (var j = 0; j < edu.data.length; j++) {
            var e = edu.data[j];
            eduTbody += '<tr>';
            eduTbody += '<td class="col-name text-navy font-weight-bold">' + escapeHtml(e.label) + '</td>';
            eduTbody += '<td class="text-center">' + e.male.toLocaleString('th-TH') + '</td>';
            eduTbody += '<td class="text-center">' + e.female.toLocaleString('th-TH') + '</td>';
            eduTbody += '<td class="total-cell text-center">' + e.total.toLocaleString('th-TH') + '</td>';
            eduTbody += '</tr>';
          }
        }
        $('#summaryEduTbody').html(eduTbody);

        var eduTfoot = '<tr>';
        eduTfoot += '<td class="col-name text-navy font-weight-bold text-right">รวม</td>';
        eduTfoot += '<td class="text-center font-weight-bold">' + edu.male.toLocaleString('th-TH') + '</td>';
        eduTfoot += '<td class="text-center font-weight-bold">' + edu.female.toLocaleString('th-TH') + '</td>';
        eduTfoot += '<td class="total-cell text-center font-weight-bold" style="background: #E6F0FF;">' + edu.total.toLocaleString('th-TH') + '</td>';
        eduTfoot += '</tr>';
        $('#summaryEduTfoot').html(eduTfoot);

        $('#summaryLoading').addClass('d-none');
        $('#summaryContent').removeClass('d-none');
      },
      error: function(jqXHR, textStatus, errorThrown) {
        var msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
          msg = jqXHR.responseJSON.message;
        } else if (jqXHR.status === 401) {
          msg = 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่';
        }
        $('#summaryLoading').html('<div class="alert alert-danger">' + escapeHtml(msg) + '</div>');
      }
    });
  });

  // ========== Summary Print Button (PDF) ==========
  $('#btnPrintSummary').on('click', function() {
    var from = $('#reportDateFrom').val().trim();
    var to   = $('#reportDateTo').val().trim();

    var params = [];
    if (from) params.push('from=' + encodeURIComponent(from));
    if (to)   params.push('to=' + encodeURIComponent(to));

    var $btn = $(this);
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> กำลังสร้าง PDF...');

    var url = 'api/job_placement_summary_report.php?' + params.join('&');
    window.open(url, '_blank');

    setTimeout(function() {
      $btn.prop('disabled', false).html(originalHtml);
    }, 1500);
  });

});
</script>
</body>
</html>