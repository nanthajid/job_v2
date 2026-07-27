<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$employerId = trim($_GET['EmployerID'] ?? '');
if ($employerId === '') {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุเลขประจำตัวนายจ้าง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM vacancy_employer WHERE EmployerID = :id LIMIT 1');
$stmt->execute(['id' => $employerId]);
$employer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employer) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลนายจ้างรายนี้ในระบบ'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => true, 'data' => $employer], JSON_UNESCAPED_UNICODE);
