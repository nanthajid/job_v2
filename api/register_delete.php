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

$docNo = (int)($_POST['DocNo'] ?? 0);
if ($docNo <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขที่เอกสารไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

$check = $pdo->prepare("SELECT DocID FROM register WHERE DocNo = :doc");
$check->execute([':doc' => $docNo]);
$row = $check->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเอกสารที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM register WHERE DocNo = :doc");
    $del->execute([':doc' => $docNo]);

    echo json_encode([
        'success' => true,
        'message' => 'ลบเอกสารเรียบร้อย',
        'data'    => ['DocNo' => $docNo, 'DocID' => $row['DocID'] ?? ''],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ลบไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
