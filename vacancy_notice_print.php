<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$pdo = getDB();

$noticeId = (int)($_GET['id'] ?? 0);
if ($noticeId <= 0) {
    http_response_code(400);
    exit('ไม่พบรหัสใบแจ้งตำแหน่งงานว่าง');
}

$stmt = $pdo->prepare('SELECT * FROM vacancy_notice WHERE NoticeID = :id');
$stmt->execute(['id' => $noticeId]);
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notice) {
    http_response_code(404);
    exit('ไม่พบใบแจ้งตำแหน่งงานว่างที่ระบุ');
}

$posStmt = $pdo->prepare('SELECT p.*, m.PositionName FROM vacancy_notice_position p
    JOIN vacancy_position_master m ON m.PositionMasterID = p.PositionMasterID
    WHERE p.NoticeID = :id ORDER BY p.PositionID ASC');
$posStmt->execute(['id' => $noticeId]);
$positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);

$thaiDate = function ($iso) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
    if (!$d) return $iso;
    $thMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};

$addressParts = array_filter([
    $notice['HouseNo'] ? 'เลขที่ ' . $notice['HouseNo'] : '',
    $notice['Moo'] ? 'หมู่ที่ ' . $notice['Moo'] : '',
    $notice['Soi'] ? 'ซอย' . $notice['Soi'] : '',
    $notice['Road'] ? 'ถนน' . $notice['Road'] : '',
    $notice['Subdistrict'] ? 'แขวง/ตำบล ' . $notice['Subdistrict'] : '',
    $notice['District'] ? 'เขต/อำเภอ ' . $notice['District'] : '',
    $notice['Province'] ? 'จังหวัด ' . $notice['Province'] : '',
]);
$address = implode(' ', $addressParts) ?: '-';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>พิมพ์ใบแจ้งตำแหน่งงานว่าง | สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <style>
    :root { --gov-navy: #002D62; --gov-gold: #D4AF37; }
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; font-size: 14px; color: #1A1A1A; background: white; margin: 0; padding: 1.5rem; }
    .print-header { text-align: center; border-bottom: 3px solid var(--gov-navy); padding-bottom: 1rem; margin-bottom: 1.5rem; }
    .print-header h2 { font-family: 'Prompt', sans-serif; font-size: 1.5rem; color: var(--gov-navy); margin: 0 0 0.3rem; }
    .print-header .subtitle { font-size: 0.95rem; color: #64748B; font-weight: 600; }
    .info-section { margin-bottom: 1.25rem; }
    .info-section h3 { font-family: 'Prompt', sans-serif; font-size: 1.05rem; color: var(--gov-navy); border-bottom: 2px solid var(--gov-gold); padding-bottom: 0.3rem; margin-bottom: 0.6rem; }
    .info-list { list-style: none; margin: 0; padding: 0; }
    .info-list li { padding: 0.2rem 0; }
    .info-list .label { font-weight: 600; color: var(--gov-navy); display: inline-block; min-width: 190px; }
    .table-report { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 0.5rem; }
    .table-report thead th { background: #E9ECEF; color: var(--gov-navy); font-weight: 600; padding: 0.6rem 0.5rem; border: 1px solid #DEE2E6; text-align: center; font-size: 0.8rem; }
    .table-report tbody td { padding: 0.5rem 0.5rem; border: 1px solid #E9ECEF; vertical-align: middle; }
    .table-report tbody tr:nth-child(even) { background: #F8F9FA; }
    .text-center { text-align: center; }
    .print-footer { margin-top: 2rem; border-top: 1px solid #DEE2E6; padding-top: 0.5rem; text-align: center; font-size: 0.8rem; color: #64748B; }
    .no-print { text-align: center; margin-bottom: 1.5rem; }
    @media print {
      .no-print { display: none !important; }
      body { padding: 1cm; }
      @page { size: A4 landscape; margin: 1cm; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button type="button" id="btnPrint" class="btn btn-warning btn-sm px-3" style="border-radius: 8px; font-weight: 600;"><i class="fas fa-print mr-1"></i> พิมพ์เอกสาร</button>
  <a href="vacancy_management.php" class="btn btn-outline-secondary btn-sm px-3" style="border-radius: 8px;">ย้อนกลับ</a>
</div>

<div class="print-header">
  <h2>ใบแจ้งตำแหน่งงานว่าง</h2>
  <div class="subtitle">กรมการจัดหางาน</div>
</div>

<div class="info-section">
  <h3><i class="fas fa-building mr-2"></i>ข้อมูลสถานประกอบการ</h3>
  <ul class="info-list">
    <li><span class="label">ชื่อสถานประกอบการ:</span> <?= htmlspecialchars($notice['EmployerName']) ?></li>
    <li><span class="label">เลขที่จดทะเบียนพาณิชย์:</span> <?= htmlspecialchars($notice['EmployerID'] ?: '-') ?></li>
    <li><span class="label">ประเภทกิจการ:</span> <?= htmlspecialchars($notice['BusinessType'] ?: '-') ?></li>
    <li><span class="label">สถานที่ตั้ง:</span> <?= htmlspecialchars($address) ?></li>
    <li><span class="label">รหัสไปรษณีย์:</span> <?= htmlspecialchars($notice['PostalCode'] ?: '-') ?></li>
  </ul>
</div>

<div class="info-section">
  <h3><i class="fas fa-user mr-2"></i>ข้อมูลบุคคลที่ติดต่อ</h3>
  <ul class="info-list">
    <li><span class="label">ชื่อบุคคลติดต่อ:</span> <?= htmlspecialchars($notice['ContactName'] ?: '-') ?></li>
    <li><span class="label">ตำแหน่ง:</span> <?= htmlspecialchars($notice['ContactPosition'] ?: '-') ?></li>
    <li><span class="label">โทรศัพท์:</span> <?= htmlspecialchars($notice['EmployerPhone'] ?: '-') ?></li>
  </ul>
</div>

<div class="info-section">
  <h3><i class="fas fa-briefcase mr-2"></i>รายละเอียดตำแหน่งงานว่าง</h3>
  <table class="table-report">
    <thead>
      <tr>
        <th style="width: 40px;">ลำดับที่</th>
        <th>ตำแหน่งงานว่าง</th>
        <th style="width: 70px;">จำนวนอัตรา</th>
        <th>อัตราค่าจ้าง</th>
        <th style="width: 50px;">เพศ</th>
        <th>อายุ (คุณสมบัติ)</th>
        <th>วุฒิการศึกษา</th>
        <th>เงื่อนไข/สวัสดิการ</th>
        <th>ช่วงวัน-เวลาการทำงาน</th>
        <th>หมายเหตุ</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($positions)): ?>
        <tr><td colspan="10" class="text-center text-muted py-3">ไม่พบตำแหน่งงานว่าง</td></tr>
      <?php else: $i = 0; foreach ($positions as $p): $i++; ?>
        <tr>
          <td class="text-center"><?= $i ?></td>
          <td><?= htmlspecialchars($p['PositionName']) ?></td>
          <td class="text-center"><?= htmlspecialchars((string)$p['Headcount']) ?></td>
          <td><?= htmlspecialchars($p['Wage'] ?: '-') ?></td>
          <td class="text-center"><?= htmlspecialchars($p['Gender'] ?: '-') ?></td>
          <td><?= htmlspecialchars($p['AgeRange'] ?: '-') ?></td>
          <td><?= htmlspecialchars($p['Education'] ?: '-') ?></td>
          <td><?= htmlspecialchars($p['Conditions'] ?: '-') ?></td>
          <td><?= htmlspecialchars($p['WorkSchedule'] ?: '-') ?></td>
          <td><?= htmlspecialchars($p['Remark'] ?: '-') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="print-footer">
  วันที่พิมพ์: <?= $thaiDate(date('Y-m-d')) ?> &nbsp;•&nbsp; © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });
</script>
</body>
</html>
