<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$docNo = (int)($_POST['DocNo'] ?? 0);

// ข้อมูลการทำรายการ (register)
$rDate = trim($_POST['RDate'] ?? '');
$kNo   = trim($_POST['KNo']   ?? '');
$sexNo = trim($_POST['SexNo'] ?? '');
$qNo   = trim($_POST['QNo']   ?? '');
$eqNo  = trim($_POST['EqNo']  ?? '');
$potNo = trim($_POST['PotNo'] ?? '');
$stID  = trim($_POST['StID']  ?? '');

// ข้อมูลส่วนบุคคล (employee)
$empID   = trim($_POST['EmpID']   ?? '');
$titles  = trim($_POST['Titles']  ?? '');
$empName = trim($_POST['EmpName'] ?? '');
$phone   = trim($_POST['Phone']   ?? '');
$lineID  = trim($_POST['lineID']  ?? '');
$address = trim($_POST['Address'] ?? '');

$errors = [];
if ($docNo <= 0) {
    $errors[] = 'เลขที่เอกสารไม่ถูกต้อง';
}
if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนไม่ถูกต้อง';
}
if (!ctype_digit($titles) || (int)$titles <= 0) {
    $errors[] = 'กรุณาเลือกคำนำหน้า';
}
if ($empName === '') {
    $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rDate)) {
    $errors[] = 'รูปแบบวันที่ไม่ถูกต้อง';
} else {
    [$y, $m, $d] = explode('-', $rDate);
    if (!checkdate((int)$m, (int)$d, (int)$y)) {
        $errors[] = 'วันที่ไม่ถูกต้อง';
    }
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
}
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
}
if (!ctype_digit($qNo) || (int)$qNo <= 0) {
    $errors[] = 'กรุณาเลือกสาเหตุที่ออกจากงาน';
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
    ['t' => 'titles', 'k' => 'TitleNo', 'v' => (int)$titles, 'msg' => 'ไม่พบคำนำหน้าที่เลือก'],
    ['t' => 'kate',   'k' => 'KNo',     'v' => (int)$kNo,    'msg' => 'ไม่พบเขตที่เลือก'],
    ['t' => 'sex',    'k' => 'SexNo',   'v' => (int)$sexNo,  'msg' => 'ไม่พบเพศที่เลือก'],
    ['t' => 'quit',   'k' => 'QNo',     'v' => (int)$qNo,    'msg' => 'ไม่พบสาเหตุที่เลือก'],
];

if ($eqNo !== '' && ctype_digit($eqNo) && (int)$eqNo > 0) {
    $fkChecks[] = ['t' => 'educational_qualification', 'k' => 'EqNo', 'v' => (int)$eqNo, 'msg' => 'ไม่พบวุฒิการศึกษาที่เลือก'];
}
if ($potNo !== '' && ctype_digit($potNo) && (int)$potNo > 0) {
    $fkChecks[] = ['t' => 'emp_position', 'k' => 'PotNo', 'v' => (int)$potNo, 'msg' => 'ไม่พบตำแหน่งที่เลือก'];
}
if ($stID !== '') {
    $fkChecks[] = ['t' => 'staff', 'k' => 'StID',  'v' => $stID, 'msg' => 'ไม่พบเจ้าหน้าที่ที่เลือก'];
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

// ตรวจว่า register record มีจริง และดึง EmpID ที่ผูกกับเอกสาร
$regStmt = $pdo->prepare("SELECT EmpID FROM register WHERE DocNo = :doc");
$regStmt->execute([':doc' => $docNo]);
$regEmpID = $regStmt->fetchColumn();
if ($regEmpID === false) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเอกสารที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// กันการเปลี่ยนเลขบัตร (PK) ของเอกสาร
if ((string)$regEmpID !== $empID) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถเปลี่ยนเลขบัตรประชาชนของเอกสารได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ตรวจว่า employee record มีจริง
$empExists = $pdo->prepare("SELECT 1 FROM employee WHERE EmpID = :id");
$empExists->execute([':id' => $empID]);
if (!$empExists->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ลงทะเบียน'], JSON_UNESCAPED_UNICODE);
    exit;
}

$eqVal  = ($eqNo  !== '' && (int)$eqNo  > 0) ? (int)$eqNo  : null;
$potVal = ($potNo !== '' && (int)$potNo > 0) ? (int)$potNo : null;

try {
    $pdo->beginTransaction();

    // 1) อัปเดตตาราง employee (ข้อมูลส่วนบุคคล)
    $updEmp = $pdo->prepare(
        "UPDATE employee
         SET Titles = :titles, EmpName = :name, KNo = :kno, SexNo = :sex,
             Phone = :phone, lineID = :line, Address = :addr
         WHERE EmpID = :id"
    );
    $updEmp->execute([
        ':titles' => (int)$titles,
        ':name'   => $empName,
        ':kno'    => (int)$kNo,
        ':sex'    => (int)$sexNo,
        ':phone'  => $phone   !== '' ? $phone   : null,
        ':line'   => $lineID  !== '' ? $lineID  : null,
        ':addr'   => $address !== '' ? $address : null,
        ':id'     => $empID,
    ]);

    // 2) อัปเดตตาราง register (ข้อมูลการทำรายการ)
    $updReg = $pdo->prepare(
        "UPDATE register
         SET RDate = :rdate, KNo = :kno, SexNo = :sex, QNo = :qno,
             EqNo = :eqno, PotNo = :potno,
             StID = COALESCE(NULLIF(:stid, ''), StID)
         WHERE DocNo = :doc"
    );
    $updReg->execute([
        ':rdate' => $rDate,
        ':kno'   => (int)$kNo,
        ':sex'   => (int)$sexNo,
        ':qno'   => (int)$qNo,
        ':eqno'  => $eqVal,
        ':potno' => $potVal,
        ':stid'  => $stID,
        ':doc'   => $docNo,
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการแก้ไขเรียบร้อย',
        'data'    => [
            'DocNo'   => $docNo,
            'EmpID'   => $empID,
            'Titles'  => (int)$titles,
            'EmpName' => $empName,
            'RDate'   => $rDate,
            'KNo'     => (int)$kNo,
            'SexNo'   => (int)$sexNo,
            'QNo'     => (int)$qNo,
            'EqNo'    => $eqVal,
            'PotNo'   => $potVal,
            'Phone'   => $phone,
            'lineID'  => $lineID,
            'Address' => $address,
            'StID'    => $stID,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
