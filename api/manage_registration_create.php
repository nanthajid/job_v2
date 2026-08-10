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

$type = trim($_POST['type'] ?? '');
$empID = trim($_POST['empID'] ?? '');
$empName = trim($_POST['empName'] ?? '');
$kNo = trim($_POST['KNo'] ?? '');
$rDate = trim($_POST['rDate'] ?? date('Y-m-d'));
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if (!in_array($type, ['register', 'selfrep'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ประเภทไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$errors = [];

if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
}
if (empty($empName)) {
    $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
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

try {
    $pdo = getDB();

    // ตรวจสอบเขต
    $kCheck = $pdo->prepare("SELECT 1 FROM kate WHERE KNo = :k");
    $kCheck->execute([':k' => (int)$kNo]);
    if (!$kCheck->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ไม่พบเขตที่เลือก'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ตรวจสอบว่ามี register/selfrep ในวันนี้แล้วหรือไม่
    $table = ($type === 'register') ? 'register' : 'selft_rep';
    $checkStmt = $pdo->prepare("SELECT 1 FROM $table WHERE EmpID = :empID AND DATE(RDate) = DATE(:rDate)");
    $checkStmt->execute([':empID' => $empID, ':rDate' => $rDate]);
    if ($checkStmt->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'บันทึก ' . ($type === 'register' ? 'ขึ้นทะเบียน' : 'รายงานตัว') . ' สำหรับวันนี้มีอยู่แล้ว'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    // UPSERT employee
    $exists = $pdo->prepare("SELECT 1 FROM employee WHERE EmpID = :id");
    $exists->execute([':id' => $empID]);

    if ($exists->fetchColumn()) {
        $upd = $pdo->prepare("
            UPDATE employee
            SET EmpName = :name, KNo = :kno, Phone = :phone, Address = :addr
            WHERE EmpID = :id
        ");
        $upd->execute([
            ':name' => $empName,
            ':kno' => (int)$kNo,
            ':phone' => $phone !== '' ? $phone : null,
            ':addr' => $address !== '' ? $address : null,
            ':id' => $empID
        ]);
    } else {
        $ins = $pdo->prepare("
            INSERT INTO employee (EmpID, EmpName, KNo, Phone, Address, SDate)
            VALUES (:id, :name, :kno, :phone, :addr, NOW())
        ");
        $ins->execute([
            ':id' => $empID,
            ':name' => $empName,
            ':kno' => (int)$kNo,
            ':phone' => $phone !== '' ? $phone : null,
            ':addr' => $address !== '' ? $address : null
        ]);
    }

    // ดึง DocNo ใหม่
    $docResult = nextDocRunning($pdo, $type, $rDate);
    $docNo = (int)$docResult['seq'];
    $docID = $docResult['docid'];

    // บันทึก register/selfrep
    $ins = $pdo->prepare("
        INSERT INTO $table (DocNo, DocID, EmpID, KNo, RDate, SDate)
        VALUES (:docNo, :docID, :empID, :kno, :rDate, NOW())
    ");
    $ins->execute([
        ':docNo' => $docNo,
        ':docID' => $docID,
        ':empID' => $empID,
        ':kno' => (int)$kNo,
        ':rDate' => $rDate
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกเรียบร้อย',
        'docNo' => $docNo
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('manage_registration_create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
