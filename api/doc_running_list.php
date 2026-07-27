<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
requireAdminApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/doc_running_helper.php';

$pdo  = getDB();
$date = trim($_GET['date'] ?? '') ?: date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รูปแบบวันที่ไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rows = docRunningAll($pdo);
    $data = [];

    foreach ($rows as $cfg) {
        $item = $cfg;
        $item['CycleName'] = docRunningCycleName((string) $cfg['ResetCycle']);

        // สถานะปัจจุบันคำนวณสดด้วยตัวคำนวณเดียวกับตอนออกเลขจริง ตัวเลขที่เห็นจึงตรงกับที่จะได้จริง
        try {
            $last   = docRunningLastSeq($pdo, $cfg, $date);
            $manual = docRunningManualSeq($cfg, $date);
            $next   = docRunningResolveNextSeq($pdo, $cfg, $date, false);

            $item['Prefix_Preview'] = docRunningPrefix($cfg, $date);
            $item['LastSeq']        = $last;
            $item['ManualSeq']      = $manual;
            $item['NextSeq']        = $next;
            $item['LastDocID']      = $last > 0 ? docRunningFormat($cfg, $last, $date) : null;
            $item['NextDocID']      = docRunningFormat($cfg, $next, $date);
            $item['Error']          = null;

            // เจ้าของเอกสารล่าสุด ไว้ยืนยันว่าเลขล่าสุดที่เห็นเป็นของใคร
            $owner = $item['LastDocID'] ? docRunningDocOwner($pdo, $cfg, $item['LastDocID']) : null;
            $item['LastEmpID']   = $owner['EmpID'] ?? null;
            $item['LastEmpName'] = $owner ? trim(($owner['Title'] ?? '') . ' ' . ($owner['EmpName'] ?? '')) : null;
        } catch (Throwable $e) {
            $item['Prefix_Preview'] = null;
            $item['LastSeq']        = null;
            $item['ManualSeq']      = null;
            $item['NextSeq']        = null;
            $item['LastDocID']      = null;
            $item['NextDocID']      = null;
            $item['LastEmpID']      = null;
            $item['LastEmpName']    = null;
            $item['Error']          = $e->getMessage();
        }

        $data[] = $item;
    }

    echo json_encode([
        'success' => true,
        'date'    => $date,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'อ่านข้อมูลไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
