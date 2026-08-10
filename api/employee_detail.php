<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ GET'], JSON_UNESCAPED_UNICODE);
    exit;
}

$empID = trim($_GET['empID'] ?? '');

if (!preg_match('/^\d{13}$/', $empID)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขบัตรไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    $sql = "
        SELECT *
        FROM employee
        WHERE EmpID = :empID
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':empID' => $empID]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
