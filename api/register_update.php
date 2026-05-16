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

$docNo = (int)($_POST['DocNo'] ?? 0);
$rDate = trim($_POST['RDate'] ?? '');
$kNo   = trim($_POST['KNo']   ?? '');
$sexNo = trim($_POST['SexNo'] ?? '');
$qNo   = trim($_POST['QNo']   ?? '');
$stID  = trim($_POST['StID']  ?? '');

$errors = [];
if ($docNo <= 0) {
    $errors[] = 'เลขที่เอกสารไม่ถูกต้อง';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rDate)) {
    $errors[] = 'รูปแบบวันที่ไม่ถูกต้อง';
} else {
    [$y, $m, $d] = explode('-', $rDate);
    if (!checkdate((int)$m, (int)$d, (int)$y)) {
        $errors[] = 'วันที่ไม่ถูกต้อง';
    }
}
if (!ctype_digit($kNo) || (int)$kNo <= 0) {
    $errors[] = 'กรุณาเลือกเขต';
}
if (!ctype_digit($sexNo) || (int)$sexNo <= 0) {
    $errors[] = 'กรุณาเลือกเพศ';
}
if (!ctype_digit($qNo) || (int)$qNo <= 0) {
    $errors[] = 'กรุณาเลือกสาเหตุที่ออกจากงาน';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

// ตรวจ FK
$fkChecks = [
    ['t' => 'kate', 'k' => 'KNo',   'v' => (int)$kNo,   'msg' => 'ไม่พบเขตที่เลือก'],
    ['t' => 'sex',  'k' => 'SexNo', 'v' => (int)$sexNo, 'msg' => 'ไม่พบเพศที่เลือก'],
    ['t' => 'quit', 'k' => 'QNo',   'v' => (int)$qNo,   'msg' => 'ไม่พบสาเหตุที่เลือก'],
];
if ($stID !== '') {
    $fkChecks[] = ['t' => 'staft', 'k' => 'StID',  'v' => $stID, 'msg' => 'ไม่พบเจ้าหน้าที่ที่เลือก'];
}

foreach ($fkChecks as $fk) {
    $st = $pdo->prepare("SELECT 1 FROM {$fk['t']} WHERE {$fk['k']} = :v");
    $st->execute([':v' => $fk['v']]);
    if (!$st->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $fk['msg']], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ตรวจ record มีจริง
$exists = $pdo->prepare("SELECT 1 FROM register WHERE DocNo = :doc");
$exists->execute([':doc' => $docNo]);
if (!$exists->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเอกสารที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $upd = $pdo->prepare(
        "UPDATE register
         SET RDate = :rdate, KNo = :kno, SexNo = :sex, QNo = :qno, 
             StID = COALESCE(NULLIF(:stid, ''), StID)
         WHERE DocNo = :doc"
    );
    $upd->execute([
        ':rdate' => $rDate,
        ':kno'   => (int)$kNo,
        ':sex'   => (int)$sexNo,
        ':qno'   => (int)$qNo,
        ':stid'  => $stID,
        ':doc'   => $docNo,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'บันทึกการแก้ไขเรียบร้อย',
        'data'    => [
            'DocNo' => $docNo,
            'RDate' => $rDate,
            'KNo'   => (int)$kNo,
            'SexNo' => (int)$sexNo,
            'QNo'   => (int)$qNo,
            'StID'  => $stID,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
