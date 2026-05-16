<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$docNo = (int)($_GET['doc'] ?? 0);
if ($docNo <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รูปแบบเลขที่เอกสารไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    "SELECT r.DocNo, r.DocID, r.RDate, r.SDate,
            r.EmpID, e.EmpName, e.Phone, e.lineID, e.Address,
            r.KNo, k.KName,
            r.SexNo, s.SexName,
            r.QNo, q.QName,
            r.JNo, j.JName,
            r.StID, st.StName
     FROM selft_rep r
     LEFT JOIN employee e ON e.EmpID  = r.EmpID
     LEFT JOIN kate     k ON k.KNo    = r.KNo
     LEFT JOIN sex      s ON s.SexNo  = r.SexNo
     LEFT JOIN quit     q ON q.QNo    = r.QNo
     LEFT JOIN job      j ON j.JNo    = r.JNo
     LEFT JOIN staft    st ON st.StID = r.StID
     WHERE r.DocNo = :doc"
);
$stmt->execute([':doc' => $docNo]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'ไม่พบเอกสารที่ระบุ'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
