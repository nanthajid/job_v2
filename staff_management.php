<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo  = getDB();

// ดึงข้อมูลเจ้าหน้าที่พร้อมชื่อผู้ใช้
$sql = "SELECT s.*, t.Title, p.StPostName, pt.PostTypeName, pt.PostType, d.DepName,
               u.UserName, u.StatusNo, us.StatusName, us.BadgeClass
        FROM staff s
        LEFT JOIN titles t ON s.TitleNo = t.TitleNo
        LEFT JOIN position p ON s.StPost = p.StPost
        LEFT JOIN post_type pt ON p.PostType = pt.PostType
        LEFT JOIN department d ON s.DepNo = d.DepNo
        LEFT JOIN users u ON s.StID = u.StID
        LEFT JOIN user_status us ON u.StatusNo = us.StatusNo
        ORDER BY s.StID ASC";

$staffList = [];
try {
    $staffList = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}

// ดึงข้อมูลสำหรับ Dropdown
$titlesRows = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll();
$sexRows    = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll();
$posRows    = $pdo->query("SELECT StPost, StPostName, PostType FROM position ORDER BY StPostName")->fetchAll();
$depRows    = $pdo->query("SELECT DepNo, DepName FROM department ORDER BY DepName")->fetchAll();
$ptRows     = $pdo->query("SELECT PostType, PostTypeName FROM post_type ORDER BY PostType")->fetchAll();

function getPostTypeBadgeClass($type) {
    $type = trim($type);
    switch ($type) {
        case '1': case 'ข้าราชการ': return 'badge-primary';
        case '2': case 'พนักงานราชการ': return 'badge-success';
        case '3': case 'ลูกจ้างชั่วคราว': return 'badge-warning';
        case '4': case 'พนักงานจ้างเหมาบริการ': return 'badge-danger';
        default: return 'badge-light border';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>จัดการข้อมูลเจ้าหน้าที่ | ระบบจัดการผู้ว่างงาน</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; background-color: var(--gov-bg); color: var(--gov-text-dark); }
    h1, h2, h3, h4, .brand-text, .nav-link, .btn { font-family: 'Prompt', sans-serif; }
    .content-wrapper { background-color: var(--gov-bg); padding-bottom: 3rem; }
    .gov-card { background: var(--gov-white); border: none; border-radius: 12px; box-shadow: var(--gov-shadow); margin-bottom: 1.5rem; overflow: hidden; }
    .gov-card-header { background-color: transparent; border-bottom: 1px solid var(--gov-gray); padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .gov-card-title { font-size: 1.25rem; font-weight: 600; color: var(--gov-navy); margin: 0; }
    .table thead th { background-color: var(--gov-gray); color: var(--gov-navy); font-weight: 600; border-bottom: 2px solid var(--gov-border); font-size: 0.9rem; padding: 1rem; }
    .btn-gov-primary { background-color: var(--gov-royal); color: white; border-radius: 6px; padding: 0.5rem 1rem; font-weight: 500; transition: all 0.2s; }
    .btn-gov-primary:hover { background-color: var(--gov-navy); color: white; transform: translateY(-1px); }
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
  <?php $current_page = 'staff_management'; include __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-bold"><i class="fas fa-users-cog mr-2 text-primary"></i>จัดการข้อมูลเจ้าหน้าที่</h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="card gov-card">
          <div class="gov-card-header">
            <h3 class="gov-card-title">รายชื่อเจ้าหน้าที่</h3>
            <button class="btn btn-gov-primary btn-sm" data-toggle="modal" data-target="#addModal">
              <i class="fas fa-user-plus mr-1"></i>เพิ่มเจ้าหน้าที่
            </button>
          </div>
          <div class="card-body">
            <?php if (isset($error)): ?>
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>พบข้อผิดพลาด: <?= htmlspecialchars($error) ?>
              </div>
            <?php endif; ?>

            <div class="table-responsive">
              <table class="table table-hover" id="staffTable">
                <thead>
                  <tr>
                    <th width="60">ลำดับ</th>
                    <th>ชื่อเจ้าหน้าที่</th>
                    <th>ชื่อผู้ใช้</th>
                    <th>ตำแหน่ง/ฝ่าย</th>
                    <th width="140" class="text-center">สถานะ</th>
                    <th width="120" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($staffList as $index => $row): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td>
                        <div class="d-flex align-items-center">
                          <img src="uploads/staff/<?= $row['image'] ?: 'noimage.png' ?>" class="img-circle mr-2" style="width: 35px; height: 35px; object-fit: cover;" onerror="this.src='uploads/staff/noimage.png'">
                          <div class="font-weight-bold text-navy"><?= htmlspecialchars(($row['Title'] ?? '') . ' ' . ($row['StName'] ?? '')) ?></div>
                        </div>
                      </td>
                      <td><code class="text-primary"><?= htmlspecialchars($row['UserName'] ?? '-') ?></code></td>
                      <td>
                        <div><?= htmlspecialchars($row['StPostName'] ?? '-') ?></div>
                        <small class="text-muted"><i class="fas fa-building mr-1"></i><?= htmlspecialchars($row['DepName'] ?? '-') ?></small>
                      </td>
                      <td class="text-center">
                        <?php if (!empty($row['UserName'])):
                          $isActive = ((int)($row['StatusNo'] ?? 1) === 1);
                          $badgeCls = $row['BadgeClass'] ?? ($isActive ? 'badge-success' : 'badge-secondary');
                          $stName   = $row['StatusName'] ?? ($isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน');
                        ?>
                          <div class="custom-control custom-switch d-inline-block align-middle">
                            <input type="checkbox" class="custom-control-input status-toggle" id="st-<?= htmlspecialchars($row['StID']) ?>"
                                   data-stid="<?= htmlspecialchars($row['StID']) ?>"
                                   data-name="<?= htmlspecialchars($row['StName']) ?>" <?= $isActive ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="st-<?= htmlspecialchars($row['StID']) ?>"></label>
                          </div>
                          <span class="badge <?= htmlspecialchars($badgeCls) ?> status-badge" data-stid="<?= htmlspecialchars($row['StID']) ?>"><?= htmlspecialchars($stName) ?></span>
                        <?php else: ?>
                          <span class="text-muted small">ไม่มีบัญชี</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center">
                        <div class="btn-group">
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-staff='<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>' title="แก้ไข">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= htmlspecialchars($row['StID']) ?>" data-name="<?= htmlspecialchars($row['StName']) ?>" title="ลบ">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Add Modal -->
  <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="addStaffForm" method="post" action="api/staff_save.php" enctype="multipart/form-data">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>เพิ่มเจ้าหน้าที่ใหม่</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 text-center border-right">
                <div class="form-group">
                  <label>รูปถ่าย</label>
                  <div class="mt-2 mb-3">
                    <img id="img-preview-add" src="uploads/staff/noimage.png" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                  </div>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" name="image" id="image-add" accept="image/*">
                    <label class="custom-file-label text-left" for="image-add">เลือกไฟล์</label>
                  </div>
                </div>
                <hr>
                <div class="form-group text-left">
                  <label class="text-primary"><i class="fas fa-key mr-1"></i>ข้อมูลการเข้าระบบ</label>
                  <input type="text" name="UserName" class="form-control mb-2" placeholder="ชื่อผู้ใช้ (Username)" required>
                  <input type="password" name="Password" class="form-control" placeholder="รหัสผ่าน (Password)" required>
                </div>
              </div>
              <div class="col-md-8">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group"><label>คำนำหน้า</label>
                      <select name="TitleNo" class="form-control" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($titlesRows as $t): ?><option value="<?= $t['TitleNo'] ?>"><?= htmlspecialchars($t['Title']) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group"><label>ชื่อ-นามสกุล</label>
                      <input type="text" name="StName" class="form-control" placeholder="ระบุชื่อและนามสกุล" required>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group"><label>รหัสเจ้าหน้าที่</label>
                      <div class="input-group">
                        <input type="text" name="StID" id="StID-add" class="form-control bg-light" placeholder="ออกเลขอัตโนมัติ" readonly required>
                        <div class="input-group-append">
                          <button type="button" class="btn btn-outline-secondary" id="btn-refresh-stid" title="ออกเลขใหม่">
                            <i class="fas fa-sync-alt"></i>
                          </button>
                        </div>
                      </div>
                      <small class="text-muted">ระบบออกเลขรันให้อัตโนมัติ</small>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group"><label>เพศ</label>
                      <select name="SexNo" class="form-control" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($sexRows as $s): ?><option value="<?= $s['SexNo'] ?>"><?= htmlspecialchars($s['SexName']) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group"><label>ประเภทพนักงาน</label>
                      <select id="PostType-add" class="form-control" required>
                        <option value="">-- เลือกประเภท --</option>
                        <?php foreach ($ptRows as $pt): ?><option value="<?= $pt['PostType'] ?>"><?= htmlspecialchars($pt['PostTypeName']) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group"><label>ตำแหน่ง</label>
                      <select name="StPost" id="StPost-add" class="form-control" required disabled>
                        <option value="">-- เลือกประเภทก่อน --</option>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="form-group"><label>ฝ่าย</label>
                  <select name="DepNo" class="form-control" required>
                    <option value="">-- เลือกฝ่าย --</option>
                    <?php foreach ($depRows as $d): ?><option value="<?= $d['DepNo'] ?>"><?= htmlspecialchars($d['DepName']) ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>บันทึก</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="editStaffForm" method="post" action="api/staff_update.php" enctype="multipart/form-data">
        <input type="hidden" name="old_StID" id="e-old-stid">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>แก้ไขข้อมูลเจ้าหน้าที่</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 text-center border-right">
                <div class="form-group">
                  <label>รูปถ่าย</label>
                  <div class="mt-2 mb-3">
                    <img id="img-preview-edit" src="uploads/staff/noimage.png" class="img-thumbnail rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                  </div>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" name="image" id="image-edit" accept="image/*">
                    <label class="custom-file-label text-left" for="image-edit">เปลี่ยนรูป</label>
                  </div>
                </div>
                <hr>
                <div class="form-group text-left">
                  <label class="text-primary"><i class="fas fa-key mr-1"></i>ข้อมูลการเข้าระบบ</label>
                  <input type="text" name="UserName" id="e-UserName" class="form-control mb-2" placeholder="ชื่อผู้ใช้" required>
                  <input type="password" name="Password" class="form-control" placeholder="รหัสผ่านใหม่ (ว่างไว้ถ้าไม่เปลี่ยน)">
                </div>
                <hr>
                <div class="form-group text-left">
                  <label class="text-primary d-block"><i class="fas fa-toggle-on mr-1"></i>สถานะบัญชี</label>
                  <input type="hidden" name="StatusNo" id="e-StatusNo">
                  <div class="custom-control custom-switch d-inline-block align-middle">
                    <input type="checkbox" class="custom-control-input" id="e-Status-toggle">
                    <label class="custom-control-label" for="e-Status-toggle"></label>
                  </div>
                  <span class="badge ml-1" id="e-Status-badge"></span>
                </div>
              </div>
              <div class="col-md-8">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group"><label>คำนำหน้า</label>
                      <select name="TitleNo" id="e-TitleNo" class="form-control" required>
                        <?php foreach ($titlesRows as $t): ?><option value="<?= $t['TitleNo'] ?>"><?= htmlspecialchars($t['Title']) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group"><label>ชื่อ-นามสกุล</label>
                      <input type="text" name="StName" id="e-StName" class="form-control" required>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group"><label>รหัสเจ้าหน้าที่</label>
                      <input type="text" name="StID" id="e-StID" class="form-control" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group"><label>เพศ</label>
                      <select name="SexNo" id="e-SexNo" class="form-control" required>
                        <?php foreach ($sexRows as $s): ?><option value="<?= $s['SexNo'] ?>"><?= htmlspecialchars($s['SexName']) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group"><label>ประเภทพนักงาน</label>
                      <select id="PostType-edit" class="form-control" required>
                        <?php foreach ($ptRows as $pt): ?><option value="<?= $pt['PostType'] ?>"><?= htmlspecialchars($pt['PostTypeName']) ?></option><?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group"><label>ตำแหน่ง</label>
                      <select name="StPost" id="StPost-edit" class="form-control" required></select>
                    </div>
                  </div>
                </div>
                <div class="form-group"><label>ฝ่าย</label>
                  <select name="DepNo" id="e-DepNo" class="form-control" required>
                    <?php foreach ($depRows as $d): ?><option value="<?= $d['DepNo'] ?>"><?= htmlspecialchars($d['DepName']) ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>บันทึกการแก้ไข</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <footer class="main-footer">
    <strong>&copy; <?= date('Y') + 543 ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</strong>
  </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  const allPositions = <?= json_encode($posRows, JSON_UNESCAPED_UNICODE) ?>;
  $('#staffTable').DataTable({ "language": { "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/th.json" } });

  function filterPositions(typeId, targetSelect, currentVal = '') {
    const $select = $(targetSelect).empty().prop('disabled', true);
    if (!typeId) { $select.append('<option value="">-- เลือกประเภทก่อน --</option>'); return; }
    const filtered = allPositions.filter(p => String(p.PostType) === String(typeId));
    if (filtered.length > 0) {
      $select.append('<option value="">-- เลือกตำแหน่ง --</option>');
      filtered.forEach(p => $select.append(`<option value="${p.StPost}" ${p.StPost == currentVal ? 'selected' : ''}>${p.StPostName}</option>`));
      $select.prop('disabled', false);
    } else { $select.append('<option value="">-- ไม่พบตำแหน่ง --</option>'); }
  }

  $('#PostType-add').on('change', function() { filterPositions($(this).val(), '#StPost-add'); });
  $('#PostType-edit').on('change', function() { filterPositions($(this).val(), '#StPost-edit'); });

  // ออกเลขรหัสเจ้าหน้าที่อัตโนมัติ
  function loadNextStID() {
    const $input = $('#StID-add').val('').attr('placeholder', 'กำลังออกเลข...');
    $.getJSON('api/staff_next_id.php')
      .done(res => {
        if (res.success) $input.val(res.next_id);
        else Swal.fire('ผิดพลาด', res.message || 'ออกเลขรหัสไม่สำเร็จ', 'error');
      })
      .fail(() => Swal.fire('ผิดพลาด', 'ออกเลขรหัสไม่สำเร็จ', 'error'))
      .always(() => $input.attr('placeholder', 'ออกเลขอัตโนมัติ'));
  }

  $('#addModal').on('show.bs.modal', loadNextStID);
  $('#btn-refresh-stid').on('click', loadNextStID);

  $('.btn-edit').on('click', function() {
    const data = $(this).data('staff');
    $('#e-old-stid').val(data.StID);
    $('#e-StID').val(data.StID);
    $('#e-StName').val(data.StName);
    $('#e-UserName').val(data.UserName);
    $('#e-TitleNo').val(data.TitleNo);
    $('#e-SexNo').val(data.SexNo);
    $('#e-DepNo').val(data.DepNo);
    $('#PostType-edit').val(data.PostType);
    filterPositions(data.PostType, '#StPost-edit', data.StPost);
    $('#img-preview-edit').attr('src', 'uploads/staff/' + (data.image || 'noimage.png'));
    // ตั้งค่าสวิตช์สถานะ (ค่าเริ่มต้น = เปิดใช้งาน หากไม่มีข้อมูล)
    const active = String(data.StatusNo ?? '1') !== '0';
    $('#e-Status-toggle').prop('checked', active);
    syncEditStatus();
    $('#editModal').modal('show');
  });

  // ซิงค์สวิตช์สถานะในฟอร์มแก้ไข -> hidden input + badge
  function syncEditStatus() {
    const active = $('#e-Status-toggle').is(':checked');
    $('#e-StatusNo').val(active ? '1' : '0');
    $('#e-Status-badge')
      .removeClass('badge-success badge-secondary')
      .addClass(active ? 'badge-success' : 'badge-secondary')
      .text(active ? 'เปิดใช้งาน' : 'ปิดใช้งาน');
  }
  $('#e-Status-toggle').on('change', syncEditStatus);

  $('#image-add, #image-edit').on('change', function() {
    const reader = new FileReader();
    const preview = $(this).attr('id') === 'image-add' ? '#img-preview-add' : '#img-preview-edit';
    reader.onload = e => $(preview).attr('src', e.target.result);
    if (this.files[0]) reader.readAsDataURL(this.files[0]);
    $(this).next('.custom-file-label').html(this.files[0].name);
  });

  $('#addStaffForm, #editStaffForm').on('submit', function(e) {
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
    $.ajax({ url: $(this).attr('action'), type: 'POST', data: new FormData(this), processData: false, contentType: false, dataType: 'json' })
    .done(res => { if(res.success) { Swal.fire('สำเร็จ', res.message, 'success').then(() => location.reload()); } else { Swal.fire('ผิดพลาด', res.message, 'error'); } })
    .fail(xhr => Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'เกิดข้อผิดพลาด', 'error'))
    .always(() => $btn.prop('disabled', false));
  });

  // สลับสถานะเปิด/ปิดใช้งานบัญชี
  $(document).on('change', '.status-toggle', function() {
    const $chk   = $(this);
    const stid   = $chk.data('stid');
    const name   = $chk.data('name');
    const status = $chk.is(':checked') ? '1' : '0';
    const wasChecked = !$chk.is(':checked'); // ค่าก่อนเปลี่ยน (ไว้ย้อนกลับถ้า error)

    $chk.prop('disabled', true);
    $.post('api/staff_status_toggle.php', { StID: stid, status: status }, null, 'json')
      .done(res => {
        if (res.success) {
          const $badge = $('.status-badge[data-stid="' + stid + '"]');
          $badge.removeClass('badge-success badge-secondary').addClass(res.BadgeClass).text(res.StatusName);
          const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
          Toast.fire({ icon: 'success', title: res.message });
        } else {
          $chk.prop('checked', wasChecked); // ย้อนกลับ
          Swal.fire('ไม่สำเร็จ', res.message, 'warning');
        }
      })
      .fail(xhr => {
        $chk.prop('checked', wasChecked); // ย้อนกลับ
        Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'อัปเดตสถานะไม่สำเร็จ', 'error');
      })
      .always(() => $chk.prop('disabled', false));
  });

  $('.btn-delete').on('click', function() {
    const id = $(this).data('id'), name = $(this).data('name');
    Swal.fire({ title: 'ยืนยันการลบ?', text: `คุณต้องการลบ "${name}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'ลบเลย' })
    .then(result => { if (result.isConfirmed) $.post('api/staff_delete.php', { StID: id }, res => { if(res.success) Swal.fire('สำเร็จ', res.message, 'success').then(() => location.reload()); else Swal.fire('ผิดพลาด', res.message, 'error'); }, 'json'); });
  });
});
</script>
</body>
</html>
