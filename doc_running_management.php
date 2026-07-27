<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
requireAdmin();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/doc_running_helper.php';

$user = currentUser();
$pdo  = getDB();

$tokens = docRunningTokens();
$locked = docRunningLockedTypes();

// รายชื่อตารางในฐานข้อมูล ไว้ช่วยเลือกตารางต้นทางในฟอร์ม
$tables = $pdo->query(
    "SELECT TABLE_NAME FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
)->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>จัดการเลขที่เอกสาร | ระบบจัดการผู้ว่างงาน</title>

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
      --gov-border: #DEE2E6;
      --gov-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06);
    }
    body { font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif; background-color: var(--gov-bg); }
    h1, h2, h3, h4, .brand-text, .nav-link, .btn { font-family: 'Prompt', sans-serif; }
    .content-wrapper { background-color: var(--gov-bg); padding-bottom: 3rem; }
    .gov-card { background: var(--gov-white); border: none; border-radius: 12px; box-shadow: var(--gov-shadow); margin-bottom: 1.5rem; overflow: hidden; }
    .gov-card-header { border-bottom: 1px solid var(--gov-gray); padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; }
    .gov-card-title { font-size: 1.25rem; font-weight: 600; color: var(--gov-navy); margin: 0; }
    .table thead th { background-color: var(--gov-gray); color: var(--gov-navy); font-weight: 600; border-bottom: 2px solid var(--gov-border); font-size: .9rem; padding: .85rem; }
    .btn-gov-primary { background-color: var(--gov-royal); color: #fff; border-radius: 6px; font-weight: 500; }
    .btn-gov-primary:hover { background-color: var(--gov-navy); color: #fff; }
    code.docid { font-size: .95rem; color: var(--gov-navy); background: #EEF3FA; padding: .15rem .4rem; border-radius: 4px; }
    .token-btn { margin: 0 .25rem .25rem 0; }
    .preview-box { background: #F8FAFC; border: 1px dashed var(--gov-border); border-radius: 8px; padding: .75rem 1rem; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

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
        <a class="nav-link" href="logout.php" role="button"><i class="fas fa-sign-out-alt mr-1"></i>ออกจากระบบ</a>
      </li>
    </ul>
  </nav>

  <?php $current_page = 'doc_running_management'; include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0 text-bold"><i class="fas fa-hashtag mr-2 text-primary"></i>จัดการเลขที่เอกสาร</h1>
            <small class="text-muted">
              กำหนดรูปแบบเลขรันของแต่ละประเภทเอกสาร — เลขที่ออกจริงนับต่อจากเลขสูงสุดที่มีอยู่ในตารางข้อมูลจริงเสมอ
            </small>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="card gov-card">
          <div class="gov-card-header">
            <h3 class="gov-card-title">รูปแบบเลขรันเอกสาร</h3>
            <div class="d-flex align-items-center" style="gap:.5rem;">
              <label class="mb-0 text-muted small">ดูสถานะ ณ วันที่</label>
              <input type="date" id="statusDate" class="form-control form-control-sm" style="width:170px;" value="<?= date('Y-m-d') ?>">
              <button class="btn btn-outline-secondary btn-sm" id="btnReload" title="โหลดใหม่"><i class="fas fa-sync-alt"></i></button>
              <button class="btn btn-gov-primary btn-sm" id="btnAdd"><i class="fas fa-plus mr-1"></i>เพิ่มรูปแบบ</button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover mb-0" id="runTable">
                <thead>
                  <tr>
                    <th width="60">ลำดับ</th>
                    <th>ประเภทเอกสาร</th>
                    <th>ผู้ถือเอกสารล่าสุด</th>
                    <th class="text-center" width="110">รอบรีเซ็ต</th>
                    <th class="text-center" width="160">เลขล่าสุด</th>
                    <th width="180">เลขถัดไปที่จะออก</th>
                    <th class="text-center" width="90">สถานะ</th>
                    <th class="text-center" width="110">จัดการ</th>
                  </tr>
                </thead>
                <tbody id="runTableBody">
                  <tr><td colspan="8" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card gov-card">
          <div class="gov-card-header"><h3 class="gov-card-title">รหัสแทนค่าที่ใช้ได้ในรูปแบบ</h3></div>
          <div class="card-body pt-2">
            <div class="row">
              <?php foreach ($tokens as $token => $desc): ?>
                <div class="col-md-4 mb-2">
                  <code class="docid"><?= htmlspecialchars($token) ?></code>
                  <small class="text-muted d-block"><?= htmlspecialchars($desc) ?></small>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="alert alert-light border mb-0 mt-2 small">
              <i class="fas fa-info-circle text-primary mr-1"></i>
              รูปแบบต้องลงท้ายด้วย <code>{SEQ}</code> เสมอ เพราะระบบอ่านเลขลำดับจากส่วนท้ายของเลขเอกสาร
              และรอบรีเซ็ตที่เลือกต้องสอดคล้องกับรหัสวันที่ในรูปแบบ (เช่น รายวันต้องมี <code>{DATE}</code> หรือ <code>{DD}</code> ครบ)
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- Modal เพิ่ม/แก้ไข -->
  <div class="modal fade" id="runModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="runForm">
        <input type="hidden" name="RunID" id="f-RunID">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="runModalTitle"><i class="fas fa-plus mr-2"></i>เพิ่มรูปแบบเลขรัน</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>รหัสประเภทเอกสาร <span class="text-danger">*</span></label>
                  <input type="text" name="DocType" id="f-DocType" class="form-control" placeholder="เช่น register" required>
                  <small class="text-muted" id="f-DocType-hint">a-z 0-9 _ (โค้ดใช้อ้างอิง)</small>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label>ชื่อเอกสาร <span class="text-danger">*</span></label>
                  <input type="text" name="DocName" id="f-DocName" class="form-control" placeholder="เช่น ขึ้นทะเบียนว่างงาน" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>ตารางข้อมูลจริง <span class="text-danger">*</span></label>
                  <select name="SourceTable" id="f-SourceTable" class="form-control" required>
                    <option value="">-- เลือกตาราง --</option>
                    <?php foreach ($tables as $t): ?>
                      <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <small class="text-muted">ตารางที่ระบบใช้หาเลขสูงสุดของรอบ</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>คอลัมน์ที่เก็บเลขเอกสาร <span class="text-danger">*</span></label>
                  <input type="text" name="SourceColumn" id="f-SourceColumn" class="form-control" value="DocID" required>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>ข้อความนำหน้า (Prefix)</label>
                  <input type="text" name="Prefix" id="f-Prefix" class="form-control" placeholder="ปล่อยว่างได้">
                  <small class="text-muted">ใช้ผ่านรหัส {PREFIX}</small>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label>รูปแบบเลข <span class="text-danger">*</span></label>
                  <input type="text" name="Format" id="f-Format" class="form-control" value="{DATE}/{SEQ}" required>
                  <div class="mt-2">
                    <?php foreach (array_keys($tokens) as $token): ?>
                      <button type="button" class="btn btn-outline-secondary btn-sm token-btn" data-token="<?= htmlspecialchars($token) ?>"><?= htmlspecialchars($token) ?></button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>รอบการรีเซ็ต <span class="text-danger">*</span></label>
                  <select name="ResetCycle" id="f-ResetCycle" class="form-control" required>
                    <option value="daily">รายวัน</option>
                    <option value="monthly">รายเดือน</option>
                    <option value="yearly">รายปี</option>
                    <option value="never">ไม่รีเซ็ต (นับต่อเนื่อง)</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>จำนวนหลักของเลขลำดับ</label>
                  <input type="number" name="Padding" id="f-Padding" class="form-control" value="0" min="0" max="10">
                  <small class="text-muted">0 = ไม่เติมศูนย์นำหน้า</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>เลขเริ่มต้นของแต่ละรอบ</label>
                  <input type="number" name="StartSeq" id="f-StartSeq" class="form-control" value="1" min="1">
                </div>
              </div>
            </div>

            <div class="form-group">
              <input type="hidden" name="FillGap" id="f-FillGap" value="1">
              <div class="custom-control custom-switch d-inline-block align-middle">
                <input type="checkbox" class="custom-control-input" id="f-FillGap-toggle" checked>
                <label class="custom-control-label" for="f-FillGap-toggle">นำเลขที่ว่างจากการลบกลับมาใช้ใหม่</label>
              </div>
              <small class="text-muted d-block mt-1">
                เปิด: มีเลข 1, 3, 4 (เลข 2 ถูกลบ) → เอกสารถัดไปได้เลข <b>2</b> ·
                ปิด: ได้เลข <b>5</b> (นับต่อจากเลขสูงสุดเสมอ)
              </small>
            </div>

            <div class="form-group">
              <label>หมายเหตุ</label>
              <input type="text" name="Note" id="f-Note" class="form-control" placeholder="บันทึกช่วยจำ (ไม่บังคับ)">
            </div>

            <div class="form-group">
              <input type="hidden" name="Active" id="f-Active" value="1">
              <div class="custom-control custom-switch d-inline-block align-middle">
                <input type="checkbox" class="custom-control-input" id="f-Active-toggle" checked>
                <label class="custom-control-label" for="f-Active-toggle">เปิดใช้งาน</label>
              </div>
              <small class="text-muted ml-2">ปิดใช้งานแล้วหน้าบันทึกของเอกสารประเภทนี้จะออกเลขไม่ได้</small>
            </div>

            <div class="preview-box">
              <div class="small text-muted mb-1"><i class="fas fa-eye mr-1"></i>ตัวอย่างเลขที่จะได้</div>
              <div id="previewResult" class="font-weight-bold text-navy">—</div>
              <div id="previewMeta" class="small text-muted mt-1"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>บันทึก</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal แก้ไขเลขล่าสุด -->
  <div class="modal fade" id="seqModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form id="seqForm">
        <input type="hidden" name="RunID" id="s-RunID">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title"><i class="fas fa-pen mr-2"></i>แก้ไขเลขล่าสุด</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <div class="font-weight-bold text-navy" id="s-DocName">—</div>
              <small class="text-muted">รอบของวันที่ <span id="s-Date"></span> · ส่วนหน้าเลข <code class="docid" id="s-Head"></code></small>
            </div>

            <div class="row">
              <div class="col-6">
                <div class="preview-box text-center">
                  <div class="small text-muted">เลขสูงสุดที่ใช้จริง</div>
                  <div class="h5 mb-0 text-navy" id="s-RealMax">-</div>
                </div>
              </div>
              <div class="col-6">
                <div class="preview-box text-center">
                  <div class="small text-muted">ค่าที่ตั้งไว้เอง</div>
                  <div class="h5 mb-0 text-warning" id="s-Manual">ไม่ได้ตั้ง</div>
                </div>
              </div>
            </div>

            <div class="form-group mt-3 mb-2">
              <label>กำหนดเลขล่าสุดเป็น</label>
              <input type="number" name="LastSeq" id="s-LastSeq" class="form-control" min="0" required>
              <small class="text-muted">
                เอกสารถัดไปจะได้เลขนี้ + 1 — หากเลขนั้นถูกใช้ไปแล้ว ระบบจะเลื่อนไปเลขว่างถัดไปให้เองเพื่อไม่ให้เลขซ้ำ
              </small>
            </div>

            <div class="alert alert-light border small mb-0">
              <i class="fas fa-info-circle text-primary mr-1"></i>
              ค่าที่ตั้งไว้ใช้ได้เฉพาะรอบนี้ เมื่อขึ้นรอบใหม่ระบบจะกลับไปนับต่อจากข้อมูลจริงเองอัตโนมัติ
            </div>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-outline-danger" id="btnClearSeq">
              <i class="fas fa-undo mr-1"></i>ล้างค่าที่ตั้งไว้
            </button>
            <div>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>บันทึกเลขล่าสุด</button>
            </div>
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
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
  const lockedTypes = <?= json_encode(array_keys($locked), JSON_UNESCAPED_UNICODE) ?>;
  const esc = s => $('<div>').text(s ?? '').html();

  function loadList() {
    const date = $('#statusDate').val() || '';
    $('#runTableBody').html('<tr><td colspan="8" class="text-center text-muted py-4">กำลังโหลด...</td></tr>');

    $.getJSON('api/doc_running_list.php', { date: date })
      .done(res => {
        if (!res.success) { Swal.fire('ผิดพลาด', res.message, 'error'); return; }
        if (!res.data.length) {
          $('#runTableBody').html('<tr><td colspan="8" class="text-center text-muted py-4">ยังไม่มีรูปแบบเลขรัน</td></tr>');
          return;
        }

        const rows = res.data.map((r, i) => {
          const isLocked = lockedTypes.includes(r.DocType);
          const status = Number(r.Active) === 1
            ? '<span class="badge badge-success">เปิดใช้งาน</span>'
            : '<span class="badge badge-secondary">ปิดใช้งาน</span>';

          const next = r.Error
            ? `<span class="text-danger small"><i class="fas fa-exclamation-triangle mr-1"></i>${esc(r.Error)}</span>`
            : `<code class="docid">${esc(r.NextDocID)}</code>`;

          let last;
          if (r.Error) {
            last = '-';
          } else {
            const realPart = r.LastSeq > 0
              ? `${r.LastSeq} <small class="text-muted d-block">${esc(r.LastDocID)}</small>`
              : '<span class="text-muted">ยังไม่มี</span>';
            const manualPart = r.ManualSeq !== null && r.ManualSeq !== undefined
              ? `<span class="badge badge-warning mt-1" title="ผู้ดูแลกำหนดเลขล่าสุดไว้เองสำหรับรอบนี้"><i class="fas fa-user-edit mr-1"></i>ตั้งไว้ ${r.ManualSeq}</span>`
              : '';
            last = `${realPart}${manualPart}
              <button class="btn btn-link btn-sm p-0 mt-1 d-block mx-auto btn-seq"
                      data-row='${JSON.stringify(r).replace(/'/g, "&#39;")}' title="แก้ไขเลขล่าสุด">
                <i class="fas fa-pen mr-1"></i>แก้เลขล่าสุด
              </button>`;
          }

          // เจ้าของเอกสารเลขล่าสุดของรอบนั้น (ตารางที่ไม่ได้ผูกกับทะเบียนผู้ว่างงานจะไม่มีข้อมูลส่วนนี้)
          const owner = r.LastEmpID
            ? `<div class="font-weight-bold text-navy">${esc(r.LastEmpName || '-')}</div>
               <small class="text-muted"><i class="fas fa-id-card mr-1"></i>${esc(r.LastEmpID)}</small>`
            : '<span class="text-muted">-</span>';

          const fillBadge = Number(r.FillGap) === 1
            ? '<span class="badge badge-info ml-1" title="เลขที่ว่างจากการลบจะถูกนำกลับมาใช้ก่อน"><i class="fas fa-recycle mr-1"></i>เติมช่องว่าง</span>'
            : '<span class="badge badge-light border ml-1" title="นับต่อจากเลขสูงสุดเสมอ">ไม่เติมช่องว่าง</span>';

          return `<tr>
            <td>${i + 1}</td>
            <td>
              <div class="font-weight-bold text-navy">${esc(r.DocName)}</div>
              <small class="text-muted"><code>${esc(r.DocType)}</code> → ${esc(r.SourceTable)}.${esc(r.SourceColumn)}</small>
              <div class="mt-1">
                ${isLocked ? '<span class="badge badge-light border"><i class="fas fa-lock mr-1"></i>ระบบใช้อยู่</span>' : ''}${fillBadge}
              </div>
            </td>
            <td>${owner}</td>
            <td class="text-center">${esc(r.CycleName)}</td>
            <td class="text-center">${last}</td>
            <td>${next}</td>
            <td class="text-center">${status}</td>
            <td class="text-center">
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary btn-edit" data-row='${JSON.stringify(r).replace(/'/g, "&#39;")}' title="แก้ไข"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-id="${r.RunID}" data-name="${esc(r.DocName)}" ${isLocked ? 'disabled title="ระบบใช้อยู่ ลบไม่ได้"' : 'title="ลบ"'}><i class="fas fa-trash-alt"></i></button>
              </div>
            </td>
          </tr>`;
        }).join('');

        $('#runTableBody').html(rows);
      })
      .fail(xhr => {
        $('#runTableBody').html('<tr><td colspan="8" class="text-center text-danger py-4">โหลดข้อมูลไม่สำเร็จ</td></tr>');
        Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'โหลดข้อมูลไม่สำเร็จ', 'error');
      });
  }

  // ---- ตัวอย่างเลขแบบสด ----
  let previewTimer = null;
  function refreshPreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(() => {
      $.post('api/doc_running_preview.php', {
        Prefix:   $('#f-Prefix').val(),
        Format:   $('#f-Format').val(),
        Padding:  $('#f-Padding').val(),
        StartSeq: $('#f-StartSeq').val(),
        date:     $('#statusDate').val()
      }, null, 'json')
      .done(res => {
        if (res.success) {
          $('#previewResult').html(res.samples.map(s => `<code class="docid mr-2">${esc(s)}</code>`).join(''));
          const cycleSel = $('#f-ResetCycle').val();
          const warn = cycleSel !== res.cycle
            ? ` <span class="text-danger">— รูปแบบนี้รีเซ็ต${esc(res.cycleName)} ไม่ตรงกับรอบที่เลือก</span>`
            : '';
          $('#previewMeta').html(`ความยาว ${res.length} ตัวอักษร · รีเซ็ต${esc(res.cycleName)}${warn}`);
        } else {
          $('#previewResult').html(`<span class="text-danger">${esc(res.message)}</span>`);
          $('#previewMeta').text('');
        }
      })
      .fail(xhr => {
        $('#previewResult').html(`<span class="text-danger">${esc(xhr.responseJSON?.message || 'รูปแบบไม่ถูกต้อง')}</span>`);
        $('#previewMeta').text('');
      });
    }, 250);
  }

  $('#f-Prefix, #f-Format, #f-Padding, #f-StartSeq').on('input', refreshPreview);
  $('#f-ResetCycle').on('change', refreshPreview);

  $('.token-btn').on('click', function () {
    const $input = $('#f-Format');
    const token  = $(this).data('token');
    const el     = $input[0];
    const pos    = el.selectionStart ?? $input.val().length;
    $input.val($input.val().slice(0, pos) + token + $input.val().slice(pos));
    el.focus();
    el.selectionStart = el.selectionEnd = pos + token.length;
    refreshPreview();
  });

  $('#f-Active-toggle').on('change', function () {
    $('#f-Active').val($(this).is(':checked') ? '1' : '0');
  });

  $('#f-FillGap-toggle').on('change', function () {
    $('#f-FillGap').val($(this).is(':checked') ? '1' : '0');
  });

  // ---- เพิ่ม ----
  $('#btnAdd').on('click', function () {
    $('#runForm')[0].reset();
    $('#f-RunID').val('');
    $('#f-Format').val('{DATE}/{SEQ}');
    $('#f-SourceColumn').val('DocID');
    $('#f-Padding').val(0);
    $('#f-StartSeq').val(1);
    $('#f-Active').val('1');
    $('#f-Active-toggle').prop('checked', true);
    $('#f-FillGap').val('1');
    $('#f-FillGap-toggle').prop('checked', true);
    $('#f-DocType').prop('readonly', false);
    $('#f-DocType-hint').text('a-z 0-9 _ (โค้ดใช้อ้างอิง)').removeClass('text-warning').addClass('text-muted');
    $('#runModalTitle').html('<i class="fas fa-plus mr-2"></i>เพิ่มรูปแบบเลขรัน');
    refreshPreview();
    $('#runModal').modal('show');
  });

  // ---- แก้ไข ----
  $(document).on('click', '.btn-edit', function () {
    const r = $(this).data('row');
    $('#f-RunID').val(r.RunID);
    $('#f-DocType').val(r.DocType);
    $('#f-DocName').val(r.DocName);
    $('#f-SourceTable').val(r.SourceTable);
    $('#f-SourceColumn').val(r.SourceColumn);
    $('#f-Prefix').val(r.Prefix);
    $('#f-Format').val(r.Format);
    $('#f-ResetCycle').val(r.ResetCycle);
    $('#f-Padding').val(r.Padding);
    $('#f-StartSeq').val(r.StartSeq);
    $('#f-Note').val(r.Note || '');
    $('#f-Active').val(String(r.Active) === '1' ? '1' : '0');
    $('#f-Active-toggle').prop('checked', String(r.Active) === '1');
    $('#f-FillGap').val(String(r.FillGap) === '1' ? '1' : '0');
    $('#f-FillGap-toggle').prop('checked', String(r.FillGap) === '1');

    const isLocked = lockedTypes.includes(r.DocType);
    $('#f-DocType').prop('readonly', isLocked);
    $('#f-DocType-hint')
      .text(isLocked ? 'รหัสนี้โค้ดเรียกใช้อยู่ จึงเปลี่ยนไม่ได้' : 'a-z 0-9 _ (โค้ดใช้อ้างอิง)')
      .toggleClass('text-warning', isLocked)
      .toggleClass('text-muted', !isLocked);

    $('#runModalTitle').html('<i class="fas fa-edit mr-2"></i>แก้ไขรูปแบบเลขรัน');
    refreshPreview();
    $('#runModal').modal('show');
  });

  // ---- บันทึก ----
  $('#runForm').on('submit', function (e) {
    e.preventDefault();
    const isEdit = $('#f-RunID').val() !== '';
    const url = isEdit ? 'api/doc_running_update.php' : 'api/doc_running_save.php';
    const $btn = $(this).find('button[type="submit"]').prop('disabled', true);

    $.post(url, $(this).serialize(), null, 'json')
      .done(res => {
        if (!res.success) { Swal.fire('ผิดพลาด', res.message, 'error'); return; }
        $('#runModal').modal('hide');
        if (res.warning) {
          Swal.fire({ icon: 'warning', title: res.message, text: res.warning }).then(loadList);
        } else {
          Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }).then(loadList);
        }
      })
      .fail(xhr => Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'บันทึกไม่สำเร็จ', 'error'))
      .always(() => $btn.prop('disabled', false));
  });

  // ---- แก้ไขเลขล่าสุด ----
  $(document).on('click', '.btn-seq', function () {
    const r = $(this).data('row');
    const hasManual = r.ManualSeq !== null && r.ManualSeq !== undefined;

    $('#s-RunID').val(r.RunID);
    $('#s-DocName').text(r.DocName);
    $('#s-Date').text($('#statusDate').val());
    $('#s-Head').text(r.Prefix_Preview || '');
    $('#s-RealMax').text(r.LastSeq ?? 0);
    $('#s-Manual').text(hasManual ? r.ManualSeq : 'ไม่ได้ตั้ง');
    $('#s-LastSeq').val(hasManual ? r.ManualSeq : (r.LastSeq ?? 0));
    $('#btnClearSeq').prop('disabled', !hasManual);
    $('#seqModal').modal('show');
  });

  function afterSeqSaved(res) {
    $('#seqModal').modal('hide');
    const detail = res.NextDocID ? `เอกสารถัดไปจะได้เลข ${res.NextDocID}` : '';
    if (res.warning) {
      Swal.fire({ icon: 'warning', title: res.message, text: res.warning }).then(loadList);
    } else {
      Swal.fire({ icon: 'success', title: res.message, text: detail, timer: 2200, showConfirmButton: false }).then(loadList);
    }
  }

  $('#seqForm').on('submit', function (e) {
    e.preventDefault();
    const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
    $.post('api/doc_running_seq_update.php', {
      RunID:   $('#s-RunID').val(),
      LastSeq: $('#s-LastSeq').val(),
      date:    $('#statusDate').val()
    }, null, 'json')
      .done(res => { if (res.success) afterSeqSaved(res); else Swal.fire('ผิดพลาด', res.message, 'error'); })
      .fail(xhr => Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'บันทึกไม่สำเร็จ', 'error'))
      .always(() => $btn.prop('disabled', false));
  });

  $('#btnClearSeq').on('click', function () {
    const $btn = $(this).prop('disabled', true);
    $.post('api/doc_running_seq_update.php', {
      RunID: $('#s-RunID').val(), clear: '1', date: $('#statusDate').val()
    }, null, 'json')
      .done(res => { if (res.success) afterSeqSaved(res); else Swal.fire('ผิดพลาด', res.message, 'error'); })
      .fail(xhr => Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'ล้างค่าไม่สำเร็จ', 'error'))
      .always(() => $btn.prop('disabled', false));
  });

  // ---- ลบ ----
  $(document).on('click', '.btn-del', function () {
    const id = $(this).data('id'), name = $(this).data('name');
    Swal.fire({
      title: 'ยืนยันการลบ?',
      html: `ลบรูปแบบเลขรัน "<b>${esc(name)}</b>"<br><small class="text-muted">เอกสารเดิมที่ออกเลขไปแล้วจะไม่ถูกแก้ไข</small>`,
      icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
      confirmButtonText: 'ลบเลย', cancelButtonText: 'ยกเลิก'
    }).then(result => {
      if (!result.isConfirmed) return;
      $.post('api/doc_running_delete.php', { RunID: id }, null, 'json')
        .done(res => {
          if (res.success) Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false }).then(loadList);
          else Swal.fire('ผิดพลาด', res.message, 'error');
        })
        .fail(xhr => Swal.fire('ผิดพลาด', xhr.responseJSON?.message || 'ลบไม่สำเร็จ', 'error'));
    });
  });

  $('#btnReload, #statusDate').on('click change', loadList);
  loadList();
});
</script>
</body>
</html>
