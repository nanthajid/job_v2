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
$date  = trim($_POST['date'] ?? '') ?: date('Y-m-d');
$clear = (string) ($_POST['clear'] ?? '') === '1';

if ($runID <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ต้องการแก้ไข'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รูปแบบวันที่ไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM doc_running WHERE RunID = :id");
$stmt->execute([':id' => $runID]);
$cfg = $stmt->fetch();

if (!$cfg) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบรายการที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = currentUser();

try {
    // ล้างค่าที่ตั้งไว้ — กลับไปนับต่อจากเลขสูงสุดที่มีอยู่จริงตามปกติ
    if ($clear) {
        $pdo->prepare(
            "UPDATE doc_running
             SET ManualLastSeq = NULL, ManualPeriod = NULL, ManualUpdatedAt = NULL, ManualBy = NULL
             WHERE RunID = :id"
        )->execute([':id' => $runID]);

        $fresh = $pdo->prepare("SELECT * FROM doc_running WHERE RunID = :id");
        $fresh->execute([':id' => $runID]);
        $cfgNew = $fresh->fetch();
        $next   = docRunningResolveNextSeq($pdo, $cfgNew, $date, false);

        echo json_encode([
            'success'    => true,
            'message'    => 'ล้างค่าเลขล่าสุดที่ตั้งไว้แล้ว',
            'NextSeq'    => $next,
            'NextDocID'  => docRunningFormat($cfgNew, $next, $date),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($_POST['LastSeq']) || !ctype_digit((string) $_POST['LastSeq'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกเลขล่าสุดเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $manual = (int) $_POST['LastSeq'];
    if ($manual > 4294967295) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'เลขล่าสุดมีค่าสูงเกินไป'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $head    = docRunningPrefix($cfg, $date);
    $realMax = docRunningLastSeq($pdo, $cfg, $date);

    $pdo->prepare(
        "UPDATE doc_running
         SET ManualLastSeq = :seq, ManualPeriod = :period, ManualUpdatedAt = NOW(), ManualBy = :by
         WHERE RunID = :id"
    )->execute([
        ':seq'    => $manual,
        ':period' => $head,
        ':by'     => $user['StID'] ?? null,
        ':id'     => $runID,
    ]);

    // อ่านกลับมาคำนวณเลขถัดไปด้วยตัวคำนวณเดียวกับตอนออกเลขจริง
    $fresh = $pdo->prepare("SELECT * FROM doc_running WHERE RunID = :id");
    $fresh->execute([':id' => $runID]);
    $cfgNew = $fresh->fetch();
    $next   = docRunningResolveNextSeq($pdo, $cfgNew, $date, false);

    // ตั้งย้อนหลังต่ำกว่าเลขที่ใช้ไปแล้ว ระบบจะข้ามเลขที่ถูกใช้ไปเพื่อไม่ให้เอกสารซ้ำ
    $warning = null;
    if ($manual < $realMax) {
        $warning = 'เลขที่ตั้งไว้ต่ำกว่าเลขสูงสุดที่ใช้ไปแล้วในรอบนี้ (' . $realMax . ') '
                 . 'ระบบจะเลือกเลขว่างตัวแรกให้แทน คือ ' . $next . ' เพื่อไม่ให้เลขเอกสารซ้ำ';
    }

    echo json_encode([
        'success'   => true,
        'message'   => 'บันทึกเลขล่าสุดเรียบร้อย',
        'warning'   => $warning,
        'RealMax'   => $realMax,
        'ManualSeq' => $manual,
        'NextSeq'   => $next,
        'NextDocID' => docRunningFormat($cfgNew, $next, $date),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
