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

$pdo = getDB();

$check = $pdo->prepare("SELECT 1 FROM job_placement WHERE JPNo = :id");
$check->execute([':id' => $jpNo]);
if (!$check->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM job_placement WHERE JPNo = :id");
    $del->execute([':id' => $jpNo]);

    echo json_encode([
        'success' => true,
        'message' => 'ลบข้อมูลเรียบร้อย',
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}