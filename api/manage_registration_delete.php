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

if (!in_array($type, ['register', 'selfrep'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ประเภทไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!ctype_digit($docNo)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขที่เอกสารไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    if ($type === 'register') {
        $table = 'register';
    } else {
        $table = 'selft_rep';
    }

    $stmt = $pdo->prepare("DELETE FROM $table WHERE DocNo = :docNo");
    $result = $stmt->execute([':docNo' => (int)$docNo]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'ลบข้อมูลเรียบร้อย'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
