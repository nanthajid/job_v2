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

$docId = (int)($_POST['DocID'] ?? 0);
if ($docId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสเอกสารไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$v       = static fn($key) => trim((string)($_POST[$key] ?? ''));
$docDate = alienInsuredParseDate($v('DocDate'));
if ($docDate === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'กรุณาเลือกวันที่ของเอกสาร'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo   = getDB();
$check = $pdo->prepare('SELECT 1 FROM alien_insured_doc WHERE DocID = :id');
$check->execute(['id' => $docId]);
if (!$check->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเอกสารที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $persons = alienInsuredNormalizePersons($_POST['persons'] ?? []);

    $pdo->beginTransaction();
    $upd = $pdo->prepare('UPDATE alien_insured_doc SET
        DocDate=:DocDate, OfficeName=:OfficeName, SenderName=:SenderName, SenderPosition=:SenderPosition,
        ReceiverOffice=:ReceiverOffice, ReceiverName=:ReceiverName, Remark=:Remark
        WHERE DocID=:DocID');
    $upd->execute([
        'DocDate'        => $docDate,
        'OfficeName'     => $v('OfficeName') ?: ALIEN_INSURED_DEFAULT_OFFICE,
        'SenderName'     => $v('SenderName') ?: null,
        'SenderPosition' => $v('SenderPosition') ?: null,
        'ReceiverOffice' => $v('ReceiverOffice') ?: null,
        'ReceiverName'   => $v('ReceiverName') ?: null,
        'Remark'         => $v('Remark') ?: null,
        'DocID'          => $docId,
    ]);

    $pdo->prepare('DELETE FROM alien_insured_person WHERE DocID = :id')->execute(['id' => $docId]);
    alienInsuredInsertPersons($pdo, $docId, $persons);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'บันทึกการแก้ไขเรียบร้อย'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
