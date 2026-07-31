<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/alien_insured_helper.php';

$pdo   = getDB();
$q     = trim((string)($_GET['q'] ?? ''));
$from  = alienInsuredParseDate($_GET['from'] ?? '');
$to    = alienInsuredParseDate($_GET['to'] ?? '');
$limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));

$where  = [];
$params = [];
if ($q !== '') {
    // ใช้ placeholder แยกกัน เพราะ PDO แบบ native prepare ใช้ชื่อซ้ำในคำสั่งเดียวไม่ได้
    $where[]           = 'EXISTS (SELECT 1 FROM alien_insured_person p WHERE p.DocID = d.DocID AND (p.FullName LIKE :qName OR p.SsoCardNo LIKE :qCard))';
    $params['qName']   = '%' . $q . '%';
    $params['qCard']   = '%' . $q . '%';
}
if ($from !== null) {
    $where[]        = 'd.DocDate >= :from';
    $params['from'] = $from;
}
if ($to !== null) {
    $where[]      = 'd.DocDate <= :to';
    $params['to'] = $to;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare('SELECT d.* FROM alien_insured_doc d' . $whereSql . ' ORDER BY d.DocDate DESC, d.DocID DESC LIMIT ' . $limit);
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($docs) {
    $ids         = array_column($docs, 'DocID');
    $placeholder = implode(',', array_fill(0, count($ids), '?'));
    $pStmt       = $pdo->prepare('SELECT * FROM alien_insured_person WHERE DocID IN (' . $placeholder . ') ORDER BY DocID ASC, SeqNo ASC, PersonID ASC');
    $pStmt->execute($ids);

    $grouped = [];
    foreach ($pStmt->fetchAll(PDO::FETCH_ASSOC) as $person) {
        $grouped[$person['DocID']][] = $person;
    }
    foreach ($docs as &$doc) {
        $doc['persons']     = $grouped[$doc['DocID']] ?? [];
        $doc['PersonCount'] = count($doc['persons']);
    }
    unset($doc);
}

echo json_encode(['success' => true, 'data' => $docs], JSON_UNESCAPED_UNICODE);
