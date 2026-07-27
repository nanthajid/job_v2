<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$like = '%' . $q . '%';

$stmt = $pdo->prepare(
    "SELECT DISTINCT jp.EmpID, jp.EmpName, jp.Phone, jp.Address, jp.Age, jp.EduNo, edu.EqName AS EduName
     FROM job_placement jp
     LEFT JOIN educational_qualification edu ON edu.EqNo = jp.EduNo
     WHERE jp.EmpID LIKE :s1
        OR jp.EmpName LIKE :s2
        OR jp.CompanyName LIKE :s3
        OR jp.Phone LIKE :s4
     ORDER BY jp.EmpName
     LIMIT 20"
);
$stmt->execute([
    ':s1' => $like,
    ':s2' => $like,
    ':s3' => $like,
    ':s4' => $like,
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = array_map(function ($r) {
    return [
        'EmpID'   => $r['EmpID']   ?? '',
        'EmpName' => $r['EmpName'] ?? '',
        'Phone'   => $r['Phone']   ?? '',
        'Address' => $r['Address'] ?? '',
        'Age'     => $r['Age']     ? (int)$r['Age'] : null,
        'EduNo'   => $r['EduNo']   ? (int)$r['EduNo'] : null,
        'EduName' => $r['EduName'] ?? '',
    ];
}, $rows);

echo json_encode($data, JSON_UNESCAPED_UNICODE);