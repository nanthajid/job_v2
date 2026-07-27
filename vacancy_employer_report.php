<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$pdo = getDB();

$employerNo = (int)($_GET['EmployerNo'] ?? 0);
if ($employerNo <= 0) {
    http_response_code(400);
    exit('ไม่พบรหัสสถานประกอบการ');
}

$stmt = $pdo->prepare('SELECT * FROM vacancy_employer WHERE EmployerNo = :id');
$stmt->execute(['id' => $employerNo]);
$employer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employer) {
    http_response_code(404);
    exit('ไม่พบข้อมูลสถานประกอบการที่ระบุ');
}

// Every position ever requested by this employer, across every notice it has submitted
// (vacancy_notice_position is the N:M junction between vacancy_notice and vacancy_position_master).
$listStmt = $pdo->prepare(
    'SELECT n.NoticeID, n.CreatedAt, m.PositionName, p.Headcount, p.Wage, p.Gender, p.AgeRange, p.Education, p.Conditions, p.WorkSchedule, p.Remark
     FROM vacancy_notice n
     JOIN vacancy_notice_position p ON p.NoticeID = n.NoticeID
     JOIN vacancy_position_master m ON m.PositionMasterID = p.PositionMasterID
     WHERE n.EmployerNo = :id
     ORDER BY n.CreatedAt DESC, p.PositionID ASC'
);
$listStmt->execute(['id' => $employerNo]);
$rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// Totals per distinct position across all notices — this is the payoff of the N:M model:
// the same position master can be requested repeatedly across separate notices over time.
$summaryStmt = $pdo->prepare(
    'SELECT m.PositionName, COUNT(DISTINCT n.NoticeID) NoticeCount, SUM(p.Headcount) TotalHeadcount
     FROM vacancy_notice n
     JOIN vacancy_notice_position p ON p.NoticeID = n.NoticeID
     JOIN vacancy_position_master m ON m.PositionMasterID = p.PositionMasterID
     WHERE n.EmployerNo = :id
     GROUP BY m.PositionMasterID
     ORDER BY TotalHeadcount DESC'
);
$summaryStmt->execute(['id' => $employerNo]);
$summary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

$noticeCountStmt = $pdo->prepare('SELECT COUNT(*) FROM vacancy_notice WHERE EmployerNo = :id');
$noticeCountStmt->execute(['id' => $employerNo]);
$noticeCount = (int)$noticeCountStmt->fetchColumn();

$thaiDate = function ($iso) {
    if (!$iso) return '';
    $d = DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
    if (!$d) return $iso;
    $thMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return (int)$d->format('j') . ' ' . $thMonths[(int)$d->format('n')] . ' ' . ((int)$d->format('Y') + 543);
};
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>รายงานตำแหน่งงานว่างตามนายจ้าง | สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</title>

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
    .info-list .label { font-weight: 600; color: var(--gov-navy); display: inline-block; min-width: 150px; }
    .stat-row { display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
    .stat-box { flex: 1; min-width: 150px; background: #F8F9FA; border: 1px solid #DEE2E6; border-radius: 10px; padding: 0.85rem 1rem; text-align: center; }
    .stat-box .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--gov-navy); }
    .stat-box .stat-label { font-size: 0.8rem; color: #64748B; }
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
  <h2>รายงานตำแหน่งงานว่างตามนายจ้าง</h2>
  <div class="subtitle">สรุปทุกตำแหน่งงานว่างที่แจ้งไว้ ทุกใบแจ้งของสถานประกอบการนี้</div>
</div>

<div class="info-section">
  <h3><i class="fas fa-building mr-2"></i>ข้อมูลสถานประกอบการ</h3>
  <ul class="info-list">
    <li><span class="label">ชื่อสถานประกอบการ:</span> <?= htmlspecialchars($employer['EmployerName']) ?></li>
    <li><span class="label">เลขที่จดทะเบียนพาณิชย์:</span> <?= htmlspecialchars($employer['EmployerID'] ?: '-') ?></li>
    <li><span class="label">ประเภทกิจการ:</span> <?= htmlspecialchars($employer['BusinessType'] ?: '-') ?></li>
    <li><span class="label">เบอร์โทรศัพท์:</span> <?= htmlspecialchars($employer['EmployerPhone'] ?: '-') ?></li>
    <li><span class="label">ผู้ติดต่อ:</span> <?= htmlspecialchars($employer['ContactName'] ?: '-') ?></li>
  </ul>
</div>

<div class="stat-row">
  <div class="stat-box"><div class="stat-value"><?= $noticeCount ?></div><div class="stat-label">ใบแจ้งทั้งหมด</div></div>
  <div class="stat-box"><div class="stat-value"><?= count($summary) ?></div><div class="stat-label">ตำแหน่งงานที่ต่างกัน</div></div>
  <div class="stat-box"><div class="stat-value"><?= array_sum(array_column($summary, 'TotalHeadcount')) ?></div><div class="stat-label">อัตราที่ต้องการรวมทั้งหมด</div></div>
</div>

<div class="info-section">
  <h3><i class="fas fa-layer-group mr-2"></i>สรุปยอดรวมตามตำแหน่งงาน (ทุกใบแจ้ง)</h3>
  <table class="table-report">
    <thead><tr><th style="width: 40px;">ลำดับ</th><th>ตำแหน่งงานว่าง</th><th style="width: 100px;">จำนวนใบแจ้งที่ขอตำแหน่งนี้</th><th style="width: 100px;">อัตรารวม</th></tr></thead>
    <tbody>
      <?php if (empty($summary)): ?>
        <tr><td colspan="4" class="text-center text-muted py-3">ไม่พบข้อมูล</td></tr>
      <?php else: $i = 0; foreach ($summary as $s): $i++; ?>
        <tr>
          <td class="text-center"><?= $i ?></td>
          <td><?= htmlspecialchars($s['PositionName']) ?></td>
          <td class="text-center"><?= (int)$s['NoticeCount'] ?></td>
          <td class="text-center"><?= (int)$s['TotalHeadcount'] ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="info-section">
  <h3><i class="fas fa-list mr-2"></i>รายละเอียดทุกตำแหน่งงาน แยกตามใบแจ้ง</h3>
  <table class="table-report">
    <thead>
      <tr>
        <th style="width: 40px;">ลำดับ</th>
        <th style="width: 60px;">เลขที่ใบแจ้ง</th>
        <th style="width: 100px;">วันที่แจ้ง</th>
        <th>ตำแหน่งงานว่าง</th>
        <th style="width: 70px;">จำนวนอัตรา</th>
        <th>อัตราค่าจ้าง</th>
        <th style="width: 50px;">เพศ</th>
        <th>อายุ</th>
        <th>วุฒิการศึกษา</th>
        <th>เงื่อนไข/สวัสดิการ</th>
        <th>ช่วงวัน-เวลาการทำงาน</th>
        <th>หมายเหตุ</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="12" class="text-center text-muted py-3">ไม่พบตำแหน่งงานว่าง</td></tr>
      <?php else: $i = 0; foreach ($rows as $r): $i++; ?>
        <tr>
          <td class="text-center"><?= $i ?></td>
          <td class="text-center">#<?= (int)$r['NoticeID'] ?></td>
          <td class="text-center"><?= $thaiDate($r['CreatedAt']) ?></td>
          <td><?= htmlspecialchars($r['PositionName']) ?></td>
          <td class="text-center"><?= htmlspecialchars((string)$r['Headcount']) ?></td>
          <td><?= htmlspecialchars($r['Wage'] ?: '-') ?></td>
          <td class="text-center"><?= htmlspecialchars($r['Gender'] ?: '-') ?></td>
          <td><?= htmlspecialchars($r['AgeRange'] ?: '-') ?></td>
          <td><?= htmlspecialchars($r['Education'] ?: '-') ?></td>
          <td><?= htmlspecialchars($r['Conditions'] ?: '-') ?></td>
          <td><?= htmlspecialchars($r['WorkSchedule'] ?: '-') ?></td>
          <td><?= htmlspecialchars($r['Remark'] ?: '-') ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="print-footer">
  วันที่พิมพ์: <?= $thaiDate(date('Y-m-d')) ?> &nbsp;•&nbsp; © <?php echo (date('Y') + 543); ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2
</div>

<script>
document.getElementById('btnPrint').addEventListener('click', function () { window.print(); });
</script>
</body>
</html>
