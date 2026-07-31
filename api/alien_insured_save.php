<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/alien_insured_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$v       = static fn($key) => trim((string)($_POST[$key] ?? ''));
$docDate = alienInsuredParseDate($v('DocDate'));

if ($docDate === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกวันที่ของเอกสาร'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$user = currentUser();

try {
    $persons = alienInsuredNormalizePersons($_POST['persons'] ?? []);

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO alien_insured_doc
        (DocDate, OfficeName, SenderName, SenderPosition, ReceiverOffice, ReceiverName, Remark, StID)
        VALUES (:DocDate,:OfficeName,:SenderName,:SenderPosition,:ReceiverOffice,:ReceiverName,:Remark,:StID)');
    $stmt->execute([
        'DocDate'        => $docDate,
        'OfficeName'     => $v('OfficeName') ?: ALIEN_INSURED_DEFAULT_OFFICE,
        'SenderName'     => $v('SenderName') ?: null,
        'SenderPosition' => $v('SenderPosition') ?: null,
        'ReceiverOffice' => $v('ReceiverOffice') ?: null,
        'ReceiverName'   => $v('ReceiverName') ?: null,
        'Remark'         => $v('Remark') ?: null,
        'StID'           => $user['StID'] ?? null,
    ]);

    $docId = (int)$pdo->lastInsertId();
    alienInsuredInsertPersons($pdo, $docId, $persons);
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกแบบขึ้นทะเบียนผู้ประกันตนแรงงานต่างด้าวเรียบร้อย',
        'DocID'   => $docId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
