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

$empID   = trim($_POST['EmpID']   ?? '');
$titles  = trim($_POST['Titles']  ?? '');
$empName = trim($_POST['EmpName'] ?? '');
$kNo     = trim($_POST['KNo']     ?? '');
$sexNo   = trim($_POST['SexNo']   ?? '');
$phone   = trim($_POST['Phone']   ?? '');
$lineID  = trim($_POST['lineID']  ?? '');
$address = trim($_POST['Address'] ?? '');

$errors = [];
if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนไม่ถูกต้อง';
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
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
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
$tCheck = $pdo->prepare("SELECT 1 FROM titles WHERE DocNo = :t");
$tCheck->execute([':t' => (int)$titles]);
if (!$tCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำนำหน้าที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$kCheck = $pdo->prepare("SELECT 1 FROM kate WHERE KNo = :k");
$kCheck->execute([':k' => (int)$kNo]);
if (!$kCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเขตที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sCheck = $pdo->prepare("SELECT 1 FROM sex WHERE SexNo = :s");
$sCheck->execute([':s' => (int)$sexNo]);
if (!$sCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเพศที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ตรวจว่า record มีจริง
$exists = $pdo->prepare("SELECT 1 FROM employee WHERE EmpID = :id");
$exists->execute([':id' => $empID]);
if (!$exists->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ลงทะเบียน'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $upd = $pdo->prepare(
        "UPDATE employee
         SET Titles = :titles, EmpName = :name, KNo = :kno, SexNo = :sex,
             Phone = :phone, lineID = :line, Address = :addr
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

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการแก้ไขเรียบร้อย',
        'data'    => [
            'EmpID'   => $empID,
            'Titles'  => (int)$titles,
            'EmpName' => $empName,
            'KNo'     => (int)$kNo,
            'SexNo'   => (int)$sexNo,
            'Phone'   => $phone,
            'lineID'  => $lineID,
            'Address' => $address,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
