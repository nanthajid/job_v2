<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/data_import_helper.php';

$user = currentUser();
if (empty($user['level']) || $user['level'] != 1) {
    header('Location: index.php');
    exit;
}

$pdo = getDB();
$tables = dataImportGetAllConfig();

$statsByKey = [];
$errorByKey = [];
foreach ($tables as $key => $cfg) {
    try {
        $statsByKey[$key] = dataImportGetStats($pdo, $cfg);
    } catch (PDOException $e) {
        $errorByKey[$key] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>นำเข้าข้อมูล | ระบบจัดการผู้ว่างงาน</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    :root {
      --gov-navy: #002D62;
      --gov-royal: #005EB8;
      --gov-bg: #F0F2F5;
      --gov-white: #FFFFFF;
      --gov-gray: #E9ECEF;
      --gov-text-dark: #1A1A1A;
      --gov-text-muted: #64748B;
      --gov-border: #DEE2E6;
      --gov-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; background-color: var(--gov-bg); color: var(--gov-text-dark); }
    h1, h2, h3, h4, .brand-text, .nav-link, .btn { font-family: 'Prompt', sans-serif; }
    .content-wrapper { background-color: var(--gov-bg); padding-bottom: 3rem; }
    .gov-card { background: var(--gov-white); border: none; border-radius: 12px; box-shadow: var(--gov-shadow); margin-bottom: 1.5rem; overflow: hidden; }
    .gov-card-header { background-color: transparent; border-bottom: 1px solid var(--gov-gray); padding: 1.25rem 1.5rem; }
    .gov-card-title { font-size: 1.15rem; font-weight: 600; color: var(--gov-navy); margin: 0; }
    .import-stat { border-radius: 10px; padding: 1.1rem; text-align: center; height: 100%; }
    .import-stat .num { font-size: 1.75rem; font-weight: 700; line-height: 1; }
    .import-stat .lbl { color: var(--gov-text-muted); font-size: .85rem; margin-top: .35rem; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-lg-block">
        <span class="nav-link text-navy font-weight-bold">
          <i class="fas fa-desktop mr-2"></i>ระบบจัดการคนว่างงาน สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2
        </span>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" href="logout.php" role="button">
          <i class="fas fa-sign-out-alt mr-1"></i>ออกจากระบบ
        </a>
      </li>
    </ul>
  </nav>

  <!-- Sidebar -->
  <?php $current_page = 'data_import'; include __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-bold"><i class="fas fa-file-import mr-2 text-primary"></i>นำเข้าข้อมูล</h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">

        <div class="alert alert-info">
          <i class="fas fa-info-circle mr-1"></i>
          ดึงข้อมูลจากฐานข้อมูลต้นทาง (<code><?= htmlspecialchars(IMPORT_SOURCE_DB) ?></code>) มาเปรียบเทียบกับปลายทางทีละตาราง —
          รายการที่ <b>รหัสตรงกัน</b> จะถูก <b>เขียนทับ (replace)</b> ด้วยข้อมูลจากต้นทาง ส่วนรายการที่ยังไม่มีจะถูก <b>เพิ่มใหม่ (insert)</b>
        </div>

        <?php foreach ($tables as $key => $cfg): ?>
        <div class="card gov-card">
          <div class="gov-card-header d-flex flex-wrap align-items-center justify-content-between">
            <h3 class="gov-card-title"><?= htmlspecialchars($cfg['label']) ?></h3>
            <?php if (!isset($errorByKey[$key])): ?>
            <button class="btn btn-sm btn-import" data-table="<?= htmlspecialchars($key) ?>"
                    style="background-color: var(--gov-royal); color:#fff;"
                    <?= $statsByKey[$key]['total_source'] === 0 ? 'disabled' : '' ?>>
              <i class="fas fa-file-import mr-1"></i>เริ่มนำเข้าข้อมูล
            </button>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <?php if (isset($errorByKey[$key])): ?>
              <div class="alert alert-danger mb-0">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                ไม่สามารถเชื่อมต่อตารางต้นทางได้: <?= htmlspecialchars($errorByKey[$key]) ?>
              </div>
            <?php else: $s = $statsByKey[$key]; ?>
              <div class="row">
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                  <div class="import-stat bg-light border">
                    <div class="num text-navy"><?= number_format($s['total_source']) ?></div>
                    <div class="lbl">รายการในต้นทาง</div>
                  </div>
                </div>
                <div class="col-6 col-md-3 mb-2 mb-md-0">
                  <div class="import-stat bg-light border">
                    <div class="num text-secondary"><?= number_format($s['total_target']) ?></div>
                    <div class="lbl">รายการในปลายทางปัจจุบัน</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="import-stat bg-light border">
                    <div class="num text-success"><?= number_format($s['will_insert']) ?></div>
                    <div class="lbl"><i class="fas fa-plus-circle mr-1"></i>จะเพิ่มใหม่</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="import-stat bg-light border">
                    <div class="num text-warning"><?= number_format($s['will_update']) ?></div>
                    <div class="lbl"><i class="fas fa-sync-alt mr-1"></i>จะถูกแทนที่</div>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>

      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>&copy; <?= date('Y') + 543 ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</strong>
  </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  $('.btn-import').on('click', function () {
    const $btn = $(this);
    const table = $btn.data('table');
    const label = $btn.closest('.gov-card').find('.gov-card-title').text();

    Swal.fire({
      title: 'ยืนยันการนำเข้าข้อมูล?',
      html: `ตาราง <b>${label}</b><br>รายการที่รหัสซ้ำกันจะถูก <b>เขียนทับ</b> ด้วยข้อมูลจากต้นทางทั้งหมด<br>การทำรายการนี้ไม่สามารถย้อนกลับได้`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#005EB8',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'ยืนยัน นำเข้าข้อมูล',
      cancelButtonText: 'ยกเลิก'
    }).then((result) => {
      if (!result.isConfirmed) return;

      const originalHtml = $btn.html();
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>กำลังนำเข้าข้อมูล...');

      $.post('api/data_import.php', { table: table }, function (res) {
        if (res.success) {
          Swal.fire('สำเร็จ', res.message, 'success').then(() => location.reload());
        } else {
          Swal.fire('ผิดพลาด', res.message, 'error');
          $btn.prop('disabled', false).html(originalHtml);
        }
      }, 'json').fail(function (xhr) {
        Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        $btn.prop('disabled', false).html(originalHtml);
      });
    });
  });
});
</script>
</body>
</html>
