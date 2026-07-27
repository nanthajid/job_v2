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

$runID = (int) ($_POST['RunID'] ?? 0);
if ($runID <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ต้องการแก้ไข'], JSON_UNESCAPED_UNICODE);
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

$cur = $pdo->prepare("SELECT * FROM doc_running WHERE RunID = :id");
$cur->execute([':id' => $runID]);
$current = $cur->fetch();
if (!$current) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// รหัสประเภทที่โค้ดอ้างอิงอยู่ ห้ามเปลี่ยน (แก้รูปแบบและรายละเอียดอื่นได้ตามปกติ)
$locked = docRunningLockedTypes();
if (isset($locked[$current['DocType']]) && $input['DocType'] !== $current['DocType']) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'เปลี่ยนรหัสประเภท "' . $current['DocType'] . '" ไม่ได้ เพราะ '
                   . $locked[$current['DocType']] . ' เรียกใช้รหัสนี้อยู่',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$errors = docRunningValidate($pdo, $input);
if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$dup = $pdo->prepare("SELECT 1 FROM doc_running WHERE DocType = :t AND RunID <> :id");
$dup->execute([':t' => $input['DocType'], ':id' => $runID]);
if ($dup->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'มีรหัสประเภทเอกสารนี้อยู่แล้ว'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE doc_running SET
            DocType = :type, DocName = :name, SourceTable = :table, SourceColumn = :col,
            Prefix = :prefix, Format = :format, ResetCycle = :cycle, Padding = :pad,
            StartSeq = :start, FillGap = :fill, Active = :active, Note = :note, UpdatedAt = NOW()
         WHERE RunID = :id"
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
        ':id'     => $runID,
    ]);

    // เตือนเมื่อเปลี่ยนรูปแบบของประเภทที่มีเอกสารเดิมอยู่แล้ว — เลขเดิมจะไม่ถูกนับรวมในรอบใหม่
    $warning = null;
    if ($current['Format'] !== $input['Format'] || $current['Prefix'] !== $input['Prefix']
        || (int) $current['Padding'] !== $input['Padding']) {
        $warning = 'เปลี่ยนรูปแบบเลขแล้ว — เอกสารเดิมที่ใช้รูปแบบเก่าจะไม่ถูกนำมานับต่อ '
                 . 'เลขของรูปแบบใหม่จะเริ่มนับจากเอกสารที่ขึ้นต้นด้วยรูปแบบใหม่เท่านั้น';
    }

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการแก้ไขเรียบร้อย',
        'warning' => $warning,
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
