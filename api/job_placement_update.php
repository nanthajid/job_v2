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

$jpNo = (int)($_POST['JPNo'] ?? 0);
if ($jpNo <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสรายการไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$empID         = trim($_POST['EmpID']         ?? '');
$empName       = trim($_POST['EmpName']       ?? '');
$gender        = trim($_POST['Gender']        ?? '');
$age           = trim($_POST['Age']           ?? '');
$eduNo         = trim($_POST['EduNo']         ?? '');
$address       = trim($_POST['Address']       ?? '');
$phone         = trim($_POST['Phone']         ?? '');
$companyName   = trim($_POST['CompanyName']   ?? '');
$companyAddress= trim($_POST['CompanyAddress'] ?? '');
$position      = trim($_POST['Position']      ?? '');
$incomeDay     = trim($_POST['IncomeDay']     ?? '');
$incomeMonth   = trim($_POST['IncomeMonth']   ?? '');
$startDate     = trim($_POST['StartDate']     ?? '');
$serviceType   = trim($_POST['ServiceType']   ?? '');
$staffService  = trim($_POST['StaffService']  ?? '');

// ===== Validation =====
$errors = [];

if (!preg_match('/^\d{13}$/', $empID)) {
    $errors[] = 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก';
}
if ($empName === '') {
    $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
}
if ($age !== '' && (!ctype_digit($age) || (int)$age < 15 || (int)$age > 120)) {
    $errors[] = 'อายุต้องเป็นตัวเลขระหว่าง 15-120';
}
if ($eduNo !== '' && !ctype_digit($eduNo)) {
    $errors[] = 'กรุณาเลือกวุฒิการศึกษาให้ถูกต้อง';
}
if ($companyName === '') {
    $errors[] = 'กรุณากรอกชื่อสถานประกอบการ';
}
if ($phone !== '' && !preg_match('/^[0-9\-\s]{6,15}$/', $phone)) {
    $errors[] = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
}
if ($incomeDay !== '' && !is_numeric($incomeDay)) {
    $errors[] = 'รายได้ต่อวันต้องเป็นตัวเลข';
}
if ($incomeMonth !== '' && !is_numeric($incomeMonth)) {
    $errors[] = 'รายได้ต่อเดือนต้องเป็นตัวเลข';
}
if ($startDate !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $errors[] = 'รูปแบบวันที่เริ่มงานไม่ถูกต้อง';
    } else {
        [$y, $m, $d] = explode('-', $startDate);
        if (!checkdate((int)$m, (int)$d, (int)$y)) {
            $errors[] = 'วันที่เริ่มงานไม่ถูกต้อง';
        }
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

// Check record exists
$check = $pdo->prepare("SELECT 1 FROM job_placement WHERE JPNo = :id");
$check->execute([':id' => $jpNo]);
if (!$check->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validate FK if EduNo provided
if ($eduNo !== '') {
    $eduCheck = $pdo->prepare("SELECT 1 FROM educational_qualification WHERE EqNo = :eq");
    $eduCheck->execute([':eq' => (int)$eduNo]);
    if (!$eduCheck->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ไม่พบวุฒิการศึกษาที่เลือก'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    $upd = $pdo->prepare(
        "UPDATE job_placement SET
            EmpID = :empid,
            EmpName = :empname,
            Gender = :gender,
            Age = :age,
            EduNo = :eduno,
            Address = :address,
            Phone = :phone,
            CompanyName = :companyname,
            CompanyAddress = :companyaddress,
            Position = :position,
            IncomeDay = :incomeday,
            IncomeMonth = :incomemonth,
            StartDate = :startdate,
            ServiceType = :servicetype,
            StaffService = :staffservice
         WHERE JPNo = :id"
    );
    $upd->execute([
        ':empid'          => $empID,
        ':empname'        => $empName,
        ':gender'         => $gender       !== '' ? $gender         : null,
        ':age'            => $age          !== '' ? (int)$age       : null,
        ':eduno'          => $eduNo        !== '' ? (int)$eduNo     : null,
        ':address'        => $address      !== '' ? $address        : null,
        ':phone'          => $phone        !== '' ? $phone          : null,
        ':companyname'    => $companyName,
        ':companyaddress' => $companyAddress !== '' ? $companyAddress : null,
        ':position'       => $position     !== '' ? $position       : null,
        ':incomeday'      => $incomeDay    !== '' ? $incomeDay      : null,
        ':incomemonth'    => $incomeMonth  !== '' ? $incomeMonth    : null,
        ':startdate'      => $startDate    !== '' ? $startDate      : null,
        ':servicetype'    => $serviceType  !== '' ? $serviceType    : null,
        ':staffservice'   => $staffService !== '' ? $staffService   : null,
        ':id'             => $jpNo,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตข้อมูลเรียบร้อย',
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}