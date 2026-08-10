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
$empName = trim($_POST['empName'] ?? '');
$kNo = trim($_POST['KNo'] ?? '');
$phone = trim($_POST['Phone'] ?? '');
$address = trim($_POST['Address'] ?? '');

if (!in_array($type, ['register', 'selfrep'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ประเภทไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ctype_digit($docNo) || empty($empName) || !ctype_digit($kNo)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
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
        SET EmpName = :empName, KNo = :kNo, Phone = :phone, Address = :addr
        WHERE EmpID = :empID
    ");
    $updEmp->execute([
        ':empName' => $empName,
        ':kNo' => (int)$kNo,
        ':phone' => $phone ?: null,
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
