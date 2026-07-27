<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$empID = trim($_GET['id'] ?? '');

if (!preg_match('/^\d{13}$/', $empID)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'เลขบัตรไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

// Get employee info
$empStmt = $pdo->prepare(
    "SELECT e.EmpID, e.EmpName, e.Phone, e.Address, e.SexNo,
            t.Title AS TitleName
     FROM employee e
     LEFT JOIN titles t ON t.TitleNo = e.Titles
     WHERE e.EmpID = :id"
);
$empStmt->execute([':id' => $empID]);
$emp = $empStmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ลงทะเบียนในระบบ'], JSON_UNESCAPED_UNICODE);
    exit;
}

// map employee.SexNo (1=ชาย, 2=หญิง) -> ค่าที่ฟอร์ม job_placement ใช้ (Male/Female)
$sexToGender = [1 => 'Male', 2 => 'Female'];
$gender = isset($sexToGender[(int)($emp['SexNo'] ?? 0)]) ? $sexToGender[(int)$emp['SexNo']] : null;

// Get latest education — check register (ขึ้นทะเบียน) and selft_rep (รายงานตัวว่างงาน)
$edu = null;
$eduDate = '';

// Check register first
$rStmt = $pdo->prepare(
    "SELECT r.EqNo, edu.EqName, r.RDate
     FROM register r
     LEFT JOIN educational_qualification edu ON edu.EqNo = r.EqNo
     WHERE r.EmpID = :id AND r.EqNo IS NOT NULL
     ORDER BY r.RDate DESC
     LIMIT 1"
);
$rStmt->execute([':id' => $empID]);
$rEdu = $rStmt->fetch(PDO::FETCH_ASSOC);
if ($rEdu) {
    $edu = $rEdu;
    $eduDate = $rEdu['RDate'] ?? '';
}

// Check selft_rep
$sStmt = $pdo->prepare(
    "SELECT sr.EqNo, edu.EqName, sr.RDate
     FROM selft_rep sr
     LEFT JOIN educational_qualification edu ON edu.EqNo = sr.EqNo
     WHERE sr.EmpID = :id AND sr.EqNo IS NOT NULL
     ORDER BY sr.RDate DESC
     LIMIT 1"
);
$sStmt->execute([':id' => $empID]);
$sEdu = $sStmt->fetch(PDO::FETCH_ASSOC);
if ($sEdu && ($edu === null || ($sEdu['RDate'] ?? '') > $eduDate)) {
    $edu = $sEdu;
}

echo json_encode([
    'success'  => true,
    'data'     => [
        'EmpID'     => $emp['EmpID'],
        'EmpName'   => $emp['EmpName'] ?? '',
        'TitleName' => $emp['TitleName'] ?? '',
        'Gender'    => $gender,
        'Phone'     => $emp['Phone']   ?? '',
        'Address'   => $emp['Address'] ?? '',
        'EduNo'     => $edu ? (int)$edu['EqNo']   : null,
        'EduName'   => $edu ? $edu['EqName']       : null,
    ],
], JSON_UNESCAPED_UNICODE);