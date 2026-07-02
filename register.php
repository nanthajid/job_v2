<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo  = getDB();

try {
    $kateRows = $pdo->query("SELECT KNo, KName FROM kate ORDER BY KNo")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $quitRows = $pdo->query("SELECT QNo, QName FROM quit ORDER BY QNo")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $sexRows  = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $titlesRows = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $eduRows  = $pdo->query("SELECT EqNo, EqName FROM educational_qualification ORDER BY EqNo")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $potRows  = $pdo->query("SELECT PotNo, PotName FROM emp_position ORDER BY PotNo")->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $kateRows = $quitRows = $sexRows = $titlesRows = $eduRows = $potRows = [];
    error_log("Error loading lookup data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ขึ้นทะเบียน | Government Digital Service</title>

  <!-- Fonts: Modern Thai GovTech Stack -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
      transition: transform 0.2s ease;
    }

    .gov-card-header {
      background-color: transparent;
      border-bottom: 1px solid var(--gov-gray);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
    }

    .gov-card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gov-navy);
      margin: 0;
    }

    .gov-card-body {
      padding: 1.5rem;
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

    .custom-gov-radio .custom-control-input:checked ~ .custom-control-label::before {
      background-color: var(--gov-royal);
      border-color: var(--gov-royal);
    }

    .btn-gov-primary {
      background-color: var(--gov-navy);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.2s;
    }

    .btn-gov-primary:hover {
      background-color: var(--gov-royal);
      color: white;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 45, 98, 0.2);
    }

    .btn-secondary {
      background-color: transparent;
      color: var(--gov-text-muted);
      border: 1px solid var(--gov-border);
      border-radius: 8px;
      padding: 0.75rem 2rem;
      font-weight: 500;
    }

    .btn-secondary:hover {
      background-color: var(--gov-gray);
      color: var(--gov-text-dark);
    }

    .btn-navy-icon {
      color: var(--gov-navy);
      border-color: var(--gov-navy);
      background-color: transparent;
    }

    .btn-navy-icon:hover {
      background-color: var(--gov-navy);
      color: white;
      border-color: var(--gov-gold);
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
      margin-bottom: 0.5rem;
    }

    .gov-page-subtitle {
      opacity: 0.9;
      font-weight: 300;
    }

    .ui-autocomplete {
      border-radius: 8px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--gov-border);
      font-family: 'IBM Plex Sans Thai', sans-serif;
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

    /* Success Alert Styling */
    .success-result-card {
      background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%);
      border: none;
      border-radius: 16px;
      padding: 1.75rem 2rem;
      color: white;
      box-shadow: 0 8px 24px -2px rgba(0, 45, 98, 0.25);
      margin-bottom: 2rem;
      animation: slideInDown 0.5s ease-out;
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .success-icon-circle {
      width: 90px;
      height: 90px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 2.5rem;
    }

    .doc-number-display {
      background: rgba(255, 255, 255, 0.15);
      border: 3px solid var(--gov-gold);
      border-radius: 12px;
      padding: 1rem 1.5rem;
      text-align: center;
      margin: 1rem 0;
      backdrop-filter: blur(10px);
    }

    .doc-number-label {
      font-size: 0.85rem;
      opacity: 0.9;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.3rem;
      font-weight: 500;
    }

    .doc-number-value {
      font-size: 3.5rem;
      font-weight: 900;
      color: white;
      text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.3);
      font-family: 'Courier New', monospace;
      letter-spacing: 2px;
    }

    .result-info-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      margin-top: 2rem;
    }

    .result-info-item {
      background: rgba(255, 255, 255, 0.1);
      padding: 1rem 1.5rem;
      border-radius: 8px;
      border-left: 4px solid var(--gov-gold);
    }

    .result-info-label {
      font-size: 0.85rem;
      opacity: 0.8;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.5rem;
      font-weight: 600;
    }

    .result-info-value {
      font-size: 1.1rem;
      font-weight: 600;
      word-break: break-all;
    }

    .result-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1.5rem;
      justify-content: center;
    }

    .btn-success-action {
      background: rgba(255, 255, 255, 0.95);
      color: var(--gov-navy);
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1.75rem;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-success-action:hover {
      background: var(--gov-gold);
      color: var(--gov-navy);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      text-decoration: none;
    }

    .btn-success-action:active {
      transform: translateY(0);
    }

    /* Form Disabled State */
    .form-disabled {
      opacity: 0.5;
      pointer-events: none;
    }

    .form-disabled .form-control,
    .form-disabled .select2-selection,
    .form-disabled .btn {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    @media (max-width: 768px) {
      .gov-page-title { font-size: 1.5rem; }
      .gov-card-body { padding: 1rem; }
      .btn-gov-primary, .btn-secondary { width: 100%; margin-bottom: 0.5rem; }
      .success-result-card { padding: 1.25rem 1.5rem; }
      .doc-number-value { font-size: 2.5rem; }
      .result-info-row { grid-template-columns: 1fr; gap: 1rem; }
      .success-icon-circle { width: 70px; height: 70px; font-size: 2rem; }
      .result-actions { flex-direction: column; }
      .btn-success-action { width: 100%; justify-content: center; }
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

    <!-- Hero Page Header -->
    <div class="gov-page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">ขึ้นทะเบียน</h1>
            <p class="gov-page-subtitle">ระบบบริการภาครัฐดิจิทัล เพื่อการจัดการข้อมูลผู้ว่างงานอย่างมีประสิทธิภาพ</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-user-plus fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid px-lg-5">

        <div id="formAlert" class="alert d-none shadow-sm" role="alert"></div>

        <!-- ===== Registration Result ===== -->
        <div id="successAlert" class="success-result-card d-none" role="alert">
          <div class="success-icon-circle">
            <i class="fas fa-check"></i>
          </div>

          <div style="text-align: center; margin-bottom: 1rem;">
            <h4 style="font-size: 1.1rem; font-weight: 600; margin: 0;">ขึ้นทะเบียนสำเร็จ!</h4>
          </div>

          <div class="doc-number-display">
            <div class="doc-number-label">เลขที่เอกสารลำดับที่</div>
            <div class="doc-number-value" id="resultDocID"></div>
          </div>

          <div class="result-actions">
            <button type="button" class="btn-success-action" id="btnNewRegister" style="background: transparent; border: 2px solid rgba(255, 255, 255, 0.5); color: white;">
              <i class="fas fa-plus"></i> ลงทะเบียนใหม่
            </button>
          </div>
        </div>

        <!-- ===== Search Button ===== -->
        <div class="mb-4">
          <button type="button" class="btn text-white" id="btnSearchRegister" style="background-color: var(--gov-navy); border-color: var(--gov-navy); border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;">
            <i class="fas fa-search mr-2"></i> ค้นหาข้อมูลขึ้นทะเบียน
          </button>
        </div>

        <form id="registerForm" autocomplete="off">
          <div class="row">

            <!-- Card: Personal Information -->
            <div class="col-lg-8">
              <div class="gov-card">
                <div class="gov-card-header bg-navy text-white" style="border-bottom: 3px solid var(--gov-gold) !important;">
                  <i class="fas fa-id-card-clip fa-lg text-white mr-3"></i>
                  <h3 class="gov-card-title text-white">ข้อมูลส่วนบุคคลและที่อยู่</h3>
                </div>
                <div class="gov-card-body">

                  <div class="row">
                    <div class="col-md-12 mb-4">
                      <label class="form-label" for="empID">
                        เลขบัตรประจำตัวประชาชน <span class="text-danger">*</span>
                      </label>
                      <div class="input-group">
                        <input
                          type="text"
                          id="empID"
                          name="EmpID"
                          class="form-control form-control-lg"
                          placeholder="0-0000-00000-00-0"
                          maxlength="13"
                          inputmode="numeric"
                          required>
                        <div class="input-group-append d-none" id="addEmpAppend">
                          <button type="button" id="btnAddEmp" class="btn btn-success px-4">
                            <i class="fas fa-user-plus mr-2"></i>เพิ่มใหม่
                          </button>
                        </div>
                      </div>
                      <small class="text-muted mt-2 d-block">กรอกเลข 13 หลักเพื่อตรวจสอบข้อมูลในระบบ</small>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-12">
                      <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                      <div class="d-flex flex-wrap">
                        <?php foreach ($titlesRows as $t):
                          $tid = 'main_title_' . (int)$t['TitleNo'];
                        ?>
                          <div class="custom-control custom-radio custom-gov-radio mr-4 mb-2">
                            <input
                              type="radio"
                              id="<?= $tid ?>"
                              name="Titles"
                              class="custom-control-input"
                              value="<?= (int)$t['TitleNo'] ?>"
                              required>
                            <label class="custom-control-label" for="<?= $tid ?>">
                              <?= htmlspecialchars($t['Title']) ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-7">
                      <label class="form-label" for="empName">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <input
                          type="text"
                          id="empName"
                          name="EmpName"
                          class="form-control"
                          placeholder="ไม่ต้องระบุคำนำหน้า"
                          required>
                        <div class="input-group-append">
                          <button type="button" id="btnEditEmp" class="btn btn-navy-icon px-3" title="แก้ไขข้อมูลพื้นฐาน">
                            <i class="fas fa-user-edit"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <label class="form-label">เพศ <span class="text-danger">*</span></label>
                      <div class="d-flex pt-2">
                        <?php foreach ($sexRows as $s):
                          $sid = 'sex_' . (int)$s['SexNo'];
                        ?>
                          <div class="custom-control custom-radio custom-gov-radio mr-4">
                            <input
                              type="radio"
                              id="<?= $sid ?>"
                              name="SexNo"
                              class="custom-control-input"
                              value="<?= (int)$s['SexNo'] ?>"
                              required>
                            <label class="custom-control-label" for="<?= $sid ?>">
                              <?= htmlspecialchars($s['SexName']) ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-6">
                      <label class="form-label" for="phone">เบอร์โทรศัพท์ติดต่อ</label>
                      <input
                        type="text"
                        id="phone"
                        name="Phone"
                        class="form-control"
                        placeholder="เช่น 0812345678"
                        maxlength="15">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="lineID">Line ID (ถ้ามี)</label>
                      <input
                        type="text"
                        id="lineID"
                        name="lineID"
                        class="form-control"
                        placeholder="ไอดีไลน์เพื่อการแจ้งเตือน">
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-6">
                      <label class="form-label" for="kNo">เขตพื้นที่ (ตามที่อยู่ปัจจุบัน) <span class="text-danger">*</span></label>
                      <select id="kNo" name="KNo" class="form-control select2" required>
                        <option value="">— เลือกเขต —</option>
                        <?php foreach ($kateRows as $r): ?>
                          <option value="<?= (int)$r['KNo'] ?>"><?= htmlspecialchars($r['KName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-12">
                      <label class="form-label" for="address">ที่อยู่ปัจจุบัน</label>
                      <textarea
                        id="address"
                        name="Address"
                        class="form-control"
                        rows="3"
                        placeholder="ระบุ บ้านเลขที่, ซอย, ถนน, แขวง..."></textarea>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- Card: Registration Details -->
            <div class="col-lg-4">
              <div class="gov-card h-100">
                <div class="gov-card-header bg-navy text-white" style="border-bottom: 3px solid var(--gov-gold) !important;">
                  <i class="fas fa-clipboard-check fa-lg text-white mr-3"></i>
                  <h3 class="gov-card-title text-white">ข้อมูลการทำรายการ</h3>
                </div>
                <div class="gov-card-body">
                  <div class="form-group mb-4">
                    <label class="form-label" for="rDate">วันที่มาขึ้นทะเบียน <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0">
                          <i class="far fa-calendar-alt text-navy"></i>
                        </span>
                      </div>
                      <input
                        type="text"
                        id="rDate"
                        name="RDate"
                        class="form-control border-left-0 bg-light"
                        placeholder="วันที่จากระบบ"
                        readonly
                        required>
                    </div>
                    <small class="text-muted mt-2 d-block">ใช้วันที่ปัจจุบันของระบบโดยอัตโนมัติ</small>
                  </div>

                  <div class="form-group mb-5">
                    <label class="form-label">สาเหตุที่ออกจากงาน <span class="text-danger">*</span></label>
                    <div class="pt-2">
                      <?php foreach ($quitRows as $r):
                        $qid = 'main_quit_' . (int)$r['QNo'];
                      ?>
                        <div class="custom-control custom-radio custom-gov-radio mb-3">
                          <input
                            type="radio"
                            id="<?= $qid ?>"
                            name="QNo"
                            class="custom-control-input"
                            value="<?= (int)$r['QNo'] ?>"
                            required>
                          <label class="custom-control-label" for="<?= $qid ?>">
                            <?= htmlspecialchars($r['QName']) ?>
                          </label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <div class="form-group mb-4">
                    <label class="form-label" for="eqNo">วุฒิการศึกษาล่าสุด <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <select id="eqNo" name="EqNo" class="form-control select2" required>
                        <option value="">— เลือกวุฒิการศึกษา —</option>
                        <?php foreach ($eduRows as $r): ?>
                          <option value="<?= (int)$r['EqNo'] ?>"><?= htmlspecialchars($r['EqName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="input-group-append">
                        <button type="button" id="btnAddEdu" class="btn btn-success">
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="form-group mb-4">
                    <label class="form-label" for="potNo">ตำแหน่งล่าสุด <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <select id="potNo" name="PotNo" class="form-control select2" required>
                        <option value="">— เลือกตำแหน่ง —</option>
                        <?php foreach ($potRows as $r): ?>
                          <option value="<?= (int)$r['PotNo'] ?>"><?= htmlspecialchars($r['PotName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <div class="input-group-append">
                        <button type="button" id="btnAddPot" class="btn btn-success">
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <hr class="my-4">

                  <!-- Edit mode banner (shown when a record is loaded for editing) -->
                  <div id="viewModeBanner" class="alert d-none mb-4" role="alert" style="background: #E8F0FE; border: 1px solid #C7DAF7; border-left: 4px solid var(--gov-royal); border-radius: 8px; color: #1A4E8A;">
                    <i class="fas fa-pen-to-square mr-2"></i> กำลังแก้ไขข้อมูลที่บันทึกไว้ — แก้ไขได้ทุกช่อง แล้วกด <strong>บันทึกการแก้ไขข้อมูล</strong>
                  </div>

                  <div class="text-muted small mb-4">
                    <i class="fas fa-info-circle mr-1"></i> ตรวจสอบความถูกต้องของข้อมูลก่อนทำการบันทึกทุกครั้ง ข้อมูลจะถูกเชื่อมโยงไปยังระบบกลางของกรมการจัดหางาน
                  </div>

                  <div class="d-flex flex-column">
                    <button type="button" id="btnEnterEditMode" class="btn mb-3" disabled title="โหลดข้อมูลผู้ขึ้นทะเบียนก่อน จึงจะแก้ไขได้" style="background-color: var(--gov-gold); color: var(--gov-navy); border: none; border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;">
                      <i class="fas fa-pen-to-square mr-2"></i> แก้ไขข้อมูล
                    </button>
                    <button type="submit" id="btnSubmitRegister" class="btn btn-gov-primary mb-3">
                      <i class="fas fa-save mr-2"></i> บันทึกข้อมูลการขึ้นทะเบียน
                    </button>
                    <button type="reset" id="btnResetRegister" class="btn btn-secondary">
                      <i class="fas fa-rotate-left mr-2"></i> ล้างข้อมูล
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- /row -->
        </form>

      </div><!-- /container-fluid -->
    </section>
  </div><!-- /content-wrapper -->

  <!-- ===== Modal: Employee (Add/Edit) ===== -->
  <div class="modal fade" id="empModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white" id="empModalTitle" style="font-weight: 600;">
            <i class="fas fa-user-plus mr-2"></i> เพิ่มข้อมูลผู้ว่างงานใหม่
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
        </div>
        <form id="empModalForm" autocomplete="off">
          <input type="hidden" id="empModalAction" value="add">
          <div class="modal-body px-4 pt-4 pb-2" style="background: #F8FAFC;">
            <div id="empModalAlert" class="alert alert-danger d-none mb-3" role="alert"></div>

            <div class="gov-card shadow-sm border mb-3" style="border-radius: 10px;">
              <div class="gov-card-header d-flex align-items-center py-2 px-3" style="background: linear-gradient(135deg, #E8F0FE 0%, #F0F4FF 100%); border-bottom: 2px solid var(--gov-royal); border-radius: 10px 10px 0 0;">
                <div style="width: 32px; height: 32px; background: var(--gov-royal); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                  <i class="fas fa-user-edit text-white" style="font-size: 0.85rem;"></i>
                </div>
                <h6 class="gov-card-title mb-0" style="font-size: 0.95rem; font-weight: 600; color: var(--gov-navy);">ข้อมูลผู้ว่างงาน</h6>
              </div>
              <div class="gov-card-body p-3">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label mb-1" for="modalEmpID" style="font-size: 0.85rem;">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-id-card text-muted"></i></span>
                      </div>
                      <input type="text" id="modalEmpID" name="EmpID" class="form-control border-left-0" maxlength="13" inputmode="numeric" required style="border-radius: 0 8px 8px 0;">
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label mb-1" style="font-size: 0.85rem;">คำนำหน้า <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap pt-2">
                      <?php foreach ($titlesRows as $t): ?>
                        <div class="custom-control custom-radio custom-gov-radio mr-3 mb-2">
                          <input type="radio" id="modal_title_<?= $t['TitleNo'] ?>" name="Titles" class="custom-control-input" value="<?= $t['TitleNo'] ?>" required>
                          <label class="custom-control-label" for="modal_title_<?= $t['TitleNo'] ?>"><?= $t['Title'] ?></label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-8 mb-3">
                    <label class="form-label mb-1" for="modalEmpName" style="font-size: 0.85rem;">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-user text-muted"></i></span>
                      </div>
                      <input type="text" id="modalEmpName" name="EmpName" class="form-control border-left-0" required style="border-radius: 0 8px 8px 0;">
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label mb-1" style="font-size: 0.85rem;">เพศ <span class="text-danger">*</span></label>
                    <div class="d-flex pt-2">
                      <?php foreach ($sexRows as $s): ?>
                        <div class="custom-control custom-radio custom-gov-radio mr-3">
                          <input type="radio" id="modal_sex_<?= $s['SexNo'] ?>" name="SexNo" class="custom-control-input" value="<?= $s['SexNo'] ?>" required>
                          <label class="custom-control-label" for="modal_sex_<?= $s['SexNo'] ?>"><?= $s['SexName'] ?></label>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label mb-1" for="modalKNo" style="font-size: 0.85rem;">เขต <span class="text-danger">*</span></label>
                    <select id="modalKNo" name="KNo" class="form-control select2" required style="border-radius: 8px; width: 100%;">
                      <option value="">— เลือกเขต —</option>
                      <?php foreach ($kateRows as $r): ?>
                        <option value="<?= $r['KNo'] ?>"><?= $r['KName'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label mb-1" for="modalPhone" style="font-size: 0.85rem;">เบอร์โทรศัพท์</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-phone-alt text-muted"></i></span>
                      </div>
                      <input type="text" id="modalPhone" name="Phone" class="form-control border-left-0" maxlength="15" style="border-radius: 0 8px 8px 0;">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12 mb-0">
                    <label class="form-label mb-1" for="modalAddress" style="font-size: 0.85rem;">ที่อยู่ปัจจุบัน</label>
                    <textarea id="modalAddress" name="Address" class="form-control" rows="2" placeholder="ระบุที่อยู่ปัจจุบัน" style="border-radius: 8px; resize: none;"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pt-2 pb-4" style="background: #F8FAFC;">
            <button type="button" class="btn btn-light px-4" data-dismiss="modal" style="border-radius: 8px; font-weight: 500; border: 1px solid #DEE2E6;">
              <i class="fas fa-times mr-1"></i> ยกเลิก
            </button>
            <button type="submit" class="btn px-5" id="btnSaveModalEmp" style="background: linear-gradient(135deg, var(--gov-royal) 0%, var(--gov-navy) 100%); color: white; border: none; border-radius: 8px; padding: 0.7rem 2rem; font-weight: 600; box-shadow: 0 2px 8px rgba(0,94,184,0.3);">
              <i class="fas fa-save mr-2"></i> บันทึกข้อมูล
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===== Modal: Educational Qualification (Add) ===== -->
  <div class="modal fade" id="eduModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4">
          <h5 class="gov-card-title text-navy">
            <i class="fas fa-graduation-cap mr-2"></i> เพิ่มวุฒิการศึกษาใหม่
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
            <button type="submit" class="btn btn-gov-primary px-4" id="btnSaveModalEdu">บันทึกวุฒิการศึกษา</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===== Modal: Position (Add) ===== -->
  <div class="modal fade" id="potModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4">
          <h5 class="gov-card-title text-navy">
            <i class="fas fa-briefcase mr-2"></i> เพิ่มตำแหน่งใหม่
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
            <button type="submit" class="btn btn-gov-primary px-4" id="btnSaveModalPot">บันทึกตำแหน่ง</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ===== Modal: Search Register ===== -->
  <div class="modal fade" id="searchRegisterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white" style="font-weight: 600;">
            <i class="fas fa-search mr-2"></i> ค้นหาข้อมูลขึ้นทะเบียน
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pt-4 pb-2" style="background: #F8FAFC;">
          <!-- Search Panel -->
          <div class="gov-card shadow-sm border mb-4" style="border-radius: 10px;">
            <div class="gov-card-header d-flex align-items-center py-2 px-3" style="background: linear-gradient(135deg, #E8F0FE 0%, #F0F4FF 100%); border-bottom: 2px solid var(--gov-royal); border-radius: 10px 10px 0 0;">
              <div style="width: 32px; height: 32px; background: var(--gov-royal); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                <i class="fas fa-filter text-white" style="font-size: 0.85rem;"></i>
              </div>
              <h6 class="gov-card-title mb-0" style="font-size: 0.95rem; font-weight: 600; color: var(--gov-navy);">เงื่อนไขการค้นหา</h6>
            </div>
            <div class="gov-card-body p-3">
              <div class="row align-items-end">
                <div class="col-md-5 mb-2">
                  <label class="form-label mb-1" for="searchEmpID" style="font-size: 0.85rem;">เลขบัตรประชาชน</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-id-card text-muted"></i></span>
                    </div>
                    <input type="text" id="searchEmpID" class="form-control border-left-0" placeholder="กรอกเลขบัตร 13 หลัก" maxlength="13" inputmode="numeric" style="border-radius: 0 8px 8px 0;">
                  </div>
                </div>
                <div class="col-md-5 mb-2">
                  <label class="form-label mb-1" for="searchEmpName" style="font-size: 0.85rem;">ชื่อ-นามสกุล</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-user-edit text-muted"></i></span>
                    </div>
                    <input type="text" id="searchEmpName" class="form-control border-left-0" placeholder="กรอกชื่อหรือนามสกุล" style="border-radius: 0 8px 8px 0;">
                  </div>
                </div>
                <div class="col-md-2 mb-2">
                  <button type="button" class="btn btn-block" id="btnPerformSearch" style="background: linear-gradient(135deg, var(--gov-royal) 0%, var(--gov-navy) 100%); color: white; border: none; border-radius: 8px; padding: 0.65rem 1rem; font-weight: 600; box-shadow: 0 2px 8px rgba(0,94,184,0.3);">
                    <i class="fas fa-search mr-1"></i> ค้นหา
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Result Panel -->
          <div id="searchResultContainer" class="d-none">
            <div class="gov-card shadow-sm border" style="border-radius: 10px;">
              <div class="gov-card-header d-flex align-items-center justify-content-between py-2 px-3" style="background: linear-gradient(135deg, #E8F8F0 0%, #F0FFF4 100%); border-bottom: 2px solid #28a745; border-radius: 10px 10px 0 0;">
                <div class="d-flex align-items-center">
                  <div style="width: 32px; height: 32px; background: #28a745; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                    <i class="fas fa-list-check text-white" style="font-size: 0.85rem;"></i>
                  </div>
                  <h6 class="gov-card-title mb-0" style="font-size: 0.95rem; font-weight: 600; color: #1B5E20;">ผลการค้นหา <span id="searchResultCount" class="badge badge-success ml-2" style="font-size: 0.8rem;"></span></h6>
                </div>
              </div>
              <div class="gov-card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0" id="searchResultTable" style="font-size: 0.9rem;">
                    <thead style="background: #F8FAFC;">
                      <tr>
                        <th style="width: 140px; border-top: none;">เลขบัตร</th>
                        <th style="border-top: none;">ชื่อ-นามสกุล</th>
                        <th style="width: 120px; border-top: none;">วันที่ขึ้นทะเบียน</th>
                        <th style="width: 100px; border-top: none;">เขต</th>
                        <th style="width: 140px; border-top: none; text-align: center;">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody id="searchResultBody">
                    </tbody>
                  </table>
                </div>
                <div id="noResultMsg" class="text-center py-5 d-none">
                  <div style="width: 64px; height: 64px; background: #E9ECEF; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                    <i class="fas fa-search fa-2x text-muted"></i>
                  </div>
                  <h6 class="text-muted">ไม่พบข้อมูลที่ค้นหา</h6>
                  <p class="text-muted small">ลองเปลี่ยนคำค้นหาหรือตรวจสอบความถูกต้องของข้อมูล</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pt-2 pb-4" style="background: #F8FAFC;">
          <button type="button" class="btn btn-light px-4" data-dismiss="modal" style="border-radius: 8px; font-weight: 500; border: 1px solid #DEE2E6;">
            <i class="fas fa-times mr-1"></i> ปิดหน้าต่าง
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Footer ===== -->
  <footer class="main-footer border-top-0 bg-transparent text-center py-4">
    <div class="text-muted small">
      © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Develop By Nanthajd sawasri
    </div>
  </footer>

</div><!-- /wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
  // Initialize Select2
  $('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
  });

  // Config SweetAlert2 global
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });

  // Flatpickr Setup
  flatpickr('#rDate', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: 'today',
    allowInput: false
  });

  // ID Card Input Formatting
  $('#empID, #modalEmpID').on('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
  });

  // ตั้งค่าเริ่มต้น = โหมดสร้างใหม่
  setRegisterMode('new');

  // Autocomplete Logic
  function toggleAddEmpButton(term, results) {
    var v = (term || '').trim();
    if (/^\d{13}$/.test(v)) {
      var exists = (results || []).some(function (d) { return d.EmpID === v; });
      $('#addEmpAppend').toggleClass('d-none', exists);
    } else {
      $('#addEmpAppend').addClass('d-none');
    }
  }

  $('#empID').autocomplete({
    minLength: 2,
    delay: 200,
    source: function (request, response) {
      $.getJSON('api/employee_search.php', { q: request.term })
        .done(function (data) {
          response(data);
          toggleAddEmpButton(request.term, data);
        })
        .fail(function () {
          response([]);
          toggleAddEmpButton(request.term, []);
        });
    },
    select: function (event, ui) {
      fillMainForm(ui.item);
      $('#addEmpAppend').addClass('d-none');
      event.preventDefault();
      Toast.fire({ icon: 'success', title: 'พบข้อมูลผู้ลงทะเบียน' });
    },
    focus: function (event, ui) {
      $('#empID').val(ui.item.EmpID);
      event.preventDefault();
    }
  }).autocomplete('instance')._renderItem = function (ul, item) {
    return $('<li>')
      .append('<div class="py-2 px-3 border-bottom"><div class="font-weight-bold text-navy">' + item.EmpID + '</div><small class="text-muted">' + (item.EmpName || 'ไม่ระบุชื่อ') + '</small></div>')
      .appendTo(ul);
  };

  function fillMainForm(d) {
    $('#empID').val(d.EmpID);
    $('#empName').val(d.EmpName || '');
    if (d.Titles) {
      $('input[name="Titles"][value="' + parseInt(d.Titles) + '"]', '#registerForm').prop('checked', true);
    }
    if (d.KNo) {
      $('#kNo').val(parseInt(d.KNo) || '').trigger('change');
    }
    if (d.SexNo) $('input[name="SexNo"][value="' + parseInt(d.SexNo) + '"]').prop('checked', true);
    $('#phone').val(d.Phone || '');
    $('#lineID').val(d.lineID || '');
    $('#address').val(d.Address || '');
  }

  // ===== Register Form Mode Control =====
  // mode: 'new' (สร้างใหม่ - ปุ่มแก้ไขถูกปิด), 'edit' (โหลดข้อมูลเดิมมาแก้ไขได้ทันที)
  // ปุ่ม "แก้ไขข้อมูล" แสดงในการ์ดตลอด: โหมดใหม่ = disabled, โหลดข้อมูลแล้ว = enabled
  function setRegisterMode(mode) {
    var $form = $('#registerForm');
    $form.data('mode', mode);

    if (mode === 'edit') {
      // โหลดข้อมูลเดิม -> แก้ไขได้ทุกช่องทันที (ยกเว้นเลขบัตรซึ่งเป็น PK)
      $('#empID').prop('readonly', true);
      $('#addEmpAppend').addClass('d-none');
      $('#viewModeBanner').removeClass('d-none');
      $('#btnEnterEditMode').prop('disabled', false)
        .attr('title', 'กำลังอยู่ในโหมดแก้ไขข้อมูล');
      $('#btnSubmitRegister')
        .html('<i class="fas fa-save mr-2"></i> บันทึกการแก้ไขข้อมูล');
    } else {
      // 'new' = สร้างใหม่: แก้ไขได้ทุกช่อง ปุ่มแก้ไขถูกปิดไว้ (ยังไม่มีข้อมูลให้แก้)
      $('#empID').prop('readonly', false);
      $('#addEmpAppend').addClass('d-none'); // ให้ autocomplete จัดการเอง
      $('#viewModeBanner').addClass('d-none');
      $('#btnEnterEditMode').prop('disabled', true)
        .attr('title', 'โหลดข้อมูลผู้ขึ้นทะเบียนก่อน จึงจะแก้ไขได้');
      $('#btnSubmitRegister')
        .html('<i class="fas fa-save mr-2"></i> บันทึกข้อมูลการขึ้นทะเบียน');
    }
  }

  // jQuery Validation Defaults
  jQuery.validator.setDefaults({
    errorElement: 'span',
    errorPlacement: function (error, element) {
      error.addClass('invalid-feedback');
      if (element.prop('type') === 'radio') {
        element.closest('.d-flex, .pt-2').after(error);
      } else if (element.parent('.input-group').length) {
        error.insertAfter(element.parent());
      } else {
        error.insertAfter(element);
      }
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('is-invalid');
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('is-invalid');
    }
  });

  // Main Form Validation
  $('#registerForm').validate({
    rules: {
      EmpID: { required: true, digits: true, minlength: 13, maxlength: 13 },
      Titles: { required: true },
      EmpName: { required: true },
      SexNo: { required: true },
      KNo: { required: true },
      RDate: { required: true },
      QNo: { required: true },
      EqNo: { required: true },
      PotNo: { required: true }
    },
    messages: {
      EmpID: { required: "กรุณากรอกเลขบัตรประชาชน", digits: "กรุณากรอกเฉพาะตัวเลข", minlength: "ต้องครบ 13 หลัก", maxlength: "ต้องครบ 13 หลัก" },
      Titles: "เลือกคำนำหน้า",
      EmpName: "กรุณากรอกชื่อ-นามสกุล",
      SexNo: "เลือกเพศ",
      KNo: "เลือกเขตพื้นที่",
      RDate: "ระบุวันที่",
      QNo: "เลือกสาเหตุ",
      EqNo: "เลือกวุฒิการศึกษา",
      PotNo: "เลือกตำแหน่ง"
    },
    submitHandler: function(form) {
      var $btnSubmit = $(form).find('button[type="submit"]');
      $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังประมวลผล...');

      var docNo = $('#registerForm').data('docNo');
      var url = docNo ? 'api/register_update.php' : 'api/register_save.php';
      var formData = $(form).serialize();
      if (docNo) {
        formData += '&DocNo=' + docNo;
      }

      $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: formData
      })
      .done(function (res) {
        if (res && res.success) {
          var isUpdate = docNo ? true : false;

          if (isUpdate) {
            Swal.fire({
              icon: 'success',
              title: 'แก้ไขข้อมูลเรียบร้อย!',
              text: 'บันทึกการแก้ไขสำเร็จแล้ว',
              confirmButtonText: 'ตกลง',
              confirmButtonColor: '#002D62'
            }).then(() => {
              form.reset();
              $('#eqNo').val('').trigger('change');
              $('#potNo').val('').trigger('change');
              $('#kNo').val('').trigger('change');
              $('#registerForm').data('docNo', null);
              document.querySelector('#rDate')._flatpickr.setDate('today', true);
              setRegisterMode('new');
              window.scrollTo({ top: 0, behavior: 'smooth' });
            });
          } else {
            var docID = res.data.DocID || '';
            var seq = docID.split('/')[1] || docID;

            // Display on page
            $('#resultDocID').text(seq);
            $('#successAlert').removeClass('d-none');

            // Disable form
            $('#registerForm').addClass('form-disabled');
            $('#registerForm').find('input, select, textarea, button').prop('disabled', true);

            Toast.fire({ icon: 'success', title: 'ขึ้นทะเบียนสำเร็จ!' });

            setTimeout(() => {
              form.reset();
              $('#eqNo').val('').trigger('change');
              $('#potNo').val('').trigger('change');
              $('#kNo').val('').trigger('change');
              document.querySelector('#rDate')._flatpickr.setDate('today', true);
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 1000);
          }
        } else {
          Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.message || 'ไม่สามารถบันทึกได้' });
        }
      })
      .fail(function(xhr) {
         var msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
         if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
         }
         Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg });
      })
      .always(function () {
        $btnSubmit.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> บันทึกข้อมูลการขึ้นทะเบียน');
      });
    }
  });

  // Add/Edit Employee Modal Form Validation
  $('#empModalForm').validate({
    rules: {
      EmpID: { required: true, digits: true, minlength: 13, maxlength: 13 },
      Titles: { required: true },
      EmpName: { required: true },
      SexNo: { required: true },
      KNo: { required: true }
    },
    messages: {
      EmpID: "กรุณากรอกเลขบัตร 13 หลัก",
      Titles: "ระบุคำนำหน้า",
      EmpName: "กรุณากรอกชื่อ",
      SexNo: "ระบุเพศ",
      KNo: "ระบุเขต"
    },
    submitHandler: function(form) {
      var action = $('#empModalAction').val();
      var url = (action === 'edit') ? 'api/employee_update.php' : 'api/employee_add.php';
      
      var $btn = $('#btnSaveModalEmp');
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>บันทึก...');
      
      $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: $(form).serialize()
      })
      .done(function (res) {
        if (res && res.success) {
          fillMainForm(res.data);
          $('#empModal').modal('hide');
          if (action === 'add') $('#addEmpAppend').addClass('d-none');
          Toast.fire({ icon: 'success', title: 'บันทึกข้อมูลเรียบร้อย' });
        } else {
          Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: res.message || 'กรุณาลองใหม่อีกครั้ง' });
        }
      })
      .fail(function() {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
      })
      .always(function () {
        $btn.prop('disabled', false).html('บันทึกข้อมูล');
      });
    }
  });

  // Add/Edit Employee Modal Buttons
  $('#btnAddEmp').on('click', function () {
    $('#empModalForm')[0].reset();
    $('#empModalForm').validate().resetForm();
    $('.is-invalid', '#empModalForm').removeClass('is-invalid');
    $('#modalEmpID').val($('#empID').val().trim()).prop('readonly', false);
    $('#empModalTitle').html('<i class="fas fa-user-plus mr-2"></i> เพิ่มข้อมูลผู้ว่างงานใหม่');
    $('#empModalAction').val('add');
    $('#empModal').modal('show');
  });

  $('#btnEditEmp').on('click', function () {
    var empID = $('#empID').val().trim();
    if (!/^\d{13}$/.test(empID)) {
      Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณากรอกเลขบัตรประชาชน 13 หลักให้ถูกต้องก่อนแก้ไข' });
      return;
    }
    
    $('#empModalForm')[0].reset();
    $('#empModalForm').validate().resetForm();
    $('.is-invalid', '#empModalForm').removeClass('is-invalid');
    
    $('#modalEmpID').val(empID).prop('readonly', true);
    $('#modalEmpName').val($('#empName').val());
    var titleVal = $('input[name="Titles"]:checked', '#registerForm').val();
    if (titleVal) $('input[name="Titles"][value="' + titleVal + '"]', '#empModalForm').prop('checked', true);
    var sexVal = $('input[name="SexNo"]:checked', '#registerForm').val();
    if (sexVal) $('input[name="SexNo"][value="' + sexVal + '"]', '#empModalForm').prop('checked', true);
    $('#modalKNo').val($('#kNo').val());
    $('#modalPhone').val($('#phone').val());
    $('#modalAddress').val($('#address').val());

    $('#empModalTitle').html('<i class="fas fa-user-edit mr-2"></i> แก้ไขข้อมูลผู้ว่างงาน');
    $('#empModalAction').val('edit');
    $('#empModal').modal('show');
  });

  // Educational Qualification Modal
  $('#btnAddEdu').on('click', function() {
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
        // Add to dropdown and select it
        var newOption = new Option(res.data.EqName, res.data.EqNo, true, true);
        $('#eqNo').append(newOption).trigger('change');
        $('#eduModal').modal('hide');
        Toast.fire({ icon: 'success', title: 'เพิ่มวุฒิการศึกษาเรียบร้อย' });
      } else {
        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: res.message || 'กรุณาลองใหม่อีกครั้ง' });
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
    })
    .always(function() {
      $btn.prop('disabled', false).html('บันทึกวุฒิการศึกษา');
    });
  });

  // Position Modal
  $('#btnAddPot').on('click', function() {
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
        $('#potNo').append(newOption).trigger('change');
        $('#potModal').modal('hide');
        Toast.fire({ icon: 'success', title: 'เพิ่มตำแหน่งเรียบร้อย' });
      } else {
        Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: res.message || 'กรุณาลองใหม่อีกครั้ง' });
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
    })
    .always(function() {
      $btn.prop('disabled', false).html('บันทึกตำแหน่ง');
    });
  });

  // Auto Gender Selection based on Title
  function handleTitleChange(name, targetSexName) {
    const titleVal = parseInt($(`input[name="${name}"]:checked`).val());
    if (titleVal === 1) { // นาย
      $(`input[name="${targetSexName}"][value="1"]`).prop('checked', true);
    } else if (titleVal === 2 || titleVal === 3) { // นางสาว, นาง
      $(`input[name="${targetSexName}"][value="2"]`).prop('checked', true);
    }
  }

  $(document).on('change', 'input[name="Titles"]', function() {
    const isModal = $(this).closest('#empModal').length > 0;
    if (isModal) {
      handleTitleChange('Titles', 'SexNo'); // Within modal, both names are same but handled by context
    } else {
      handleTitleChange('Titles', 'SexNo');
    }
  });

  // Search Register Feature
  $('#btnSearchRegister').on('click', function() {
    $('#searchEmpID').val('');
    $('#searchEmpName').val('');
    $('#searchResultContainer').addClass('d-none');
    $('#searchResultBody').empty();
    $('#searchRegisterModal').modal('show');
  });

  $('#btnPerformSearch').on('click', function() {
    var empID = $('#searchEmpID').val().trim();
    var empName = $('#searchEmpName').val().trim();

    if (empID === '' && empName === '') {
      Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณากรอกเลขบัตรประชาชน หรือ ชื่อ-นามสกุล' });
      return;
    }

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังค้นหา...');

    $.ajax({
      url: 'api/register_search.php',
      type: 'GET',
      dataType: 'json',
      data: { empID: empID, empName: empName }
    })
    .done(function(res) {
      if (res && res.success && res.data && res.data.length > 0) {
        var tbody = $('#searchResultBody');
        tbody.empty();

        res.data.forEach(function(row, idx) {
          var tr = $('<tr>')
            .attr('data-docno', row.DocNo)
            .append($('<td>').text(row.EmpID))
            .append($('<td>').text(row.EmpName))
            .append($('<td>').text(row.RDate))
            .append($('<td>').text(row.KName))
            .append($('<td>').html(
              '<button type="button" class="btn btn-sm btn-primary btnEditRegister mr-1" data-docno="' + row.DocNo + '" title="แก้ไข">' +
                '<i class="fas fa-edit"></i>' +
              '</button>' +
              '<button type="button" class="btn btn-sm btn-danger btnDeleteRegister" data-docno="' + row.DocNo + '" title="ลบ">' +
                '<i class="fas fa-trash"></i>' +
              '</button>'
            ));
          tbody.append(tr);
        });

        $('#searchResultContainer').removeClass('d-none');
        $('#noResultMsg').addClass('d-none');
      } else {
        $('#searchResultBody').empty();
        $('#searchResultContainer').removeClass('d-none');
        $('#noResultMsg').removeClass('d-none');
      }
    })
    .fail(function(xhr) {
      var msg = 'เกิดข้อผิดพลาดในการค้นหา';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        msg = xhr.responseJSON.message;
      }
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg });
    })
    .always(function() {
      $btn.prop('disabled', false).html('<i class="fas fa-search mr-2"></i> ค้นหา');
    });
  });

  // Edit Register
  $(document).on('click', '.btnEditRegister', function() {
    var docNo = $(this).data('docno');

    $.ajax({
      url: 'api/register_detail.php',
      type: 'GET',
      dataType: 'json',
      data: { doc: docNo }
    })
    .done(function(res) {
      if (res && res.success && res.data) {
        var data = res.data;

        $('#empID').val(data.EmpID);
        $('#empName').val(data.EmpName || '');

        $('input[name="Titles"]', '#registerForm').prop('checked', false);
        if (data.TitleNo) {
          $('input[name="Titles"][value="' + data.TitleNo + '"]', '#registerForm').prop('checked', true);
        }

        $('input[name="SexNo"]', '#registerForm').prop('checked', false);
        if (data.SexNo) {
          $('input[name="SexNo"][value="' + data.SexNo + '"]').prop('checked', true);
        }
        if (data.KNo) {
          $('#kNo').val(data.KNo).trigger('change');
        }
        $('#phone').val(data.Phone || '');
        $('#lineID').val(data.lineID || '');
        $('#address').val(data.Address || '');

        $('input[name="QNo"]', '#registerForm').prop('checked', false);
        if (data.QNo) {
          $('input[name="QNo"][value="' + data.QNo + '"]').prop('checked', true);
        }
        if (data.EqNo) {
          $('#eqNo').val(data.EqNo).trigger('change');
        }
        if (data.PotNo) {
          $('#potNo').val(data.PotNo).trigger('change');
        }
        if (data.RDate) {
          document.querySelector('#rDate')._flatpickr.setDate(data.RDate, true);
        }

        // Store DocNo for update
        $('#registerForm').data('docNo', docNo);

        // เข้าสู่โหมดแก้ไขทันที — แก้ไขได้ทุกช่อง
        setRegisterMode('edit');

        $('#searchRegisterModal').modal('hide');
        $('#successAlert').addClass('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        Toast.fire({ icon: 'info', title: 'โหลดข้อมูลเรียบร้อย — แก้ไขได้ทันที' });
      }
    })
    .fail(function(xhr) {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้' });
    });
  });

  // Delete Register
  $(document).on('click', '.btnDeleteRegister', function() {
    var docNo = $(this).data('docno');

    Swal.fire({
      icon: 'warning',
      title: 'ยืนยันการลบ',
      text: 'คุณแน่ใจหรือที่จะลบข้อมูลการขึ้นทะเบียนนี้?',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'ลบ',
      cancelButtonText: 'ยกเลิก'
    }).then(function(result) {
      if (result.isConfirmed) {
        $.ajax({
          url: 'api/register_delete.php',
          type: 'POST',
          dataType: 'json',
          data: { DocNo: docNo }
        })
        .done(function(res) {
          if (res && res.success) {
            Toast.fire({ icon: 'success', title: 'ลบข้อมูลเรียบร้อย' });
            $('#btnPerformSearch').click();
          } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message || 'ไม่สามารถลบได้' });
          }
        })
        .fail(function() {
          Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
        });
      }
    });
  });

  // ปุ่มแก้ไขข้อมูล (แสดงตลอด) — ใช้งานได้เมื่อโหลดข้อมูลเดิมมาแล้ว
  $('#btnEnterEditMode').on('click', function() {
    if (!$('#registerForm').data('docNo')) return; // ยังไม่มีข้อมูลให้แก้
    setRegisterMode('edit');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    $('#empName').focus();
    Toast.fire({ icon: 'info', title: 'แก้ไขข้อมูลได้ทุกช่อง แล้วกดบันทึกการแก้ไข' });
  });

  // Reset -> กลับสู่โหมดสร้างใหม่
  $('#btnResetRegister').on('click', function() {
    setTimeout(function() {
      $('#eqNo').val('').trigger('change');
      $('#potNo').val('').trigger('change');
      $('#kNo').val('').trigger('change');
      $('#registerForm').data('docNo', null);
      document.querySelector('#rDate')._flatpickr.setDate('today', true);
      setRegisterMode('new');
    }, 0);
  });

  // Print Receipt
  $('#btnPrintReceipt').on('click', function() {
    var docID = $('#resultDocID').text();
    if (docID) {
      window.open('register_management.php?print=' + docID, '_blank');
    }
  });

  // New Register
  $('#btnNewRegister').on('click', function() {
    $('#successAlert').addClass('d-none');

    // Enable form
    $('#registerForm').removeClass('form-disabled');
    $('#registerForm').find('input, select, textarea, button').prop('disabled', false);

    setTimeout(() => {
      $('#registerForm')[0].reset();
      $('#eqNo').val('').trigger('change');
      $('#potNo').val('').trigger('change');
      $('#kNo').val('').trigger('change');
      $('#registerForm').data('docNo', null);
      document.querySelector('#rDate')._flatpickr.setDate('today', true);
      setRegisterMode('new');
      $('#empID').focus();
      $('html, body').animate({ scrollTop: $('#registerForm').offset().top - 50 }, 'smooth');
    }, 300);
  });

  // Add hover effect for new register button in success card
  $('#btnNewRegister').on('mouseenter', function() {
    $(this).css({
      'background': 'rgba(255, 255, 255, 0.2)',
      'border-color': 'var(--gov-gold)',
      'color': 'var(--gov-gold)'
    });
  }).on('mouseleave', function() {
    $(this).css({
      'background': 'transparent',
      'border-color': 'rgba(255, 255, 255, 0.5)',
      'color': 'white'
    });
  });
});
</script>
</body>
</html>
