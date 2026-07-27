<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$noticeId = (int)($_GET['id'] ?? 0);
if ($noticeId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสรายการไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM vacancy_notice WHERE NoticeID = :id');
$stmt->execute(['id' => $noticeId]);
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notice) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$posStmt = $pdo->prepare('SELECT p.*, m.PositionName FROM vacancy_notice_position p
    JOIN vacancy_position_master m ON m.PositionMasterID = p.PositionMasterID
    WHERE p.NoticeID = :id ORDER BY p.PositionID ASC');
$posStmt->execute(['id' => $noticeId]);
$positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => ['notice' => $notice, 'positions' => $positions]], JSON_UNESCAPED_UNICODE);
