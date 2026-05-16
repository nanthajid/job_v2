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
    0 => 'r.DocID',
    1 => 'r.EmpID',
    2 => 'e.EmpName',
    3 => 'r.RDate',
    4 => 'k.KName',
    5 => 'q.QName',
    6 => 'j.JName',
];
$orderColIdx = (int)($_GET['order'][0]['column'] ?? 3);
$orderDir    = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$orderCol    = $columnsMap[$orderColIdx] ?? 'r.RDate';

$pdo = getDB();

$baseSql = "FROM selft_rep r
            LEFT JOIN employee e ON e.EmpID = r.EmpID
            LEFT JOIN kate     k ON k.KNo   = r.KNo
            LEFT JOIN quit     q ON q.QNo   = r.QNo
            LEFT JOIN job      j ON j.JNo   = r.JNo
            WHERE r.EmpID IS NOT NULL AND r.EmpID <> ''";

$total = (int)$pdo->query("SELECT COUNT(*) " . $baseSql)->fetchColumn();

$where  = '';
$params = [];
if ($search !== '') {
    $where = " AND (r.EmpID LIKE :s1
                 OR e.EmpName LIKE :s2
                 OR r.RDate LIKE :s3
                 OR k.KName LIKE :s4
                 OR q.QName LIKE :s5
                 OR j.JName LIKE :s6
                 OR r.DocID LIKE :s7)";
    $like = '%' . $search . '%';
    $params = [
        ':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like,
        ':s5' => $like, ':s6' => $like, ':s7' => $like,
    ];
}

$cntStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql . $where);
$cntStmt->execute($params);
$filtered = (int)$cntStmt->fetchColumn();

$sql = "SELECT r.DocNo, r.DocID, r.EmpID, e.EmpName, r.RDate, k.KName, q.QName, j.JName "
     . $baseSql . $where
     . " ORDER BY $orderCol $orderDir, r.DocNo DESC"
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
        'DocNo'   => (int)$r['DocNo'],
        'DocID'   => $r['DocID']   ?? '',
        'EmpID'   => $r['EmpID']   ?? '',
        'EmpName' => $r['EmpName'] ?? '',
        'RDate'   => $r['RDate']   ?? '',
        'KName'   => $r['KName']   ?? '',
        'QName'   => $r['QName']   ?? '',
        'JName'   => $r['JName']   ?? '',
    ];
}, $rows);

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $total,
    'recordsFiltered' => $filtered,
    'data'            => $data,
], JSON_UNESCAPED_UNICODE);
