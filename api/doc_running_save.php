<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
requireAdminApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/doc_running_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = [
    'DocType'      => trim($_POST['DocType']      ?? ''),
    'DocName'      => trim($_POST['DocName']      ?? ''),
    'SourceTable'  => trim($_POST['SourceTable']  ?? ''),
    'SourceColumn' => trim($_POST['SourceColumn'] ?? 'DocID'),
    'Prefix'       => trim($_POST['Prefix']       ?? ''),
    'Format'       => trim($_POST['Format']       ?? ''),
    'ResetCycle'   => trim($_POST['ResetCycle']   ?? 'daily'),
    'Padding'      => (int) ($_POST['Padding']    ?? 0),
    'StartSeq'     => (int) ($_POST['StartSeq']   ?? 1),
    'FillGap'      => isset($_POST['FillGap']) && (string) $_POST['FillGap'] === '1' ? 1 : 0,
    'Active'       => isset($_POST['Active']) && (string) $_POST['Active'] === '1' ? 1 : 0,
    'Note'         => trim($_POST['Note']         ?? ''),
];

$pdo = getDB();

$errors = docRunningValidate($pdo, $input);
if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$dup = $pdo->prepare("SELECT 1 FROM doc_running WHERE DocType = :t");
$dup->execute([':t' => $input['DocType']]);
if ($dup->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'มีรหัสประเภทเอกสารนี้อยู่แล้ว'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO doc_running
            (DocType, DocName, SourceTable, SourceColumn, Prefix, Format, ResetCycle, Padding, StartSeq, FillGap, Active, Note, UpdatedAt)
         VALUES
            (:type, :name, :table, :col, :prefix, :format, :cycle, :pad, :start, :fill, :active, :note, NOW())"
    );
    $stmt->execute([
        ':type'   => $input['DocType'],
        ':name'   => $input['DocName'],
        ':table'  => $input['SourceTable'],
        ':col'    => $input['SourceColumn'],
        ':prefix' => $input['Prefix'],
        ':format' => $input['Format'],
        ':cycle'  => $input['ResetCycle'],
        ':pad'    => $input['Padding'],
        ':start'  => $input['StartSeq'],
        ':fill'   => $input['FillGap'],
        ':active' => $input['Active'],
        ':note'   => $input['Note'] !== '' ? $input['Note'] : null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'เพิ่มรูปแบบเลขรันเรียบร้อย',
        'data'    => ['RunID' => (int) $pdo->lastInsertId()],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
