<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$draw   = (int)($_GET['draw']   ?? 1);
$start  = max(0, (int)($_GET['start']  ?? 0));
$length = (int)($_GET['length'] ?? 25);
if ($length <= 0 || $length > 200) {
    $length = 25;
}

$search = trim($_GET['search']['value'] ?? '');

$columnsMap = [
    0 => 'e.EmpID',
    1 => 'e.EmpName',
    2 => 's.SexName',
    3 => 'k.KName',
];
$orderColIdx = (int)($_GET['order'][0]['column'] ?? 0);
$orderDir    = strtolower($_GET['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$orderCol    = $columnsMap[$orderColIdx] ?? 'e.EmpID';

$pdo = getDB();

// Filter: เฉพาะ employee ที่มี selft_rep record
$baseSql = "FROM employee e
            LEFT JOIN sex  s ON s.SexNo = e.SexNo
            LEFT JOIN kate k ON k.KNo   = e.KNo
            WHERE EXISTS (
                SELECT 1 FROM selft_rep sr
                WHERE sr.EmpID = e.EmpID
                  AND sr.EmpID IS NOT NULL
                  AND sr.EmpID <> ''
            )";

$total = (int)$pdo->query("SELECT COUNT(*) " . $baseSql)->fetchColumn();

$where  = '';
$params = [];
if ($search !== '') {
    $where = " AND (e.EmpID LIKE :s1 OR e.EmpName LIKE :s2 OR k.KName LIKE :s3 OR s.SexName LIKE :s4)";
    $like = '%' . $search . '%';
    $params = [':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like];
}

$cntStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql . $where);
$cntStmt->execute($params);
$filtered = (int)$cntStmt->fetchColumn();

$sql = "SELECT e.EmpID, e.EmpName, s.SexName, k.KName "
     . $baseSql . $where
     . " ORDER BY $orderCol $orderDir"
     . " LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit',  $length, PDO::PARAM_INT);
$stmt->bindValue(':offset', $start,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = array_map(function ($r) {
    return [
        'EmpID'   => $r['EmpID'],
        'EmpName' => $r['EmpName'] ?? '',
        'SexName' => $r['SexName'] ?? '',
        'KName'   => $r['KName']   ?? '',
    ];
}, $rows);

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $total,
    'recordsFiltered' => $filtered,
    'data'            => $data,
], JSON_UNESCAPED_UNICODE);
