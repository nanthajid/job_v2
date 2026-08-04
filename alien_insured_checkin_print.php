<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/alien_insured_checkin_helper.php';

$pdo   = getDB();
$docId = (int)($_GET['id'] ?? 0);
if ($docId <= 0) {
    http_response_code(400);
    exit('ไม่พบรหัสเอกสาร');
}

$stmt = $pdo->prepare('SELECT * FROM ' . ALIEN_CHECKIN_DOC_TABLE . ' WHERE DocID = :id');
$stmt->execute(['id' => $docId]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    exit('ไม่พบเอกสารที่ระบุ');
}

$pStmt = $pdo->prepare('SELECT * FROM ' . ALIEN_CHECKIN_PERSON_TABLE . ' WHERE DocID = :id ORDER BY SeqNo ASC, PersonID ASC');
$pStmt->execute(['id' => $docId]);
$persons = $pStmt->fetchAll(PDO::FETCH_ASSOC);

$thaiDateFull = function ($iso) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
    if (!$d) return $iso;
    $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    return (int)$d->format('j') . ' ' . $months[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

$officeName     = $doc['OfficeName'] ?: ALIEN_INSURED_DEFAULT_OFFICE;
$receiverOffice = $doc['ReceiverOffice'] ?: ALIEN_INSURED_DEFAULT_RECEIVER;
// เอกสารที่บันทึกชื่อผู้ส่งไว้แบบไม่มีคำนำหน้า ให้เติมจากทะเบียนเจ้าหน้าที่ให้ตรงกับหน้าอื่น
$senderName     = alienInsuredWithTitle($doc['SenderName'] ?? '', alienInsuredTitleMap($pdo));
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>พิมพ์<?= htmlspecialchars(ALIEN_CHECKIN_DOC_TITLE) ?> | <?= htmlspecialchars($officeName) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <style>
    :root { --gov-navy: #002D62; --gov-gold: #D4AF37; }
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; font-size: 15px; color: #1A1A1A; background: #fff; margin: 0; padding: 1.5rem; }
    .sheet { max-width: 20cm; margin: 0 auto; }
    .no-print { text-align: center; margin-bottom: 1.5rem; }

    .doc-head { text-align: center; margin-bottom: 1.25rem; }
    .doc-head h2 { font-family: 'Prompt', sans-serif; font-size: 1.35rem; color: var(--gov-navy); margin: 0 0 .35rem; }
    .doc-head .office { font-size: 1.05rem; font-weight: 600; }
    .doc-head .doc-date { margin-top: .35rem; }
    .doc-remark { margin-bottom: .75rem; }

    .table-person { width: 100%; border-collapse: collapse; font-size: .95rem; }
    .table-person th, .table-person td { border: 1px solid #333; padding: .45rem .5rem; vertical-align: middle; }
    .table-person thead th { background: #E9ECEF; color: var(--gov-navy); font-weight: 600; text-align: center; }
    .table-person tbody tr { page-break-inside: avoid; }
    .text-center { text-align: center; }
    /* ห้ามใช้ชื่อ .mark — ชนกับ utility ของ Bootstrap ที่ใส่พื้นหลังสีเหลือง */
    .check-mark { font-size: 1.05rem; font-weight: 700; }

    .signatures { display: flex; justify-content: flex-end; margin-top: 2.5rem; page-break-inside: avoid; }
    .sign-col { width: 60%; }
    .sign-block { text-align: center; margin-bottom: 2.25rem; }
    .sign-block .line { margin: .35rem 0; }
    .sign-office { margin-top: -1.75rem; margin-bottom: 1.75rem; font-size: .95rem; }
    .print-footer { margin-top: 2rem; border-top: 1px solid #DEE2E6; padding-top: .5rem; text-align: center; font-size: .8rem; color: #64748B; }

    @media print {
      .no-print { display: none !important; }
      body { padding: 0; font-size: 15px; }
      .sheet { max-width: none; }
      @page { size: A4 portrait; margin: 1.5cm; }
      .table-person thead { display: table-header-group; }
    }
  </style>
</head>
<body>

<div class="sheet">

  <div class="no-print">
    <button type="button" id="btnPrint" class="btn btn-warning btn-sm px-3" style="border-radius:8px;font-weight:600;"><i class="fas fa-print mr-1"></i> พิมพ์เอกสาร</button>
    <a href="alien_insured_checkin.php" class="btn btn-outline-secondary btn-sm px-3" style="border-radius:8px;">ย้อนกลับ</a>
  </div>

  <div class="doc-head">
    <h2><?= htmlspecialchars(ALIEN_CHECKIN_DOC_TITLE) ?></h2>
    <div class="office"><?= htmlspecialchars($officeName) ?></div>
    <div class="doc-date">วันที่ <?= htmlspecialchars($thaiDateFull($doc['DocDate'])) ?></div>
  </div>

  <?php if (!empty($doc['Remark'])): ?>
    <div class="doc-remark"><strong>หมายเหตุ:</strong> <?= htmlspecialchars($doc['Remark']) ?></div>
  <?php endif; ?>

  <table class="table-person">
    <thead>
      <tr>
        <th style="width:60px;">ลำดับ</th>
        <th>ชื่อ-สกุลผู้ประกันตน</th>
        <th style="width:170px;">เลขที่บัตร (ปกส.)</th>
        <th style="width:80px;">เลิกจ้าง</th>
        <th style="width:80px;">ลาออก</th>
        <th style="width:150px;">หมายเหตุ</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$persons): ?>
        <tr><td colspan="6" class="text-center">ไม่มีรายชื่อผู้ประกันตนในเอกสารนี้</td></tr>
      <?php else: foreach ($persons as $i => $p): ?>
        <tr>
          <td class="text-center"><?= $i + 1 ?></td>
          <td><?= htmlspecialchars(trim(($p['TitleName'] ?? '') . ' ' . $p['FullName'])) ?></td>
          <td class="text-center"><?= htmlspecialchars($p['SsoCardNo'] ?: '') ?></td>
          <td class="text-center check-mark"><?= $p['IsTerminated'] ? '&#10003;' : '' ?></td>
          <td class="text-center check-mark"><?= $p['IsResigned'] ? '&#10003;' : '' ?></td>
          <td><?= htmlspecialchars($p['Remark'] ?: '') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <div class="signatures">
    <div class="sign-col">
      <div class="sign-block">
        <div class="line">ขอแสดงความนับถือ / ผู้ส่งมอบเอกสาร</div>
        <div class="line">(<?= htmlspecialchars($senderName ?: '.....................................................') ?>)</div>
        <div class="line">ตำแหน่ง<?= htmlspecialchars($doc['SenderPosition'] ?: '.....................................................') ?></div>
      </div>
      <div class="sign-office">เจ้าหน้าที่<?= htmlspecialchars($officeName) ?></div>

      <div class="sign-block">
        <div class="line">ผู้รับมอบเอกสาร</div>
        <div class="line">ลงชื่อ <?= htmlspecialchars($doc['ReceiverName'] ?: '...................................................') ?></div>
        <div class="line">เจ้าหน้าที่<?= htmlspecialchars($receiverOffice) ?></div>
      </div>
    </div>
  </div>

  <div class="print-footer">
    เอกสารเลขที่ <?= (int)$doc['DocID'] ?> &nbsp;•&nbsp; รวม <?= count($persons) ?> รายชื่อ &nbsp;•&nbsp;
    วันที่พิมพ์ <?= $thaiDateFull(date('Y-m-d')) ?>
  </div>

</div>

<script>
document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });
</script>
</body>
</html>
