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
$sexNo   = trim($_POST['SexNo']   ?? '');
$kNo     = trim($_POST['KNo']     ?? '');
$phone   = trim($_POST['Phone']   ?? '');
$lineID  = trim($_POST['lineID']  ?? '');
$address = trim($_POST['Address'] ?? '');

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
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
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

$tCheck = $pdo->prepare("SELECT 1 FROM titles WHERE DocNo = :t");
$tCheck->execute([':t' => (int)$titles]);
if (!$tCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำนำหน้าที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sCheck = $pdo->prepare("SELECT 1 FROM sex WHERE SexNo = :s");
$sCheck->execute([':s' => (int)$sexNo]);
if (!$sCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเพศที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

$kCheck = $pdo->prepare("SELECT 1 FROM kate WHERE KNo = :k");
$kCheck->execute([':k' => (int)$kNo]);
if (!$kCheck->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเขตที่เลือก'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $check = $pdo->prepare("SELECT 1 FROM employee WHERE EmpID = :id");
    $check->execute([':id' => $empID]);
    if ($check->fetchColumn()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'มีข้อมูลผู้ว่างงานนี้อยู่แล้ว'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ins = $pdo->prepare(
        "INSERT INTO employee (EmpID, Titles, EmpName, SexNo, KNo, Phone, lineID, Address, SDate)
         VALUES (:id, :titles, :name, :sex, :kno, :phone, :line, :addr, NOW())"
    );
    $ins->execute([
        ':id'     => $empID,
        ':titles' => $titles,
        ':name'   => $empName,
        ':sex'    => (int)$sexNo,
        ':kno'    => (int)$kNo,
        ':phone'  => $phone   !== '' ? $phone   : null,
        ':line'   => $lineID  !== '' ? $lineID  : null,
        ':addr'   => $address !== '' ? $address : null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มข้อมูลผู้ว่างงานเรียบร้อย',
        'data'    => [
            'EmpID'   => $empID,
            'Titles'  => (int)$titles,
            'EmpName' => $empName,
            'SexNo'   => (int)$sexNo,
            'KNo'     => (int)$kNo,
            'Phone'   => $phone,
            'lineID'  => $lineID,
            'Address' => $address,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
