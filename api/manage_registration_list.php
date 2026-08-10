<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ GET'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = trim($_GET['type'] ?? 'all'); // all, register, selfrep
$rDate = trim($_GET['rDate'] ?? date('Y-m-d'));
$searchEmpID = trim($_GET['empID'] ?? '');
$searchEmpName = trim($_GET['empName'] ?? '');

try {
    $pdo = getDB();

    if ($type === 'register' || $type === 'all') {
        $sql = "
            SELECT
                'register' as type,
                r.DocNo,
                r.DocID,
                e.EmpID,
                e.EmpName,
                r.RDate,
                k.KName,
                r.SDate
            FROM register r
            LEFT JOIN employee e ON r.EmpID = e.EmpID
            LEFT JOIN kate k ON r.KNo = k.KNo
            WHERE DATE(r.RDate) = DATE(:rDate)
        ";

        $params = [':rDate' => $rDate];

        if ($searchEmpID) {
            $sql .= " AND e.EmpID LIKE :empID";
            $params[':empID'] = "%$searchEmpID%";
        }
        if ($searchEmpName) {
            $sql .= " AND e.EmpName LIKE :empName";
            $params[':empName'] = "%$searchEmpName%";
        }

        $sql .= " ORDER BY r.RDate DESC, r.DocNo DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registerData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $registerData = [];
    }

    if ($type === 'selfrep' || $type === 'all') {
        $sql = "
            SELECT
                'selfrep' as type,
                s.DocNo,
                s.DocID,
                e.EmpID,
                e.EmpName,
                s.RDate,
                k.KName,
                s.SDate
            FROM selft_rep s
            LEFT JOIN employee e ON s.EmpID = e.EmpID
            LEFT JOIN kate k ON s.KNo = k.KNo
            WHERE DATE(s.RDate) = DATE(:rDate)
        ";

        $params = [':rDate' => $rDate];

        if ($searchEmpID) {
            $sql .= " AND e.EmpID LIKE :empID";
            $params[':empID'] = "%$searchEmpID%";
        }
        if ($searchEmpName) {
            $sql .= " AND e.EmpName LIKE :empName";
            $params[':empName'] = "%$searchEmpName%";
        }

        $sql .= " ORDER BY s.RDate DESC, s.DocNo DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $selfrepData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $selfrepData = [];
    }

    $data = array_merge($registerData, $selfrepData);

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
