<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/vacancy_position_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$noticeId = (int)($_POST['NoticeID'] ?? 0);
if ($noticeId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสรายการไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$v = static fn($key) => trim((string)($_POST[$key] ?? ''));
$employerName = $v('EmployerName');
$positions = $_POST['positions'] ?? [];

if ($employerName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อสถานประกอบการ'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!is_array($positions) || count($positions) === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'กรุณาเพิ่มรายละเอียดตำแหน่งงานอย่างน้อย 1 รายการ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

$check = $pdo->prepare('SELECT 1 FROM vacancy_notice WHERE NoticeID = :id');
$check->execute(['id' => $noticeId]);
if (!$check->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare('UPDATE vacancy_notice SET
        EmployerID=:EmployerID, EmployerName=:EmployerName, BusinessType=:BusinessType, EmployeeCount=:EmployeeCount,
        EmployerPhone=:EmployerPhone, IssuedBy=:IssuedBy, BuildingName=:BuildingName, HouseNo=:HouseNo, Moo=:Moo,
        Soi=:Soi, Road=:Road, Subdistrict=:Subdistrict, District=:District, Province=:Province, PostalCode=:PostalCode,
        ContactName=:ContactName, ContactPosition=:ContactPosition
        WHERE NoticeID=:NoticeID');
    $upd->execute([
        'EmployerID' => $v('EmployerID') ?: null,
        'EmployerName' => $employerName,
        'BusinessType' => $v('BusinessType') ?: null,
        'EmployeeCount' => $v('EmployeeCount') !== '' ? (int)$v('EmployeeCount') : null,
        'EmployerPhone' => $v('EmployerPhone') ?: null,
        'IssuedBy' => $v('IssuedBy') ?: null,
        'BuildingName' => $v('BuildingName') ?: null,
        'HouseNo' => $v('HouseNo') ?: null,
        'Moo' => $v('Moo') ?: null,
        'Soi' => $v('Soi') ?: null,
        'Road' => $v('Road') ?: null,
        'Subdistrict' => $v('Subdistrict') ?: null,
        'District' => $v('District') ?: null,
        'Province' => $v('Province') ?: null,
        'PostalCode' => $v('PostalCode') ?: null,
        'ContactName' => $v('ContactName') ?: null,
        'ContactPosition' => $v('ContactPosition') ?: null,
        'NoticeID' => $noticeId,
    ]);

    $pdo->prepare('DELETE FROM vacancy_notice_position WHERE NoticeID = :id')->execute(['id' => $noticeId]);

    $detail = $pdo->prepare('INSERT INTO vacancy_notice_position (NoticeID,PositionMasterID,Headcount,Gender,AgeRange,Education,Wage,WorkSchedule,MilitaryStatus,Conditions,ExpireDate,Remark) VALUES (:NoticeID,:PositionMasterID,:Headcount,:Gender,:AgeRange,:Education,:Wage,:WorkSchedule,:MilitaryStatus,:Conditions,:ExpireDate,:Remark)');
    foreach ($positions as $p) {
        if (!is_array($p) || trim((string)($p['PositionName'] ?? '')) === '') {
            throw new InvalidArgumentException('กรุณากรอกชื่อตำแหน่งให้ครบถ้วน');
        }
        $detail->execute([
            'NoticeID' => $noticeId,
            'PositionMasterID' => resolvePositionMasterId($pdo, $p['PositionName']),
            'Headcount' => max(1, (int)($p['Headcount'] ?? 1)),
            'Gender' => trim((string)($p['Gender'] ?? '')) ?: null,
            'AgeRange' => trim((string)($p['AgeRange'] ?? '')) ?: null,
            'Education' => trim((string)($p['Education'] ?? '')) ?: null,
            'Wage' => trim((string)($p['Wage'] ?? '')) ?: null,
            'WorkSchedule' => trim((string)($p['WorkSchedule'] ?? '')) ?: null,
            'MilitaryStatus' => trim((string)($p['MilitaryStatus'] ?? '')) ?: null,
            'Conditions' => trim((string)($p['Conditions'] ?? '')) ?: null,
            'ExpireDate' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($p['ExpireDate'] ?? '')) ? $p['ExpireDate'] : null,
            'Remark' => trim((string)($p['Remark'] ?? '')) ?: null,
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'บันทึกการแก้ไขเรียบร้อย'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
