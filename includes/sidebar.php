<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="index.php" class="brand-link">
    <img src="assets/images/logo.png"
         alt="Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
    <span class="brand-text font-weight-light" style="font-size:13px;">สจก. 2</span>
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
          <a href="alien_insured.php" class="nav-link <?= $current_page === 'alien_insured' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-passport"></i>
            <p>ขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าว</p>
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

        <li class="nav-item">
          <a href="job_placement.php" class="nav-link <?= $current_page === 'job_placement' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user-check"></i>
            <p>จัดการการบรรจุงาน</p>
          </a>
        </li>
<li class="nav-item">
          <a href="vacancy_notice.php" class="nav-link <?= $current_page === 'vacancy_notice' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-briefcase"></i>
            <p>เพิ่มตำแหน่งงานว่าง</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="vacancy_management.php" class="nav-link <?= $current_page === 'vacancy_management' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tasks"></i>
            <p>จัดการตำแหน่งงานว่าง</p>
          </a>
        </li>
        <?php
        $report_pages = ['daily_report_print', 'daily_checkin_print', 'list', 'gotjob', 'comparison_report', 'edu_comparison_report', 'pot_comparison_report', 'alien_insured_report_print'];
        $is_report_active = in_array($current_page, $report_pages);
        ?>
        <li class="nav-item <?= $is_report_active ? 'menu-open' : '' ?>">
          <a href="#" class="nav-link <?= $is_report_active ? 'active' : '' ?>">
            <i class="nav-icon fas fa-chart-bar"></i>
            <p>
              รายงานสรุปสถิติ
              <i class="right fas fa-angle-left"></i>
            </p>
          </a>
          <ul class="nav nav-treeview">
            <li class="nav-item">
              <a href="daily_report_print.php" class="nav-link <?= $current_page === 'daily_report_print' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>รายงานขึ้นทะเบียนว่างงาน</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="daily_checkin_print.php" class="nav-link <?= $current_page === 'daily_checkin_print' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>รายงานการรายงานตัวว่างงาน</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="list.php" class="nav-link <?= $current_page === 'list' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>รายงานสาเหตุออกจากงาน</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="gotjob.php" class="nav-link <?= $current_page === 'gotjob' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>รายงานผลการได้งาน</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="comparison_report.php" class="nav-link <?= $current_page === 'comparison_report' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>เปรียบเทียบ (เขต/เพศ)</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="edu_comparison_report.php" class="nav-link <?= $current_page === 'edu_comparison_report' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>เปรียบเทียบ (วุฒิการศึกษา)</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="pot_comparison_report.php" class="nav-link <?= $current_page === 'pot_comparison_report.php' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>เปรียบเทียบ (ตำแหน่งงาน)</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="alien_insured_report_print.php" class="nav-link <?= $current_page === 'alien_insured_report_print' ? 'active' : '' ?>">
                <i class="far fa-circle nav-icon"></i>
                <p>ผู้ประกันตนแรงงานต่างด้าว</p>
              </a>
            </li>
          </ul>
        </li>

        <?php if (isset($user['level']) && $user['level'] == 1): ?>
        <li class="nav-header">ระบบ</li>

        <li class="nav-item">
          <a href="staff_management.php" class="nav-link <?= $current_page === 'staff_management' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-users-cog"></i>
            <p>จัดการข้อมูลเจ้าหน้าที่</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="doc_running_management.php" class="nav-link <?= $current_page === 'doc_running_management' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-hashtag"></i>
            <p>จัดการเลขที่เอกสาร</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="data_import.php" class="nav-link <?= $current_page === 'data_import' ? 'active' : '' ?>">
            <i class="nav-icon fas fa-file-import"></i>
            <p>นำเข้าข้อมูล</p>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </nav>
  </div>
</aside>
