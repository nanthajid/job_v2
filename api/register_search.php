<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$empID = trim($_GET['empID'] ?? '');
$empName = trim($_GET['empName'] ?? '');

if ($empID === '' && $empName === '') {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกเลขบัตรประชาชน หรือ ชื่อ-นามสกุล'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

$sql = "SELECT r.DocNo, r.DocID, r.EmpID, t.Title AS TitleName, e.EmpName, r.RDate, k.KName, q.QName
        FROM register r
        LEFT JOIN employee e ON e.EmpID = r.EmpID
        LEFT JOIN titles t ON t.TitleNo = e.Titles
        LEFT JOIN kate k ON k.KNo = r.KNo
        LEFT JOIN quit q ON q.QNo = r.QNo
        WHERE r.EmpID IS NOT NULL AND r.EmpID <> ''";

$params = [];

if ($empID !== '') {
    $sql .= " AND r.EmpID LIKE :empID";
    $params[':empID'] = $empID . '%';
}

if ($empName !== '') {
    $sql .= " AND e.EmpName LIKE :empName";
    $params[':empName'] = '%' . $empName . '%';
}

$sql .= " ORDER BY r.RDate DESC, r.DocNo DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = array_map(function ($r) {
    return [
        'DocNo'   => (int)$r['DocNo'],
        'DocID'   => $r['DocID'] ?? '',
        'EmpID'   => $r['EmpID'] ?? '',
        'TitleName' => $r['TitleName'] ?? '',
        'EmpName' => (($r['TitleName'] ? $r['TitleName'] . ' ' : '') . ($r['EmpName'] ?? '')),
        'RDate'   => $r['RDate'] ?? '',
        'KName'   => $r['KName'] ?? '',
        'QName'   => $r['QName'] ?? '',
    ];
}, $rows);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
