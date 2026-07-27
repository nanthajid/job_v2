<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
requireAdminApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/doc_running_helper.php';

// ดูตัวอย่างเลขจากรูปแบบที่กำลังกรอก โดยยังไม่บันทึกลงฐานข้อมูล
$cfg = [
    'Prefix'  => trim($_POST['Prefix']  ?? ''),
    'Format'  => trim($_POST['Format']  ?? ''),
    'Padding' => (int) ($_POST['Padding'] ?? 0),
];
$startSeq = max(1, (int) ($_POST['StartSeq'] ?? 1));
$date     = trim($_POST['date'] ?? '') ?: date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
    $date = date('Y-m-d');
}

try {
    $samples = [
        docRunningFormat($cfg, $startSeq, $date),
        docRunningFormat($cfg, $startSeq + 1, $date),
        docRunningFormat($cfg, $startSeq + 11, $date),
    ];

    echo json_encode([
        'success'  => true,
        'samples'  => $samples,
        'cycle'    => docRunningDetectCycle($cfg),
        'cycleName' => docRunningCycleName(docRunningDetectCycle($cfg)),
        'length'   => strlen($samples[2]),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
