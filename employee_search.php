<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo  = getDB();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ค้นประวัติ ขึ้นทะเบียน/รายงานตัว | Government Digital Service</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
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

    .search-container {
      max-width: 800px;
      margin: 0 auto;
    }

    .input-search-gov {
      font-size: 1.5rem !important;
      font-weight: 600 !important;
      letter-spacing: 0.1em;
      text-align: center;
      border-radius: 12px !important;
      height: 70px !important;
      border: 2px solid var(--gov-border) !important;
      box-shadow: var(--gov-shadow);
    }
    .input-search-gov:focus {
      border-color: var(--gov-royal) !important;
      box-shadow: 0 0 0 0.25rem rgba(0, 94, 184, 0.25) !important;
    }

    .history-label {
      font-size: 0.85rem;
      color: var(--gov-text-muted);
      margin-bottom: 2px;
      text-transform: uppercase;
      font-weight: 500;
    }
    .history-value {
      font-weight: 600;
      color: var(--gov-navy);
      font-size: 1.1rem;
    }

    .table-history thead th {
      background-color: var(--gov-gray);
      color: var(--gov-navy);
      font-weight: 600;
      font-size: 0.85rem;
      border-bottom: 2px solid var(--gov-border);
      padding: 12px;
    }
    .table-history td {
      padding: 12px;
      vertical-align: middle;
    }

    @media print {
      .no-print, .main-sidebar, .main-header, .main-footer { display: none !important; }
      .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: white !important; }
      .gov-card { box-shadow: none !important; border: 1px solid #eee !important; }
      .report-paper { padding: 0 !important; }
      .gov-page-header { background: white !important; color: black !important; border-bottom: 2px solid black; }
      .gov-page-title { color: black !important; }
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light no-print">
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

  <?php include 'includes/sidebar.php'; ?>

  <div class="content-wrapper">

    <!-- Page Header -->
    <div class="gov-page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">ค้นประวัติ ขึ้นทะเบียน/รายงานตัว</h1>
            <p class="mb-0 opacity-9">ตรวจสอบประวัติย้อนหลังด้วยเลขบัตรประจำตัวประชาชน</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block">
             <i class="fas fa-history fa-4x opacity-2"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid px-lg-5">

        <div class="search-container no-print mb-5">
          <div class="form-group">
            <label class="form-label d-block text-center mb-3" style="font-size: 1.1rem; color: var(--gov-navy);">ระบุเลขบัตรประชาชน 13 หลัก</label>
            <div class="input-group">
               <input type="text" id="searchID" class="form-control input-search-gov" placeholder="X-XXXX-XXXXX-XX-X" maxlength="13" autofocus>
               <div class="input-group-append">
                  <button class="btn btn-primary px-4" id="btnSearch" style="background-color: var(--gov-navy); border-color: var(--gov-navy); border-radius: 0 12px 12px 0;">
                    <i class="fas fa-search fa-lg"></i>
                  </button>
               </div>
            </div>
          </div>
        </div>

        <div id="searchResult" class="d-none">
           <!-- Result Content -->
           <div class="gov-card">
              <div class="gov-card-header bg-light d-flex align-items-center">
                 <h3 class="gov-card-title"><i class="fas fa-id-card mr-2 text-royal"></i> ข้อมูลส่วนบุคคล</h3>
                 <button onclick="window.print()" class="btn btn-gov-outline btn-sm ml-auto no-print">
                   <i class="fas fa-print mr-1"></i>พิมพ์ประวัติ
                 </button>
              </div>
              <div class="gov-card-body p-4">
                 <div class="row">
                    <div class="col-md-4 mb-4">
                       <div class="history-label">เลขบัตรประชาชน</div>
                       <div id="rs-empid" class="history-value h4 text-monospace"></div>
                    </div>
                    <div class="col-md-5 mb-4">
                       <div class="history-label">ชื่อ-นามสกุล</div>
                       <div id="rs-empname" class="history-value h4"></div>
                    </div>
                    <div class="col-md-3 mb-4">
                       <div class="history-label">เพศ</div>
                       <div id="rs-sex" class="history-value"></div>
                    </div>
                    <div class="col-md-4 mb-4">
                       <div class="history-label">เบอร์โทรศัพท์</div>
                       <div id="rs-phone" class="history-value"></div>
                    </div>
                    <div class="col-md-4 mb-4">
                       <div class="history-label">Line ID</div>
                       <div id="rs-line" class="history-value"></div>
                    </div>
                    <div class="col-md-4 mb-4">
                       <div class="history-label">เขตพื้นที่</div>
                       <div id="rs-kate" class="history-value"></div>
                    </div>
                    <div class="col-md-12">
                       <div class="history-label">ที่อยู่ปัจจุบัน</div>
                       <div id="rs-address" class="history-value" style="font-weight: 400;"></div>
                    </div>
                 </div>
              </div>
           </div>

           <div class="row">
              <div class="col-lg-6">
                 <div class="gov-card h-100">
                    <div class="gov-card-header bg-navy text-white" style="border-bottom: 3px solid var(--gov-gold) !important;">
                       <h3 class="gov-card-title text-white"><i class="fas fa-file-contract mr-2"></i> ประวัติการขึ้นทะเบียน</h3>
                    </div>
                    <div class="gov-card-body p-0">
                       <div class="table-responsive">
                          <table class="table table-history mb-0">
                             <thead>
                                <tr>
                                   <th class="text-center" style="width: 100px;">วันที่</th>
                                   <th>เลขที่เอกสาร</th>
                                   <th>สาเหตุออกจากงาน</th>
                                </tr>
                             </thead>
                             <tbody id="rs-reg-list"></tbody>
                          </table>
                       </div>
                    </div>
                 </div>
              </div>
              <div class="col-lg-6">
                 <div class="gov-card h-100">
                    <div class="gov-card-header bg-navy text-white" style="border-bottom: 3px solid var(--gov-gold) !important;">
                       <h3 class="gov-card-title text-white"><i class="fas fa-clipboard-check mr-2"></i> ประวัติการรายงานตัว</h3>
                    </div>
                    <div class="gov-card-body p-0">
                       <div class="table-responsive">
                          <table class="table table-history mb-0">
                             <thead>
                                <tr>
                                   <th class="text-center" style="width: 100px;">วันที่</th>
                                   <th>เขตพื้นที่</th>
                                   <th>สถานะงาน</th>
                                </tr>
                             </thead>
                             <tbody id="rs-rep-list"></tbody>
                          </table>
                       </div>
                    </div>
                 </div>
              </div>
           </div>
        </div>

        <div id="searchEmpty" class="text-center py-5 d-none">
           <div class="mb-3 text-muted"><i class="fas fa-search fa-4x opacity-2"></i></div>
           <h4 class="text-muted">ไม่พบประวัติข้อมูลของเลขบัตรประชาชนนี้</h4>
           <p class="text-muted">โปรดตรวจสอบความถูกต้องของเลข 13 หลักอีกครั้ง</p>
        </div>

        <div id="searchInit" class="text-center py-5">
           <div class="mb-3 text-muted" style="opacity: 0.3;"><i class="fas fa-id-card fa-5x"></i></div>
           <h5 class="text-muted">กรุณากรอกเลขบัตรประชาชนเพื่อค้นหาข้อมูล</h5>
        </div>

      </div>
    </section>
  </div>

  <footer class="main-footer border-top-0 bg-transparent text-center py-4 no-print">
    <div class="text-muted small">
      © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Government Digital Service Platform
    </div>
  </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
$(function() {
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

  function formatDocID(d) {
    if (!d) return '';
    var parts = d.split('/');
    return parts.length > 1 ? parts[1] : d;
  }

  function search() {
    var id = $('#searchID').val().replace(/-/g, '');
    if (id.length !== 13) {
      alert('กรุณากรอกเลขบัตรประชาชนให้ครบ 13 หลัก');
      return;
    }

    $('#btnSearch').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    $('#searchInit').addClass('d-none');
    $('#searchEmpty').addClass('d-none');
    $('#searchResult').addClass('d-none');

    $.getJSON('api/employee_detail.php', { id: id })
      .done(function(res) {
        if (res.success) {
          var d = res.data;
          $('#rs-empid').text(d.EmpID);
          $('#rs-empname').text((d.TitleName ? d.TitleName + ' ' : '') + d.EmpName);
          $('#rs-sex').text(d.SexName || '—');
          $('#rs-phone').text(d.Phone || '—');
          $('#rs-line').text(d.lineID || '—');
          $('#rs-kate').text(d.KName || '—');
          $('#rs-address').text(d.Address || '—');

          var $reg = $('#rs-reg-list').empty();
          if (d.history && d.history.length > 0) {
            d.history.forEach(function(h) {
              $reg.append('<tr><td class="text-center">'+formatThaiDate(h.RDate)+'</td><td class="font-weight-bold text-royal">'+formatDocID(h.DocID)+'</td><td>'+(h.QName || '—')+'</td></tr>');
            });
          } else {
            $reg.append('<tr><td colspan="3" class="text-center py-4 text-muted">ไม่พบประวัติการขึ้นทะเบียน</td></tr>');
          }

          var $rep = $('#rs-rep-list').empty();
          if (d.reporting_history && d.reporting_history.length > 0) {
            d.reporting_history.forEach(function(rh) {
              $rep.append('<tr><td class="text-center">'+formatThaiDate(rh.RDate)+'</td><td>'+(rh.KName || '—')+'</td><td class="text-success font-weight-bold">'+(rh.JName || '—')+'</td></tr>');
            });
          } else {
            $rep.append('<tr><td colspan="3" class="text-center py-4 text-muted">ไม่พบประวัติการรายงานตัว</td></tr>');
          }

          $('#searchResult').removeClass('d-none');
        } else {
          $('#searchEmpty').removeClass('d-none');
        }
      })
      .fail(function() {
        $('#searchEmpty').removeClass('d-none');
      })
      .always(function() {
        $('#btnSearch').prop('disabled', false).html('<i class="fas fa-search fa-lg"></i>');
      });
  }

  $('#btnSearch').click(search);
  $('#searchID').keypress(function(e) {
    if (e.which == 13) search();
  });
});
</script>
</body>
</html>
