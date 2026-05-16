<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$id = trim($_GET['id'] ?? '');
if (!preg_match('/^\d{13}$/', $id)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รูปแบบเลขบัตรประชาชนไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT e.EmpID, e.Titles, t.Title AS TitleName, e.EmpName, e.SexNo, s.SexName, e.KNo, k.KName,
            e.Phone, e.lineID, e.Address, e.SDate
     FROM employee e
     LEFT JOIN titles t ON t.DocNo = e.Titles
     LEFT JOIN sex    s ON s.SexNo = e.SexNo
     LEFT JOIN kate   k ON k.KNo   = e.KNo
     WHERE e.EmpID = :id"
);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ลงทะเบียน'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ดึงประวัติการขึ้นทะเบียน
$stReg = $pdo->prepare(
    "SELECT r.DocID, r.RDate, q.QName, k.KName
     FROM register r
     LEFT JOIN quit q ON q.QNo = r.QNo
     LEFT JOIN kate k ON k.KNo = r.KNo
     WHERE r.EmpID = :id
     ORDER BY r.RDate DESC, r.DocNo DESC"
);
$stReg->execute([':id' => $id]);
$row['history'] = $stReg->fetchAll(PDO::FETCH_ASSOC);

// ดึงประวัติการรายงานตัว
$stRep = $pdo->prepare(
    "SELECT sr.DocNo, sr.RDate, q.QName, j.JName, k.KName
     FROM selft_rep sr
     LEFT JOIN quit q ON q.QNo = sr.QNo
     LEFT JOIN job  j ON j.JNo = sr.JNo
     LEFT JOIN kate k ON k.KNo = sr.KNo
     WHERE sr.EmpID = :id
     ORDER BY sr.RDate DESC, sr.DocNo DESC"
);
$stRep->execute([':id' => $id]);
$row['reporting_history'] = $stRep->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
