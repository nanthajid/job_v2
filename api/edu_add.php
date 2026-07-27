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

$eqName = trim($_POST['EqName'] ?? '');

if ($eqName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อวุฒิการศึกษา'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

try {
    // Check if name already exists
    $stmt = $pdo->prepare("SELECT EqNo FROM educational_qualification WHERE EqName = :name");
    $stmt->execute([':name' => $eqName]);
    $existing = $stmt->fetch();

    if ($existing) {
        $eqNo = (int)$existing['EqNo'];
        // Update (Replace) the existing record
        $upd = $pdo->prepare("UPDATE educational_qualification SET EqName = :name WHERE EqNo = :eqNo");
        $upd->execute([':name' => $eqName, ':eqNo' => $eqNo]);
        $message = 'อัปเดตข้อมูลวุฒิการศึกษาเรียบร้อย';
    } else {
        // AddNew
        $ins = $pdo->prepare("INSERT INTO educational_qualification (EqName) VALUES (:name)");
        $ins->execute([':name' => $eqName]);
        $eqNo = (int)$pdo->lastInsertId();
        $message = 'เพิ่มวุฒิการศึกษาเรียบร้อย';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => ['EqNo' => $eqNo, 'EqName' => $eqName],
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'บันทึกข้อมูลไม่สำเร็จ: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
