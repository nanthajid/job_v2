<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo = getDB();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>จัดการขึ้นทะเบียน/รายงานตัว | Government Digital Service</title>

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
  <style>
    :root {
      --gov-navy: #002D62;
      --gov-royal: #005EB8;
      --gov-gold: #D4AF37;
    }
  </style>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
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

    @media (max-width: 768px) {
      .gov-card-body { padding: 1rem; }
      .btn-gov-primary { width: 100%; margin-bottom: 0.5rem; }
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
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>จัดการขึ้นทะเบียน/รายงานตัวว่างงาน</h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div id="formAlert" class="alert d-none shadow-sm" role="alert"></div>

        <div class="gov-card">
          <div class="gov-card-header bg-navy text-white">
            <i class="fas fa-search fa-lg text-white mr-3"></i>
            <h3 class="gov-card-title text-white">ค้นหาข้อมูล</h3>
          </div>
          <div class="gov-card-body">
            <div class="row">
              <div class="col-md-3">
                <label class="form-label">วันที่</label>
                <input type="text" id="filterDate" class="form-control" placeholder="เลือกวันที่">
              </div>
              <div class="col-md-3">
                <label class="form-label">เลขบัตรประชาชน</label>
                <input type="text" id="filterEmpID" class="form-control" placeholder="กรอกเลขบัตร" maxlength="13">
              </div>
              <div class="col-md-3">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" id="filterEmpName" class="form-control" placeholder="กรอกชื่อ">
              </div>
              <div class="col-md-3">
                <label class="form-label">ประเภท</label>
                <select id="filterType" class="form-control">
                  <option value="all">ทั้งหมด</option>
                  <option value="register">ลงทะเบียน</option>
                  <option value="selfrep">รายงานตัว</option>
                </select>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-md-12">
                <button id="btnSearch" class="btn btn-gov-primary">
                  <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
                <button id="btnReset" class="btn btn-secondary ml-2">
                  <i class="fas fa-redo mr-2"></i>ล้างตัวกรอง
                </button>
                <button id="btnAdd" class="btn btn-success ml-2" style="border-radius: 8px; font-weight: 600;">
                  <i class="fas fa-plus mr-2"></i>เพิ่มใหม่
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="gov-card">
          <div class="gov-card-header bg-navy text-white">
            <i class="fas fa-list fa-lg text-white mr-3"></i>
            <h3 class="gov-card-title text-white">รายการ (<span id="resultCount">0</span>)</h3>
          </div>
          <div class="gov-card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="dataTable">
                <thead style="background: #F8FAFC;">
                  <tr>
                    <th style="width: 100px;">ประเภท</th>
                    <th style="width: 140px;">เลขบัตร</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th style="width: 120px;">วันที่</th>
                    <th style="width: 100px;">เขต</th>
                    <th style="width: 180px; text-align: center;">จัดการ</th>
                  </tr>
                </thead>
                <tbody id="dataBody">
                  <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                      <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                      ไม่มีข้อมูล
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- ===== Footer ===== -->
  <footer class="main-footer border-top-0 bg-transparent text-center py-4">
    <div class="text-muted small">
      © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2
    </div>
  </footer>

</div>

<!-- ===== Modal: Add New ===== -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); border-bottom: 3px solid var(--gov-gold); border-radius: 12px 12px 0 0;">
        <h5 class="modal-title text-white" style="font-weight: 600;">
          <i class="fas fa-plus-circle mr-2"></i> เพิ่มข้อมูลใหม่
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
      </div>
      <form id="addForm">
        <div class="modal-body px-4 pt-4 pb-2">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">ประเภท <span class="text-danger">*</span></label>
                <select id="addType" name="type" class="form-control" required>
                  <option value="">— เลือกประเภท —</option>
                  <option value="register">ลงทะเบียน</option>
                  <option value="selfrep">รายงานตัว</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">วันที่ <span class="text-danger">*</span></label>
                <input type="text" id="addRDate" name="rDate" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">เลขบัตรประชาชน <span class="text-danger">*</span></label>
            <input type="text" id="addEmpID" name="empID" class="form-control" placeholder="กรอก 13 หลัก" maxlength="13" required>
          </div>
          <div class="form-group">
            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
            <input type="text" id="addEmpName" name="empName" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">เขต <span class="text-danger">*</span></label>
            <select id="addKNo" name="KNo" class="form-control select2" required>
              <option value="">— เลือกเขต —</option>
              <?php
              $kateRows = $pdo->query("SELECT KNo, KName FROM kate ORDER BY KNo")->fetchAll();
              foreach ($kateRows as $k):
              ?>
                <option value="<?= (int)$k['KNo'] ?>"><?= htmlspecialchars($k['KName']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">เบอร์โทรศัพท์</label>
            <input type="text" id="addPhone" name="phone" class="form-control" maxlength="15">
          </div>
          <div class="form-group">
            <label class="form-label">ที่อยู่</label>
            <textarea id="addAddress" name="address" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pt-2 pb-4">
          <button type="button" class="btn btn-light px-4" data-dismiss="modal" style="border-radius: 8px; font-weight: 500; border: 1px solid #DEE2E6;">
            <i class="fas fa-times mr-1"></i> ยกเลิก
          </button>
          <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 600;">
            <i class="fas fa-save mr-2"></i> บันทึก
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== Modal: Edit ===== -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); border-bottom: 3px solid var(--gov-gold); border-radius: 12px 12px 0 0;">
        <h5 class="modal-title text-white" id="editModalTitle" style="font-weight: 600;">
          <i class="fas fa-edit mr-2"></i> แก้ไขข้อมูล
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
      </div>
      <form id="editForm">
        <input type="hidden" id="editType" name="type">
        <input type="hidden" id="editDocNo" name="docNo">
        <div class="modal-body px-4 pt-4 pb-2">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                <select id="editTitles" name="Titles" class="form-control" required>
                  <option value="">— เลือกคำนำหน้า —</option>
                  <?php
                  $titlesRows = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll();
                  foreach ($titlesRows as $t):
                  ?>
                    <option value="<?= (int)$t['TitleNo'] ?>"><?= htmlspecialchars($t['Title']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" id="editEmpName" name="EmpName" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">เพศ <span class="text-danger">*</span></label>
                <select id="editSexNo" name="SexNo" class="form-control" required>
                  <option value="">— เลือกเพศ —</option>
                  <?php
                  $sexRows = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll();
                  foreach ($sexRows as $s):
                  ?>
                    <option value="<?= (int)$s['SexNo'] ?>"><?= htmlspecialchars($s['SexName']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">เขต <span class="text-danger">*</span></label>
                <select id="editKNo" name="KNo" class="form-control select2" required>
                  <option value="">— เลือกเขต —</option>
                  <?php
                  $kateRows = $pdo->query("SELECT KNo, KName FROM kate ORDER BY KNo")->fetchAll();
                  foreach ($kateRows as $k):
                  ?>
                    <option value="<?= (int)$k['KNo'] ?>"><?= htmlspecialchars($k['KName']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">วุฒิการศึกษา <span class="text-danger">*</span></label>
                <select id="editEqNo" name="EqNo" class="form-control select2" required>
                  <option value="">— เลือกวุฒิ —</option>
                  <?php
                  $eduRows = $pdo->query("SELECT EqNo, EqName FROM educational_qualification ORDER BY EqNo")->fetchAll();
                  foreach ($eduRows as $e):
                  ?>
                    <option value="<?= (int)$e['EqNo'] ?>"><?= htmlspecialchars($e['EqName']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">ตำแหน่ง <span class="text-danger">*</span></label>
                <select id="editPotNo" name="PotNo" class="form-control select2" required>
                  <option value="">— เลือกตำแหน่ง —</option>
                  <?php
                  $potRows = $pdo->query("SELECT PotNo, PotName FROM emp_position ORDER BY PotNo")->fetchAll();
                  foreach ($potRows as $p):
                  ?>
                    <option value="<?= (int)$p['PotNo'] ?>"><?= htmlspecialchars($p['PotName']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">สาเหตุที่ออกจากงาน <span class="text-danger">*</span></label>
                <select id="editQNo" name="QNo" class="form-control select2" required>
                  <option value="">— เลือกสาเหตุ —</option>
                  <?php
                  $quitRows = $pdo->query("SELECT QNo, QName FROM quit ORDER BY QNo")->fetchAll();
                  foreach ($quitRows as $q):
                  ?>
                    <option value="<?= (int)$q['QNo'] ?>"><?= htmlspecialchars($q['QName']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label class="form-label">เบอร์โทรศัพท์</label>
                <input type="text" id="editPhone" name="Phone" class="form-control" maxlength="15">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Line ID</label>
            <input type="text" id="editLineID" name="lineID" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">ที่อยู่</label>
            <textarea id="editAddress" name="Address" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pt-2 pb-4">
          <button type="button" class="btn btn-light px-4" data-dismiss="modal" style="border-radius: 8px; font-weight: 500; border: 1px solid #DEE2E6;">
            <i class="fas fa-times mr-1"></i> ยกเลิก
          </button>
          <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 600;">
            <i class="fas fa-save mr-2"></i> บันทึก
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function () {
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });

  // Initialize Select2
  $('.select2').select2({
    theme: 'bootstrap4',
    width: '100%'
  });

  // Flatpickr Setup
  flatpickr('#filterDate', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: 'today'
  });

  // Load data on page load
  loadData();

  // Search button
  $('#btnSearch').on('click', loadData);

  // Reset button
  $('#btnReset').on('click', function() {
    $('#filterDate').val('<?php echo date('Y-m-d'); ?>');
    $('#filterEmpID').val('');
    $('#filterEmpName').val('');
    $('#filterType').val('all');
    loadData();
  });

  // Add new button
  $('#btnAdd').on('click', function() {
    $('#addForm')[0].reset();
    $('#addType').val('').trigger('change');
    $('#addKNo').val('').trigger('change');
    flatpickr('#addRDate', {
      locale: 'th',
      dateFormat: 'Y-m-d',
      defaultDate: 'today'
    });
    $('#addRDate').val('<?php echo date('Y-m-d'); ?>');
    $('#addModal').modal('show');
  });

  function loadData() {
    var rDate = $('#filterDate').val() || '<?php echo date('Y-m-d'); ?>';
    var empID = $('#filterEmpID').val().trim();
    var empName = $('#filterEmpName').val().trim();
    var type = $('#filterType').val();

    $.ajax({
      url: 'api/manage_registration_list.php',
      type: 'GET',
      dataType: 'json',
      data: { rDate: rDate, empID: empID, empName: empName, type: type }
    })
    .done(function(res) {
      if (res && res.success) {
        var tbody = $('#dataBody');
        tbody.empty();

        if (res.data && res.data.length > 0) {
          res.data.forEach(function(row) {
            var typeLabel = row.type === 'register' ? '<span class="badge badge-primary">ลงทะเบียน</span>' : '<span class="badge badge-success">รายงานตัว</span>';
            var tr = $('<tr>')
              .append($('<td>').html(typeLabel))
              .append($('<td>').text(row.EmpID))
              .append($('<td>').text(row.EmpName || '-'))
              .append($('<td>').text(row.RDate))
              .append($('<td>').text(row.KName || '-'))
              .append($('<td>').html(
                '<button class="btn btn-sm btn-info btnEdit" data-type="' + row.type + '" data-docno="' + row.DocNo + '" title="แก้ไข"><i class="fas fa-edit"></i> แก้ไข</button> ' +
                '<button class="btn btn-sm btn-warning btnUnlock" data-type="' + row.type + '" data-docno="' + row.DocNo + '" title="ปลดล็อค"><i class="fas fa-unlock"></i> ปลดล็อค</button> ' +
                '<button class="btn btn-sm btn-danger btnDelete" data-type="' + row.type + '" data-docno="' + row.DocNo + '" title="ลบ"><i class="fas fa-trash"></i> ลบ</button>'
              ));
            tbody.append(tr);
          });
        } else {
          tbody.append('<tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>ไม่มีข้อมูล</td></tr>');
        }

        $('#resultCount').text(res.count);
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการดึงข้อมูล' });
    });
  }

  // Unlock button
  $(document).on('click', '.btnUnlock', function() {
    var type = $(this).data('type');
    var docNo = $(this).data('docno');

    Swal.fire({
      icon: 'question',
      title: 'ปลดล็อค',
      text: 'คุณแน่ใจหรือที่จะปลดล็อคข้อมูลนี้? ผู้ใช้จะสามารถลงทะเบียน/รายงานตัวซ้ำได้',
      showCancelButton: true,
      confirmButtonColor: '#ffc107',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'ปลดล็อค',
      cancelButtonText: 'ยกเลิก'
    }).then(function(result) {
      if (result.isConfirmed) {
        $.ajax({
          url: 'api/manage_registration_delete.php',
          type: 'POST',
          dataType: 'json',
          data: { type: type, docNo: docNo }
        })
        .done(function(res) {
          if (res && res.success) {
            Toast.fire({ icon: 'success', title: 'ปลดล็อคเรียบร้อย' });
            loadData();
          } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message || 'ไม่สามารถปลดล็อคได้' });
          }
        })
        .fail(function() {
          Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
        });
      }
    });
  });

  // Delete button
  $(document).on('click', '.btnDelete', function() {
    var type = $(this).data('type');
    var docNo = $(this).data('docno');

    Swal.fire({
      icon: 'warning',
      title: 'ยืนยันการลบ',
      text: 'คุณแน่ใจหรือที่จะลบข้อมูลนี้?',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'ลบ',
      cancelButtonText: 'ยกเลิก'
    }).then(function(result) {
      if (result.isConfirmed) {
        $.ajax({
          url: 'api/manage_registration_delete.php',
          type: 'POST',
          dataType: 'json',
          data: { type: type, docNo: docNo }
        })
        .done(function(res) {
          if (res && res.success) {
            Toast.fire({ icon: 'success', title: 'ลบข้อมูลเรียบร้อย' });
            loadData();
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

  // ID Card input formatting
  $('#filterEmpID').on('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
  });

  $('#addEmpID').on('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
  });

  // Save add form
  $('#addForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>บันทึก...');

    $.ajax({
      url: 'api/manage_registration_create.php',
      type: 'POST',
      dataType: 'json',
      data: $(this).serialize()
    })
    .done(function(res) {
      if (res && res.success) {
        Toast.fire({ icon: 'success', title: 'บันทึกเรียบร้อย' });
        $('#addModal').modal('hide');
        loadData();
      } else {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message || 'ไม่สามารถบันทึกได้' });
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
    })
    .always(function() {
      $btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>บันทึก');
    });
  });

  // Edit button
  $(document).on('click', '.btnEdit', function() {
    var type = $(this).data('type');
    var docNo = $(this).data('docno');

    $.ajax({
      url: 'api/manage_registration_detail.php',
      type: 'GET',
      dataType: 'json',
      data: { type: type, docNo: docNo }
    })
    .done(function(res) {
      if (res && res.success) {
        var data = res.data;
        $('#editType').val(type);
        $('#editDocNo').val(docNo);
        $('#editTitles').val(data.Titles || '').trigger('change');
        $('#editEmpName').val(data.EmpName || '');
        $('#editSexNo').val(data.SexNo || '').trigger('change');
        $('#editKNo').val(data.KNo || '').trigger('change');
        $('#editEqNo').val(data.EqNo || '').trigger('change');
        $('#editPotNo').val(data.PotNo || '').trigger('change');
        $('#editQNo').val(data.QNo || '').trigger('change');
        $('#editPhone').val(data.Phone || '');
        $('#editLineID').val(data.lineID || '');
        $('#editAddress').val(data.Address || '');
        $('#editModal').modal('show');
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้' });
    });
  });

  // Save edit form
  $('#editForm').on('submit', function(e) {
    e.preventDefault();
    var $btn = $(this).find('button[type="submit"]');
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>บันทึก...');

    $.ajax({
      url: 'api/manage_registration_save.php',
      type: 'POST',
      dataType: 'json',
      data: $(this).serialize()
    })
    .done(function(res) {
      if (res && res.success) {
        Toast.fire({ icon: 'success', title: 'บันทึกเรียบร้อย' });
        $('#editModal').modal('hide');
        loadData();
      } else {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message || 'ไม่สามารถบันทึกได้' });
      }
    })
    .fail(function() {
      Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
    })
    .always(function() {
      $btn.prop('disabled', false).html('<i class="fas fa-save mr-2"></i>บันทึก');
    });
  });
});
</script>
</body>
</html>
