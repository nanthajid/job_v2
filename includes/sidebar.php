<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="index.php" class="brand-link">
    <img src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/img/AdminLTELogo.png"
         alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
    <span class="brand-text font-weight-light" style="font-size:13px;">จัดหางาน กทม. พื้นที่ 2</span>
  </a>

  <div class="sidebar">
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <i class="fas fa-user-circle fa-2x text-light opacity-5"></i>
      </div>
      <div class="info">
        <a href="#" class="d-block"><?= htmlspecialchars(isset($user) && $user['StName'] ? $user['StName'] : 'เจ้าหน้าที่') ?></a>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <li class="nav-item">
          <a href="index.php" class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="employee_search.php" class="nav-link <?= $current_page === 'employee_search' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-history"></i>
            <p>ค้นประวัติ ขึ้นทะเบียน/รายงานตัว</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="register.php" class="nav-link <?= $current_page === 'register' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user-plus"></i>
            <p>ขึ้นทะเบียน</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="selfrep.php" class="nav-link <?= $current_page === 'selfrep' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-check"></i>
            <p>รายงานตัวว่างงาน</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="register_management.php" class="nav-link <?= $current_page === 'register_management' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-list-alt"></i>
            <p>รายชื่อผู้ลงทะเบียน</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="report_unemployment.php" class="nav-link <?= $current_page === 'report_unemployment' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <p>รายชื่อผู้รายงานตัวว่างงาน</p>
          </a>
        </li>

        <li class="nav-header">รายงาน</li>

        <li class="nav-item">
          <a href="daily_report_print.php" class="nav-link <?= $current_page === 'daily_report_print' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-file-export"></i>
            <p>ออกรายงานขึ้นทะเบียนว่างงาน</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="daily_checkin_print.php" class="nav-link <?= $current_page === 'daily_checkin_print' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <p>ออกรายการรายงานตัวว่างงาน</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="list.php" class="nav-link <?= $current_page === 'list' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-chart-pie"></i>
            <p>รายงานสาเหตุออกจากงาน</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="gotjob.php" class="nav-link <?= $current_page === 'gotjob' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-briefcase"></i>
            <p>รายงานผลการได้งาน</p>
          </a>
        </li>

        <li class="nav-header">ระบบ</li>

        <li class="nav-item">
          <a href="settings.php" class="nav-link <?= $current_page === 'settings' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-cog"></i>
            <p>ตั้งค่าระบบ</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>
