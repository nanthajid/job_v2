<?php
/**
 * ออกเลขรหัสเจ้าหน้าที่ (StID) ตัวถัดไปแบบรันต่อเนื่อง
 * ใช้ค่าตัวเลขสูงสุดที่มีอยู่ในตาราง staff บวกหนึ่ง
 */
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();
    $max = (int) $pdo->query("SELECT COALESCE(MAX(CAST(StID AS UNSIGNED)), 0) FROM staff")->fetchColumn();
    $next = str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);

    echo json_encode([
        'success' => true,
        'next_id' => $next,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'ออกเลขรหัสไม่สำเร็จ: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
