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

$stID = trim($_POST['StID'] ?? '');

if ($stID === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสเจ้าหน้าที่ที่จะลบ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // ลบจากตาราง users ก่อน (ถ้ามี)
    $stmtUser = $pdo->prepare("DELETE FROM users WHERE StID = :id");
    $stmtUser->execute([':id' => $stID]);

    // ลบจากตาราง staff
    $stmt = $pdo->prepare("DELETE FROM staff WHERE StID = :id");
    $stmt->execute([':id' => $stID]);

    if ($stmt->rowCount() > 0 || $stmtUser->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเจ้าหน้าที่เรียบร้อย'], JSON_UNESCAPED_UNICODE);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลเจ้าหน้าที่ในระบบ'], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ลบไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
