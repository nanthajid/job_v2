<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT EmpID, Titles, EmpName, KNo, SexNo, Phone, lineID, Address
     FROM employee
     WHERE EmpID LIKE :q
     ORDER BY EmpID
     LIMIT 15"
);
$stmt->execute([':q' => $q . '%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(function ($r) {
    $name  = $r['EmpName'] ?? '';
    $label = $r['EmpID'] . ($name !== '' ? ' — ' . $name : '');
    return [
        'label'   => $label,
        'value'   => $r['EmpID'],
        'EmpID'   => $r['EmpID'],
        'Titles'  => $r['Titles']  !== null ? (int)$r['Titles']  : null,
        'EmpName' => $name,
        'KNo'     => $r['KNo']     !== null ? (int)$r['KNo']     : null,
        'SexNo'   => $r['SexNo']   !== null ? (int)$r['SexNo']   : null,
        'Phone'   => $r['Phone']   ?? '',
        'lineID'  => $r['lineID']  ?? '',
        'Address' => $r['Address'] ?? '',
    ];
}, $rows);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
