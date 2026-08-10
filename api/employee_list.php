<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ GET'], JSON_UNESCAPED_UNICODE);
    exit;
}

$empID = trim($_GET['empID'] ?? '');
$empName = trim($_GET['empName'] ?? '');

try {
    $pdo = getDB();

    $sql = "
        SELECT
            e.EmpID,
            e.EmpName,
            e.KNo,
            k.KName
        FROM employee e
        LEFT JOIN kate k ON e.KNo = k.KNo
        WHERE 1=1
    ";

    $params = [];

    if ($empID) {
        $sql .= " AND e.EmpID LIKE :empID";
        $params[':empID'] = "%$empID%";
    }

    if ($empName) {
        $sql .= " AND e.EmpName LIKE :empName";
        $params[':empName'] = "%$empName%";
    }

    $sql .= " ORDER BY e.EmpID DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
