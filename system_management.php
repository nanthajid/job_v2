<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/config/database.php';

$user = currentUser();
$pdo  = getDB();

// ดึงข้อมูลเจ้าหน้าที่
$sql = "SELECT s.*, t.Title, p.StPostName, pt.PostTypeName, pt.PostType, d.DepName
        FROM staff s
        LEFT JOIN titles t ON s.TitleNo = t.TitleNo
        LEFT JOIN position p ON s.StPost = p.StPost
        LEFT JOIN post_type pt ON p.PostType = pt.PostType
        LEFT JOIN department d ON s.DepNo = d.DepNo
        ORDER BY s.StID ASC";

$staffList = [];
try {
    $staffList = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    // Fallback หรือแจ้งเตือนกรณีโครงสร้างฐานข้อมูลไม่ตรง
    // ในที่นี้จะพยายามดึงข้อมูลเท่าที่ได้ถ้าเกิด error จาก join ที่ไม่แน่นอน
    $error = $e->getMessage();
}

// ดึงข้อมูลสำหรับ Dropdown ในฟอร์มเพิ่ม/แก้ไข
$titlesRows = $pdo->query("SELECT TitleNo, Title FROM titles ORDER BY TitleNo")->fetchAll();
$sexRows    = $pdo->query("SELECT SexNo, SexName FROM sex ORDER BY SexNo")->fetchAll();
$posRows    = $pdo->query("SELECT StPost, StPostName, PostType FROM position ORDER BY StPostName")->fetchAll();
$depRows    = $pdo->query("SELECT DepNo, DepName FROM department ORDER BY DepName")->fetchAll();
$ptRows     = $pdo->query("SELECT PostType, PostTypeName FROM post_type ORDER BY PostType")->fetchAll();

/**
 * ฟังก์ชันคืนค่าสี Badge ตามประเภทพนักงาน
 */
function getPostTypeBadgeClass($type) {
    // กำจัดช่องว่างที่อาจติดมา
    $type = trim($type);
    switch ($type) {
        case '1': // ข้าราชการ
        case 'ข้าราชการ':
            return 'badge-primary'; // สีน้ำเงิน
        case '2': // พนักงานราชการ
        case 'พนักงานราชการ':
            return 'badge-success'; // สีเขียว
        case '3': // ลูกจ้างชั่วคราว
        case 'ลูกจ้างชั่วคราว':
            return 'badge-warning'; // สีเหลือง
        case '4': // พนักงานจ้างเหมาบริการ
        case 'พนักงานจ้างเหมาบริการ':
            return 'badge-danger'; // สีแดง
        default:
            return 'badge-light border';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ตั้งค่าระบบ | ระบบจัดการผู้ว่างงาน</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

    body {
      font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif;
      background-color: var(--gov-bg);
      color: var(--gov-text-dark);
    }

    h1, h2, h3, h4, .brand-text, .nav-link, .btn {
      font-family: 'Prompt', sans-serif;
    }

    .content-wrapper {
      background-color: var(--gov-bg);
      padding-bottom: 3rem;
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
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .gov-card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gov-navy);
      margin: 0;
    }

    .nav-tabs-gov {
      border-bottom: 2px solid var(--gov-gray);
      padding: 0 1.5rem;
      background: #fff;
    }
    .nav-tabs-gov .nav-link {
      border: none;
      color: var(--gov-text-muted);
      padding: 1rem 1.5rem;
      font-weight: 500;
      position: relative;
    }
    .nav-tabs-gov .nav-link.active {
      color: var(--gov-navy);
      background: transparent;
    }
    .nav-tabs-gov .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 3px;
      background-color: var(--gov-royal);
    }

    .table thead th {
      background-color: var(--gov-gray);
      color: var(--gov-navy);
      font-weight: 600;
      border-bottom: 2px solid var(--gov-border);
      font-size: 0.9rem;
      padding: 1rem;
    }
    
    .btn-gov-primary {
      background-color: var(--gov-royal);
      color: white;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      font-weight: 500;
      transition: all 0.2s;
    }
    .btn-gov-primary:hover {
      background-color: var(--gov-navy);
      color: white;
      transform: translateY(-1px);
    }
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
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-bold"><i class="fas fa-cog mr-2 text-primary"></i>ตั้งค่าระบบ</h1>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs nav-tabs-gov mb-4" id="systemTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="staff-tab" data-toggle="tab" href="#staff-panel" role="tab">
              <i class="fas fa-users-cog mr-1"></i>จัดการเจ้าหน้าที่
            </a>
          </li>
          <!-- สามารถเพิ่ม Tab อื่นๆ ในอนาคตได้ที่นี่ -->
        </ul>

        <div class="tab-content" id="systemTabsContent">
          <!-- Tab จัดการเจ้าหน้าที่ -->
          <div class="tab-pane fade show active" id="staff-panel" role="tabpanel">
            <div class="card gov-card">
              <div class="gov-card-header">
                <h3 class="gov-card-title">ข้อมูลเจ้าหน้าที่</h3>
                <button class="btn btn-gov-primary btn-sm" data-toggle="modal" data-target="#addModal">
                  <i class="fas fa-user-plus mr-1"></i>เพิ่มเจ้าหน้าที่
                </button>
              </div>
              <div class="card-body">
                <?php if (isset($error)): ?>
                  <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    พบข้อผิดพลาดในการดึงข้อมูล: <?= htmlspecialchars($error) ?>
                  </div>
                <?php endif; ?>

                <div class="table-responsive">
                  <table class="table table-hover" id="staffTable">
                    <thead>
                      <tr>
                        <th width="80">ลำดับ</th>
                        <th>ชื่อเจ้าหน้าที่</th>
                        <th>ตำแหน่ง</th>
                        <th>ฝ่าย</th>
                        <th width="180" class="text-center">จัดการ</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($staffList)): ?>
                        <tr>
                          <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลเจ้าหน้าที่</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($staffList as $index => $row): ?>
                          <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                              <div class="font-weight-bold text-primary">
                                <?= htmlspecialchars(($row['Title'] ?? '') . ' ' . ($row['StName'] ?? '')) ?>
                              </div>
                            </td>
                            <td>
                              <div><?= htmlspecialchars($row['StPostName'] ?? '-') ?></div>
                              <small class="badge <?= getPostTypeBadgeClass($row['PostType'] ?? $row['PostTypeName'] ?? '') ?>"><?= htmlspecialchars($row['PostTypeName'] ?? '-') ?></small>
                            </td>
                            <td>
                              <span class="text-muted"><i class="fas fa-building mr-1 small"></i><?= htmlspecialchars($row['DepName'] ?? '-') ?></span>
                            </td>
                            <td class="text-center">
                              <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-info btn-view" 
                                        title="ดูรายละเอียด"
                                        data-staff='<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>'>
                                  <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit" 
                                        title="แก้ไข"
                                        data-staff='<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>'>
                                  <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete" 
                                        title="ลบ"
                                        data-id="<?= htmlspecialchars($row['StID']) ?>"
                                        data-name="<?= htmlspecialchars(($row['Title'] ?? '') . ' ' . ($row['StName'] ?? '')) ?>">
                                  <i class="fas fa-trash-alt"></i>
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

  <!-- View Modal -->
  <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="viewModalLabel"><i class="fas fa-user-circle mr-2"></i>รายละเอียดเจ้าหน้าที่</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-4 text-center border-right">
              <div class="p-1 bg-light d-inline-block shadow-sm mb-3">
                <img id="v-img" src="uploads/staff/noimage.png" 
                     onerror="this.onerror=null;this.src='uploads/staff/noimage.png';" 
                     class="img-fluid" style="max-width: 200px; height: 250px; object-fit: cover;">
              </div>
              <h4 class="mt-2 text-bold" id="v-fullname">-</h4>
              <span class="badge badge-pill p-2" id="v-badge">-</span>
            </div>
            <div class="col-md-8">
              <div class="table-responsive">
                <table class="table table-sm table-borderless">
                  <tr>
                    <th width="150" class="text-muted">รหัสเจ้าหน้าที่:</th>
                    <td id="v-stid" class="text-bold">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">ตำแหน่ง:</th>
                    <td id="v-postname">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">ประเภท:</th>
                    <td id="v-posttypename">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">ฝ่าย:</th>
                    <td id="v-depname">-</td>
                  </tr>
                  <tr>
                    <th class="text-muted">เพศ:</th>
                    <td id="v-sex">-</td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิดหน้าต่าง</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Modal -->
  <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="addStaffForm" method="post" action="api/staff_save.php" enctype="multipart/form-data">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="addModalLabel"><i class="fas fa-user-plus mr-2"></i>เพิ่มเจ้าหน้าที่ใหม่</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 text-center border-right">
                <div class="form-group">
                  <label>รูปถ่ายเจ้าหน้าที่</label>
                  <div class="mt-2 mb-3">
                    <img id="img-preview-add" src="uploads/staff/noimage.png" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                  </div>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                    <label class="custom-file-label text-left" for="image">เลือกไฟล์</label>
                  </div>
                  <small class="text-muted mt-2 d-block">รองรับ JPG, PNG (ขนาดไม่เกิน 2MB)</small>
                </div>
              </div>
              <div class="col-md-8">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="TitleNo">คำนำหน้าชื่อ</label>
                      <select name="TitleNo" id="TitleNo" class="form-control" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($titlesRows as $t): ?>
                          <option value="<?= $t['TitleNo'] ?>"><?= htmlspecialchars($t['Title']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="StName">ชื่อ-นามสกุล</label>
                      <input type="text" name="StName" id="StName" class="form-control" placeholder="ระบุชื่อและนามสกุล" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="SexNo">เพศ</label>
                      <select name="SexNo" id="SexNo" class="form-control" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($sexRows as $s): ?>
                          <option value="<?= $s['SexNo'] ?>"><?= htmlspecialchars($s['SexName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="StID">รหัสเจ้าหน้าที่</label>
                      <input type="text" name="StID" id="StID" class="form-control" placeholder="ระบุรหัสเจ้าหน้าที่" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="PostType">ประเภทพนักงาน</label>
                      <select id="PostType" class="form-control" required>
                        <option value="">-- เลือกประเภท --</option>
                        <?php foreach ($ptRows as $pt): ?>
                          <option value="<?= $pt['PostType'] ?>"><?= htmlspecialchars($pt['PostTypeName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="StPost">ตำแหน่ง</label>
                      <select name="StPost" id="StPost" class="form-control" required disabled>
                        <option value="">-- กรุณาเลือกประเภทพนักงานก่อน --</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="DepNo">ฝ่าย</label>
                      <select name="DepNo" id="DepNo" class="form-control" required>
                        <option value="">-- เลือก --</option>
                        <?php foreach ($depRows as $d): ?>
                          <option value="<?= $d['DepNo'] ?>"><?= htmlspecialchars($d['DepName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>บันทึกข้อมูล</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="editStaffForm" method="post" action="api/staff_update.php" enctype="multipart/form-data">
        <input type="hidden" name="old_StID" id="e-old-stid">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit mr-2"></i>แก้ไขข้อมูลเจ้าหน้าที่</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4 text-center border-right">
                <div class="form-group">
                  <label>รูปถ่ายเจ้าหน้าที่</label>
                  <div class="mt-2 mb-3">
                    <img id="img-preview-edit" src="uploads/staff/noimage.png" onerror="this.onerror=null;this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23eee%22%2F%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20dominant-baseline%3D%22middle%22%20text-anchor%3D%22middle%22%20font-family%3Asans-serif%20font-size%3D%2216%22%20fill%3D%22%23999%22%3ENo%20Image%3C%2Ftext%3E%3C%2Fsvg%3E';" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                  </div>
                  <div class="custom-file">
                    <input type="file" class="custom-file-input" id="e-image" name="image" accept="image/*">
                    <label class="custom-file-label text-left" for="e-image">เลือกไฟล์ใหม่</label>
                  </div>
                  <small class="text-muted mt-2 d-block">เลือกไฟล์เมื่อต้องการเปลี่ยนรูปใหม่</small>
                </div>
              </div>
              <div class="col-md-8">
                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="e-TitleNo">คำนำหน้าชื่อ</label>
                      <select name="TitleNo" id="e-TitleNo" class="form-control" required>
                        <?php foreach ($titlesRows as $t): ?>
                          <option value="<?= $t['TitleNo'] ?>"><?= htmlspecialchars($t['Title']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="e-StName">ชื่อ-นามสกุล</label>
                      <input type="text" name="StName" id="e-StName" class="form-control" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="e-SexNo">เพศ</label>
                      <select name="SexNo" id="e-SexNo" class="form-control" required>
                        <?php foreach ($sexRows as $s): ?>
                          <option value="<?= $s['SexNo'] ?>"><?= htmlspecialchars($s['SexName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="e-StID">รหัสเจ้าหน้าที่</label>
                      <input type="text" name="StID" id="e-StID" class="form-control" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="e-PostType">ประเภทพนักงาน</label>
                      <select id="e-PostType" class="form-control" required>
                        <option value="">-- เลือกประเภท --</option>
                        <?php foreach ($ptRows as $pt): ?>
                          <option value="<?= $pt['PostType'] ?>"><?= htmlspecialchars($pt['PostTypeName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <div class="form-group">
                      <label for="e-StPost">ตำแหน่ง</label>
                      <select name="StPost" id="e-StPost" class="form-control" required>
                        <!-- Will be populated via JS -->
                      </select>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label for="e-DepNo">ฝ่าย</label>
                      <select name="DepNo" id="e-DepNo" class="form-control" required>
                        <?php foreach ($depRows as $d): ?>
                          <option value="<?= $d['DepNo'] ?>"><?= htmlspecialchars($d['DepName']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
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
    <div class="float-right d-none d-sm-inline">
      ระบบจัดการผู้ว่างงาน v2.0
    </div>
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
  // ข้อมูลตำแหน่งสำหรับใช้ใน JS Filtering
  const allPositions = <?= json_encode($posRows, JSON_UNESCAPED_UNICODE) ?>;

  $('#staffTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/th.json"
    },
    "responsive": true,
    "autoWidth": false,
  });

  // เมื่อเปลี่ยนประเภทพนักงาน (Add Modal)
  $('#PostType').on('change', function() {
    const selectedType = $(this).val();
    const $stPost = $('#StPost');
    
    // ล้างค่าเก่า
    $stPost.empty().prop('disabled', true);

    if (selectedType) {
      // กรองตำแหน่งตามประเภทที่เลือก
      const filtered = allPositions.filter(p => String(p.PostType) === String(selectedType));
      
      if (filtered.length > 0) {
        $stPost.append('<option value="">-- เลือกตำแหน่ง --</option>');
        filtered.forEach(p => {
          $stPost.append(`<option value="${p.StPost}">${p.StPostName}</option>`);
        });
        $stPost.prop('disabled', false);
      } else {
        $stPost.append('<option value="">-- ไม่พบตำแหน่งในประเภทนี้ --</option>');
      }
    } else {
      $stPost.append('<option value="">-- กรุณาเลือกประเภทพนักงานก่อน --</option>');
    }
  });

  // Handle View Button Click
  $('.btn-view').on('click', function() {
    const data = $(this).data('staff');
    
    // Populate Modal
    $('#v-fullname').text((data.Title || '') + ' ' + (data.StName || ''));
    $('#v-stid').text(data.StID || '-');
    $('#v-postname').text(data.StPostName || '-');
    $('#v-posttypename').text(data.PostTypeName || '-');
    $('#v-depname').text(data.DepName || '-');

    // Image logic
    if (data.image) {
      $('#v-img').attr('src', 'uploads/staff/' + data.image);
    } else {
      $('#v-img').attr('src', 'uploads/staff/noimage.png');
    }
    
    // Sex mapping
    const sexMap = {'1': 'ชาย', '2': 'หญิง'};
    $('#v-sex').text(sexMap[data.SexNo] || '-');

    // Badge Class
    const badgeClass = getBadgeClass(data.PostType);
    $('#v-badge').text(data.PostTypeName || '-').removeClass().addClass('badge badge-pill p-2 ' + badgeClass);

    $('#viewModal').modal('show');
  });

  // Handle Edit Button Click
  $('.btn-edit').on('click', function() {
    const data = $(this).data('staff');
    
    // Populate Basic Fields
    $('#e-old-stid').val(data.StID);
    $('#e-StID').val(data.StID);
    $('#e-StName').val(data.StName);
    $('#e-TitleNo').val(data.TitleNo);
    $('#e-SexNo').val(data.SexNo);
    $('#e-DepNo').val(data.DepNo);
    $('#e-PostType').val(data.PostType);

    // Image logic for Edit Preview
    if (data.image) {
      $('#img-preview-edit').attr('src', 'uploads/staff/' + data.image);
    } else {
      $('#img-preview-edit').attr('src', 'uploads/staff/noimage.png');
    }
    // Reset file input label
    $('#e-image').next('.custom-file-label').html('เลือกไฟล์ใหม่');

    // Filter and Populate Position Dropdown
    const $stPost = $('#e-StPost');
    $stPost.empty();
    
    const filtered = allPositions.filter(p => String(p.PostType) === String(data.PostType));
    filtered.forEach(p => {
      $stPost.append(`<option value="${p.StPost}">${p.StPostName}</option>`);
    });
    
    // Set current position
    $stPost.val(data.StPost);

    $('#editModal').modal('show');
  });

  // Image Preview for Edit Modal
  $('#e-image').on('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        $('#img-preview-edit').attr('src', e.target.result);
      }
      reader.readAsDataURL(file);
      $(this).next('.custom-file-label').html(file.name);
    }
  });

  // Handle Edit Staff Form Submission
  $('#editStaffForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');
    const originalHtml = $btn.html();

    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>กำลังบันทึก...');

    const formData = new FormData(this);

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json'
    })
    .done(function(res) {
      if (res.success) {
        Swal.fire({
          icon: 'success',
          title: 'สำเร็จ',
          text: res.message,
          confirmButtonText: 'ตกลง'
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'ข้อผิดพลาด',
          text: res.message || 'เกิดข้อผิดพลาด',
          confirmButtonText: 'ตกลง'
        });
      }
    })
    .fail(function(xhr) {
      const res = xhr.responseJSON;
      Swal.fire({
        icon: 'error',
        title: 'ข้อผิดพลาด',
        text: res && res.message ? res.message : 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
        confirmButtonText: 'ตกลง'
      });
    })
    .always(function() {
      $btn.prop('disabled', false).html(originalHtml);
    });
  });

  // Handle Delete Staff
  $('.btn-delete').on('click', function() {
    const id = $(this).data('id');
    const name = $(this).data('name');

    Swal.fire({
      title: 'ยืนยันการลบ?',
      text: 'คุณต้องการลบข้อมูลเจ้าหน้าที่ "' + name + '" ใช่หรือไม่?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'ใช่, ลบเลย!',
      cancelButtonText: 'ยกเลิก'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post('api/staff_delete.php', { StID: id }, function(res) {
          if (res.success) {
            Swal.fire({
              icon: 'success',
              title: 'ลบสำเร็จ',
              text: res.message,
              confirmButtonText: 'ตกลง'
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'ข้อผิดพลาด',
              text: res.message || 'ลบไม่สำเร็จ',
              confirmButtonText: 'ตกลง'
            });
          }
        }, 'json')
        .fail(function() {
          Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
            confirmButtonText: 'ตกลง'
          });
        });
      }
    });
  });

  // Helper function for JS badge classes
  function getBadgeClass(type) {
    switch (String(type)) {
      case '1': return 'badge-primary';
      case '2': return 'badge-success';
      case '3': return 'badge-warning';
      case '4': return 'badge-danger';
      default: return 'badge-light border';
    }
  }
});
</script>
</body>
</html>
