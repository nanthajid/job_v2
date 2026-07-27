<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = getDB();

  $jno = isset($_GET['jno']) ? (int)$_GET['jno'] : null;
  $dateFrom = isset($_GET['dateFrom']) ? trim($_GET['dateFrom']) : null;
  $dateTo = isset($_GET['dateTo']) ? trim($_GET['dateTo']) : null;
  $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
  $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
  $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;

  $query = "SELECT sr.DocNo, sr.DocID, sr.RDate, sr.EmpID, e.EmpName, sr.QNo, sr.JNo,
                   k.KName, q.QName, j.JName
             FROM selft_rep sr
             LEFT JOIN employee e ON sr.EmpID = e.EmpID
            LEFT JOIN kate k ON sr.KNo = k.KNo
            LEFT JOIN quit q ON sr.QNo = q.QNo
            LEFT JOIN job j ON sr.JNo = j.JNo
            WHERE 1=1";

  $params = [];

  if ($jno) {
    $query .= " AND sr.JNo = ?";
    $params[] = $jno;
  }

  if ($dateFrom) {
    $query .= " AND sr.RDate >= ?";
    $params[] = $dateFrom;
  }

  if ($dateTo) {
    $query .= " AND sr.RDate <= ?";
    $params[] = $dateTo;
  }

  $query .= " ORDER BY sr.DocNo DESC LIMIT ? OFFSET ?";
  $params[] = $length;
  $params[] = $start;

  $stmt = $pdo->prepare($query);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // For DataTables format
  if (isset($_GET['draw'])) {
    $countQuery = "SELECT COUNT(*) as total FROM selft_rep sr WHERE 1=1";
    $countParams = [];
    if ($jno) {
      $countQuery .= " AND sr.JNo = ?";
      $countParams[] = $jno;
    }
    if ($dateFrom) {
      $countQuery .= " AND sr.RDate >= ?";
      $countParams[] = $dateFrom;
    }
    if ($dateTo) {
      $countQuery .= " AND sr.RDate <= ?";
      $countParams[] = $dateTo;
    }
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
      'draw' => $draw,
      'recordsTotal' => $total,
      'recordsFiltered' => $total,
      'data' => $rows
    ], JSON_UNESCAPED_UNICODE);
  } else {
    // For non-DataTables usage (like selfrep.php)
    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
