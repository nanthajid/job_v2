<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$empID = trim($_POST['empID'] ?? '');

if (!preg_match('/^\d{13}$/', $empID)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขบัตรไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("DELETE FROM employee WHERE EmpID = :empID");
    $stmt->execute([':empID' => $empID]);

    echo json_encode([
        'success' => true,
        'message' => 'ลบข้อมูลเรียบร้อย'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
