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

$type = trim($_GET['type'] ?? '');
$docNo = trim($_GET['docNo'] ?? '');

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

    $sql = "
        SELECT
            r.DocNo,
            e.EmpID,
            e.EmpName,
            e.KNo,
            e.Phone,
            e.Address,
            k.KName
        FROM $table r
        LEFT JOIN employee e ON r.EmpID = e.EmpID
        LEFT JOIN kate k ON e.KNo = k.KNo
        WHERE r.DocNo = :docNo
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':docNo' => (int)$docNo]);
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
