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

$type = trim($_POST['type'] ?? '');
$docNo = trim($_POST['docNo'] ?? '');
$titles = trim($_POST['Titles'] ?? '');
$empName = trim($_POST['EmpName'] ?? '');
$sexNo = trim($_POST['SexNo'] ?? '');
$kNo = trim($_POST['KNo'] ?? '');
$eqNo = trim($_POST['EqNo'] ?? '');
$potNo = trim($_POST['PotNo'] ?? '');
$qNo = trim($_POST['QNo'] ?? '');
$phone = trim($_POST['Phone'] ?? '');
$lineID = trim($_POST['lineID'] ?? '');
$address = trim($_POST['Address'] ?? '');

if (!in_array($type, ['register', 'selfrep'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ประเภทไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$errors = [];
if (!ctype_digit($docNo)) {
    $errors[] = 'เลขที่เอกสารไม่ถูกต้อง';
}
if (empty($empName)) {
    $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
}
if (!ctype_digit($titles) || (int)$titles <= 0) {
    $errors[] = 'กรุณาเลือกคำนำหน้า';
}
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
}
if (!ctype_digit($eqNo) || (int)$eqNo <= 0) {
    $errors[] = 'กรุณาเลือกวุฒิการศึกษา';
}
if (!ctype_digit($potNo) || (int)$potNo <= 0) {
    $errors[] = 'กรุณาเลือกตำแหน่ง';
}
if (!ctype_digit($qNo) || (int)$qNo <= 0) {
    $errors[] = 'กรุณาเลือกสาเหตุที่ออกจากงาน';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    if ($type === 'register') {
        $table = 'register';
    } else {
        $table = 'selft_rep';
    }

    // ตรวจสอบว่ามี EmpID ที่เกี่ยวข้องกับ DocNo นี้
    $checkStmt = $pdo->prepare("SELECT EmpID FROM $table WHERE DocNo = :docNo");
    $checkStmt->execute([':docNo' => (int)$docNo]);
    $empData = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$empData) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลที่จะแก้ไข'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $empID = $empData['EmpID'];

    // Update employee
    $updEmp = $pdo->prepare("
        UPDATE employee
        SET Titles = :titles, EmpName = :empName, SexNo = :sexNo, KNo = :kNo, Phone = :phone, lineID = :line, Address = :addr
        WHERE EmpID = :empID
    ");
    $updEmp->execute([
        ':titles' => (int)$titles,
        ':empName' => $empName,
        ':sexNo' => (int)$sexNo,
        ':kNo' => (int)$kNo,
        ':phone' => $phone ?: null,
        ':line' => $lineID ?: null,
        ':addr' => $address ?: null,
        ':empID' => $empID
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการแก้ไขเรียบร้อย'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
