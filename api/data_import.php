<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/data_import_helper.php';

$user = currentUser();
if (empty($user['level']) || $user['level'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่ทำรายการนี้ได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$key = trim($_POST['table'] ?? '');
$cfg = dataImportGetConfig($key);
if (!$cfg) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบตารางที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

try {
    $result = dataImportRun($pdo, $cfg);

    echo json_encode([
        'success'  => true,
        'message'  => "นำเข้าข้อมูล \"{$cfg['label']}\" สำเร็จ: เพิ่มใหม่ {$result['will_insert']} รายการ, แทนที่ข้อมูลเดิม {$result['will_update']} รายการ (รวม {$result['total_source']} รายการ)",
        'total'    => $result['total_source'],
        'inserted' => $result['will_insert'],
        'updated'  => $result['will_update'],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'นำเข้าข้อมูลไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
