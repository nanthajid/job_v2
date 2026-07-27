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

$search   = trim($_GET['search']['value'] ?? '');
$dateFrom = trim($_GET['dateFrom'] ?? '');
$dateTo   = trim($_GET['dateTo'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $dateFrom = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $dateTo = '';
}

$pdo = getDB();

$baseSql = "FROM vacancy_notice n WHERE 1=1";

$total = (int)$pdo->query("SELECT COUNT(*) " . $baseSql)->fetchColumn();

$where  = '';
$params = [];
if ($search !== '') {
    $where .= " AND (n.EmployerName LIKE :s1 OR n.ContactName LIKE :s2 OR n.EmployerPhone LIKE :s3)";
    $like = '%' . $search . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
}
if ($dateFrom !== '') {
    $where .= " AND n.CreatedAt >= :dateFrom";
    $params[':dateFrom'] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where .= " AND n.CreatedAt <= :dateTo";
    $params[':dateTo'] = $dateTo . ' 23:59:59';
}

$cntStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql . $where);
$cntStmt->execute($params);
$filtered = (int)$cntStmt->fetchColumn();

$sql = "SELECT n.NoticeID, n.EmployerNo, n.EmployerName, n.ContactName, n.EmployerPhone, n.CreatedAt,
               COUNT(p.PositionID) PositionCount, COALESCE(SUM(p.Headcount),0) TotalHeadcount
        FROM vacancy_notice n
        LEFT JOIN vacancy_notice_position p ON p.NoticeID = n.NoticeID
        WHERE 1=1" . $where . "
        GROUP BY n.NoticeID
        ORDER BY n.NoticeID DESC
        LIMIT :limit OFFSET :offset";

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
        'NoticeID'       => (int)$r['NoticeID'],
        'EmployerNo'     => $r['EmployerNo'] ? (int)$r['EmployerNo'] : null,
        'EmployerName'   => $r['EmployerName']   ?? '',
        'ContactName'    => $r['ContactName']    ?? '',
        'EmployerPhone'  => $r['EmployerPhone']  ?? '',
        'CreatedAt'      => $r['CreatedAt']      ?? '',
        'PositionCount'  => (int)$r['PositionCount'],
        'TotalHeadcount' => (int)$r['TotalHeadcount'],
    ];
}, $rows);

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => $total,
    'recordsFiltered' => $filtered,
    'data'            => $data,
], JSON_UNESCAPED_UNICODE);
