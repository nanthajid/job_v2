<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/alien_insured_checkin_helper.php';

$docId = (int)($_GET['id'] ?? 0);
if ($docId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสเอกสารไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM ' . ALIEN_CHECKIN_DOC_TABLE . ' WHERE DocID = :id');
$stmt->execute(['id' => $docId]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเอกสารที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pStmt = $pdo->prepare('SELECT * FROM ' . ALIEN_CHECKIN_PERSON_TABLE . ' WHERE DocID = :id ORDER BY SeqNo ASC, PersonID ASC');
$pStmt->execute(['id' => $docId]);

echo json_encode([
    'success' => true,
    'data'    => ['doc' => $doc, 'persons' => $pStmt->fetchAll(PDO::FETCH_ASSOC)],
], JSON_UNESCAPED_UNICODE);
