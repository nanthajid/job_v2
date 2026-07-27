<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
requireAdminApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/doc_running_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$runID = (int) ($_POST['RunID'] ?? 0);
if ($runID <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ต้องการลบ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM doc_running WHERE RunID = :id");
$stmt->execute([':id' => $runID]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ประเภทที่โค้ดเรียกใช้อยู่ ลบไม่ได้ — ถ้าต้องการหยุดใช้ให้ปิดใช้งานแทน
$locked = docRunningLockedTypes();
if (isset($locked[$row['DocType']])) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'ลบ "' . $row['DocName'] . '" ไม่ได้ เพราะ ' . $locked[$row['DocType']]
                   . ' เรียกใช้รูปแบบนี้อยู่ (หากต้องการหยุดใช้ให้ปิดใช้งานแทน)',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM doc_running WHERE RunID = :id");
    $del->execute([':id' => $runID]);

    echo json_encode([
        'success' => true,
        'message' => 'ลบรูปแบบเลขรันเรียบร้อย',
        'data'    => ['RunID' => $runID, 'DocType' => $row['DocType']],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ลบไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
