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

$empID = trim($_POST['EmpID'] ?? '');
if (!preg_match('/^\d{13}$/', $empID)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขบัตรประชาชนไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

$check = $pdo->prepare("SELECT EmpName FROM employee WHERE EmpID = :id");
$check->execute([':id' => $empID]);
$row = $check->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ลงทะเบียน'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    $delReg = $pdo->prepare("DELETE FROM register WHERE EmpID = :id");
    $delReg->execute([':id' => $empID]);
    $regCount = $delReg->rowCount();

    $delSr = $pdo->prepare("DELETE FROM selft_rep WHERE EmpID = :id");
    $delSr->execute([':id' => $empID]);
    $srCount = $delSr->rowCount();

    $delEmp = $pdo->prepare("DELETE FROM employee WHERE EmpID = :id");
    $delEmp->execute([':id' => $empID]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'ลบข้อมูลเรียบร้อย',
        'data'    => [
            'EmpID'             => $empID,
            'register_deleted'  => $regCount,
            'selft_rep_deleted' => $srCount,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ลบไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
