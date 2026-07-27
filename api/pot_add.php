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

$potName = trim($_POST['PotName'] ?? '');

if ($potName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อตำแหน่ง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

try {
    // Check if name already exists
    $stmt = $pdo->prepare("SELECT PotNo FROM emp_position WHERE PotName = :name");
    $stmt->execute([':name' => $potName]);
    $existing = $stmt->fetch();

    if ($existing) {
        $potNo = (int)$existing['PotNo'];
        // Update (Replace) the existing record
        $upd = $pdo->prepare("UPDATE emp_position SET PotName = :name WHERE PotNo = :potNo");
        $upd->execute([':name' => $potName, ':potNo' => $potNo]);
        $message = 'อัปเดตข้อมูลตำแหน่งเรียบร้อย';
    } else {
        // AddNew
        $ins = $pdo->prepare("INSERT INTO emp_position (PotName) VALUES (:name)");
        $ins->execute([':name' => $potName]);
        $potNo = (int)$pdo->lastInsertId();
        $message = 'เพิ่มตำแหน่งเรียบร้อย';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => ['PotNo' => $potNo, 'PotName' => $potName],
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'บันทึกข้อมูลไม่สำเร็จ: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
