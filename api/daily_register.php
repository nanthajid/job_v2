<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$dateFrom = (isset($_GET['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from']))
    ? $_GET['date_from']
    : date('Y-m-01');

$dateTo = (isset($_GET['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to']))
    ? $_GET['date_to']
    : date('Y-m-d');

if ($dateFrom > $dateTo) {
    echo json_encode(['success' => false, 'message' => 'date_from must be <= date_to']);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT RDate AS day, COUNT(EmpID) AS cnt
     FROM register
     WHERE RDate >= :from AND RDate <= :to
     GROUP BY RDate
     ORDER BY RDate ASC"
);
$stmt->execute([':from' => $dateFrom, ':to' => $dateTo]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countMap = [];
foreach ($rows as $row) {
    $countMap[$row['day']] = (int)$row['cnt'];
}

// Fill every date in the range so the chart has no gaps
$result = [];
$current = new DateTime($dateFrom);
$end     = new DateTime($dateTo);
while ($current <= $end) {
    $d        = $current->format('Y-m-d');
    $result[] = ['day' => $d, 'cnt' => $countMap[$d] ?? 0];
    $current->modify('+1 day');
}

echo json_encode(['success' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
