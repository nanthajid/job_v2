<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$draw = (int)($_GET['draw'] ?? 1);
$start = max(0, (int)($_GET['start'] ?? 0));
$length = (int)($_GET['length'] ?? 25);
if ($length <= 0 || $length > 200) {
    $length = 25;
}

$search = trim($_GET['search']['value'] ?? '');
$columnsMap = [
    1 => 'p.EmpID',
    2 => 'p.EmpName',
    3 => 'p.CompanyName',
    4 => 'p.Position',
    5 => 'p.StartDate',
];
$orderColIdx = (int)($_GET['order'][0]['column'] ?? 5);
$orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$orderCol = $columnsMap[$orderColIdx] ?? 'p.SourceDate';

$pdo = getDB();

// Keep all manually-created placement records visible.  Add people reported as
// employed this month (selft_rep.JNo = 2) when they do not have a placement yet.
$baseSql = "FROM (
    SELECT
        NULL AS SelfRepDocNo,
        jp.JPNo,
        jp.EmpID,
        COALESCE(NULLIF(CONCAT_WS(' ', NULLIF(t.Title, ''), NULLIF(e.EmpName, '')), ''), jp.EmpName, '') AS EmpName,
        jp.Age,
        jp.CompanyName,
        jp.Position,
        jp.StartDate,
        jp.CreateDate AS SourceDate
    FROM job_placement jp
    LEFT JOIN employee e ON e.EmpID = jp.EmpID
    LEFT JOIN titles t ON t.TitleNo = e.Titles

    UNION ALL

    SELECT
        sr.DocNo AS SelfRepDocNo,
        NULL AS JPNo,
        e.EmpID,
        CONCAT_WS(' ', NULLIF(t.Title, ''), NULLIF(e.EmpName, '')) AS EmpName,
        NULL AS Age,
        NULL AS CompanyName,
        NULL AS Position,
        NULL AS StartDate,
        sr.RDate AS SourceDate
    FROM selft_rep sr
    INNER JOIN employee e ON e.EmpID = sr.EmpID
    LEFT JOIN titles t ON t.TitleNo = e.Titles
    WHERE sr.JNo = 2
      AND sr.RDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND sr.RDate < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
      AND NOT EXISTS (
          SELECT 1
          FROM job_placement jp_exists
          WHERE jp_exists.EmpID = sr.EmpID
      )
) p
WHERE 1=1";

$total = (int)$pdo->query("SELECT COUNT(*) " . $baseSql)->fetchColumn();

$where = '';
$params = [];
if ($search !== '') {
    $where = " AND (p.EmpID LIKE :s1
                 OR p.EmpName LIKE :s2
                 OR p.CompanyName LIKE :s3
                 OR p.Position LIKE :s4
                 OR p.StartDate LIKE :s5)";
    $like = '%' . $search . '%';
    $params = [
        ':s1' => $like,
        ':s2' => $like,
        ':s3' => $like,
        ':s4' => $like,
        ':s5' => $like,
    ];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) " . $baseSql . $where);
$countStmt->execute($params);
$filtered = (int)$countStmt->fetchColumn();

$sql = "SELECT p.SelfRepDocNo, p.JPNo, p.EmpID, p.EmpName, p.Age,
               p.CompanyName, p.Position, p.StartDate
        " . $baseSql . $where
     . " ORDER BY $orderCol $orderDir, p.SourceDate DESC, p.JPNo DESC
         LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $length, PDO::PARAM_INT);
$stmt->bindValue(':offset', $start, PDO::PARAM_INT);
$stmt->execute();

$data = array_map(static function (array $row): array {
    return [
        'SelfRepDocNo' => $row['SelfRepDocNo'] !== null ? (int)$row['SelfRepDocNo'] : null,
        'JPNo' => $row['JPNo'] !== null ? (int)$row['JPNo'] : null,
        'EmpID' => $row['EmpID'] ?? '',
        'EmpName' => $row['EmpName'] ?? '',
        'Age' => $row['Age'] !== null ? (int)$row['Age'] : null,
        'CompanyName' => $row['CompanyName'] ?? '',
        'Position' => $row['Position'] ?? '',
        'StartDate' => $row['StartDate'] ?? '',
    ];
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $filtered,
    'data' => $data,
], JSON_UNESCAPED_UNICODE);