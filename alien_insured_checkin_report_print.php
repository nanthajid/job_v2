<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/alien_insured_checkin_helper.php';

$pdo  = getDB();
$user = currentUser();

$dateFrom = isset($_GET['from']) ? trim((string)$_GET['from']) : date('Y-m-01');
$dateTo   = isset($_GET['to'])   ? trim((string)$_GET['to'])   : date('Y-m-d');
$mode     = ($_GET['mode'] ?? 'summary') === 'forms' ? 'forms' : 'summary';

$validDate = function ($s) {
    if ($s === '') return false;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
};

$conds  = [];
$params = [];
if ($validDate($dateFrom)) {
    $conds[]         = 'd.DocDate >= :from';
    $params[':from'] = $dateFrom;
}
if ($validDate($dateTo)) {
    $conds[]       = 'd.DocDate <= :to';
    $params[':to'] = $dateTo;
}
$where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

$docStmt = $pdo->prepare('SELECT d.* FROM ' . ALIEN_CHECKIN_DOC_TABLE . ' d' . $where . ' ORDER BY d.DocDate ASC, d.DocID ASC');
$docStmt->execute($params);
$docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

$personsByDoc = [];
$allPersons   = [];
if ($docs) {
    $ids   = array_column($docs, 'DocID');
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $pStmt = $pdo->prepare('SELECT * FROM ' . ALIEN_CHECKIN_PERSON_TABLE . ' WHERE DocID IN (' . $marks . ') ORDER BY DocID ASC, SeqNo ASC, PersonID ASC');
    $pStmt->execute($ids);

    $docDates = array_column($docs, 'DocDate', 'DocID');
    foreach ($pStmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $personsByDoc[$p['DocID']][] = $p;
        $p['DocDate']                = $docDates[$p['DocID']] ?? null;
        $allPersons[]                = $p;
    }
}

$senderTitleMap  = alienInsuredTitleMap($pdo);

// เลือกผู้ส่งมอบเอกสารสำหรับใบที่จะพิมพ์ได้ — เว้นว่างไว้ = ใช้ตามที่บันทึกในเอกสาร
$staffOptions    = alienInsuredStaffOptions($pdo);
$positionOptions = alienInsuredPositionOptions($pdo);

// รับเฉพาะค่าที่มีอยู่จริงในรายการ กัน URL แต่งชื่อมั่วขึ้นไปอยู่บนเอกสารราชการ
$senderOverride = trim((string)($_GET['sender'] ?? ''));
if (!in_array($senderOverride, array_column($staffOptions, 'DisplayName'), true)) {
    $senderOverride = '';
}
$senderPosOverride = trim((string)($_GET['sender_pos'] ?? ''));
if (!in_array($senderPosOverride, $positionOptions, true)) {
    $senderPosOverride = '';
}

/** ชื่อ/ตำแหน่งที่จะพิมพ์ในบล็อกลงชื่อของเอกสารฉบับหนึ่ง */
$signName = static function (array $doc) use ($senderOverride, $senderTitleMap): string {
    return $senderOverride !== '' ? $senderOverride : alienInsuredWithTitle($doc['SenderName'] ?? '', $senderTitleMap);
};
$signPosition = static function (array $doc) use ($senderPosOverride): string {
    return $senderPosOverride !== '' ? $senderPosOverride : trim((string)($doc['SenderPosition'] ?? ''));
};

$totalPersons    = count($allPersons);
$totalTerminated = count(array_filter($allPersons, static fn($p) => (int)$p['IsTerminated'] === 1));
$totalResigned   = count(array_filter($allPersons, static fn($p) => (int)$p['IsResigned'] === 1));

$thMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$thaiDate = function ($iso) use ($thMonths) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
    if (!$d) return $iso;
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

if ($validDate($dateFrom) && $validDate($dateTo) && $dateFrom === $dateTo) {
    $rangeLabel = 'ประจำวันที่ ' . $thaiDate($dateFrom);
} elseif ($validDate($dateFrom) && $validDate($dateTo)) {
    $rangeLabel = 'ช่วงวันที่ ' . $thaiDate($dateFrom) . ' ถึง ' . $thaiDate($dateTo);
} elseif ($validDate($dateFrom)) {
    $rangeLabel = 'ตั้งแต่วันที่ ' . $thaiDate($dateFrom) . ' เป็นต้นไป';
} elseif ($validDate($dateTo)) {
    $rangeLabel = 'ถึงวันที่ ' . $thaiDate($dateTo);
} else {
    $rangeLabel = 'ข้อมูลทั้งหมด (ไม่ระบุช่วงเวลา)';
}

$printedAt = (int)date('j') . ' ' . $thMonths[(int)date('n')] . ' ' . (date('Y') + 543) . ' เวลา ' . date('H:i') . ' น.';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>พิมพ์แบบรายงานตัวผู้ประกันตนแรงงานต่างด้าว | สำนักงานจัดหางาน กทม. พื้นที่ 2</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    :root {
      --gov-navy: #002D62; --gov-royal: #005EB8; --gov-gold: #D4AF37;
      --gov-bg: #F0F2F5; --gov-white: #FFF; --gov-gray: #E9ECEF;
      --gov-text-dark: #1A1A1A; --gov-text-muted: #64748B; --gov-border: #DEE2E6;
      --gov-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
    }
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; background: var(--gov-bg); color: var(--gov-text-dark); font-size: 16px; }
    h1, h2, h3, h4, .brand-text, .nav-link, .btn { font-family: 'Prompt', sans-serif; }
    .content-wrapper { background: var(--gov-bg); padding-bottom: 3rem; }
    .main-header { border-bottom: 3px solid var(--gov-gold) !important; box-shadow: var(--gov-shadow); }

    .gov-card { background: var(--gov-white); border: 0; border-radius: 12px; box-shadow: var(--gov-shadow); margin-bottom: 1.5rem; overflow: hidden; }
    /* หัวการ์ดพื้นกรมท่าคาดทอง โทนเดียวกับหน้าขึ้นทะเบียน (register.php) */
    .gov-card-header { background: var(--gov-navy); border-bottom: 3px solid var(--gov-gold); padding: 1.15rem 1.5rem; display: flex; align-items: center; }
    .gov-card-title { font-size: 1.15rem; font-weight: 600; color: #fff; margin: 0; }
    .gov-page-header { background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%); padding: 2.5rem 0; margin-bottom: 2rem; color: #fff; box-shadow: var(--gov-shadow); }
    .gov-page-title { font-size: 2rem; font-weight: 600; }
    .form-label { font-weight: 500; color: var(--gov-navy); margin-bottom: .5rem; display: block; }
    .form-control { border: 1px solid var(--gov-border); border-radius: 8px; padding: .6rem 1rem; height: auto; transition: border-color .2s, box-shadow .2s; }
    .form-control:focus { border-color: var(--gov-royal); box-shadow: 0 0 0 3px rgba(0, 94, 184, .15); }
    /* ความสูงคงที่ของ .form-control ตัดสระ/วรรณยุกต์ไทยในช่อง select */
    select.form-control { height: auto; line-height: 1.5; }
    .input-group-text { background: #f8fafc; border: 1px solid var(--gov-border); color: var(--gov-navy); }
    .input-group > .input-group-prepend > .input-group-text { border-radius: 8px 0 0 8px; }
    .field-hint { color: var(--gov-text-muted); font-size: .85rem; margin-top: .4rem; }

    /* หัวข้อย่อยในฟอร์ม — แบ่งกลุ่มให้กวาดตาอ่านง่าย */
    .filter-section { display: flex; align-items: center; gap: .6rem; margin: 1.75rem 0 1.1rem; padding-bottom: .6rem; border-bottom: 2px solid var(--gov-gray); color: var(--gov-navy); font-family: 'Prompt', sans-serif; font-weight: 600; font-size: 1.02rem; }
    .filter-section:first-child { margin-top: 0; }
    .filter-section i { width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; background: var(--gov-royal); color: #fff; border-radius: 8px; font-size: .85rem; flex-shrink: 0; }

    .filter-actions { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end; align-items: center; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--gov-gray); }
    .filter-actions .btn { border-radius: 8px; padding: .65rem 1.5rem; font-weight: 600; }
    .filter-actions .btn-secondary { background: transparent; color: var(--gov-text-muted); border: 1px solid var(--gov-border); font-weight: 500; }
    .filter-actions .btn-secondary:hover { background: var(--gov-gray); color: var(--gov-text-dark); }
    .btn-gov-primary { background: var(--gov-navy); border-color: var(--gov-navy); color: #fff; }
    .btn-gov-primary:hover { background: var(--gov-royal); border-color: var(--gov-royal); color: #fff; }
    .btn-gov-print { background: var(--gov-gold); border-color: var(--gov-gold); color: var(--gov-navy); }
    .btn-gov-print:hover { background: #c19b2b; border-color: #c19b2b; color: var(--gov-navy); }

    /* Select2 ให้เข้าชุดกับ .form-control */
    .select2-container--bootstrap4 .select2-selection { border: 1px solid var(--gov-border); border-radius: 8px; min-height: calc(1.5em + 1.2rem + 2px); padding: .35rem .75rem; display: flex; align-items: center; }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { padding-left: 0; line-height: 1.6; color: var(--gov-text-dark); }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: 100%; top: 0; }
    .select2-container--bootstrap4.select2-container--focus .select2-selection { border-color: var(--gov-royal); box-shadow: 0 0 0 3px rgba(0, 94, 184, .15); }
    .select2-dropdown { border: 1px solid var(--gov-border); border-radius: 8px; box-shadow: var(--gov-shadow); }
    .select2-results__option { padding: .65rem 1rem; }
    .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected] { background: var(--gov-navy); }

    .summary-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .summary-chip { background: #eef4fb; color: var(--gov-navy); border-radius: 99px; padding: .4rem .9rem; font-weight: 500; font-size: .9rem; }
    .summary-chip strong { color: var(--gov-royal); }

    /* ---------- กระดาษรายงาน ---------- */
    .report-paper { background: #fff; padding: 15mm; margin: 0 auto 1.5rem; box-shadow: var(--gov-shadow); color: #000; border-radius: 8px; }
    .doc-header { text-align: center; margin-bottom: 1.25rem; }
    .doc-header h1 { font-size: 18pt; font-weight: 700; color: var(--gov-navy); margin-bottom: .25rem; }
    .doc-header h2 { font-size: 14pt; font-weight: 600; margin-bottom: .25rem; }
    .doc-header .range { font-size: 12pt; }

    table.table-report { width: 100%; border-collapse: collapse; margin-top: .75rem; }
    table.table-report th, table.table-report td { border: 1px solid #333; padding: .45rem .5rem; text-align: center; vertical-align: middle; color: #000; }
    table.table-report thead th { background: var(--gov-gray); color: var(--gov-navy); font-weight: 700; font-size: 14px; }
    table.table-report tbody td.text-left { text-align: left; }
    table.table-report tbody tr { page-break-inside: avoid; }
    .check-mark { font-size: 1.05rem; font-weight: 700; }

    .doc-footer { margin-top: 1.5rem; display: flex; justify-content: space-between; gap: 1rem; font-size: 12px; color: var(--gov-text-muted); border-top: 1px dashed #dee2e6; padding-top: .9rem; }

    /* ---------- โหมดพิมพ์แยกทีละฉบับ ---------- */
    .form-sheet + .form-sheet { margin-top: 1.5rem; }
    .form-sheet .sheet-head { text-align: center; margin-bottom: 1.25rem; }
    .form-sheet .sheet-head h2 { font-family: 'Prompt', sans-serif; font-size: 1.3rem; color: var(--gov-navy); margin: 0 0 .35rem; }
    .form-sheet .sheet-head .office { font-size: 1.05rem; font-weight: 600; }
    .form-sheet .sheet-head .doc-date { margin-top: .35rem; }
    .signatures { display: flex; justify-content: flex-end; margin-top: 2.5rem; page-break-inside: avoid; }
    .sign-col { width: 60%; }
    .sign-block { text-align: center; margin-bottom: 2.25rem; }
    .sign-block .line { margin: .35rem 0; }
    .sign-office { margin-top: -1.75rem; margin-bottom: 1.75rem; font-size: .95rem; }
    .sheet-no { text-align: right; font-size: 12px; color: var(--gov-text-muted); }

    .empty-note { text-align: center; padding: 3rem 1rem; color: var(--gov-text-muted); }

    @media print {
      .no-print, .main-sidebar, .main-header, .main-footer { display: none !important; }
      .content-wrapper { margin-left: 0 !important; padding: 0 !important; background: #fff !important; }
      body { background: #fff !important; }
      .report-paper { box-shadow: none; padding: 0; margin: 0; border-radius: 0; }
      .doc-header h1 { color: #000; }
      table.table-report thead { display: table-header-group; }
      table.table-report thead th { background: #f1f3f5 !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      /* พิมพ์แยกทีละฉบับ: ขึ้นหน้าใหม่ทุกฉบับ ยกเว้นฉบับสุดท้าย */
      .form-sheet { page-break-after: always; }
      .form-sheet:last-of-type { page-break-after: auto; }
      .form-sheet + .form-sheet { margin-top: 0; }
      @page { size: A4 portrait; margin: 15mm 12mm; }
    }

    @media (max-width: 768px) {
      .gov-page-title { font-size: 1.5rem; }
      .report-paper { padding: 1rem; }
      .gov-card-body { padding: 1rem !important; }
      .filter-actions { flex-direction: column-reverse; align-items: stretch; }
      .filter-actions .btn { width: 100%; margin: 0 !important; min-height: 46px; }
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand navbar-white navbar-light no-print">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars text-navy"></i></a></li>
      <li class="nav-item d-none d-lg-block"><span class="nav-link text-navy font-weight-bold">ระบบจัดการข้อมูลการจ้างงาน</span></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><span class="nav-link"><?= htmlspecialchars($user['StName'] ?? $user['UserName'] ?? 'เจ้าหน้าที่') ?></span></li>
    </ul>
  </nav>

  <?php $current_page = 'alien_insured_checkin_report_print'; include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="content-wrapper">

    <div class="gov-page-header no-print">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8 px-lg-5">
            <h1 class="gov-page-title">พิมพ์แบบรายงานตัวผู้ประกันตนแรงงานต่างด้าว</h1>
            <p class="mb-0 opacity-9">เลือกช่วงวันที่ของเอกสารรายงานตัว แล้วพิมพ์เป็นบัญชีรายชื่อรวม หรือแยกเป็นแบบฟอร์มทีละฉบับ</p>
          </div>
          <div class="col-md-4 px-lg-5 text-md-right d-none d-md-block"><i class="fas fa-print fa-4x opacity-2"></i></div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid px-lg-5">

        <div class="gov-card no-print">
          <div class="gov-card-header">
            <i class="fas fa-filter fa-lg text-white mr-3"></i>
            <h3 class="gov-card-title">ตัวกรองข้อมูล</h3>
          </div>
          <div class="gov-card-body p-4">
            <form method="get" id="filterForm">

              <div class="filter-section"><i class="fas fa-calendar-days"></i>ช่วงข้อมูลที่ต้องการพิมพ์</div>
              <div class="form-row">
                <div class="col-md-3 mb-3">
                  <label class="form-label" for="filterDateFrom">ตั้งแต่วันที่</label>
                  <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar-alt"></i></span></div>
                    <input type="text" id="filterDateFrom" name="from" class="form-control" placeholder="เลือกวันที่" value="<?= htmlspecialchars($dateFrom) ?>" readonly>
                  </div>
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label" for="filterDateTo">ถึงวันที่</label>
                  <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar-alt"></i></span></div>
                    <input type="text" id="filterDateTo" name="to" class="form-control" placeholder="เลือกวันที่" value="<?= htmlspecialchars($dateTo) ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label" for="filterMode">รูปแบบการพิมพ์</label>
                  <select name="mode" id="filterMode" class="form-control">
                    <option value="summary"<?= $mode === 'summary' ? ' selected' : '' ?>>บัญชีรายชื่อรวมทั้งช่วง</option>
                    <option value="forms"<?= $mode === 'forms' ? ' selected' : '' ?>>แบบฟอร์มแยกทีละฉบับ (ขึ้นหน้าใหม่ทุกฉบับ)</option>
                  </select>
                  <div class="field-hint" id="modeHint"></div>
                </div>
              </div>

              <div class="filter-section"><i class="fas fa-signature"></i>ผู้ลงนามในใบที่พิมพ์</div>
              <div class="form-row">
                <div class="col-md-7 mb-3">
                  <label class="form-label" for="filterSender">ผู้ส่งมอบเอกสาร</label>
                  <select name="sender" id="filterSender" class="form-control select2-field" data-placeholder="พิมพ์เพื่อค้นหาเจ้าหน้าที่">
                    <option value="">— ใช้ชื่อตามที่บันทึกในเอกสาร —</option>
                    <?php foreach ($staffOptions as $s): ?>
                      <option value="<?= htmlspecialchars($s['DisplayName']) ?>"
                              data-position="<?= htmlspecialchars($s['StPostName'] ?? '') ?>"
                              <?= $senderOverride === $s['DisplayName'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['DisplayName']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="field-hint">
                    <i class="fas fa-circle-info mr-1"></i>เปลี่ยนชื่อผู้ลงนามเฉพาะใบที่พิมพ์ครั้งนี้ ไม่กระทบข้อมูลที่บันทึกไว้ในระบบ
                  </div>
                </div>
                <div class="col-md-5 mb-3">
                  <label class="form-label" for="filterSenderPos">ตำแหน่ง</label>
                  <select name="sender_pos" id="filterSenderPos" class="form-control select2-field" data-placeholder="พิมพ์เพื่อค้นหาตำแหน่ง">
                    <option value="">— ใช้ตำแหน่งตามที่บันทึกในเอกสาร —</option>
                    <?php foreach ($positionOptions as $posName): ?>
                      <option value="<?= htmlspecialchars($posName) ?>" <?= $senderPosOverride === $posName ? 'selected' : '' ?>>
                        <?= htmlspecialchars($posName) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="field-hint">เลือกเจ้าหน้าที่แล้วช่องนี้จะเติมให้อัตโนมัติ</div>
                </div>
              </div>

              <div class="filter-actions">
                <a href="alien_insured_checkin.php" class="btn btn-secondary mr-auto"><i class="fas fa-arrow-left mr-2"></i>ย้อนกลับ</a>
                <button type="submit" class="btn btn-gov-primary shadow-sm">
                  <i class="fas fa-magnifying-glass mr-2"></i>แสดงข้อมูล
                </button>
                <button type="button" class="btn btn-gov-print shadow-sm" onclick="window.print()"<?= $docs ? '' : ' disabled' ?>>
                  <i class="fas fa-print mr-2"></i>พิมพ์
                </button>
              </div>
            </form>

            <div class="summary-chips mt-4 mb-0">
              <span class="summary-chip">เอกสาร <strong><?= number_format(count($docs)) ?></strong> ฉบับ</span>
              <span class="summary-chip">รายชื่อรวม <strong><?= number_format($totalPersons) ?></strong> คน</span>
              <span class="summary-chip">เลิกจ้าง <strong><?= number_format($totalTerminated) ?></strong> คน</span>
              <span class="summary-chip">ลาออก <strong><?= number_format($totalResigned) ?></strong> คน</span>
            </div>
          </div>
        </div>

        <?php if (!$docs): ?>
          <div class="report-paper">
            <div class="doc-header">
              <h1>สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</h1>
              <h2><?= htmlspecialchars(ALIEN_CHECKIN_DOC_TITLE) ?></h2>
              <div class="range"><?= htmlspecialchars($rangeLabel) ?></div>
            </div>
            <div class="empty-note">ไม่พบเอกสารในช่วงวันที่ที่เลือก</div>
          </div>

        <?php elseif ($mode === 'summary'):
            // โหมดสรุปรวมหลายเอกสารไว้แผ่นเดียว จึงยึดผู้ส่ง/ผู้รับจากเอกสารฉบับแรกในช่วงที่กรอง
            $firstDoc              = $docs[0];
            $summaryOffice         = $firstDoc['OfficeName'] ?: ALIEN_INSURED_DEFAULT_OFFICE;
            $summaryReceiverOffice = $firstDoc['ReceiverOffice'] ?: ALIEN_INSURED_DEFAULT_RECEIVER;
        ?>
          <div class="report-paper">
            <div class="doc-header">
              <h1>สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</h1>
              <h2><?= htmlspecialchars(ALIEN_CHECKIN_DOC_TITLE) ?></h2>
              <div class="range"><?= htmlspecialchars($rangeLabel) ?></div>
            </div>

            <table class="table-report">
              <thead>
                <tr>
                  <th style="width:6%;">ลำดับ</th>
                  <th style="width:15%;">วันที่เอกสาร</th>
                  <th style="width:28%;">ชื่อ-สกุลผู้ประกันตน</th>
                  <th style="width:17%;">เลขที่บัตร (ปกส.)</th>
                  <th style="width:9%;">เลิกจ้าง</th>
                  <th style="width:9%;">ลาออก</th>
                  <th style="width:16%;">หมายเหตุ</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allPersons as $i => $p): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($thaiDate($p['DocDate'])) ?></td>
                    <td class="text-left"><?= htmlspecialchars(trim(($p['TitleName'] ?? '') . ' ' . $p['FullName'])) ?></td>
                    <td><?= htmlspecialchars($p['SsoCardNo'] ?: '') ?></td>
                    <td class="check-mark"><?= (int)$p['IsTerminated'] === 1 ? '&#10003;' : '' ?></td>
                    <td class="check-mark"><?= (int)$p['IsResigned'] === 1 ? '&#10003;' : '' ?></td>
                    <td class="text-left"><?= htmlspecialchars($p['Remark'] ?: '') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <div class="signatures">
              <div class="sign-col">
                <div class="sign-block">
                  <div class="line">ขอแสดงความนับถือ / ผู้ส่งมอบเอกสาร</div>
                  <div class="line">(<?= htmlspecialchars($signName($firstDoc) ?: '.....................................................') ?>)</div>
                  <div class="line">ตำแหน่ง<?= htmlspecialchars($signPosition($firstDoc) ?: '.....................................................') ?></div>
                </div>
                <div class="sign-office">เจ้าหน้าที่<?= htmlspecialchars($summaryOffice) ?></div>

                <div class="sign-block">
                  <div class="line">ผู้รับมอบเอกสาร</div>
                  <div class="line">ลงชื่อ <?= htmlspecialchars($firstDoc['ReceiverName'] ?: '...................................................') ?></div>
                  <div class="line">เจ้าหน้าที่<?= htmlspecialchars($summaryReceiverOffice) ?></div>
                </div>
              </div>
            </div>

            <div class="doc-footer">
              <span>รวม <?= number_format($totalPersons) ?> รายชื่อ จากเอกสาร <?= number_format(count($docs)) ?> ฉบับ
                &nbsp;•&nbsp; เลิกจ้าง <?= number_format($totalTerminated) ?> คน &nbsp;•&nbsp; ลาออก <?= number_format($totalResigned) ?> คน</span>
              <span>วันที่พิมพ์: <?= htmlspecialchars($printedAt) ?></span>
            </div>
          </div>

        <?php else: ?>
          <?php foreach ($docs as $doc):
              $persons        = $personsByDoc[$doc['DocID']] ?? [];
              $officeName     = $doc['OfficeName'] ?: ALIEN_INSURED_DEFAULT_OFFICE;
              $receiverOffice = $doc['ReceiverOffice'] ?: ALIEN_INSURED_DEFAULT_RECEIVER;
          ?>
            <div class="report-paper form-sheet">
              <div class="sheet-head">
                <h2><?= htmlspecialchars(ALIEN_CHECKIN_DOC_TITLE) ?></h2>
                <div class="office"><?= htmlspecialchars($officeName) ?></div>
                <div class="doc-date">วันที่ <?= htmlspecialchars($thaiDate($doc['DocDate'])) ?></div>
              </div>

              <?php if (!empty($doc['Remark'])): ?>
                <div class="mb-2"><strong>หมายเหตุ:</strong> <?= htmlspecialchars($doc['Remark']) ?></div>
              <?php endif; ?>

              <table class="table-report">
                <thead>
                  <tr>
                    <th style="width:8%;">ลำดับ</th>
                    <th style="width:37%;">ชื่อ-สกุลผู้ประกันตน</th>
                    <th style="width:20%;">เลขที่บัตร (ปกส.)</th>
                    <th style="width:10%;">เลิกจ้าง</th>
                    <th style="width:10%;">ลาออก</th>
                    <th style="width:15%;">หมายเหตุ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$persons): ?>
                    <tr><td colspan="6">ไม่มีรายชื่อผู้ประกันตนในเอกสารนี้</td></tr>
                  <?php else: foreach ($persons as $i => $p): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td class="text-left"><?= htmlspecialchars(trim(($p['TitleName'] ?? '') . ' ' . $p['FullName'])) ?></td>
                      <td><?= htmlspecialchars($p['SsoCardNo'] ?: '') ?></td>
                      <td class="check-mark"><?= (int)$p['IsTerminated'] === 1 ? '&#10003;' : '' ?></td>
                      <td class="check-mark"><?= (int)$p['IsResigned'] === 1 ? '&#10003;' : '' ?></td>
                      <td class="text-left"><?= htmlspecialchars($p['Remark'] ?: '') ?></td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>

              <div class="signatures">
                <div class="sign-col">
                  <div class="sign-block">
                    <div class="line">ขอแสดงความนับถือ / ผู้ส่งมอบเอกสาร</div>
                    <div class="line">(<?= htmlspecialchars($signName($doc) ?: '.....................................................') ?>)</div>
                    <div class="line">ตำแหน่ง<?= htmlspecialchars($signPosition($doc) ?: '.....................................................') ?></div>
                  </div>
                  <div class="sign-office">เจ้าหน้าที่<?= htmlspecialchars($officeName) ?></div>

                  <div class="sign-block">
                    <div class="line">ผู้รับมอบเอกสาร</div>
                    <div class="line">ลงชื่อ <?= htmlspecialchars($doc['ReceiverName'] ?: '...................................................') ?></div>
                    <div class="line">เจ้าหน้าที่<?= htmlspecialchars($receiverOffice) ?></div>
                  </div>
                </div>
              </div>

              <div class="sheet-no">เอกสารเลขที่ <?= (int)$doc['DocID'] ?> &nbsp;•&nbsp; รวม <?= count($persons) ?> รายชื่อ</div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </section>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  const thaiMonths = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
  function toThaiText(d) { return d ? `${d.getDate()} ${thaiMonths[d.getMonth()]} ${d.getFullYear() + 543}` : ''; }
  document.querySelectorAll('#filterDateFrom, #filterDateTo').forEach(function (el) {
    flatpickr(el, {
      locale: 'th', dateFormat: 'Y-m-d', altInput: true, altInputClass: 'form-control', allowInput: false,
      onReady: (d, s, i) => { i.altInput.value = toThaiText(d[0]); },
      onChange: (d, s, i) => { i.altInput.value = toThaiText(d[0]); },
      onValueUpdate: (d, s, i) => { i.altInput.value = toThaiText(d[0]); }
    });
  });

  $('.select2-field').each(function () {
    $(this).select2({ theme: 'bootstrap4', width: '100%', placeholder: $(this).data('placeholder'), language: { noResults: () => 'ไม่พบรายการที่ค้นหา' } });
  });

  // เลือกผู้ส่งมอบเอกสารแล้วเติมตำแหน่งของคนนั้นให้ ยังเลือกตำแหน่งอื่นเองได้
  // ผูกด้วย jQuery เพราะ Select2 ยิง change ผ่าน jQuery — addEventListener จะไม่ได้ยิน
  $('#filterSender').on('change', function () {
    const pos = this.selectedOptions[0]?.dataset.position || '';
    const $pos = $('#filterSenderPos');
    if (this.value === '') $pos.val('').trigger('change.select2');
    else if (pos && $pos.find('option').toArray().some(o => o.value === pos)) $pos.val(pos).trigger('change.select2');
  });

  // อธิบายผลของรูปแบบการพิมพ์ที่เลือกอยู่ ผู้ใช้จะได้รู้ว่ากดพิมพ์แล้วจะได้อะไร
  const modeHints = {
    summary: 'ได้ 1 แผ่น รวมรายชื่อทุกฉบับในช่วงที่เลือก เหมาะกับการตรวจสอบ',
    forms: 'ได้แบบฟอร์มแยกตามจำนวนเอกสาร แต่ละฉบับขึ้นหน้าใหม่ เหมาะกับการยื่นจริง'
  };
  const modeSelect = document.querySelector('#filterMode');
  const showModeHint = () => { document.querySelector('#modeHint').textContent = modeHints[modeSelect.value] || ''; };
  modeSelect.addEventListener('change', showModeHint);
  showModeHint();
</script>
</body>
</html>
