<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/alien_insured_checkin_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$docId = (int)($_POST['DocID'] ?? 0);
if ($docId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสเอกสารไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

// รายชื่อในเอกสารถูกลบตาม FK ON DELETE CASCADE
$stmt = getDB()->prepare('DELETE FROM ' . ALIEN_CHECKIN_DOC_TABLE . ' WHERE DocID = :id');
$stmt->execute(['id' => $docId]);
$deleted = $stmt->rowCount() > 0;

echo json_encode([
    'success' => $deleted,
    'message' => $deleted ? 'ลบเอกสารเรียบร้อย' : 'ไม่พบเอกสารที่ระบุ',
], JSON_UNESCAPED_UNICODE);
