<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/doc_running_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = currentUser();

$empID   = trim($_POST['EmpID']   ?? '');
$titles  = trim($_POST['Titles']  ?? '');
$kNo     = trim($_POST['KNo']     ?? '');
$qNo     = trim($_POST['QNo']     ?? '');
$eqNo    = trim($_POST['EqNo']    ?? '');
$jNo     = trim($_POST['JNo']     ?? '');
$potNo   = trim($_POST['PotNo']   ?? '');
$sexNo   = trim($_POST['SexNo']   ?? '');
$rDate   = trim($_POST['RDate']   ?? '');
$phone   = trim($_POST['Phone']   ?? '');
$lineID  = trim($_POST['lineID']  ?? '');
$address = trim($_POST['Address'] ?? '');

$errors = [];

if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
}
if ($titles !== '' && (!ctype_digit($titles) || (int)$titles <= 0)) {
    $errors[] = 'คำนำหน้าไม่ถูกต้อง';
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
}
if (!ctype_digit($qNo) || (int)$qNo <= 0) {
    $errors[] = 'กรุณาเลือกสาเหตุที่ออกจากงาน';
}
if ($eqNo !== '' && (!ctype_digit($eqNo) || (int)$eqNo <= 0)) {
    $errors[] = 'วุฒิการศึกษาล่าสุดไม่ถูกต้อง';
}
if (!ctype_digit($jNo) || (int)$jNo <= 0) {
    $errors[] = 'กรุณาเลือกสถานะการได้งาน';
}
if ($potNo !== '' && (!ctype_digit($potNo) || (int)$potNo <= 0)) {
    $errors[] = 'ตำแหน่งล่าสุดไม่ถูกต้อง';
}
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rDate)) {
    $errors[] = 'รูปแบบวันที่ไม่ถูกต้อง';
} else {
    [$y, $m, $d] = explode('-', $rDate);
    if (!checkdate((int)$m, (int)$d, (int)$y)) {
        $errors[] = 'วันที่ไม่ถูกต้อง';
    }
}
if ($phone !== '' && !preg_match('/^[0-9\-\s]{6,15}$/', $phone)) {
    $errors[] = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

// ตรวจ FK
$fkChecks = [
    ['t' => 'kate', 'k' => 'KNo', 'v' => (int)$kNo,   'msg' => 'ไม่พบเขตที่เลือก'],
    ['t' => 'quit', 'k' => 'QNo', 'v' => (int)$qNo,   'msg' => 'ไม่พบสาเหตุที่เลือก'],
    ['t' => 'job',  'k' => 'JNo', 'v' => (int)$jNo,   'msg' => 'ไม่พบสถานะการได้งานที่เลือก'],
    ['t' => 'sex',  'k' => 'SexNo','v' => (int)$sexNo,'msg' => 'ไม่พบเพศที่เลือก'],
];

if ($eqNo !== '') {
    $fkChecks[] = ['t' => 'educational_qualification', 'k' => 'EqNo', 'v' => (int)$eqNo, 'msg' => 'ไม่พบวุฒิการศึกษาที่เลือก'];
}
if ($potNo !== '') {
    $fkChecks[] = ['t' => 'emp_position', 'k' => 'PotNo', 'v' => (int)$potNo, 'msg' => 'ไม่พบตำแหน่งที่เลือก'];
}

foreach ($fkChecks as $fk) {
    $st = $pdo->prepare("SELECT 1 FROM {$fk['t']} WHERE {$fk['k']} = :v");
    $st->execute([':v' => $fk['v']]);
    if (!$st->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $fk['msg']], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // UPSERT employee
    $exists = $pdo->prepare("SELECT 1 FROM employee WHERE EmpID = :id");
    $exists->execute([':id' => $empID]);

    if ($exists->fetchColumn()) {
        $upd = $pdo->prepare(
            "UPDATE employee
             SET Titles = COALESCE(NULLIF(:titles, ''), Titles),
                 KNo = :kno, SexNo = :sex, Phone = :phone, lineID = :line, Address = :addr
             WHERE EmpID = :id"
        );
        $upd->execute([
            ':titles' => $titles,
            ':kno'   => (int)$kNo,
            ':sex'   => (int)$sexNo,
            ':phone' => $phone   !== '' ? $phone   : null,
            ':line'  => $lineID  !== '' ? $lineID  : null,
            ':addr'  => $address !== '' ? $address : null,
            ':id'    => $empID,
        ]);
    } else {
        $ins = $pdo->prepare(
            "INSERT INTO employee (EmpID, Titles, KNo, SexNo, Phone, lineID, Address, SDate)
             VALUES (:id, :titles, :kno, :sex, :phone, :line, :addr, NOW())"
        );
        $ins->execute([
            ':id'    => $empID,
            ':titles' => $titles !== '' ? $titles : null,
            ':kno'   => (int)$kNo,
            ':sex'   => (int)$sexNo,
            ':phone' => $phone   !== '' ? $phone   : null,
            ':line'  => $lineID  !== '' ? $lineID  : null,
            ':addr'  => $address !== '' ? $address : null,
        ]);
    }

    // สร้าง DocID ตามรูปแบบที่ตั้งไว้ในตาราง doc_running (ประเภท 'selft_rep')
    // ใช้วันที่รายงานตัว (RDate) เป็นรอบของเลข ไม่ใช่วันที่ในเครื่อง เพื่อให้เลขอยู่ในชุดเดียว
    // กับวันที่ของเอกสารเสมอ — บันทึกย้อนหลังจึงได้เลขว่างของวันนั้น ไม่ไปกินเลขของวันนี้
    $running = nextDocRunning($pdo, 'selft_rep', $rDate);
    $docID   = $running['docid'];

    // INSERT selft_rep
    $rep = $pdo->prepare(
        "INSERT INTO selft_rep (DocID, RDate, SDate, QNo, EqNo, JNo, PotNo, KNo, SexNo, EmpID, StID)
         VALUES (:docid, :rdate, NOW(), :qno, :eqno, :jno, :potno, :kno, :sex, :id, :stid)"
    );
    $rep->execute([
        ':docid' => $docID,
        ':rdate' => $rDate,
        ':qno'   => (int)$qNo,
        ':eqno'  => $eqNo !== '' ? (int)$eqNo : null,
        ':jno'   => (int)$jNo,
        ':potno' => $potNo !== '' ? (int)$potNo : null,
        ':kno'   => (int)$kNo,
        ':sex'   => (int)$sexNo,
        ':id'    => $empID,
        ':stid'  => $user['StID'] ?? null,
    ]);
    $docNo = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการรายงานตัวเรียบร้อย',
        'data'    => ['DocNo' => $docNo, 'DocID' => $docID, 'EmpID' => $empID],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // ใช้ 422 เพื่อให้หน้าบ้านแยกแยะได้ว่าเป็น Business Logic Error ไม่ใช่ Server พัง
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
