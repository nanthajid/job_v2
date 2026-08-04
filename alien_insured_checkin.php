<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/alien_insured_checkin_helper.php';

$user       = currentUser();
$pdo        = getDB();
$titleRows  = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll();

// ตัวเลือกผู้ส่งมอบเอกสาร — พ่วงตำแหน่งของแต่ละคนไว้เติมช่องตำแหน่งอัตโนมัติ
$staffRows    = alienInsuredStaffOptions($pdo);
$positionRows = alienInsuredPositionOptions($pdo);

// ชื่อพร้อมคำนำหน้าของค่าเริ่มต้น ใช้บอกผู้ใช้ในข้อความใต้ช่อง
$defaultSenderLabel = ALIEN_INSURED_DEFAULT_SENDER;
foreach ($staffRows as $s) {
    if ($s['StName'] === ALIEN_INSURED_DEFAULT_SENDER) { $defaultSenderLabel = $s['DisplayName']; break; }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายงานตัวผู้ประกันตนแรงงานต่างด้าว | Government Digital Service</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    :root {
      --navy: #002D62;
      --royal: #005EB8;
      --gold: #D4AF37;
      --bg: #F0F2F5;
      --gray: #E9ECEF;
      --border: #DEE2E6;
      --muted: #64748B;
      --green: #198754;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
    }

    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; background: var(--bg); color: #1A1A1A; font-size: 16px; }
    h1, h2, h3, h4, .brand-text, .nav-link, .btn { font-family: 'Prompt', 'Sarabun', sans-serif; }
    .main-header { border-bottom: 3px solid var(--gold) !important; box-shadow: var(--shadow); }
    .content-wrapper { background: var(--bg); padding-bottom: 3rem; }
    .page-head { background: linear-gradient(135deg, var(--navy), var(--royal)); color: #fff; padding: 2.5rem 0; margin-bottom: 1.5rem; box-shadow: var(--shadow); }
    .page-head h1 { font-size: 2rem; font-weight: 600; }
    .page-head .page-subtitle { opacity: .9; font-weight: 300; margin: 0; }
    .page-head .page-icon { opacity: .2; }
    .card { border: 0; border-radius: 12px; box-shadow: var(--shadow); margin-bottom: 1.5rem; overflow: hidden; }
    /* หัวการ์ดพื้นกรมท่าคาดทอง — โทนเดียวกับหน้าขึ้นทะเบียน (register.php) */
    .card-header { background: var(--navy); color: #fff; border-bottom: 3px solid var(--gold); padding: 1.15rem 1.5rem; font-weight: 600; font-size: 1.1rem; font-family: 'Prompt', 'Sarabun', sans-serif; }
    .card-header .btn-outline-dark, .card-header .btn-outline-secondary { color: #fff; border-color: rgba(255, 255, 255, .55); }
    .card-header .btn-primary { background: #fff; border-color: #fff; color: var(--navy); font-weight: 600; }
    .card-header .btn-outline-dark:hover, .card-header .btn-outline-secondary:hover,
    .card-header .btn-primary:hover { background: var(--gold); border-color: var(--gold); color: var(--navy); }
    .card-footer { background: #fff; border-top: 1px solid var(--gray); padding: 1rem 1.5rem; }
    .form-label, label { font-weight: 500; color: var(--navy); margin-bottom: .5rem; }
    .form-control { border: 1px solid var(--border); border-radius: 8px; padding: .6rem 1rem; }
    .form-control:focus { border-color: var(--royal); box-shadow: 0 0 0 3px rgba(0, 94, 184, .15); }
    .form-control.is-invalid { border-color: #dc3545; background-image: none; }
    .btn { border-radius: 8px; font-weight: 500; }
    .btn-primary { background: var(--royal); border-color: var(--royal); }
    .btn-primary:hover { background: var(--navy); border-color: var(--navy); }
    .required { color: #dc3545; }
    .small-help { color: var(--muted); font-size: .95rem; }
    .field-hint { color: var(--muted); font-size: .85rem; margin-top: .3rem; }

    /* ---------- Select2 ให้เข้าชุดกับ .form-control ---------- */
    .select2-container--bootstrap4 .select2-selection { border: 1px solid var(--border); border-radius: 8px; min-height: calc(1.5em + 1.2rem + 2px); padding: .35rem .75rem; display: flex; align-items: center; }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { padding-left: 0; line-height: 1.6; color: #1A1A1A; }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: 100%; top: 0; }
    .select2-container--bootstrap4.select2-container--focus .select2-selection { border-color: var(--royal); box-shadow: 0 0 0 3px rgba(0, 94, 184, .15); }
    .select2-dropdown { border: 1px solid var(--border); border-radius: 8px; box-shadow: var(--shadow); z-index: 1060; }
    .select2-results__option { padding: .65rem 1rem; }
    .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] { background: var(--navy); }
    .swal2-popup { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; border-radius: 14px; }
    .swal2-title, .swal2-styled { font-family: 'Prompt', 'Sarabun', sans-serif; }
    .swal2-styled.swal2-confirm { background: var(--royal) !important; border-radius: 8px !important; }
    .swal2-styled.swal2-cancel { border-radius: 8px !important; }

    /* ---------- ตัวบอกขั้นตอน ---------- */
    .wizard-head { background: #fff; border-radius: 12px; box-shadow: var(--shadow); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
    .wizard-mobile-label { display: none; font-family: 'Prompt', 'Sarabun', sans-serif; font-weight: 600; color: var(--navy); margin-bottom: .6rem; }
    .wizard-mobile-label small { display: block; color: var(--muted); font-weight: 400; font-size: .85rem; }
    .wizard-progress { height: 6px; background: var(--gray); border-radius: 99px; overflow: hidden; margin-bottom: 1rem; }
    .wizard-progress-bar { height: 100%; width: 0; background: linear-gradient(90deg, var(--royal), var(--navy)); border-radius: 99px; transition: width .35s ease; }
    .wizard-steps { display: flex; align-items: center; list-style: none; margin: 0; padding: 0; }
    .wizard-step { display: flex; align-items: center; gap: .6rem; color: var(--muted); font-family: 'Prompt', 'Sarabun', sans-serif; font-weight: 500; white-space: nowrap; }
    .wizard-step .step-num { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; background: var(--gray); color: var(--muted); font-weight: 600; flex-shrink: 0; transition: all .2s ease; }
    .wizard-step.done { color: var(--navy); cursor: pointer; }
    .wizard-step.done .step-num { background: var(--green); color: #fff; }
    .wizard-step.active { color: var(--navy); }
    .wizard-step.active .step-num { background: var(--royal); color: #fff; box-shadow: 0 0 0 4px rgba(0, 94, 184, .12); }
    .wizard-line { flex: 1; height: 2px; background: var(--border); margin: 0 1rem; min-width: 20px; }

    .step-panel { display: none; }
    .step-panel.active { display: block; }
    .wizard-actions { display: flex; justify-content: space-between; align-items: center; gap: .75rem; }
    .wizard-actions .right-group { display: flex; gap: .5rem; margin-left: auto; }

    .form-section-title { display: flex; align-items: center; gap: .6rem; margin: 1.75rem 0 1.1rem; padding-bottom: .6rem; border-bottom: 2px solid var(--gray); color: var(--navy); font-family: 'Prompt', 'Sarabun', sans-serif; font-weight: 600; font-size: 1.05rem; }
    .form-section-title:first-child { margin-top: 0; }
    .form-section-title i { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; background: var(--royal); color: #fff; border-radius: 8px; font-size: .85rem; flex-shrink: 0; }

    .step-alert { display: none; background: #fdecea; border: 1px solid #f5c2c7; color: #842029; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.25rem; font-size: .95rem; }
    .step-alert.show { display: block; }
    .draft-bar { display: none; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; background: #e7f1ff; border: 1px solid #b6d4fe; color: #084298; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .draft-bar.show { display: flex; }
    .editing-banner { display: none; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; background: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
    .editing-banner.show { display: flex; }
    .autosave-note { color: var(--muted); font-size: .85rem; }

    /* ---------- ตารางรายชื่อ ---------- */
    #personTable th { background: #f8fafc; color: var(--navy); font-weight: 600; font-size: .95rem; vertical-align: middle; white-space: nowrap; }
    #personTable td { vertical-align: middle; }
    #personTable .form-control { padding: .4rem .65rem; font-size: .95rem; }
    /* ความสูงคงที่ของ .form-control-sm ตัดสระ/วรรณยุกต์ไทยในช่อง select — ให้ padding กำหนดความสูงแทน */
    #personTable select.form-control { height: auto; line-height: 1.5; }
    .seq-cell { text-align: center; font-weight: 600; color: var(--navy); width: 60px; }
    .check-cell { text-align: center; width: 95px; }
    .check-cell input { width: 20px; height: 20px; cursor: pointer; }
    .check-cell .chk-text { display: none; }
    .action-cell { width: 60px; text-align: center; }
    .person-row.row-invalid { background: #fff5f5; }
    .empty-persons { color: var(--muted); text-align: center; padding: 1.5rem 0; }

    /* ---------- ขั้นตอนตรวจสอบ ---------- */
    .review-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1.25rem; margin: 0 0 1.25rem; }
    .review-item { padding: .75rem 1rem; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; }
    .review-item dt { font-size: .85rem; color: var(--muted); font-weight: 500; margin-bottom: .2rem; }
    .review-item dd { margin: 0; color: var(--navy); font-weight: 600; word-break: break-word; }
    .summary-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .summary-chip { background: #eef4fb; color: var(--navy); border-radius: 99px; padding: .4rem .9rem; font-weight: 500; font-size: .9rem; }
    .summary-chip strong { color: var(--royal); }

    /* ---------- ส่วนลงชื่อท้ายตาราง (อ้างอิงแบบฟอร์มใน al_data.md) ---------- */
    .doc-signatures { display: flex; justify-content: flex-end; margin-top: 1.75rem; }
    .doc-signatures .sign-col { width: 60%; color: var(--navy); }
    .doc-signatures .sign-block { text-align: center; margin-bottom: 1.5rem; }
    .doc-signatures .sign-block .line { margin: .3rem 0; }
    .doc-signatures .sign-office { text-align: center; margin-top: -1.1rem; margin-bottom: 1.25rem; font-size: .9rem; color: var(--muted); }

    #docTable th { background: #f8fafc; color: var(--navy); font-weight: 600; white-space: nowrap; }
    #docTable td { vertical-align: middle; }
    /* ---------- หน้าต่างรายละเอียดเอกสาร ---------- */
    #docViewModal .modal-content { border: 0; border-radius: 12px; overflow: hidden; box-shadow: 0 12px 32px rgba(0, 0, 0, .2); }
    #docViewModal .modal-header { background: var(--navy); color: #fff; border-bottom: 3px solid var(--gold); padding: 1.15rem 1.5rem; }
    #docViewModal .modal-title { font-family: 'Prompt', 'Sarabun', sans-serif; font-weight: 600; font-size: 1.1rem; }
    #docViewModal .modal-header .close { color: #fff; text-shadow: none; opacity: .8; }
    #docViewModal .modal-header .close:hover { color: var(--gold); opacity: 1; }
    #docViewModal .modal-body { padding: 1.5rem; }
    #docViewModal .modal-footer { background: #fff; border-top: 1px solid var(--gray); padding: 1rem 1.5rem; }
    #docViewModal table { margin-bottom: 0; }
    #docViewModal th { background: #f8fafc; color: var(--navy); font-weight: 600; white-space: nowrap; }
    .badge-count { background: var(--royal); color: #fff; font-size: .85rem; padding: .35rem .6rem; border-radius: 6px; }
    /* สถานะว่าง/กำลังโหลด — บอกผู้ใช้ว่าทำอะไรต่อได้ ไม่ปล่อยตารางเปล่า */
    .table-state { text-align: center; color: var(--muted); padding: 2.5rem 1rem; }
    .table-state i { font-size: 2.25rem; color: var(--border); display: block; margin-bottom: .75rem; }
    .table-state strong { display: block; color: var(--navy); font-weight: 600; margin-bottom: .25rem; }
    .row-count-badge { background: rgba(255, 255, 255, .18); border: 1px solid rgba(255, 255, 255, .35); color: #fff; font-size: .85rem; font-weight: 500; padding: .25rem .7rem; border-radius: 99px; }
    .doc-actions .btn { margin-left: .2rem; }
    .doc-actions .act-text { display: none; }   /* จอใหญ่ใช้ไอคอน + tooltip ก็พอ */

    /* ---------- มือถือ ---------- */
    @media (max-width: 767.98px) {
      .page-head { padding: 1.75rem 0; margin-bottom: 1rem; }
      .page-head h1 { font-size: 1.4rem; }
      .card-body { padding: 1rem; }
      .card-header, .card-footer { padding: 1rem; }
      .wizard-head { padding: 1rem; }
      .wizard-mobile-label { display: block; }
      .wizard-steps { justify-content: space-between; }
      .wizard-step .step-label { display: none; }
      .wizard-line { margin: 0 .35rem; }

      /* ตารางรายชื่อ → การ์ดต่อคน */
      #personTable, #personTable tbody, #personTable tr, #personTable td { display: block; width: 100%; }
      #personTable thead { display: none; }
      #personTable { border: 0; }
      #personTable tr.person-row { background: #fff; border: 1px solid var(--border); border-left: 4px solid var(--royal); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 2px 6px rgba(0, 0, 0, .05); }
      #personTable td { border: 0 !important; padding: .4rem 0 !important; }
      #personTable td:before { content: attr(data-label); display: block; font-size: .85rem; font-weight: 600; color: var(--navy); margin-bottom: .3rem; }
      #personTable .form-control { font-size: 1rem; min-height: 44px; }
      .seq-cell { text-align: left; padding-bottom: .6rem !important; border-bottom: 1px solid var(--gray) !important; margin-bottom: .4rem; }
      .seq-cell:before { display: inline !important; margin-right: .35rem; }
      .check-cell { display: flex !important; align-items: center; gap: .6rem; width: 100%; }
      .check-cell:before { display: none !important; }
      .check-cell input { width: 24px; height: 24px; }
      .check-cell .chk-text { display: inline; font-weight: 500; color: var(--navy); }
      .action-cell { width: 100%; }
      .action-cell:before { display: none !important; }
      .action-cell .btn { width: 100%; min-height: 44px; }
      .action-cell .btn:after { content: ' ลบรายชื่อนี้'; }

      /* แถบปุ่มติดขอบล่าง กดง่ายด้วยนิ้วโป้ง — ปุ่มหลักอยู่แถวบน ปุ่มย้อนกลับอยู่แถวล่าง */
      .wizard-actions { position: sticky; bottom: 0; background: #fff; margin: -1rem; padding: .75rem; border-top: 1px solid var(--gray); box-shadow: 0 -2px 8px rgba(0, 0, 0, .06); z-index: 5; flex-wrap: wrap; gap: .5rem; }
      .wizard-actions .btn { flex: 1; min-height: 46px; }
      .wizard-actions .right-group { width: 100%; order: 1; margin-left: 0; }
      .wizard-actions .prev-step { width: 100%; order: 2; }
      .review-list { grid-template-columns: 1fr; }
      .doc-signatures .sign-col { width: 100%; }
      /* จอเล็กแตะไอคอนเปล่ายาก — ใส่ข้อความกำกับปุ่มในตารางเอกสาร */
      .doc-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
      .doc-actions .btn { flex: 1 1 45%; margin-left: 0; }
      .doc-actions .act-text { display: inline; margin-left: .35rem; }
      /* ตารางสรุปอ่านยากเมื่อถูกบีบ — ให้เลื่อนแนวนอนแทนการตัดคำ */
      #reviewPersons table, #docViewModal .table-responsive table { min-width: 620px; }
      #docViewModal .modal-body { padding: 1rem; }
      #docTable .btn { min-height: 40px; }
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
      <li class="nav-item d-none d-md-block"><span class="nav-link font-weight-bold text-navy">ระบบจัดการข้อมูลการจ้างงาน</span></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><span class="nav-link"><?= htmlspecialchars($user['StName'] ?? $user['UserName'] ?? 'เจ้าหน้าที่') ?></span></li>
    </ul>
  </nav>

  <?php include 'includes/sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="page-head">
      <div class="container-fluid px-lg-5">
        <div class="row align-items-center">
          <div class="col-md-9">
            <h1 class="h3 mb-1">รายงานตัวผู้ประกันตนแรงงานต่างด้าว</h1>
            <p class="page-subtitle">บันทึกการรายงานตัวของผู้ประกันตนแรงงานต่างด้าว ทีละขั้นตอน · ระบบบันทึกร่างให้อัตโนมัติ</p>
          </div>
          <div class="col-md-3 text-md-right d-none d-md-block">
            <i class="fas fa-clipboard-user fa-4x page-icon"></i>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid px-lg-4">

        <div class="draft-bar" id="draftBar">
          <span><i class="fas fa-clock-rotate-left mr-2"></i>พบข้อมูลที่กรอกค้างไว้เมื่อ <strong id="draftTime"></strong></span>
          <span>
            <button type="button" class="btn btn-sm btn-primary" id="restoreDraft"><i class="fas fa-rotate-left mr-1"></i>กรอกต่อ</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="discardDraft">เริ่มใหม่</button>
          </span>
        </div>

        <div class="editing-banner" id="editingBanner">
          <span><i class="fas fa-pen mr-2"></i>กำลังแก้ไขเอกสารเลขที่ <strong id="editingDocId"></strong></span>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelEdit">ยกเลิกการแก้ไข</button>
        </div>

        <div class="wizard-head">
          <div class="wizard-mobile-label" id="wizardMobileLabel"></div>
          <div class="wizard-progress"><div class="wizard-progress-bar" id="wizardBar"></div></div>
          <ol class="wizard-steps" id="wizardSteps">
            <li class="wizard-step" data-step="0"><span class="step-num">1</span><span class="step-label">ข้อมูลเอกสาร</span></li>
            <li class="wizard-line"></li>
            <li class="wizard-step" data-step="1"><span class="step-num">2</span><span class="step-label">รายชื่อผู้ประกันตน</span></li>
            <li class="wizard-line"></li>
            <li class="wizard-step" data-step="2"><span class="step-num">3</span><span class="step-label">ตรวจสอบและบันทึก</span></li>
          </ol>
        </div>

        <form id="alienForm" autocomplete="off" novalidate>
          <input type="hidden" name="DocID" id="DocID" value="">

          <!-- ขั้นตอนที่ 1 : ข้อมูลเอกสาร -->
          <div class="card step-panel" data-step="0">
            <div class="card-header"><i class="fas fa-file-alt mr-2"></i>ขั้นตอนที่ 1 · ข้อมูลเอกสาร</div>
            <div class="card-body">
              <div class="step-alert" data-alert="0"></div>

              <div class="form-row">
                <div class="form-group col-md-4">
                  <label>วันที่ของเอกสาร <span class="required">*</span></label>
                  <input type="text" name="DocDate" id="DocDate" class="form-control" readonly>
                  <div class="field-hint">ระบบเลือกวันที่ปัจจุบันให้แล้ว แก้ไขได้</div>
                </div>
                <div class="form-group col-md-8">
                  <label>สำนักงานผู้ส่ง <span class="required">*</span></label>
                  <input name="OfficeName" class="form-control" value="<?= htmlspecialchars(ALIEN_INSURED_DEFAULT_OFFICE) ?>">
                  <div class="field-hint">ชื่อนี้จะไปแสดงใต้ลายเซ็นผู้ส่งมอบเอกสารในแบบฟอร์ม</div>
                </div>
              </div>

              <div class="form-section-title"><i class="fas fa-paper-plane"></i>ผู้ส่งมอบเอกสาร</div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>ชื่อผู้ส่งมอบเอกสาร</label>
                  <select name="SenderName" class="form-control select2-field" data-placeholder="เลือกหรือพิมพ์เพื่อค้นหาเจ้าหน้าที่">
                    <option value="">-- เลือกเจ้าหน้าที่ --</option>
                    <?php foreach ($staffRows as $s): ?>
                      <option value="<?= htmlspecialchars($s['DisplayName']) ?>"
                              data-position="<?= htmlspecialchars($s['StPostName'] ?? '') ?>"
                              <?= ALIEN_INSURED_DEFAULT_SENDER === $s['StName'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['DisplayName']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="field-hint">ตั้งค่าเริ่มต้นเป็น <?= htmlspecialchars($defaultSenderLabel) ?> · เลือกคนอื่นแล้วตำแหน่งจะเติมให้อัตโนมัติ</div>
                </div>
                <div class="form-group col-md-6">
                  <label>ตำแหน่ง</label>
                  <select name="SenderPosition" class="form-control select2-field" data-placeholder="เลือกหรือพิมพ์เพื่อค้นหาตำแหน่ง">
                    <option value="">-- เลือกตำแหน่ง --</option>
                    <?php foreach ($positionRows as $posName): ?>
                      <option value="<?= htmlspecialchars($posName) ?>"
                              <?= ALIEN_INSURED_DEFAULT_SENDER_POS === $posName ? 'selected' : '' ?>>
                        <?= htmlspecialchars($posName) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-section-title"><i class="fas fa-inbox"></i>ผู้รับมอบเอกสาร</div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>สำนักงานผู้รับ</label>
                  <input name="ReceiverOffice" class="form-control" value="<?= htmlspecialchars(ALIEN_INSURED_DEFAULT_RECEIVER) ?>">
                </div>
                <div class="form-group col-md-6">
                  <label>ชื่อผู้รับมอบเอกสาร</label>
                  <input name="ReceiverName" class="form-control" placeholder="เว้นว่างได้ หากลงชื่อในเอกสาร">
                  <div class="field-hint">เว้นว่างไว้ ระบบจะพิมพ์เป็นเส้นประให้เซ็นด้วยมือ</div>
                </div>
                <div class="form-group col-12">
                  <label>หมายเหตุของเอกสาร</label>
                  <input name="Remark" class="form-control" placeholder="ไม่บังคับ · เช่น ส่งแทนรอบวันที่ 10">
                </div>
              </div>
            </div>
            <div class="card-footer">
              <div class="wizard-actions">
                <span class="autosave-note d-none d-md-inline" id="autosaveNote1"></span>
                <div class="right-group">
                  <button type="button" class="btn btn-primary next-step">ถัดไป <i class="fas fa-arrow-right ml-1"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- ขั้นตอนที่ 2 : รายชื่อผู้ประกันตน -->
          <div class="card step-panel" data-step="1">
            <div class="card-header d-flex align-items-center flex-wrap" style="gap:.5rem">
              <span><i class="fas fa-users mr-2"></i>ขั้นตอนที่ 2 · รายชื่อผู้ประกันตน</span>
              <span class="row-count-badge" id="personCountBadge">0 รายชื่อ</span>
              <button type="button" id="addPerson" class="btn btn-primary btn-sm ml-auto"><i class="fas fa-plus mr-1"></i>เพิ่มรายชื่อ</button>
            </div>
            <div class="card-body">
              <div class="step-alert" data-alert="1"></div>
              <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" id="personTable">
                  <thead>
                    <tr>
                      <th class="seq-cell">ลำดับ</th>
                      <th style="width:130px">คำนำหน้า</th>
                      <th>ชื่อ-สกุลผู้ประกันตน <span class="required">*</span></th>
                      <th style="width:190px">เลขที่บัตร (ปกส.)</th>
                      <th class="check-cell">เลิกจ้าง</th>
                      <th class="check-cell">ลาออก</th>
                      <th style="width:190px">หมายเหตุ</th>
                      <th class="action-cell"></th>
                    </tr>
                  </thead>
                  <tbody id="personRows"></tbody>
                </table>
              </div>
              <div class="small-help mt-3"><i class="fas fa-circle-info mr-1"></i>กรอกอย่างน้อย 1 รายชื่อ · เลขที่บัตร (ปกส.) เป็นตัวเลข 10-20 หลัก · แถวที่ปล่อยว่างจะไม่ถูกบันทึก · กด <kbd>Enter</kbd> ในช่องหมายเหตุเพื่อเพิ่มแถวถัดไป</div>
            </div>
            <div class="card-footer">
              <div class="wizard-actions">
                <button type="button" class="btn btn-outline-secondary prev-step"><i class="fas fa-arrow-left mr-1"></i>ย้อนกลับ</button>
                <div class="right-group">
                  <button type="button" class="btn btn-primary next-step">ถัดไป <i class="fas fa-arrow-right ml-1"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- ขั้นตอนที่ 3 : ตรวจสอบและบันทึก -->
          <div class="card step-panel" data-step="2">
            <div class="card-header"><i class="fas fa-clipboard-check mr-2"></i>ขั้นตอนที่ 3 · ตรวจสอบและบันทึก</div>
            <div class="card-body">
              <div class="step-alert" data-alert="2"></div>
              <div class="summary-chips" id="summaryChips"></div>
              <dl class="review-list">
                <div class="review-item"><dt>วันที่ของเอกสาร</dt><dd id="reviewDate">-</dd></div>
                <div class="review-item"><dt>สำนักงานผู้ส่ง</dt><dd id="reviewOffice">-</dd></div>
                <div class="review-item"><dt>ผู้ส่งมอบเอกสาร</dt><dd id="reviewSender">-</dd></div>
                <div class="review-item"><dt>ผู้รับมอบเอกสาร</dt><dd id="reviewReceiver">-</dd></div>
              </dl>
              <h6 class="text-navy font-weight-bold mb-2">รายชื่อผู้ประกันตน</h6>
              <div class="table-responsive" id="reviewPersons"></div>
              <div id="reviewSignature"></div>
            </div>
            <div class="card-footer">
              <div class="wizard-actions">
                <button type="button" class="btn btn-outline-secondary prev-step"><i class="fas fa-arrow-left mr-1"></i>ย้อนกลับ</button>
                <div class="right-group">
                  <button type="button" class="btn btn-light" id="resetForm">ล้างข้อมูล</button>
                  <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>ยืนยันและบันทึก</button>
                </div>
              </div>
            </div>
          </div>
        </form>

        <div class="card">
          <div class="card-header d-flex align-items-center flex-wrap" style="gap:.5rem">
            <span><i class="fas fa-folder-open mr-2"></i>เอกสารรายงานตัวที่บันทึกไว้</span>
            <a href="alien_insured_checkin_report_print.php" id="rangePrintLink" class="btn btn-outline-dark btn-sm ml-auto" target="_blank" rel="noopener">
              <i class="fas fa-print mr-1"></i>พิมพ์ตามช่วงวันที่
            </a>
          </div>
          <div class="card-body">
            <div class="form-row align-items-end mb-3">
              <div class="form-group col-md-4">
                <label>ค้นหา (ชื่อ-สกุล / เลขที่บัตร ปกส.)</label>
                <input id="searchQ" class="form-control" placeholder="พิมพ์เพื่อค้นหา">
              </div>
              <div class="form-group col-md-3">
                <label>ตั้งแต่วันที่</label>
                <input id="searchFrom" class="form-control" readonly>
              </div>
              <div class="form-group col-md-3">
                <label>ถึงวันที่</label>
                <input id="searchTo" class="form-control" readonly>
              </div>
              <div class="form-group col-md-2">
                <button type="button" class="btn btn-outline-secondary btn-block" id="clearSearch">ล้างตัวกรอง</button>
              </div>
            </div>
            <div class="form-row mb-3">
              <div class="col-12 d-flex flex-wrap" style="gap:1.25rem">
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="onlyWithPersons" checked>
                  <label class="custom-control-label" for="onlyWithPersons">ซ่อนเอกสารที่ยังไม่มีรายชื่อ</label>
                </div>
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" id="collapseDupCards">
                  <label class="custom-control-label" for="collapseDupCards">ยุบเลขบัตร ปกส. ที่ซ้ำให้เหลือแถวเดียว</label>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="docTable">
                <thead>
                  <tr>
                    <th style="width:70px">ลำดับ</th>
                    <th>ชื่อ-สกุลผู้ประกันตน</th>
                    <th style="width:190px">เลขที่บัตร (ปกส.)</th>
                    <th style="width:90px" class="text-center">เลิกจ้าง</th>
                    <th style="width:90px" class="text-center">ลาออก</th>
                    <th style="width:200px"></th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="d-flex align-items-center flex-wrap mt-3" style="gap:.75rem">
              <span id="docCountLabel" class="text-muted small"></span>
              <button type="button" class="btn btn-outline-primary btn-sm ml-auto" id="loadMoreDocs" hidden>
                <i class="fas fa-angles-down mr-1"></i>โหลดเอกสารเพิ่ม
              </button>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>
</div>

<!-- รายละเอียดเอกสาร — แยกออกมาเป็นหน้าต่างต่างหาก ไม่แทรกกลางตารางรายชื่อ -->
<div class="modal fade" id="docViewModal" tabindex="-1" role="dialog" aria-labelledby="docViewTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="docViewTitle">รายละเอียดเอกสาร</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="ปิด"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body" id="docViewBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const titles = <?= json_encode(array_column($titleRows, 'Title'), JSON_UNESCAPED_UNICODE) ?>;
const thaiMonths = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
const stepNames = ['ข้อมูลเอกสาร', 'รายชื่อผู้ประกันตน', 'ตรวจสอบและบันทึก'];
const docFields = ['DocDate', 'OfficeName', 'SenderName', 'SenderPosition', 'ReceiverOffice', 'ReceiverName', 'Remark'];
const DRAFT_KEY = 'alien_insured_checkin_draft_v1';

const form = document.querySelector('#alienForm');
const panels = [...document.querySelectorAll('.step-panel')];
const senderSelect = form.querySelector('[name="SenderName"]');
const positionSelect = form.querySelector('[name="SenderPosition"]');
let currentStep = 0;
let personIndex = 0;

/* ---------- แจ้งเตือน ---------- */
const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
const alertError = (text, title = 'เกิดข้อผิดพลาด') => Swal.fire({ icon: 'error', title, text, confirmButtonText: 'ตกลง' });
/** กล่องยืนยันก่อนทำสิ่งที่ย้อนกลับไม่ได้ */
function confirmAction({ title, text, confirmText, icon = 'warning' }) {
  return Swal.fire({
    icon, title, text,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: 'ยกเลิก',
    reverseButtons: true,
    focusCancel: true
  }).then(r => r.isConfirmed);
}

/* ---------- ช่องเลือกแบบค้นหาได้ ---------- */
$('.select2-field').each(function () {
  $(this).select2({ theme: 'bootstrap4', width: '100%', placeholder: $(this).data('placeholder'), language: { noResults: () => 'ไม่พบรายการที่ค้นหา' } });
});
// ใช้ jQuery ผูก event เพราะ Select2 ยิง change ผ่าน jQuery — ตัวรับแบบ addEventListener จะไม่ได้ยิน
$('.select2-field').on('change', function () {
  // เลือกเจ้าหน้าที่แล้วเติมตำแหน่งของคนนั้นให้ทันที ยังเปลี่ยนเป็นตำแหน่งอื่นเองได้
  if (this === senderSelect) {
    const pos = senderSelect.selectedOptions[0]?.dataset.position || '';
    if (pos) setFieldValue(positionSelect, pos);
  }
  saveDraft();
});

function esc(s) { return String(s ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c])); }
function toThaiText(date) { return date ? `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}` : ''; }
function thaiDate(ymd) {
  if (!ymd) return '-';
  const [y, m, d] = ymd.split('-').map(Number);
  return `${d} ${thaiMonths[m - 1]} ${y + 543}`;
}
function initDatePicker(el, onChange) {
  return flatpickr(el, {
    locale: 'th', dateFormat: 'Y-m-d', altInput: true, altInputClass: el.className, allowInput: false,
    onReady: (d, s, i) => { i.altInput.value = toThaiText(d[0]); },
    onChange: (d, s, i) => { i.altInput.value = toThaiText(d[0]); if (onChange) onChange(s); },
    onValueUpdate: (d, s, i) => { i.altInput.value = toThaiText(d[0]); }
  });
}
const docDatePicker = initDatePicker(document.querySelector('#DocDate'), () => saveDraft());

/* ---------- ตารางรายชื่อผู้ประกันตน ---------- */
function addPersonRow(data = {}, focus = false) {
  const i = personIndex++;
  const tr = document.createElement('tr');
  tr.className = 'person-row';
  tr.innerHTML = `
    <td class="seq-cell" data-label="รายชื่อที่"></td>
    <td data-label="คำนำหน้า">
      <select class="form-control form-control-sm" name="persons[${i}][TitleName]">
        <option value="">-</option>
        ${titles.map(t => `<option value="${esc(t)}"${data.TitleName === t ? ' selected' : ''}>${esc(t)}</option>`).join('')}
      </select>
    </td>
    <td data-label="ชื่อ-สกุลผู้ประกันตน *"><input class="form-control form-control-sm person-name" name="persons[${i}][FullName]" value="${esc(data.FullName || '')}" placeholder="เช่น WAI WAI SAN"></td>
    <td data-label="เลขที่บัตร (ปกส.)"><input class="form-control form-control-sm person-card" name="persons[${i}][SsoCardNo]" value="${esc(data.SsoCardNo || '')}" inputmode="numeric" maxlength="20" placeholder="เลขที่บัตร ปกส."></td>
    <td class="check-cell" data-label="เลิกจ้าง"><input type="checkbox" name="persons[${i}][IsTerminated]" value="1"${Number(data.IsTerminated) ? ' checked' : ''}><span class="chk-text">เลิกจ้าง</span></td>
    <td class="check-cell" data-label="ลาออก"><input type="checkbox" name="persons[${i}][IsResigned]" value="1"${Number(data.IsResigned) ? ' checked' : ''}><span class="chk-text">ลาออก</span></td>
    <td data-label="หมายเหตุ"><input class="form-control form-control-sm person-remark" name="persons[${i}][Remark]" value="${esc(data.Remark || '')}"></td>
    <td class="action-cell"><button type="button" class="btn btn-outline-danger btn-sm remove-person" title="ลบรายชื่อ"><i class="fas fa-trash"></i></button></td>`;
  document.querySelector('#personRows').appendChild(tr);
  renumberRows();
  if (focus) tr.querySelector('.person-name').focus();
  return tr;
}

function renumberRows() {
  const rows = document.querySelectorAll('#personRows .person-row');
  rows.forEach((tr, idx) => { tr.querySelector('.seq-cell').textContent = idx + 1; });
  updatePersonCount();
}

/** ตัวเลขบนหัวการ์ด — นับเฉพาะแถวที่กรอกแล้ว ให้ตรงกับจำนวนที่จะถูกบันทึกจริง */
function updatePersonCount() {
  const filled = filledPersons(readForm().persons).length;
  document.querySelector('#personCountBadge').textContent = `${filled} รายชื่อ`;
}

document.querySelector('#addPerson').onclick = () => addPersonRow({}, true);
document.querySelector('#personRows').addEventListener('input', updatePersonCount);
// กด Enter ในช่องหมายเหตุ = ขึ้นแถวใหม่ กรอกรวดเดียวไม่ต้องละมือไปกดปุ่ม
document.querySelector('#personRows').addEventListener('keydown', e => {
  if (e.key !== 'Enter' || !e.target.classList.contains('person-remark')) return;
  e.preventDefault();
  const rows = [...document.querySelectorAll('#personRows .person-row')];
  const isLast = e.target.closest('tr') === rows[rows.length - 1];
  if (isLast) addPersonRow({}, true);
  else rows[rows.indexOf(e.target.closest('tr')) + 1].querySelector('.person-name').focus();
});
document.querySelector('#personRows').addEventListener('click', e => {
  if (!e.target.closest('.remove-person')) return;
  const tr = e.target.closest('tr');
  // แถวสุดท้ายลบทิ้งไม่ได้ (ต้องเหลืออย่างน้อย 1 แถวไว้กรอก) — ล้างค่าในแถวแทนการเตือน
  if (document.querySelectorAll('#personRows .person-row').length <= 1) {
    tr.querySelectorAll('input').forEach(el => { if (el.type === 'checkbox') el.checked = false; else el.value = ''; });
    tr.querySelector('select').value = '';
    tr.classList.remove('row-invalid');
    tr.querySelector('.person-name').focus();
  } else {
    tr.remove();
  }
  renumberRows();
  saveDraft();
});

/* ---------- อ่านค่าจากฟอร์ม ---------- */
function readForm() {
  const doc = {};
  docFields.forEach(f => { doc[f] = (form.querySelector(`[name="${f}"]`)?.value || '').trim(); });
  const persons = [...document.querySelectorAll('#personRows .person-row')].map(tr => ({
    TitleName: tr.querySelector('select').value,
    FullName: tr.querySelector('.person-name').value.trim(),
    SsoCardNo: tr.querySelector('.person-card').value.trim(),
    IsTerminated: tr.querySelectorAll('.check-cell input')[0].checked ? 1 : 0,
    IsResigned: tr.querySelectorAll('.check-cell input')[1].checked ? 1 : 0,
    Remark: tr.querySelector('.person-remark').value.trim()
  }));
  return { doc, persons };
}
/** เฉพาะแถวที่กรอกข้อมูลแล้ว (แถวว่างล้วนถือว่าไม่ได้ตั้งใจกรอก) */
function filledPersons(persons) {
  return persons.filter(p => p.FullName !== '' || p.SsoCardNo !== '');
}

/* ---------- ตรวจสอบรายขั้นตอน ---------- */
function showStepAlert(step, message) {
  const box = document.querySelector(`.step-alert[data-alert="${step}"]`);
  box.innerHTML = `<i class="fas fa-triangle-exclamation mr-2"></i>${esc(message)}`;
  box.classList.add('show');
  box.scrollIntoView({ block: 'center', behavior: 'smooth' });
}
function clearStepAlert(step) {
  document.querySelector(`.step-alert[data-alert="${step}"]`).classList.remove('show');
}

function validateStep(step) {
  clearStepAlert(step);
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('.row-invalid').forEach(el => el.classList.remove('row-invalid'));
  const { doc, persons } = readForm();

  if (step === 0) {
    if (!doc.DocDate) {
      document.querySelector('#DocDate')._flatpickr.altInput.classList.add('is-invalid');
      showStepAlert(0, 'กรุณาเลือกวันที่ของเอกสาร');
      return false;
    }
    if (!doc.OfficeName) {
      form.querySelector('[name="OfficeName"]').classList.add('is-invalid');
      showStepAlert(0, 'กรุณากรอกชื่อสำนักงานผู้ส่ง');
      return false;
    }
    return true;
  }

  if (step === 1) {
    const rows = [...document.querySelectorAll('#personRows .person-row')];
    const used = filledPersons(persons);
    if (!used.length) {
      showStepAlert(1, 'กรุณากรอกรายชื่อผู้ประกันตนอย่างน้อย 1 รายการ');
      rows[0]?.querySelector('.person-name').classList.add('is-invalid');
      return false;
    }
    const seen = new Map();
    for (let i = 0; i < persons.length; i++) {
      const p = persons[i];
      if (p.FullName === '' && p.SsoCardNo === '') continue;
      if (p.FullName === '') {
        rows[i].classList.add('row-invalid');
        rows[i].querySelector('.person-name').classList.add('is-invalid');
        showStepAlert(1, `รายชื่อที่ ${i + 1}: กรุณากรอกชื่อ-สกุลผู้ประกันตน`);
        return false;
      }
      if (p.SsoCardNo !== '' && !/^\d{10,20}$/.test(p.SsoCardNo)) {
        rows[i].classList.add('row-invalid');
        rows[i].querySelector('.person-card').classList.add('is-invalid');
        showStepAlert(1, `รายชื่อที่ ${i + 1}: เลขที่บัตร (ปกส.) ต้องเป็นตัวเลข 10-20 หลัก`);
        return false;
      }
      if (p.SsoCardNo !== '') {
        if (seen.has(p.SsoCardNo)) {
          rows[i].classList.add('row-invalid');
          rows[i].querySelector('.person-card').classList.add('is-invalid');
          showStepAlert(1, `เลขที่บัตร (ปกส.) ${p.SsoCardNo} ซ้ำกับรายชื่อที่ ${seen.get(p.SsoCardNo) + 1}`);
          return false;
        }
        seen.set(p.SsoCardNo, i);
      }
    }
    return true;
  }

  return true;
}

/* ---------- สลับขั้นตอน ---------- */
function showStep(step) {
  currentStep = step;
  panels.forEach((p, i) => p.classList.toggle('active', i === step));
  document.querySelectorAll('.wizard-step').forEach((el, i) => {
    el.classList.toggle('active', i === step);
    el.classList.toggle('done', i < step);
  });
  document.querySelector('#wizardBar').style.width = ((step + 1) / panels.length * 100) + '%';
  document.querySelector('#wizardMobileLabel').innerHTML =
    `ขั้นตอนที่ ${step + 1} จาก ${panels.length}<small>${esc(stepNames[step])}</small>`;
  if (step === 2) renderReview();
  window.scrollTo({ top: 0, behavior: 'smooth' });
  saveDraft();
}

document.querySelectorAll('.next-step').forEach(btn => btn.onclick = () => {
  if (validateStep(currentStep)) showStep(currentStep + 1);
});
document.querySelectorAll('.prev-step').forEach(btn => btn.onclick = () => showStep(currentStep - 1));
document.querySelector('#wizardSteps').addEventListener('click', e => {
  const step = e.target.closest('.wizard-step');
  if (!step) return;
  const target = Number(step.dataset.step);
  if (target < currentStep) showStep(target);              // ย้อนกลับได้เสมอ
  else if (target > currentStep && validateStep(currentStep)) showStep(currentStep + 1);
});

/* ---------- หน้าตรวจสอบ ---------- */
function renderReview() {
  const { doc, persons } = readForm();
  const used = filledPersons(persons);
  document.querySelector('#reviewDate').textContent = thaiDate(doc.DocDate);
  document.querySelector('#reviewOffice').textContent = doc.OfficeName || '-';
  document.querySelector('#reviewSender').textContent = [doc.SenderName, doc.SenderPosition].filter(Boolean).join(' · ') || '-';
  document.querySelector('#reviewReceiver').textContent = [doc.ReceiverOffice, doc.ReceiverName].filter(Boolean).join(' · ') || '-';

  const terminated = used.filter(p => p.IsTerminated).length;
  const resigned = used.filter(p => p.IsResigned).length;
  document.querySelector('#summaryChips').innerHTML = `
    <span class="summary-chip">รวมทั้งหมด <strong>${used.length}</strong> คน</span>
    <span class="summary-chip">เลิกจ้าง <strong>${terminated}</strong> คน</span>
    <span class="summary-chip">ลาออก <strong>${resigned}</strong> คน</span>`;

  document.querySelector('#reviewPersons').innerHTML = `
    <table class="table table-sm table-bordered mb-0">
      <thead><tr><th style="width:60px">ลำดับ</th><th>ชื่อ-สกุล</th><th style="width:180px">เลขที่บัตร (ปกส.)</th><th style="width:90px">เลิกจ้าง</th><th style="width:90px">ลาออก</th><th>หมายเหตุ</th></tr></thead>
      <tbody>${used.map((p, i) => `<tr>
        <td class="text-center">${i + 1}</td>
        <td>${esc([p.TitleName, p.FullName].filter(Boolean).join(' '))}</td>
        <td>${esc(p.SsoCardNo || '-')}</td>
        <td class="text-center">${p.IsTerminated ? '<i class="fas fa-check text-success"></i>' : ''}</td>
        <td class="text-center">${p.IsResigned ? '<i class="fas fa-check text-success"></i>' : ''}</td>
        <td>${esc(p.Remark || '-')}</td></tr>`).join('')}</tbody>
    </table>`;
  document.querySelector('#reviewSignature').innerHTML = signatureHtml(doc);
}

/* ---------- บันทึกร่างอัตโนมัติ ---------- */
let draftTimer;
function saveDraft() {
  if (document.querySelector('#DocID').value) return;  // โหมดแก้ไขไม่ต้องเก็บร่าง
  const { doc, persons } = readForm();
  // ไม่นับค่าที่ระบบเติมให้เป็นค่าเริ่มต้น (วันที่ / สำนักงาน / ชื่อผู้ส่ง) มิฉะนั้นฟอร์มเปล่าจะถูกเก็บเป็นร่าง
  const hasContent = filledPersons(persons).length > 0 || doc.ReceiverName || doc.Remark;
  if (!hasContent) { localStorage.removeItem(DRAFT_KEY); return; }
  try {
    localStorage.setItem(DRAFT_KEY, JSON.stringify({ doc, persons, step: currentStep, savedAt: Date.now() }));
    const time = new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    document.querySelector('#autosaveNote1').innerHTML = `<i class="fas fa-cloud mr-1"></i>บันทึกร่างอัตโนมัติเมื่อ ${time}`;
  } catch (e) { /* localStorage เต็มหรือถูกปิด — ข้ามไป */ }
}
function clearDraft() {
  localStorage.removeItem(DRAFT_KEY);
  document.querySelector('#autosaveNote1').textContent = '';
}
form.addEventListener('input', () => { clearTimeout(draftTimer); draftTimer = setTimeout(saveDraft, 600); });
form.addEventListener('change', saveDraft);

function offerDraft() {
  let draft;
  try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { draft = null; }
  if (!draft || !draft.persons) return;
  document.querySelector('#draftTime').textContent = new Date(draft.savedAt).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' });
  document.querySelector('#draftBar').classList.add('show');
  document.querySelector('#restoreDraft').onclick = () => {
    fillForm(draft.doc, draft.persons, null);
    document.querySelector('#draftBar').classList.remove('show');
    showStep(Math.min(draft.step || 0, panels.length - 1));
  };
  document.querySelector('#discardDraft').onclick = () => {
    clearDraft();
    document.querySelector('#draftBar').classList.remove('show');
  };
}

/* ---------- เติม / ล้างฟอร์ม ---------- */
/** เอกสารเก่าอาจอ้างชื่อ/ตำแหน่งที่ไม่มีใน dropdown แล้ว (เจ้าหน้าที่ย้าย หรือตำแหน่งถูกลบ)
 *  จึงเติมเป็นตัวเลือกชั่วคราวไว้ก่อน มิฉะนั้น select จะรีเซ็ตเป็นค่าว่างเงียบๆ */
function setFieldValue(el, value) {
  let v = value ?? '';
  if (el.tagName === 'SELECT') {
    el.querySelectorAll('option[data-fallback]').forEach(o => o.remove());
    if (v !== '' && ![...el.options].some(o => o.value === v)) {
      // เอกสารที่บันทึกก่อนเพิ่มคำนำหน้าจะเก็บชื่อเปล่าไว้ — จับคู่กับตัวเลือกที่ลงท้ายด้วยชื่อเดียวกัน
      const loose = [...el.options].find(o => o.value.endsWith(v));
      if (loose) {
        v = loose.value;
      } else {
        const opt = new Option(v + ' (ไม่มีในรายการปัจจุบัน)', v);
        opt.dataset.fallback = '1';
        el.add(opt);
      }
    }
  }
  el.value = v;
  // Select2 วาดจากค่าใน DOM ต้องสั่งให้รีเฟรช (namespace .select2 = อัปเดตหน้าตาอย่างเดียว ไม่ยิง handler ซ้ำ)
  if (el.classList.contains('select2-field')) $(el).trigger('change.select2');
}

function fillForm(doc, persons, docId) {
  document.querySelector('#DocID').value = docId || '';
  docDatePicker.setDate(doc.DocDate || new Date(), true);
  docFields.filter(f => f !== 'DocDate').forEach(f => {
    const el = form.querySelector(`[name="${f}"]`);
    if (el) setFieldValue(el, doc[f]);
  });
  document.querySelector('#personRows').innerHTML = '';
  personIndex = 0;
  (persons && persons.length ? persons : [{}]).forEach(p => addPersonRow(p));

  const banner = document.querySelector('#editingBanner');
  if (docId) {
    document.querySelector('#editingDocId').textContent = docId;
    banner.classList.add('show');
  } else {
    banner.classList.remove('show');
  }
}

function resetForm() {
  form.reset();
  document.querySelector('#DocID').value = '';
  document.querySelector('#editingBanner').classList.remove('show');
  document.querySelector('#personRows').innerHTML = '';
  personIndex = 0;
  addPersonRow();
  docDatePicker.setDate(new Date(), true);
  $('.select2-field').trigger('change.select2');   // form.reset() คืนค่าเดิมให้ <select> แต่ Select2 ต้องสั่งรีเฟรชเอง
  panels.forEach((p, i) => clearStepAlert(i));
  clearDraft();
  showStep(0);
}

/** หลังบันทึกสำเร็จ — คงข้อมูลเอกสาร (ขั้นตอนที่ 1) ไว้ ล้างเฉพาะรายชื่อ
 *  แล้วพากลับไปขั้นตอนที่ 2 เพื่อกรอกชุดถัดไปต่อได้ทันที */
function startNextEntry() {
  document.querySelector('#DocID').value = '';
  document.querySelector('#editingBanner').classList.remove('show');
  document.querySelector('#personRows').innerHTML = '';
  personIndex = 0;
  panels.forEach((p, i) => clearStepAlert(i));
  clearDraft();
  showStep(1);
  addPersonRow({}, true);   // เพิ่มหลังสลับขั้นตอนแล้ว โฟกัสจึงตกที่ช่องที่มองเห็นจริง
}

document.querySelector('#resetForm').onclick = async () => {
  const ok = await confirmAction({
    title: 'ล้างข้อมูลทั้งหมด?',
    text: 'ข้อมูลที่กรอกไว้ในฟอร์มนี้จะหายไป และร่างที่บันทึกอัตโนมัติจะถูกลบด้วย',
    confirmText: 'ล้างข้อมูล'
  });
  if (ok) {
    resetForm();
    Toast.fire({ icon: 'success', title: 'ล้างข้อมูลเรียบร้อย' });
  }
};
document.querySelector('#cancelEdit').onclick = () => resetForm();

/* ---------- บันทึก ---------- */
form.addEventListener('submit', async e => {
  e.preventDefault();
  if (!validateStep(0)) { showStep(0); return; }
  if (!validateStep(1)) { showStep(1); return; }

  const button = form.querySelector('button[type=submit]');
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>กำลังบันทึก...';
  try {
    const editingId = document.querySelector('#DocID').value;
    const isEdit = !!editingId;
    const res = await fetch(isEdit ? 'api/alien_insured_checkin_update.php' : 'api/alien_insured_checkin_save.php', { method: 'POST', body: new FormData(form) });
    const result = await res.json();
    if (!result.success) throw new Error(result.message || 'บันทึกข้อมูลไม่สำเร็จ');
    await Swal.fire({
      icon: 'success',
      title: isEdit ? 'บันทึกการแก้ไขแล้ว' : 'บันทึกเอกสารเรียบร้อย',
      text: result.message,
      confirmButtonText: 'เสร็จสิ้น'
    });
    startNextEntry();
    loadDocs();
    Toast.fire({ icon: 'info', title: 'กรอกรายชื่อชุดถัดไปได้เลย — ข้อมูลเอกสารเดิมถูกเก็บไว้ให้แล้ว' });
  } catch (err) {
    showStepAlert(2, err.message || 'ไม่สามารถบันทึกข้อมูลได้');
  } finally {
    button.disabled = false;
    button.innerHTML = '<i class="fas fa-save mr-1"></i>ยืนยันและบันทึก';
  }
});

/* ---------- รายการเอกสาร ---------- */
let searchTimer;
let docsById = {};   // เก็บเอกสารรอบล่าสุดไว้ให้หน้าต่างรายละเอียดหยิบไปแสดงโดยไม่ต้องยิง API ซ้ำ
const fromPicker = initDatePicker(document.querySelector('#searchFrom'), () => loadDocs());
const toPicker = initDatePicker(document.querySelector('#searchTo'), () => loadDocs());

document.querySelector('#searchQ').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadDocs, 300);
});
document.querySelector('#clearSearch').onclick = () => {
  document.querySelector('#searchQ').value = '';
  document.querySelector('#onlyWithPersons').checked = true;   // ค่าเริ่มต้นของหน้า
  document.querySelector('#collapseDupCards').checked = false;
  fromPicker.clear();
  toPicker.clear();
  loadDocs();
};

// ซ่อนเอกสารไม่มีรายชื่อ = กรองฝั่ง API (จำนวนรวมต้องเปลี่ยนตาม) ส่วนยุบแถวซ้ำวาดใหม่ฝั่งหน้าเว็บก็พอ
document.querySelector('#onlyWithPersons').addEventListener('change', () => loadDocs());
document.querySelector('#collapseDupCards').addEventListener('change', () => {
  const q = document.querySelector('#searchQ').value.trim();
  const from = document.querySelector('#searchFrom').value;
  const to = document.querySelector('#searchTo').value;
  renderDocs(q, !!(q || from || to));
});

/** ส่งช่วงวันที่ที่กำลังกรองอยู่ไปยังหน้าพิมพ์ตามช่วงวันที่ */
function syncRangePrintLink(from, to) {
  const params = new URLSearchParams();
  if (from) params.set('from', from);
  if (to) params.set('to', to);
  const qs = params.toString();
  document.querySelector('#rangePrintLink').href = 'alien_insured_checkin_report_print.php' + (qs ? '?' + qs : '');
}

/** ส่วนลงชื่อท้ายตาราง — ช่องที่ยังไม่กรอกให้เป็นเส้นประไว้เซ็นด้วยมือ */
const DOT_LINE = '...................................................';
function signatureHtml(doc) {
  return `<div class="doc-signatures">
    <div class="sign-col">
      <div class="sign-block">
        <div class="line">ขอแสดงความนับถือ / ผู้ส่งมอบเอกสาร</div>
        <div class="line">(${esc(doc.SenderName || DOT_LINE)})</div>
        <div class="line">ตำแหน่ง${esc(doc.SenderPosition || DOT_LINE)}</div>
      </div>
      <div class="sign-office">เจ้าหน้าที่${esc(doc.OfficeName || '-')}</div>
      <div class="sign-block">
        <div class="line">ผู้รับมอบเอกสาร</div>
        <div class="line">ลงชื่อ ${esc(doc.ReceiverName || DOT_LINE)}</div>
        <div class="line">เจ้าหน้าที่${esc(doc.ReceiverOffice || '-')}</div>
      </div>
    </div>
  </div>`;
}

function personRowsHtml(doc) {
  const persons = doc.persons || [];
  return `<table class="table table-sm table-bordered">
    <thead><tr><th style="width:60px">ลำดับ</th><th>ชื่อ-สกุลผู้ประกันตน</th><th style="width:180px">เลขที่บัตร (ปกส.)</th><th style="width:90px">เลิกจ้าง</th><th style="width:90px">ลาออก</th><th>หมายเหตุ</th></tr></thead>
    <tbody>${persons.map((p, i) => `<tr>
      <td class="text-center">${i + 1}</td>
      <td>${esc([p.TitleName, p.FullName].filter(Boolean).join(' '))}</td>
      <td>${esc(p.SsoCardNo || '-')}</td>
      <td class="text-center">${Number(p.IsTerminated) ? '<i class="fas fa-check text-success"></i>' : ''}</td>
      <td class="text-center">${Number(p.IsResigned) ? '<i class="fas fa-check text-success"></i>' : ''}</td>
      <td>${esc(p.Remark || '-')}</td></tr>`).join('')}</tbody></table>`;
}

/** เนื้อหาในหน้าต่างรายละเอียดเอกสาร — ตารางหลักแสดงรายคน ข้อมูลระดับเอกสารจึงมาอยู่ตรงนี้ */
function docDetailHtml(doc) {
  const info = [
    ['วันที่เอกสาร', thaiDate(doc.DocDate)],
    ['สำนักงานผู้ส่ง', doc.OfficeName || '-'],
    ['ผู้ส่งมอบเอกสาร', [doc.SenderName, doc.SenderPosition].filter(Boolean).join(' · ') || '-'],
    ['ผู้รับมอบเอกสาร', [doc.ReceiverOffice, doc.ReceiverName].filter(Boolean).join(' · ') || '-'],
    ['หมายเหตุของเอกสาร', doc.Remark || '-']
  ];
  return `
    <dl class="review-list">
      ${info.map(([label, value]) => `<div class="review-item"><dt>${esc(label)}</dt><dd>${esc(value)}</dd></div>`).join('')}
    </dl>
    <h6 class="text-navy font-weight-bold mb-2">รายชื่อผู้ประกันตน <span class="badge-count ml-1">${doc.PersonCount || 0} คน</span></h6>
    <div class="table-responsive">${personRowsHtml(doc)}</div>`;
}

/** เปิดหน้าต่างรายละเอียดของเอกสารที่แถวนั้นสังกัดอยู่ */
function openDocModal(doc) {
  if (!doc) return;
  document.querySelector('#docViewTitle').textContent = `เอกสารเลขที่ ${doc.DocID} · ${thaiDate(doc.DocDate)}`;
  document.querySelector('#docViewBody').innerHTML = docDetailHtml(doc);
  $('#docViewModal').modal('show');
}

/** ค้นด้วยคำเดียวกับที่ API ใช้กรองเอกสาร เพื่อให้เหลือเฉพาะแถวของคนที่ตรงคำค้น */
function personMatches(person, q) {
  const s = q.toLowerCase();
  return String(person.FullName || '').toLowerCase().includes(s)
      || String(person.SsoCardNo || '').toLowerCase().includes(s);
}

/* เอกสารมีหลายร้อยฉบับ จึงโหลดทีละหน้าแล้วต่อท้ายเมื่อกด "โหลดเอกสารเพิ่ม" */
const DOCS_PAGE_SIZE = 100;
let loadedDocs = [];   // เอกสารที่โหลดมาแล้วทั้งหมดของตัวกรองชุดปัจจุบัน
let docsTotal = 0;     // จำนวนเอกสารทั้งหมดที่ตรงตัวกรอง (ฝั่ง API นับให้)
let dupCards = {};     // เลขบัตร ปกส. ที่ซ้ำข้ามเอกสาร — API นับจากข้อมูลทั้งหมด ไม่ใช่แค่หน้าที่โหลด

const tableState = (icon, title, detail) =>
  `<tr><td colspan="6"><div class="table-state"><i class="fas ${icon}"></i><strong>${esc(title)}</strong>${esc(detail)}</div></td></tr>`;

function loadDocs(append = false) {
  const params = new URLSearchParams();
  const q = document.querySelector('#searchQ').value.trim();
  const from = document.querySelector('#searchFrom').value;
  const to = document.querySelector('#searchTo').value;
  if (q) params.set('q', q);
  if (from) params.set('from', from);
  if (to) params.set('to', to);
  if (document.querySelector('#onlyWithPersons').checked) params.set('has_persons', '1');
  params.set('limit', DOCS_PAGE_SIZE);
  params.set('offset', append ? loadedDocs.length : 0);
  syncRangePrintLink(from, to);

  const tbody = document.querySelector('#docTable tbody');
  const moreBtn = document.querySelector('#loadMoreDocs');
  moreBtn.disabled = true;
  if (append) moreBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>กำลังโหลด...';
  else tbody.innerHTML = tableState('fa-spinner fa-spin', 'กำลังโหลดข้อมูล', 'กรุณารอสักครู่');

  fetch('api/alien_insured_checkin_list.php?' + params.toString())
    .then(r => r.json())
    .then(res => {
      const docs = res.data || [];
      loadedDocs = append ? loadedDocs.concat(docs) : docs;
      docsTotal = typeof res.total === 'number' ? res.total : loadedDocs.length;
      dupCards = res.duplicates || {};
      renderDocs(q, !!(q || from || to));
    })
    .catch(() => {
      if (!append) tbody.innerHTML = tableState('fa-triangle-exclamation', 'โหลดรายการเอกสารไม่สำเร็จ', 'ตรวจสอบการเชื่อมต่อแล้วลองใหม่อีกครั้ง');
      else Toast.fire({ icon: 'error', title: 'โหลดเอกสารเพิ่มไม่สำเร็จ' });
    })
    .finally(() => {
      moreBtn.innerHTML = '<i class="fas fa-angles-down mr-1"></i>โหลดเอกสารเพิ่ม';
      moreBtn.disabled = false;
    });
}

/** วาดตารางจากเอกสารที่โหลดสะสมไว้ทั้งหมด */
function renderDocs(q, filtering) {
  const tbody = document.querySelector('#docTable tbody');
  const moreBtn = document.querySelector('#loadMoreDocs');
  const label = document.querySelector('#docCountLabel');

  moreBtn.hidden = loadedDocs.length >= docsTotal;

  if (!loadedDocs.length) {
    label.textContent = '';
    tbody.innerHTML = filtering
      ? tableState('fa-magnifying-glass', 'ไม่พบเอกสารที่ตรงกับตัวกรอง', 'ลองแก้คำค้นหรือกดปุ่ม "ล้างตัวกรอง"')
      : tableState('fa-folder-open', 'ยังไม่มีเอกสารรายงานตัวที่บันทึกไว้', 'กรอกแบบฟอร์มด้านบนเพื่อบันทึกเอกสารฉบับแรก');
    return;
  }

  // 1 แถว = ผู้ประกันตน 1 คน · เอกสารที่ยังไม่มีรายชื่อยังต้องมีแถวไว้ให้กดแก้ไข/ลบได้
  const collapseDup = document.querySelector('#collapseDupCards').checked;
  let collapsed = 0;
  const rows = [];
  loadedDocs.forEach(d => {
    const persons = d.persons || [];
    const matched = q ? persons.filter(p => personMatches(p, q)) : persons;
    const shown = matched.length ? matched : persons;
    if (!shown.length) rows.push({ doc: d, person: null });
    else shown.forEach(p => {
      // ยุบแถวซ้ำ = เก็บไว้เฉพาะแถวของเอกสารฉบับล่าสุดที่ใช้เลขบัตรนั้น
      const dup = dupCards[p.SsoCardNo];
      if (collapseDup && dup && Number(p.PersonID) !== dup.keepPersonID) { collapsed++; return; }
      rows.push({ doc: d, person: p, dup });
    });
  });

  label.textContent = `แสดง ${loadedDocs.length.toLocaleString('th-TH')} จาก ${docsTotal.toLocaleString('th-TH')} ฉบับ`
    + (collapsed ? ` · ยุบแถวซ้ำไว้ ${collapsed.toLocaleString('th-TH')} แถว` : '');

  docsById = {};
  loadedDocs.forEach(d => { docsById[d.DocID] = d; });

  tbody.innerHTML = rows.map((r, i) => {
    const d = r.doc, p = r.person;
    // ป้ายเตือนเลขบัตรซ้ำ ติดไว้เสมอไม่ว่าจะยุบแถวหรือไม่ จะได้รู้ว่าคนนี้มีเอกสารมากกว่าหนึ่งฉบับ
    const dupBadge = r.dup
      ? ` <span class="badge badge-warning" title="เลขบัตรนี้ปรากฏใน ${r.dup.count} เอกสาร">ซ้ำ ${r.dup.count} ฉบับ</span>`
      : '';
    return `
    <tr>
      <td class="text-center">${i + 1}</td>
      <td>${p ? esc([p.TitleName, p.FullName].filter(Boolean).join(' ')) : '<span class="text-muted">— เอกสารนี้ยังไม่มีรายชื่อ —</span>'}</td>
      <td>${p ? esc(p.SsoCardNo || '-') + dupBadge : '-'}</td>
      <td class="text-center">${p && Number(p.IsTerminated) ? '<i class="fas fa-check text-success"></i>' : ''}</td>
      <td class="text-center">${p && Number(p.IsResigned) ? '<i class="fas fa-check text-success"></i>' : ''}</td>
      <td class="text-right doc-actions">
        <button class="btn btn-outline-secondary btn-sm view-doc" data-id="${esc(d.DocID)}" title="ดูรายละเอียดเอกสาร" aria-label="ดูรายละเอียดเอกสาร"><i class="fas fa-eye"></i><span class="act-text">ดู</span></button>
        <a class="btn btn-outline-dark btn-sm" href="alien_insured_checkin_print.php?id=${esc(d.DocID)}" target="_blank" rel="noopener" title="พิมพ์เอกสารฉบับนี้" aria-label="พิมพ์เอกสารฉบับนี้"><i class="fas fa-print"></i><span class="act-text">พิมพ์</span></a>
        <button class="btn btn-outline-primary btn-sm edit-doc" data-id="${esc(d.DocID)}" title="แก้ไขเอกสาร" aria-label="แก้ไขเอกสาร"><i class="fas fa-pen"></i><span class="act-text">แก้ไข</span></button>
        <button class="btn btn-outline-danger btn-sm delete-doc" data-id="${esc(d.DocID)}" data-count="${d.PersonCount || 0}" data-date="${esc(thaiDate(d.DocDate))}" title="ลบเอกสาร" aria-label="ลบเอกสาร"><i class="fas fa-trash"></i><span class="act-text">ลบ</span></button>
      </td>
    </tr>`;
  }).join('');
}

document.querySelector('#loadMoreDocs').onclick = () => loadDocs(true);

document.querySelector('#docTable').addEventListener('click', async e => {
  const view = e.target.closest('.view-doc');
  if (view) {
    openDocModal(docsById[view.dataset.id]);
    return;
  }

  const del = e.target.closest('.delete-doc');
  if (del) {
    const count = del.dataset.count || '0';
    // แถวนี้เป็นรายบุคคล แต่ปุ่มลบทำงานทั้งฉบับ — บอกวันที่และจำนวนคนให้ชัดก่อนยืนยัน
    const ok = await confirmAction({
      title: 'ลบทั้งเอกสารฉบับนี้?',
      text: `เอกสารวันที่ ${del.dataset.date || '-'} พร้อมรายชื่อผู้ประกันตน ${count} คนจะถูกลบทั้งฉบับ และกู้คืนไม่ได้`,
      confirmText: 'ลบเอกสาร'
    });
    if (!ok) return;
    try {
      const body = new FormData();
      body.append('DocID', del.dataset.id);
      const res = await fetch('api/alien_insured_checkin_delete.php', { method: 'POST', body });
      const result = await res.json();
      if (!result.success) throw new Error(result.message || 'ลบข้อมูลไม่สำเร็จ');
      Toast.fire({ icon: 'success', title: result.message || 'ลบเอกสารเรียบร้อย' });
      loadDocs();
    } catch (err) {
      alertError(err.message || 'ไม่สามารถลบเอกสารได้', 'ลบไม่สำเร็จ');
    }
    return;
  }

  const edit = e.target.closest('.edit-doc');
  if (edit) {
    try {
      const res = await fetch('api/alien_insured_checkin_detail.php?id=' + encodeURIComponent(edit.dataset.id));
      const result = await res.json();
      if (!result.success) throw new Error(result.message || 'ไม่พบข้อมูลเอกสารนี้');
      document.querySelector('#draftBar').classList.remove('show');
      fillForm(result.data.doc, result.data.persons, result.data.doc.DocID);
      showStep(0);
      Toast.fire({ icon: 'info', title: 'โหลดเอกสารแล้ว — แก้ไขได้ทันที' });
    } catch (err) {
      alertError(err.message || 'ไม่สามารถโหลดข้อมูลได้');
    }
  }
});

/* ---------- เริ่มต้น ---------- */
addPersonRow();
docDatePicker.setDate(new Date(), true);
showStep(0);
offerDraft();
loadDocs();
</script>
</body>
</html>
