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
$rDate = trim($_GET['rDate'] ?? date('Y-m-d'));

if (!preg_match('/^\d{13}$/', $empID)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขบัตรประชาชนไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    $check = $pdo->prepare("
        SELECT COUNT(*) as cnt
        FROM selft_rep
        WHERE EmpID = :empID AND DATE(RDate) = DATE(:rDate)
    ");
    $check->execute([
        ':empID' => $empID,
        ':rDate' => $rDate
    ]);

    $result = $check->fetch(PDO::FETCH_ASSOC);
    $count = (int)($result['cnt'] ?? 0);

    echo json_encode([
        'success' => true,
        'data' => [
            'empID' => $empID,
            'rDate' => $rDate,
            'hasSelfRepToday' => $count > 0,
            'count' => $count
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
