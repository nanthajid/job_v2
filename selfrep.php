<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo  = getDB();

$kateRows = $pdo->query("SELECT KNo, KName FROM kate ORDER BY KNo")->fetchAll();
$quitRows = $pdo->query("SELECT QNo, QName FROM quit ORDER BY QNo")->fetchAll();
$jobRows  = $pdo->query("SELECT JNo, JName FROM job  ORDER BY JNo")->fetchAll();
$sexRows  = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll();
$titlesRows = $pdo->query("SELECT DocNo, Title FROM titles ORDER BY DocNo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายงานตัวว่างงาน | Government Digital Service</title>

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
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    :root {
      --gov-navy: #002D62;
      --gov-royal: #005EB8;
      --gov-teal: #20c997;
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
      border-color: var(--gov-teal);
      box-shadow: 0 0 0 3px rgba(32, 201, 151, 0.15);
    }

    .custom-gov-radio .custom-control-input:checked ~ .custom-control-label::before {
      background-color: var(--gov-teal);
      border-color: var(--gov-teal);
    }

    .btn-gov-navy-gold {
      background-color: var(--gov-navy);
      color: white;
      border: 2px solid var(--gov-gold);
      border-radius: 8px;
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: all 0.2s;
    }

    .btn-gov-navy-gold:hover {
      background-color: var(--gov-royal);
      color: white;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 45, 98, 0.2);
      border-color: var(--gov-white);
    }

    .btn-gov-outline {
      background-color: transparent;
      color: var(--gov-text-muted);
      border: 1px solid var(--gov-border);
      border-radius: 8px;
      padding: 0.75rem 2rem;
      font-weight: 500;
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
      background: linear-gradient(135deg, var(--gov-navy) 0%, #004d40 100%);
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

    .ui-autocomplete {
      border-radius: 8px;
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--gov-border);
    }

    @media (max-width: 768px) {
      .gov-page-title { font-size: 1.5rem; }
      .btn-gov-teal, .btn-gov-outline { width: 100%; margin-bottom: 0.5rem; }
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
          <i class="far fa-calendar-alt mr-2"></i>
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

    <!-- Hero Page Header -->
    <div class="gov-page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">รายงานตัวว่างงาน</h1>
            <p class="mb-0 opacity-9">ระบบบันทึกสถานะการรายงานตัวประจำงวด สำหรับผู้ประกันตนที่ว่างงาน</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-clipboard-check fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid px-lg-5">

        <div id="formAlert" class="alert d-none shadow-sm" role="alert"></div>

        <form id="selfrepForm" autocomplete="off">
          <div class="row">

            <!-- Card: Informant Information -->
            <div class="col-lg-8">
              <div class="gov-card">
                <div class="gov-card-header bg-navy text-white" style="border-bottom: 3px solid var(--gov-gold) !important;">
                  <i class="fas fa-id-card fa-lg text-white mr-3"></i>
                  <h3 class="gov-card-title text-white">ข้อมูลผู้มารายงานตัว</h3>
                </div>
                <div class="gov-card-body">

                  <div class="row mb-4">
                    <div class="col-md-12">
                      <label class="form-label" for="empID">เลขบัตรประจำตัวประชาชน <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <input
                          type="text"
                          id="empID"
                          name="EmpID"
                          class="form-control form-control-lg"
                          placeholder="กรอกเลขบัตร 13 หลัก"
                          maxlength="13"
                          inputmode="numeric"
                          required>
                        <div class="input-group-append d-none" id="addEmpAppend">
                          <button type="button" id="btnAddEmp" class="btn btn-success px-4">
                            <i class="fas fa-user-plus mr-2"></i>เพิ่มใหม่
                          </button>
                        </div>
                      </div>
                      <small class="text-muted mt-2 d-block">ค้นหาข้อมูลผู้ว่างงานที่เคยลงทะเบียนไว้ในระบบ</small>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-7">
                      <label class="form-label">ชื่อ - นามสกุล</label>
                      <div class="input-group">
                        <input type="text" id="empName" class="form-control bg-light" readonly placeholder="จะแสดงหลังค้นหาเลขบัตร">
                        <div class="input-group-append">
                          <button type="button" id="btnEditEmp" class="btn btn-navy-icon px-3" title="แก้ไขข้อมูลพื้นฐาน">
                            <i class="fas fa-user-edit"></i>
                          </button>
                        </div>
                      </div>
                      <input type="hidden" name="Titles" id="empTitles">
                    </div>
                    <div class="col-md-5">
                      <label class="form-label">เพศ <span class="text-danger">*</span></label>
                      <div class="d-flex pt-2">
                        <?php foreach ($sexRows as $s): ?>
                          <div class="custom-control custom-radio custom-gov-radio mr-4">
                            <input
                              type="radio"
                              id="sex_<?= $s['SexNo'] ?>"
                              name="SexNo"
                              class="custom-control-input"
                              value="<?= $s['SexNo'] ?>"
                              required>
                            <label class="custom-control-label" for="sex_<?= $s['SexNo'] ?>">
                              <?= $s['SexName'] ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-7">
                      <label class="form-label" for="kNo">เขตพื้นที่ (ตามทะเบียนบ้าน) <span class="text-danger">*</span></label>
                      <select id="kNo" name="KNo" class="form-control" required>
                        <option value="">— เลือกเขต —</option>
                        <?php foreach ($kateRows as $r): ?>
                          <option value="<?= (int)$r['KNo'] ?>"><?= htmlspecialchars($r['KName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-5">
                      <label class="form-label" for="phone">เบอร์โทรศัพท์ติดต่อ</label>
                      <input type="text" id="phone" name="Phone" class="form-control" placeholder="08XXXXXXXX">
                    </div>
                  </div>

                  <div class="row mb-4">
                    <div class="col-md-12">
                      <label class="form-label" for="address">ที่อยู่ปัจจุบัน</label>
                      <textarea id="address" name="Address" class="form-control" rows="3" placeholder="ระบุเลขที่บ้าน, ซอย, ถนน..."></textarea>
                    </div>
                  </div>
                  
                  <input type="hidden" name="lineID" id="lineID">

                </div>
              </div>
            </div>

            <!-- Card: Report Details -->
            <div class="col-lg-4">
              <div class="gov-card h-100">
                <div class="gov-card-header bg-navy text-white" style="border-bottom: 3px solid var(--gov-gold) !important;">
                  <i class="fas fa-calendar-check fa-lg text-white mr-3"></i>
                  <h3 class="gov-card-title text-white">รายละเอียดการรายงานตัว</h3>
                </div>
                <div class="gov-card-body">
                  <div class="form-group mb-4">
                    <label class="form-label" for="rDate">วันที่มารายงานตัว <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i class="far fa-calendar-alt text-navy"></i></span>
                      </div>
                      <input type="text" id="rDate" name="RDate" class="form-control border-left-0" readonly required>
                    </div>
                  </div>

                  <div class="form-group mb-4">
                    <label class="form-label">สาเหตุที่ออกจากงาน <span class="text-danger">*</span></label>
                    <div class="pt-2">
                      <?php foreach ($quitRows as $r):
                        $qid = 'main_quit_' . (int)$r['QNo'];
                      ?>
                        <div class="custom-control custom-radio custom-gov-radio mb-2">
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

                  <div class="form-group mb-5">
                    <label class="form-label" for="jNo">สถานะการได้งานปัจจุบัน <span class="text-danger">*</span></label>
                    <select id="jNo" name="JNo" class="form-control" required>
                      <option value="">— ระบุสถานะ —</option>
                      <?php foreach ($jobRows as $r): ?>
                        <option value="<?= $r['JNo'] ?>"><?= $r['JName'] ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="d-flex flex-column">
                    <button type="submit" class="btn btn-gov-navy-gold mb-3 shadow-sm">
                      <i class="fas fa-save mr-2"></i> บันทึกการรายงานตัว
                    </button>
                    <button type="reset" class="btn btn-gov-outline">
                      <i class="fas fa-undo mr-2"></i> ล้างข้อมูล
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

  <!-- Modal: Employee (Add/Edit) -->
  <div class="modal fade" id="empModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header border-bottom-0 pt-4 px-4">
          <h5 class="gov-card-title text-navy" id="empModalTitle"><i class="fas fa-user-plus mr-2"></i> เพิ่มข้อมูลผู้ว่างงานใหม่</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <form id="empModalForm" autocomplete="off">
          <input type="hidden" id="empModalAction" value="add">
          <div class="modal-body px-4 pb-4">
            <div id="empModalAlert" class="alert alert-danger d-none" role="alert"></div>
            
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="modalEmpID">เลขบัตรประชาชน <span class="text-danger">*</span></label>
                <input type="text" id="modalEmpID" name="EmpID" class="form-control" maxlength="13" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">คำนำหน้า <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap pt-2">
                  <?php foreach ($titlesRows as $t): ?>
                    <div class="custom-control custom-radio custom-gov-radio mr-3 mb-2">
                      <input type="radio" id="modal_title_<?= $t['DocNo'] ?>" name="Titles" class="custom-control-input" value="<?= $t['DocNo'] ?>" required>
                      <label class="custom-control-label" for="modal_title_<?= $t['DocNo'] ?>"><?= $t['Title'] ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-8 mb-3">
                <label class="form-label" for="modalEmpName">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" id="modalEmpName" name="EmpName" class="form-control" required>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label">เพศ <span class="text-danger">*</span></label>
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
                <label class="form-label" for="modalKNo">เขต <span class="text-danger">*</span></label>
                <select id="modalKNo" name="KNo" class="form-control" required>
                  <option value="">— เลือกเขต —</option>
                  <?php foreach ($kateRows as $r): ?>
                    <option value="<?= $r['KNo'] ?>"><?= $r['KName'] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label" for="modalPhone">เบอร์โทรศัพท์</label>
                <input type="text" id="modalPhone" name="Phone" class="form-control" maxlength="15">
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-12">
                <label class="form-label" for="modalAddress">ที่อยู่ปัจจุบัน</label>
                <textarea id="modalAddress" name="Address" class="form-control" rows="2"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-gov-outline px-4" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-gov-teal px-4" id="btnSaveModalEmp">บันทึกข้อมูล</button>
          </div>
        </form>
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
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>

<script>
$(function () {
  // Config SweetAlert2 global
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });

  flatpickr('#rDate', {
    locale: 'th',
    dateFormat: 'Y-m-d',
    defaultDate: 'today',
    allowInput: false
  });

  $('#empID, #modalEmpID').on('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 13);
  });

  $('#empID').autocomplete({
    minLength: 2,
    delay: 200,
    source: function (request, response) {
      $.getJSON('api/employee_search.php', { q: request.term })
        .done(function (data) {
          response(data);
          var v = request.term.trim();
          if (/^\d{13}$/.test(v)) {
            var exists = data.some(function (d) { return d.EmpID === v; });
            $('#addEmpAppend').toggleClass('d-none', exists);
          } else {
            $('#addEmpAppend').addClass('d-none');
          }
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
    $('#empTitles').val(d.Titles || '');
    if (d.KNo)   $('#kNo').val(d.KNo);
    if (d.SexNo) $('input[name="SexNo"][value="' + d.SexNo + '"]').prop('checked', true);
    $('#phone').val(d.Phone || '');
    $('#address').val(d.Address || '');
    $('#lineID').val(d.lineID || '');
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
  $('#selfrepForm').validate({
    rules: {
      EmpID: { required: true, digits: true, minlength: 13, maxlength: 13 },
      KNo: { required: true },
      SexNo: { required: true },
      RDate: { required: true },
      QNo: { required: true },
      JNo: { required: true }
    },
    messages: {
      EmpID: { required: "กรุณากรอกเลขบัตรประชาชน", digits: "กรุณากรอกเฉพาะตัวเลข", minlength: "ต้องครบ 13 หลัก", maxlength: "ต้องครบ 13 หลัก" },
      KNo: "เลือกเขตพื้นที่",
      SexNo: "เลือกเพศ",
      RDate: "ระบุวันที่",
      QNo: "เลือกสาเหตุ",
      JNo: "เลือกสถานะงาน"
    },
    submitHandler: function(form) {
      var $btnSubmit = $(form).find('button[type="submit"]');
      $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');

      $.ajax({
        url: 'api/selfrep_save.php',
        type: 'POST',
        dataType: 'json',
        data: $(form).serialize()
      })
      .done(function (res) {
        if (res && res.success) {
          var docID = res.data.DocID || '';
          var seq = docID.split('/')[1] || docID;

          Swal.fire({
            icon: 'success',
            title: 'รายงานตัวสำเร็จ!',
            html: 'เลขที่เอกสารลำดับที่: <div style="font-size: 3.5rem; font-weight: 800; color: #002D62; margin: 15px 0;">' + seq + '</div>',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#002D62'
          });

          form.reset();
          document.querySelector('#rDate')._flatpickr.setDate('today', true);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.message || 'ไม่สามารถบันทึกได้' });
        }
      })
      .fail(function() {
         Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์' });
      })
      .always(function () {
        $btnSubmit.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> บันทึกการรายงานตัว');
      });
    }
  });

  // Employee Modal Validation
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
    var titleVal = $('#empTitles').val();
    if (titleVal) $('input[name="Titles"][value="' + titleVal + '"]', '#empModalForm').prop('checked', true);
    var sexVal = $('input[name="SexNo"]:checked', '#selfrepForm').val();
    if (sexVal) $('input[name="SexNo"][value="' + sexVal + '"]', '#empModalForm').prop('checked', true);
    $('#modalKNo').val($('#kNo').val());
    $('#modalPhone').val($('#phone').val());
    $('#modalAddress').val($('#address').val());

    $('#empModalTitle').html('<i class="fas fa-user-edit mr-2"></i> แก้ไขข้อมูลผู้ว่างงาน');
    $('#empModalAction').val('edit');
    $('#empModal').modal('show');
  });
});
</script>
</body>
</html>
