<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$jpNo = (int)($_GET['id'] ?? 0);
if ($jpNo <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสรายการไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare(
      "SELECT jp.JPNo, jp.EmpID, jp.EmpName, jp.Gender, jp.Age, jp.EduNo, edu.EqName AS EduName,
              jp.Address, jp.Phone,
              jp.CompanyName, jp.CompanyAddress, jp.Position,
              jp.IncomeDay, jp.IncomeMonth, jp.StartDate, jp.CreateDate,
              jp.ServiceType, jp.StaffService,
              jp.StID, st.StName
     FROM job_placement jp
     LEFT JOIN educational_qualification edu ON edu.EqNo = jp.EduNo
     LEFT JOIN staff st ON st.StID = jp.StID
     WHERE jp.JPNo = :id"
);
$stmt->execute([':id' => $jpNo]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [
    'JPNo'          => (int)$row['JPNo'],
    'EmpID'         => $row['EmpID']         ?? '',
    'EmpName'       => $row['EmpName']       ?? '',
    'Gender'        => $row['Gender']        ?? null,
    'Age'           => $row['Age']           ? (int)$row['Age'] : null,
    'EduNo'         => $row['EduNo']         ? (int)$row['EduNo'] : null,
    'EduName'       => $row['EduName']       ?? '',
    'Address'       => $row['Address']       ?? '',
    'Phone'         => $row['Phone']         ?? '',
    'CompanyName'   => $row['CompanyName']   ?? '',
    'CompanyAddress'=> $row['CompanyAddress'] ?? '',
    'Position'      => $row['Position']      ?? '',
    'IncomeDay'     => $row['IncomeDay']     ? (float)$row['IncomeDay'] : null,
    'IncomeMonth'   => $row['IncomeMonth']   ? (float)$row['IncomeMonth'] : null,
    'StartDate'     => $row['StartDate']     ?? '',
    'CreateDate'    => $row['CreateDate']    ?? '',
    'StID'          => $row['StID']          ? (int)$row['StID'] : null,
    'StName'        => $row['StName']        ?? '',
    'ServiceType'   => $row['ServiceType']   ?? '',
    'StaffService'  => $row['StaffService']  ?? '',
];

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);