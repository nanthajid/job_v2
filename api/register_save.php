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

$empID   = trim($_POST['EmpID']   ?? '');
$titles  = trim($_POST['Titles']  ?? '');
$empName = trim($_POST['EmpName'] ?? '');
$kNo     = trim($_POST['KNo']     ?? '');
$qNo     = trim($_POST['QNo']     ?? '');
$eqNo    = trim($_POST['EqNo']    ?? '');
$potNo   = trim($_POST['PotNo']   ?? '');
$sexNo   = trim($_POST['SexNo']   ?? '');
$rDate   = trim($_POST['RDate']   ?? '');
$phone   = trim($_POST['Phone']   ?? '');
$lineID  = trim($_POST['lineID']  ?? '');
$address = trim($_POST['Address'] ?? '');

// ===== Validation =====
$errors = [];

if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
}
if (!ctype_digit($titles) || (int)$titles <= 0) {
    $errors[] = 'กรุณาเลือกคำนำหน้า';
}
if ($empName === '') {
    $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
}
if (!ctype_digit($qNo) || (int)$qNo <= 0) {
    $errors[] = 'กรุณาเลือกสาเหตุที่ออกจากงาน';
}
if (!ctype_digit($eqNo) || (int)$eqNo <= 0) {
    $errors[] = 'กรุณาเลือกวุฒิการศึกษา';
}
if (!ctype_digit($potNo) || (int)$potNo <= 0) {
    $errors[] = 'กรุณาเลือกตำแหน่ง';
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

// ตรวจ FK ก่อน เพื่อ error message ที่ชัดเจนกว่า
$kCheck = $pdo->prepare("SELECT 1 FROM kate WHERE KNo = :k");
$kCheck->execute([':k' => (int)$kNo]);
if (!$kCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเขตที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$qCheck = $pdo->prepare("SELECT 1 FROM quit WHERE QNo = :q");
$qCheck->execute([':q' => (int)$qNo]);
if (!$qCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบสาเหตุที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$eqCheck = $pdo->prepare("SELECT 1 FROM educational_qualification WHERE EqNo = :eq");
$eqCheck->execute([':eq' => (int)$eqNo]);
if (!$eqCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบวุฒิการศึกษาที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pCheck = $pdo->prepare("SELECT 1 FROM emp_position WHERE PotNo = :p");
$pCheck->execute([':p' => (int)$potNo]);
if (!$pCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบตำแหน่งที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sCheck = $pdo->prepare("SELECT 1 FROM sex WHERE SexNo = :s");
$sCheck->execute([':s' => (int)$sexNo]);
if (!$sCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเพศที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    // UPSERT employee — ถ้ามีอยู่แล้วอัปเดต ถ้าไม่มี insert ใหม่
    $exists = $pdo->prepare("SELECT 1 FROM employee WHERE EmpID = :id");
    $exists->execute([':id' => $empID]);

    if ($exists->fetchColumn()) {
        $upd = $pdo->prepare(
            "UPDATE employee
             SET Titles = :titles, EmpName = :name, KNo = :kno, SexNo = :sex, Phone = :phone, lineID = :line, Address = :addr
             WHERE EmpID = :id"
        );
        $upd->execute([
            ':titles' => (int)$titles,
            ':name'   => $empName,
            ':kno'    => (int)$kNo,
            ':sex'    => (int)$sexNo,
            ':phone'  => $phone   !== '' ? $phone   : null,
            ':line'   => $lineID  !== '' ? $lineID  : null,
            ':addr'   => $address !== '' ? $address : null,
            ':id'     => $empID,
        ]);
    } else {
        $ins = $pdo->prepare(
            "INSERT INTO employee (EmpID, Titles, EmpName, KNo, SexNo, Phone, lineID, Address, SDate)
             VALUES (:id, :titles, :name, :kno, :sex, :phone, :line, :addr, NOW())"
        );
        $ins->execute([
            ':id'     => $empID,
            ':titles' => (int)$titles,
            ':name'   => $empName,
            ':kno'    => (int)$kNo,
            ':sex'    => (int)$sexNo,
            ':phone'  => $phone   !== '' ? $phone   : null,
            ':line'   => $lineID  !== '' ? $lineID  : null,
            ':addr'   => $address !== '' ? $address : null,
        ]);
    }

    // สร้าง DocID ตามรูปแบบที่ตั้งไว้ในตาราง doc_running (ประเภท 'register')
    // เลขลำดับคิดจากเลขสูงสุดที่มีอยู่จริงของรอบนั้น + 1 จึงไม่ข้ามเมื่อลบเอกสารท้ายสุดออก
    $serverDate = date('Y-m-d');
    $running    = nextDocRunning($pdo, 'register', $serverDate);
    $docID      = $running['docid'];

    // INSERT register
    $reg = $pdo->prepare(
        "INSERT INTO register (DocID, RDate, SDate, QNo, EqNo, PotNo, KNo, SexNo, EmpID, StID)
         VALUES (:docid, :rdate, NOW(), :qno, :eqno, :potno, :kno, :sex, :id, :stid)"
    );
    $user = currentUser();
    $reg->execute([
        ':docid' => $docID,
        ':rdate' => $rDate,
        ':qno'   => (int)$qNo,
        ':eqno'  => (int)$eqNo,
        ':potno' => (int)$potNo,
        ':kno'   => (int)$kNo,
        ':sex'   => (int)$sexNo,
        ':id'    => $empID,
        ':stid'  => $user['StID'] ?? null,
    ]);
    $docNo = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกข้อมูลเรียบร้อย',
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
