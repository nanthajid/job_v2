<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = getDB();

  $empID = isset($_GET['empID']) ? trim($_GET['empID']) : '';
  $empName = isset($_GET['empName']) ? trim($_GET['empName']) : '';

  if (empty($empID) && empty($empName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกเลขบัตรหรือชื่อ']);
    exit;
  }

  $query = "SELECT sr.DocNo, sr.DocID, sr.RDate, e.EmpID, e.EmpName, k.KName
            FROM selft_rep sr
            JOIN employee e ON sr.EmpID = e.EmpID
            LEFT JOIN kate k ON sr.KNo = k.KNo
            WHERE 1=1";

  $params = [];

  if (!empty($empID)) {
    $query .= " AND e.EmpID = ?";
    $params[] = $empID;
  }

  if (!empty($empName)) {
    $query .= " AND e.EmpName LIKE ?";
    $params[] = '%' . $empName . '%';
  }

  $query .= " ORDER BY sr.DocNo DESC LIMIT 100";

  $stmt = $pdo->prepare($query);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
