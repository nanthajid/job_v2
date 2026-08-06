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
// ต้องเรียงดัชนีให้ตรงกับลำดับคอลัมน์ใน <thead> ของ job_placement.php
// 0=ลำดับ, 1=เลขบัตรประชาชน, 2=ชื่อ-นามสกุล, 3=อายุ, 4=สถานประกอบการ,
// 5=ตำแหน่ง, 6=วันที่เริ่มงาน, 7=จัดการ (คอลัมน์ 0 กับ 7 ไม่ให้เรียง)
$columnsMap = [
    1 => 'p.EmpID',
    2 => 'p.EmpName',
    3 => 'p.Age',
    4 => 'p.CompanyName',
    5 => 'p.Position',
    6 => 'p.StartDate',
];
$orderColIdx = (int)($_GET['order'][0]['column'] ?? 6);
$orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$orderCol = $columnsMap[$orderColIdx] ?? 'p.SourceDate';

$pdo = getDB();

// แสดงเฉพาะข้อมูลที่บันทึกไว้ในตาราง job_placement เท่านั้น
// (ไม่ดึงผู้ที่รายงานตัวว่าได้งาน selft_rep.JNo = 2 ขึ้นมาแสดงร่วมอีกต่อไป)
$baseSql = "FROM (
    SELECT
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

$sql = "SELECT p.JPNo, p.EmpID, p.EmpName, p.Age,
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