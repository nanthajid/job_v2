<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = getDB();
  $docNo = isset($_GET['doc']) ? (int)$_GET['doc'] : 0;

  if (!$docNo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'DocNo ไม่ถูกต้อง']);
    exit;
  }

  $query = "SELECT sr.DocNo, sr.DocID, sr.RDate, sr.QNo, sr.JNo, sr.EmpID,
                   e.EmpName, e.Titles, e.SexNo, e.Phone, e.lineID, e.Address, e.KNo, k.KName
            FROM selft_rep sr
            JOIN employee e ON sr.EmpID = e.EmpID
            LEFT JOIN kate k ON sr.KNo = k.KNo
            WHERE sr.DocNo = ?";

  $stmt = $pdo->prepare($query);
  $stmt->execute([$docNo]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$data) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล']);
    exit;
  }

  echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
