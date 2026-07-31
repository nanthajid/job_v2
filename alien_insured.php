<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/alien_insured_helper.php';

$user       = currentUser();
$pdo        = getDB();
$titleRows  = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว | Government Digital Service</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
    .card { border: 0; border-radius: 12px; box-shadow: var(--shadow); margin-bottom: 1.5rem; overflow: hidden; }
    .card-header { background: #fff; color: var(--navy); border-bottom: 1px solid var(--gray); padding: 1.25rem 1.5rem; font-weight: 600; }
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

    #docTable th { background: #f8fafc; color: var(--navy); font-weight: 600; white-space: nowrap; }
    #docTable td { vertical-align: middle; }
    .doc-persons { background: #f8fafc; }
    .doc-persons table { margin-bottom: 0; background: #fff; }
    .badge-count { background: var(--royal); color: #fff; font-size: .85rem; padding: .35rem .6rem; border-radius: 6px; }

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
      /* ตารางสรุปอ่านยากเมื่อถูกบีบ — ให้เลื่อนแนวนอนแทนการตัดคำ */
      #reviewPersons table { min-width: 620px; }
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
        <h1 class="h3 mb-1">ขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว</h1>
        <div>กรอกข้อมูลทีละขั้นตอน ระบบบันทึกร่างให้อัตโนมัติ ไม่ต้องกรอกใหม่หากออกจากหน้านี้</div>
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
                </div>
              </div>

              <div class="form-section-title"><i class="fas fa-paper-plane"></i>ผู้ส่งมอบเอกสาร</div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>ชื่อผู้ส่งมอบเอกสาร</label>
                  <input name="SenderName" class="form-control" value="<?= htmlspecialchars($user['StName'] ?? '') ?>">
                </div>
                <div class="form-group col-md-6">
                  <label>ตำแหน่ง</label>
                  <input name="SenderPosition" class="form-control" value="<?= htmlspecialchars($user['StPostName'] ?? '') ?>" placeholder="เช่น เจ้าพนักงานแรงงาน">
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
                </div>
                <div class="form-group col-12">
                  <label>หมายเหตุของเอกสาร</label>
                  <input name="Remark" class="form-control">
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
              <div class="small-help mt-3"><i class="fas fa-circle-info mr-1"></i>กรอกอย่างน้อย 1 รายชื่อ · เลขที่บัตร (ปกส.) เป็นตัวเลข 10-20 หลัก · แถวที่ปล่อยว่างจะไม่ถูกบันทึก</div>
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
            <span><i class="fas fa-folder-open mr-2"></i>เอกสารที่บันทึกไว้</span>
            <a href="alien_insured_report_print.php" id="rangePrintLink" class="btn btn-outline-dark btn-sm ml-auto" target="_blank" rel="noopener">
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
            <div class="table-responsive">
              <table class="table table-hover table-bordered" id="docTable">
                <thead>
                  <tr>
                    <th style="width:70px">เลขที่</th>
                    <th style="width:170px">วันที่เอกสาร</th>
                    <th>สำนักงานผู้ส่ง</th>
                    <th>ผู้ส่งมอบเอกสาร</th>
                    <th style="width:120px">จำนวนรายชื่อ</th>
                    <th style="width:200px"></th>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
const titles = <?= json_encode(array_column($titleRows, 'Title'), JSON_UNESCAPED_UNICODE) ?>;
const thaiMonths = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
const stepNames = ['ข้อมูลเอกสาร', 'รายชื่อผู้ประกันตน', 'ตรวจสอบและบันทึก'];
const docFields = ['DocDate', 'OfficeName', 'SenderName', 'SenderPosition', 'ReceiverOffice', 'ReceiverName', 'Remark'];
const DRAFT_KEY = 'alien_insured_draft_v1';

const form = document.querySelector('#alienForm');
const panels = [...document.querySelectorAll('.step-panel')];
let currentStep = 0;
let personIndex = 0;

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
  document.querySelectorAll('#personRows .person-row').forEach((tr, idx) => {
    tr.querySelector('.seq-cell').textContent = idx + 1;
  });
}

document.querySelector('#addPerson').onclick = () => addPersonRow({}, true);
document.querySelector('#personRows').addEventListener('click', e => {
  if (!e.target.closest('.remove-person')) return;
  if (document.querySelectorAll('#personRows .person-row').length <= 1) {
    showStepAlert(1, 'ต้องมีรายชื่ออย่างน้อย 1 รายการ');
    return;
  }
  e.target.closest('tr').remove();
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
function fillForm(doc, persons, docId) {
  document.querySelector('#DocID').value = docId || '';
  docDatePicker.setDate(doc.DocDate || new Date(), true);
  docFields.filter(f => f !== 'DocDate').forEach(f => {
    const el = form.querySelector(`[name="${f}"]`);
    if (el) el.value = doc[f] ?? '';
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
  panels.forEach((p, i) => clearStepAlert(i));
  clearDraft();
  showStep(0);
}
document.querySelector('#resetForm').onclick = () => {
  if (confirm('ล้างข้อมูลที่กรอกไว้ทั้งหมดหรือไม่')) resetForm();
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
    const isEdit = !!document.querySelector('#DocID').value;
    const res = await fetch(isEdit ? 'api/alien_insured_update.php' : 'api/alien_insured_save.php', { method: 'POST', body: new FormData(form) });
    const result = await res.json();
    if (!result.success) throw new Error(result.message || 'บันทึกข้อมูลไม่สำเร็จ');
    alert(result.message);
    resetForm();
    loadDocs();
  } catch (err) {
    showStepAlert(2, err.message || 'ไม่สามารถบันทึกข้อมูลได้');
  } finally {
    button.disabled = false;
    button.innerHTML = '<i class="fas fa-save mr-1"></i>ยืนยันและบันทึก';
  }
});

/* ---------- รายการเอกสาร ---------- */
let searchTimer;
const fromPicker = initDatePicker(document.querySelector('#searchFrom'), () => loadDocs());
const toPicker = initDatePicker(document.querySelector('#searchTo'), () => loadDocs());

document.querySelector('#searchQ').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadDocs, 300);
});
document.querySelector('#clearSearch').onclick = () => {
  document.querySelector('#searchQ').value = '';
  fromPicker.clear();
  toPicker.clear();
  loadDocs();
};

/** ส่งช่วงวันที่ที่กำลังกรองอยู่ไปยังหน้าพิมพ์ตามช่วงวันที่ */
function syncRangePrintLink(from, to) {
  const params = new URLSearchParams();
  if (from) params.set('from', from);
  if (to) params.set('to', to);
  const qs = params.toString();
  document.querySelector('#rangePrintLink').href = 'alien_insured_report_print.php' + (qs ? '?' + qs : '');
}

function personRowsHtml(persons) {
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

function loadDocs() {
  const params = new URLSearchParams();
  const q = document.querySelector('#searchQ').value.trim();
  const from = document.querySelector('#searchFrom').value;
  const to = document.querySelector('#searchTo').value;
  if (q) params.set('q', q);
  if (from) params.set('from', from);
  if (to) params.set('to', to);
  syncRangePrintLink(from, to);

  fetch('api/alien_insured_list.php?' + params.toString())
    .then(r => r.json())
    .then(res => {
      const docs = res.data || [];
      const tbody = document.querySelector('#docTable tbody');
      if (!docs.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูล</td></tr>';
        return;
      }
      tbody.innerHTML = docs.map(d => `
        <tr>
          <td>${esc(d.DocID)}</td>
          <td>${thaiDate(d.DocDate)}</td>
          <td>${esc(d.OfficeName || '-')}</td>
          <td>${esc(d.SenderName || '-')}</td>
          <td><span class="badge-count">${d.PersonCount || 0} คน</span></td>
          <td class="text-right">
            <button class="btn btn-outline-secondary btn-sm toggle-persons" data-id="${esc(d.DocID)}" title="ดูรายชื่อ"><i class="fas fa-list"></i></button>
            <a class="btn btn-outline-dark btn-sm" href="alien_insured_print.php?id=${encodeURIComponent(d.DocID)}" target="_blank" rel="noopener" title="พิมพ์แบบฟอร์ม"><i class="fas fa-print"></i></a>
            <button class="btn btn-outline-primary btn-sm edit-doc" data-id="${esc(d.DocID)}" title="แก้ไข"><i class="fas fa-pen"></i></button>
            <button class="btn btn-outline-danger btn-sm delete-doc" data-id="${esc(d.DocID)}" title="ลบ"><i class="fas fa-trash"></i></button>
          </td>
        </tr>
        <tr class="doc-persons" id="persons-${esc(d.DocID)}" style="display:none">
          <td colspan="6">${personRowsHtml(d.persons || [])}</td>
        </tr>`).join('');
    })
    .catch(() => {});
}

document.querySelector('#docTable').addEventListener('click', async e => {
  const toggle = e.target.closest('.toggle-persons');
  if (toggle) {
    const row = document.querySelector('#persons-' + toggle.dataset.id);
    row.style.display = row.style.display === 'none' ? '' : 'none';
    return;
  }

  const del = e.target.closest('.delete-doc');
  if (del) {
    if (!confirm('ยืนยันการลบเอกสารนี้พร้อมรายชื่อทั้งหมดหรือไม่')) return;
    const body = new FormData();
    body.append('DocID', del.dataset.id);
    const res = await fetch('api/alien_insured_delete.php', { method: 'POST', body });
    const result = await res.json();
    alert(result.message);
    if (result.success) loadDocs();
    return;
  }

  const edit = e.target.closest('.edit-doc');
  if (edit) {
    const res = await fetch('api/alien_insured_detail.php?id=' + encodeURIComponent(edit.dataset.id));
    const result = await res.json();
    if (!result.success) { alert(result.message || 'ไม่พบข้อมูล'); return; }
    document.querySelector('#draftBar').classList.remove('show');
    fillForm(result.data.doc, result.data.persons, result.data.doc.DocID);
    showStep(0);
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
