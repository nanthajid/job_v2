<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>จัดการตำแหน่งงานว่าง | Government Digital Service</title>

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
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; background-color: var(--gov-bg); color: var(--gov-text-dark); font-size: 16px; }
    h1, h2, h3, h4, .brand-text, .nav-link, .btn { font-family: 'Prompt', sans-serif; }
    .content-wrapper { background-color: var(--gov-bg); padding-bottom: 3rem; }
    .main-header { border-bottom: 3px solid var(--gov-gold) !important; box-shadow: var(--gov-shadow); }
    .gov-card { background: var(--gov-white); border: none; border-radius: 12px; box-shadow: var(--gov-shadow); margin-bottom: 1.5rem; overflow: hidden; }
    .gov-card-header { background-color: transparent; border-bottom: 1px solid var(--gov-gray); padding: 1.25rem 1.5rem; }
    .gov-card-title { font-size: 1.25rem; font-weight: 600; color: var(--gov-navy); margin: 0; }
    .gov-page-header { background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); padding: 2.5rem 0; margin-bottom: 2rem; color: white; box-shadow: var(--gov-shadow); }
    .gov-page-title { font-size: 2rem; font-weight: 600; }
    .table thead th { background-color: var(--gov-gray); color: var(--gov-navy); font-weight: 600; border-bottom: 2px solid var(--gov-border); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.025em; padding: 1rem; }
    .table td { padding: 0.75rem 1rem; vertical-align: middle; border-top: 1px solid var(--gov-gray); }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--gov-royal) !important; color: white !important; border: none !important; border-radius: 4px; }
    .dataTables_filter input { height: auto !important; padding: 0.4rem 0.75rem !important; font-size: 1rem !important; border-radius: 6px !important; border: 1px solid var(--gov-border) !important; margin-left: 0.5rem !important; }
    .dataTables_length select { height: auto !important; padding: 0.4rem 2rem 0.4rem 0.75rem !important; font-size: 1rem !important; border-radius: 6px !important; border: 1px solid var(--gov-border) !important; }
    .btn-action { width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s; }
    .form-label { font-weight: 500; color: var(--gov-navy); margin-bottom: 0.5rem; display: block; }
    .form-control { border-radius: 8px; border: 1px solid var(--gov-border); padding: 0.6rem 1rem; height: auto; font-size: 1rem; }
    .form-control:focus { border-color: var(--gov-royal); box-shadow: 0 0 0 3px rgba(0, 94, 184, 0.15); }
    .border-bottom-dotted { border-bottom: 1.5px dotted #666 !important; }
    .tv-table th, .tv-table td { font-size: 0.9rem; }
    .position-edit-card { border: 1px solid var(--gov-border); border-left: 4px solid var(--gov-royal); border-radius: 10px; background: #fff; margin-bottom: 1rem; overflow: hidden; }
    .position-edit-card .position-edit-card-header { background: #F8FAFC; border-bottom: 1px solid var(--gov-gray); padding: 0.6rem 1rem; display: flex; align-items: center; }
    .position-edit-card .position-edit-card-title { font-size: 0.95rem; font-weight: 600; color: var(--gov-navy); margin: 0; }
    .position-edit-card .position-edit-card-body { padding: 1rem; }
    .position-edit-card label { font-size: 0.8rem; margin-bottom: 0.25rem; }
    .position-edit-card textarea { min-height: 54px; resize: vertical; }
    @media (max-width: 768px) { .gov-page-title { font-size: 1.5rem; } }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars text-navy"></i></a></li>
      <li class="nav-item d-none d-lg-block"><span class="nav-link text-navy font-weight-bold"><i class="fas fa-desktop mr-2"></i>ระบบจัดการคนว่างงาน สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</span></li>
    </ul>
    <ul class="navbar-nav ml-auto">
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
        <div class="dropdown-menu dropdown-menu-right shadow border-0"><a href="logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ</a></div>
      </li>
    </ul>
  </nav>

  <?php include 'includes/sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="gov-page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">จัดการตำแหน่งงานว่าง</h1>
            <p class="mb-0 opacity-9">ค้นหา แก้ไข และลบใบแจ้งตำแหน่งงานว่างที่บันทึกไว้</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block"><i class="fas fa-list-check fa-4x opacity-2"></i></div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid px-lg-5">
        <div class="gov-card">
          <div class="gov-card-header d-flex align-items-center flex-wrap">
            <h3 class="gov-card-title"><i class="fas fa-list mr-2"></i> รายการใบแจ้งตำแหน่งงานว่าง</h3>
            <div class="ml-auto">
              <a href="vacancy_notice.php" class="btn btn-primary px-4" style="background-color: var(--gov-royal); border-color: var(--gov-royal); border-radius: 8px; font-weight: 600;"><i class="fas fa-plus mr-2"></i>เพิ่มใบแจ้งใหม่</a>
            </div>
          </div>
          <div class="gov-card-body p-4">
            <div class="d-flex align-items-end flex-wrap mb-3" style="gap: 0.75rem;">
              <div>
                <label class="form-label mb-1" style="font-size: 0.85rem;">จากวันที่</label>
                <div class="input-group input-group-sm" style="width: 170px;">
                  <div class="input-group-prepend"><span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px;"><i class="far fa-calendar-alt text-navy"></i></span></div>
                  <input type="text" id="filterDateFrom" class="form-control form-control-sm border-left-0" placeholder="เลือกวันที่" readonly style="border-radius: 0 8px 8px 0;">
                </div>
              </div>
              <div>
                <label class="form-label mb-1" style="font-size: 0.85rem;">ถึงวันที่</label>
                <div class="input-group input-group-sm" style="width: 170px;">
                  <div class="input-group-prepend"><span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px;"><i class="far fa-calendar-alt text-navy"></i></span></div>
                  <input type="text" id="filterDateTo" class="form-control form-control-sm border-left-0" placeholder="เลือกวันที่" readonly style="border-radius: 0 8px 8px 0;">
                </div>
              </div>
              <button type="button" id="btnClearDateFilter" class="btn btn-outline-secondary btn-sm px-3"><i class="fas fa-times mr-1"></i>ล้างตัวกรองวันที่</button>
            </div>
            <div class="table-responsive">
              <table id="vnTable" class="table table-hover w-100">
                <thead>
                  <tr>
                    <th style="width: 50px;">ลำดับ</th>
                    <th>สถานประกอบการ</th>
                    <th>ผู้ติดต่อ</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th class="text-center">วันที่แจ้ง</th>
                    <th class="text-center">จำนวนตำแหน่ง</th>
                    <th class="text-center">อัตราที่รับรวม</th>
                    <th class="text-center" style="width: 160px;">จัดการ</th>
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

  <!-- View Modal -->
  <div class="modal fade" id="viewNoticeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header bg-navy text-white py-3 px-4" style="border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white"><i class="fas fa-eye mr-2"></i> รายละเอียดใบแจ้งตำแหน่งงานว่าง</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <div id="viewNoticeLoading" class="text-center py-5"><i class="fas fa-circle-notch fa-spin fa-2x text-royal"></i></div>
          <div id="viewNoticeContent" class="d-none">
            <div class="row">
              <div class="col-md-6 mb-3 border-bottom-dotted pb-2"><label class="small text-muted mb-0">สถานประกอบการ</label><div id="vn-employer" class="h5 font-weight-bold text-navy mb-0"></div></div>
              <div class="col-md-3 mb-3 border-bottom-dotted pb-2"><label class="small text-muted mb-0">ผู้ติดต่อ</label><div id="vn-contact" class="mb-0"></div></div>
              <div class="col-md-3 mb-3 border-bottom-dotted pb-2"><label class="small text-muted mb-0">เบอร์โทรศัพท์</label><div id="vn-phone" class="mb-0"></div></div>
            </div>
            <h6 class="text-navy font-weight-bold mt-3 mb-2">ตำแหน่งงานว่างที่แจ้ง</h6>
            <div class="table-responsive">
              <table class="table table-bordered table-sm tv-table mb-0">
                <thead><tr><th>ลำดับที่</th><th>ตำแหน่งงานว่าง</th><th>จำนวนอัตรา</th><th>อัตราค่าจ้าง</th><th>เพศ</th><th>อายุ</th><th>วุฒิการศึกษา</th><th>เงื่อนไข/สวัสดิการ</th><th>ช่วงวัน-เวลาการทำงาน</th><th>หมายเหตุ</th></tr></thead>
                <tbody id="vn-positions"></tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 px-4 pb-4">
          <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">ปิดหน้าต่าง</button>
          <a href="#" id="vn-print-link" target="_blank" class="btn px-4" style="background-color: #6c757d; color: #fff; border-radius: 8px; font-weight: 600;"><i class="fas fa-print mr-2"></i>พิมพ์รายงาน</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editNoticeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content gov-card border-0">
        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); border-bottom: 3px solid var(--gov-gold) !important;">
          <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i> แก้ไขใบแจ้งตำแหน่งงานว่าง</h5>
          <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <form id="editNoticeForm" autocomplete="off">
          <input type="hidden" name="NoticeID" id="edit-NoticeID">
          <div class="modal-body px-4 pt-4 pb-2" style="background: #F8FAFC;">
            <div id="editNoticeAlert" class="alert alert-danger d-none mb-3" role="alert"></div>
            <h6 class="text-navy font-weight-bold mb-2"><i class="fas fa-building mr-2"></i>ข้อมูลนายจ้างและสถานประกอบการ</h6>
            <div class="form-row">
              <div class="form-group col-md-3"><label class="form-label">เลขประจำตัวนายจ้าง</label><input name="EmployerID" class="form-control"></div>
              <div class="form-group col-md-5"><label class="form-label">ชื่อสถานประกอบการ <span class="text-danger">*</span></label><input name="EmployerName" class="form-control" required></div>
              <div class="form-group col-md-4"><label class="form-label">ประเภทกิจการ</label><input name="BusinessType" class="form-control"></div>
              <div class="form-group col-md-3"><label class="form-label">จำนวนลูกจ้าง</label><input type="number" min="0" name="EmployeeCount" class="form-control"></div>
              <div class="form-group col-md-3"><label class="form-label">เบอร์โทรศัพท์นายจ้าง</label><input name="EmployerPhone" class="form-control"></div>
              <div class="form-group col-md-3"><label class="form-label">ผู้ติดต่อ</label><input name="ContactName" class="form-control"></div>
              <div class="form-group col-md-3"><label class="form-label">ตำแหน่งผู้ติดต่อ</label><input name="ContactPosition" class="form-control"></div>
            </div>

            <h6 class="text-navy font-weight-bold mt-3 mb-2 d-flex align-items-center">
              <span><i class="fas fa-briefcase mr-2"></i>รายละเอียดตำแหน่งงานว่าง</span>
              <button type="button" id="editAddRow" class="btn btn-primary btn-sm ml-auto"><i class="fas fa-plus mr-1"></i>เพิ่มตำแหน่ง</button>
            </h6>
            <div id="editPositionRows"></div>
          </div>
          <div class="modal-footer border-top-0 px-4 pt-2 pb-4" style="background: #F8FAFC;">
            <button type="button" class="btn btn-light px-4" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn px-5" id="btnSaveEditNotice" style="background: linear-gradient(135deg, var(--gov-royal) 0%, var(--gov-navy) 100%); color: white; border: none; border-radius: 8px; font-weight: 600;"><i class="fas fa-save mr-2"></i>บันทึกการแก้ไข</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <footer class="main-footer border-top-0 bg-transparent text-center py-4">
    <div class="text-muted small">© <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2 • Develop By Nanthajd sawasri</div>
  </footer>

</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

<script>
$(function () {
  function escapeHtml(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function dashIfEmpty(s) { s = (s == null ? '' : String(s)).trim(); return s === '' ? '<span class="text-muted">—</span>' : escapeHtml(s); }

  var thaiLang = {
    sProcessing: "กำลังประมวลผล...", sLengthMenu: "แสดง _MENU_ รายการ", sZeroRecords: "ไม่พบข้อมูล",
    sInfo: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ", sSearch: "ค้นหา:",
    oPaginate: { sFirst: "หน้าแรก", sPrevious: "ก่อนหน้า", sNext: "ถัดไป", sLast: "หน้าสุดท้าย" }
  };

  function formatThaiDate(dateStr) {
    if (!dateStr) return '';
    var d = dateStr.split(' ')[0].split('-');
    if (d.length !== 3) return dateStr;
    var monthNames = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    return parseInt(d[2]) + ' ' + monthNames[parseInt(d[1])] + ' ' + (parseInt(d[0]) + 543);
  }

  // ===== Date range filter =====
  var thaiMonthNames = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
  function toThaiBuddhistText(date) {
    if (!date) return '';
    return date.getDate() + ' ' + thaiMonthNames[date.getMonth()] + ' ' + (date.getFullYear() + 543);
  }
  var filterDateFrom = '', filterDateTo = '';
  var fpFilterFrom = flatpickr('#filterDateFrom', {
    locale: 'th', dateFormat: 'Y-m-d', altInput: true, altInputClass: 'form-control form-control-sm border-left-0', allowInput: false,
    onReady: function (d, s, i) { i.altInput.value = toThaiBuddhistText(d[0]); },
    onValueUpdate: function (d, s, i) { i.altInput.value = toThaiBuddhistText(d[0]); },
    onChange: function (d, s, i) {
      i.altInput.value = toThaiBuddhistText(d[0]);
      filterDateFrom = s;
      fpFilterTo.set('minDate', d[0] || null);
      dtNotice.ajax.reload();
    }
  });
  var fpFilterTo = flatpickr('#filterDateTo', {
    locale: 'th', dateFormat: 'Y-m-d', altInput: true, altInputClass: 'form-control form-control-sm border-left-0', allowInput: false,
    onReady: function (d, s, i) { i.altInput.value = toThaiBuddhistText(d[0]); },
    onValueUpdate: function (d, s, i) { i.altInput.value = toThaiBuddhistText(d[0]); },
    onChange: function (d, s, i) {
      i.altInput.value = toThaiBuddhistText(d[0]);
      filterDateTo = s;
      fpFilterFrom.set('maxDate', d[0] || null);
      dtNotice.ajax.reload();
    }
  });
  $('#btnClearDateFilter').on('click', function () {
    fpFilterFrom.clear();
    fpFilterTo.clear();
    fpFilterFrom.set('maxDate', null);
    fpFilterTo.set('minDate', null);
    filterDateFrom = '';
    filterDateTo = '';
    dtNotice.ajax.reload();
  });

  var dtNotice = $('#vnTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: {
      url: 'api/vacancy_notice_search.php', type: 'GET',
      data: function (d) { d.dateFrom = filterDateFrom; d.dateTo = filterDateTo; }
    },
    language: thaiLang,
    order: [[0, 'desc']],
    columns: [
      { data: 'NoticeID', className: 'text-center', orderable: false },
      {
        data: 'EmployerName',
        render: function (d, type, row) {
          if (type !== 'display') return d;
          var name = dashIfEmpty(d);
          if (!row.EmployerNo) return name;
          return name + ' <a href="vacancy_employer_report.php?EmployerNo=' + row.EmployerNo + '" target="_blank" class="ml-1" title="รายงานตำแหน่งงานว่างของนายจ้างนี้ (ทุกใบแจ้ง)"><i class="fas fa-chart-bar text-muted"></i></a>';
        }
      },
      { data: 'ContactName', render: function(d) { return dashIfEmpty(d); } },
      { data: 'EmployerPhone', render: function(d) { return dashIfEmpty(d); } },
      { data: 'CreatedAt', className: 'text-center', render: function(d) { return formatThaiDate(d); } },
      { data: 'PositionCount', className: 'text-center' },
      { data: 'TotalHeadcount', className: 'text-center' },
      {
        data: null, className: 'text-center', orderable: false,
        render: function(data, type, row) {
          return '<button class="btn btn-sm btn-info btn-action mr-1" data-action="view" data-id="' + row.NoticeID + '" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>' +
                 '<a class="btn btn-sm btn-secondary btn-action mr-1" href="vacancy_notice_print.php?id=' + row.NoticeID + '" target="_blank" title="พิมพ์รายงาน"><i class="fas fa-print"></i></a>' +
                 '<button class="btn btn-sm btn-warning btn-action mr-1" data-action="edit" data-id="' + row.NoticeID + '" title="แก้ไข"><i class="fas fa-edit"></i></button>' +
                 '<button class="btn btn-sm btn-danger btn-action" data-action="delete" data-id="' + row.NoticeID + '" title="ลบ"><i class="fas fa-trash"></i></button>';
        }
      }
    ]
  });

  // ===== View =====
  $('#vnTable tbody').on('click', 'button[data-action="view"]', function () {
    var id = $(this).data('id');
    $('#viewNoticeLoading').removeClass('d-none');
    $('#viewNoticeContent').addClass('d-none');
    $('#vn-print-link').attr('href', 'vacancy_notice_print.php?id=' + id);
    $('#viewNoticeModal').modal('show');
    $.getJSON('api/vacancy_notice_detail.php', { id: id }).done(function (res) {
      if (!res.success) return;
      var n = res.data.notice, positions = res.data.positions || [];
      $('#vn-employer').text(n.EmployerName || '-');
      $('#vn-contact').text(n.ContactName || '-');
      $('#vn-phone').text(n.EmployerPhone || '-');
      var $tbody = $('#vn-positions').empty();
      if (positions.length === 0) {
        $tbody.append('<tr><td colspan="10" class="text-center text-muted py-3">ไม่พบตำแหน่งงาน</td></tr>');
      } else {
        positions.forEach(function (p, i) {
          $tbody.append('<tr><td class="text-center">' + (i + 1) + '</td><td>' + dashIfEmpty(p.PositionName) + '</td><td class="text-center">' + dashIfEmpty(p.Headcount) + '</td><td>' + dashIfEmpty(p.Wage) + '</td><td>' + dashIfEmpty(p.Gender) + '</td><td>' + dashIfEmpty(p.AgeRange) + '</td><td>' + dashIfEmpty(p.Education) + '</td><td>' + dashIfEmpty(p.Conditions) + '</td><td>' + dashIfEmpty(p.WorkSchedule) + '</td><td>' + dashIfEmpty(p.Remark) + '</td></tr>');
        });
      }
      $('#viewNoticeLoading').addClass('d-none');
      $('#viewNoticeContent').removeClass('d-none');
    });
  });

  // ===== Edit: position card builder =====
  var editRowIndex = 0;
  function field(idx, name, label, colClass, opts) {
    opts = opts || {};
    var val = opts.val != null ? String(opts.val) : '';
    var inputHtml;
    if (opts.textarea) {
      inputHtml = '<textarea class="form-control form-control-sm" name="positions[' + idx + '][' + name + ']" rows="2">' + escapeHtml(val) + '</textarea>';
    } else if (opts.dateField) {
      inputHtml = '<input type="text" readonly class="form-control form-control-sm expire-date-picker" name="positions[' + idx + '][' + name + ']" value="' + escapeHtml(val) + '" placeholder="เลือกวันที่">';
    } else {
      var type = opts.number ? 'number" min="1"' : 'text"';
      inputHtml = '<input type="' + type + ' class="form-control form-control-sm" name="positions[' + idx + '][' + name + ']" value="' + escapeHtml(val || (opts.number ? '1' : '')) + '" ' + (opts.required ? 'required' : '') + '>';
    }
    return '<div class="form-group ' + colClass + '"><label class="form-label">' + label + (opts.required ? ' <span class="text-danger">*</span>' : '') + '</label>' + inputHtml + '</div>';
  }

  function addEditRow(p) {
    p = p || {};
    var idx = editRowIndex++;
    var html =
      '<div class="position-edit-card" data-idx="' + idx + '">' +
        '<div class="position-edit-card-header">' +
          '<h6 class="position-edit-card-title position-card-label">ตำแหน่งที่ 1</h6>' +
          '<button type="button" class="btn btn-outline-danger btn-sm ml-auto remove-edit-row"><i class="fas fa-trash mr-1"></i>ลบตำแหน่งนี้</button>' +
        '</div>' +
        '<div class="position-edit-card-body">' +
          '<div class="form-row">' +
            field(idx, 'PositionName', 'ตำแหน่งงานว่าง', 'col-md-4', { val: p.PositionName, required: true }) +
            field(idx, 'Headcount', 'จำนวนอัตรา', 'col-md-2', { val: p.Headcount, number: true }) +
            field(idx, 'Gender', 'เพศ', 'col-md-2', { val: p.Gender }) +
            field(idx, 'AgeRange', 'อายุ', 'col-md-2', { val: p.AgeRange }) +
            field(idx, 'Education', 'วุฒิการศึกษา', 'col-md-2', { val: p.Education }) +
          '</div>' +
          '<div class="form-row">' +
            field(idx, 'Wage', 'อัตราค่าจ้าง', 'col-md-3', { val: p.Wage }) +
            field(idx, 'WorkSchedule', 'ช่วงวัน/เวลาทำงาน', 'col-md-3', { val: p.WorkSchedule }) +
            field(idx, 'MilitaryStatus', 'สถานภาพทางทหาร', 'col-md-3', { val: p.MilitaryStatus }) +
            field(idx, 'ExpireDate', 'วันหมดเขตรับสมัคร', 'col-md-3', { val: p.ExpireDate, dateField: true }) +
          '</div>' +
          '<div class="form-row">' +
            field(idx, 'Conditions', 'เงื่อนไข/สวัสดิการ/ประสบการณ์', 'col-md-6', { val: p.Conditions, textarea: true }) +
            field(idx, 'Remark', 'หมายเหตุ', 'col-md-6', { val: p.Remark, textarea: true }) +
          '</div>' +
        '</div>' +
      '</div>';
    var $card = $(html);
    $('#editPositionRows').append($card);
    initThaiDatePicker($card.find('.expire-date-picker')[0]);
    renumberEditRows();
  }

  function renumberEditRows() {
    $('#editPositionRows .position-edit-card').each(function (i) {
      $(this).find('.position-card-label').text('ตำแหน่งที่ ' + (i + 1));
    });
  }

  function initThaiDatePicker(el) {
    var fp = flatpickr(el, {
      locale: 'th',
      dateFormat: 'Y-m-d',
      altInput: true,
      altInputClass: 'form-control form-control-sm',
      allowInput: false,
      onReady: function (selectedDates, dateStr, instance) { instance.altInput.value = toThaiBuddhistText(selectedDates[0]); },
      onChange: function (selectedDates, dateStr, instance) { instance.altInput.value = toThaiBuddhistText(selectedDates[0]); },
      onValueUpdate: function (selectedDates, dateStr, instance) { instance.altInput.value = toThaiBuddhistText(selectedDates[0]); }
    });
    return fp;
  }
  $('#editAddRow').on('click', function () { addEditRow(); });
  $('#editPositionRows').on('click', '.remove-edit-row', function () {
    if ($('#editPositionRows .position-edit-card').length > 1) {
      $(this).closest('.position-edit-card').remove();
      renumberEditRows();
    }
  });

  // ===== Edit: open =====
  $('#vnTable tbody').on('click', 'button[data-action="edit"]', function () {
    var id = $(this).data('id');
    $.getJSON('api/vacancy_notice_detail.php', { id: id }).done(function (res) {
      if (!res.success) { alert(res.message || 'ไม่พบข้อมูล'); return; }
      var n = res.data.notice, positions = res.data.positions || [];
      $('#editNoticeAlert').addClass('d-none');
      $('#edit-NoticeID').val(n.NoticeID);
      var form = $('#editNoticeForm')[0];
      ['EmployerID', 'EmployerName', 'BusinessType', 'EmployeeCount', 'EmployerPhone', 'ContactName', 'ContactPosition'].forEach(function (f) {
        $(form).find('[name="' + f + '"]').val(n[f] || '');
      });
      $('#editPositionRows').empty();
      editRowIndex = 0;
      if (positions.length === 0) { addEditRow(); } else { positions.forEach(function (p) { addEditRow(p); }); }
      $('#editNoticeModal').modal('show');
    });
  });

  $('#editNoticeForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#btnSaveEditNotice');
    $btn.prop('disabled', true);
    $('#editNoticeAlert').addClass('d-none');
    $.ajax({ url: 'api/vacancy_notice_update.php', type: 'POST', dataType: 'json', data: $(this).serialize() })
      .done(function (res) {
        if (res.success) {
          $('#editNoticeModal').modal('hide');
          dtNotice.ajax.reload(null, false);
        } else {
          $('#editNoticeAlert').removeClass('d-none').text(res.message || 'บันทึกการแก้ไขไม่สำเร็จ');
        }
      })
      .fail(function () { $('#editNoticeAlert').removeClass('d-none').text('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์'); })
      .always(function () { $btn.prop('disabled', false); });
  });

  // ===== Delete =====
  $('#vnTable tbody').on('click', 'button[data-action="delete"]', function () {
    var id = $(this).data('id');
    if (!confirm('ยืนยันการลบใบแจ้งนี้หรือไม่')) return;
    $.post('api/vacancy_notice_delete.php', { NoticeID: id }, function (res) {
      if (res.success) { dtNotice.ajax.reload(null, false); } else { alert(res.message || 'ลบไม่สำเร็จ'); }
    }, 'json');
  });
});
</script>
</body>
</html>
