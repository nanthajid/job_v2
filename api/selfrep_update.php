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

$user = currentUser();

$docNo   = isset($_POST['DocNo']) ? (int)$_POST['DocNo'] : 0;
$empID   = trim($_POST['EmpID']   ?? '');
$kNo     = trim($_POST['KNo']     ?? '');
$qNo     = trim($_POST['QNo']     ?? '');
$jNo     = trim($_POST['JNo']     ?? '');
$sexNo   = trim($_POST['SexNo']   ?? '');
$titles  = trim($_POST['Titles']  ?? '');
$rDate   = trim($_POST['RDate']   ?? '');
$phone   = trim($_POST['Phone']   ?? '');
$address = trim($_POST['Address'] ?? '');

$errors = [];

if (!$docNo) {
    $errors[] = 'DocNo ไม่ถูกต้อง';
}
if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
}
if (!ctype_digit($qNo) || (int)$qNo <= 0) {
    $errors[] = 'กรุณาเลือกสาเหตุที่ออกจากงาน';
}
if (!ctype_digit($jNo) || (int)$jNo <= 0) {
    $errors[] = 'กรุณาเลือกสถานะการได้งาน';
}
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
}
if (!ctype_digit($titles) || (int)$titles <= 0) {
    $errors[] = 'กรุณาเลือกคำนำหน้า';
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
    ['t' => 'titles','k' => 'TitleNo','v' => (int)$titles,'msg' => 'ไม่พบคำนำหน้าที่เลือก'],
];

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

    // Check if DocNo exists
    $chk = $pdo->prepare("SELECT 1 FROM selft_rep WHERE DocNo = :docno");
    $chk->execute([':docno' => $docNo]);
    if (!$chk->fetchColumn()) {
        throw new Exception('ไม่พบข้อมูลการรายงานตัวนี้');
    }

    // Update selft_rep
    $upd = $pdo->prepare(
        "UPDATE selft_rep SET RDate = :rdate, QNo = :qno, JNo = :jno, KNo = :kno, SexNo = :sex
         WHERE DocNo = :docno"
    );
    $upd->execute([
        ':docno' => $docNo,
        ':rdate' => $rDate,
        ':qno'   => (int)$qNo,
        ':jno'   => (int)$jNo,
        ':kno'   => (int)$kNo,
        ':sex'   => (int)$sexNo,
    ]);

    // Update employee
    $updEmp = $pdo->prepare(
        "UPDATE employee SET Titles = :titles, KNo = :kno, SexNo = :sex, Phone = :phone, Address = :addr
         WHERE EmpID = :id"
    );
    $updEmp->execute([
        ':titles'=> (int)$titles,
        ':kno'   => (int)$kNo,
        ':sex'   => (int)$sexNo,
        ':phone' => $phone !== '' ? $phone : null,
        ':addr'  => $address !== '' ? $address : null,
        ':id'    => $empID,
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'แก้ไขการรายงานตัวเรียบร้อย',
        'data'    => ['DocNo' => $docNo],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
