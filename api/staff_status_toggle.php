<?php
/**
 * สลับสถานะเปิด/ปิดใช้งานของบัญชีผู้ใช้ (users.StatusNo)
 * รับ: StID (รหัสเจ้าหน้าที่), status (0/1)
 * ป้องกัน: ปิดบัญชีของตนเอง (กันล็อกเอาต์ตัวเอง)
 */
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stID   = trim($_POST['StID'] ?? '');
$status = trim($_POST['status'] ?? '');

if ($stID === '' || ($status !== '0' && $status !== '1')) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$user = currentUser();

// กันไม่ให้ผู้ใช้ปิดสถานะบัญชีของตนเอง
if ($status === '0' && (string) ($user['StID'] ?? '') === (string) $stID) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถปิดใช้งานบัญชีของตนเองได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ต้องมีบัญชีผู้ใช้ผูกกับรหัสเจ้าหน้าที่นี้
$chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE StID = :id");
$chk->execute([':id' => $stID]);
if (!$chk->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้ของเจ้าหน้าที่นี้'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $upd = $pdo->prepare("UPDATE users SET StatusNo = :s WHERE StID = :id");
    $upd->execute([':s' => (int) $status, ':id' => $stID]);

    // ดึงข้อมูลสถานะปลายทางไปแสดงผล
    $meta = $pdo->prepare("SELECT StatusNo, StatusName, BadgeClass FROM user_status WHERE StatusNo = :s");
    $meta->execute([':s' => (int) $status]);
    $row = $meta->fetch() ?: ['StatusNo' => (int) $status, 'StatusName' => '-', 'BadgeClass' => 'badge-secondary'];

    echo json_encode([
        'success'    => true,
        'message'    => $status === '1' ? 'เปิดใช้งานบัญชีเรียบร้อย' : 'ปิดใช้งานบัญชีเรียบร้อย',
        'StatusNo'   => (int) $row['StatusNo'],
        'StatusName' => $row['StatusName'],
        'BadgeClass' => $row['BadgeClass'],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'อัปเดตสถานะไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
