<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/alien_insured_checkin_helper.php';

$pdo    = getDB();
$q      = trim((string)($_GET['q'] ?? ''));
$from   = alienInsuredParseDate($_GET['from'] ?? '');
$to     = alienInsuredParseDate($_GET['to'] ?? '');
$limit  = min(500, max(1, (int)($_GET['limit'] ?? 100)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$where  = [];
$params = [];
if ($q !== '') {
    // ใช้ placeholder แยกกัน เพราะ PDO แบบ native prepare ใช้ชื่อซ้ำในคำสั่งเดียวไม่ได้
    $where[]         = 'EXISTS (SELECT 1 FROM ' . ALIEN_CHECKIN_PERSON_TABLE . ' p WHERE p.DocID = d.DocID AND (p.FullName LIKE :qName OR p.SsoCardNo LIKE :qCard))';
    $params['qName'] = '%' . $q . '%';
    $params['qCard'] = '%' . $q . '%';
}
if ($from !== null) {
    $where[]        = 'd.DocDate >= :from';
    $params['from'] = $from;
}
if ($to !== null) {
    $where[]      = 'd.DocDate <= :to';
    $params['to'] = $to;
}
// เอกสารที่ยังไม่มีรายชื่อ (บันทึกหัวเอกสารค้างไว้) — เปิดสวิตช์นี้เพื่อไม่ให้มาเกะกะหัวตาราง
if (!empty($_GET['has_persons'])) {
    $where[] = 'EXISTS (SELECT 1 FROM ' . ALIEN_CHECKIN_PERSON_TABLE . ' hp WHERE hp.DocID = d.DocID)';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// จำนวนเอกสารทั้งหมดที่ตรงตัวกรอง — หน้าเว็บใช้บอกว่าโหลดมาแล้วกี่ฉบับจากทั้งหมดเท่าไร
$cStmt = $pdo->prepare('SELECT COUNT(*) FROM ' . ALIEN_CHECKIN_DOC_TABLE . ' d' . $whereSql);
$cStmt->execute($params);
$total = (int) $cStmt->fetchColumn();

$stmt = $pdo->prepare('SELECT d.* FROM ' . ALIEN_CHECKIN_DOC_TABLE . ' d' . $whereSql
    . ' ORDER BY d.DocDate DESC, d.DocID DESC LIMIT ' . $limit . ' OFFSET ' . $offset);
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($docs) {
    $ids         = array_column($docs, 'DocID');
    $placeholder = implode(',', array_fill(0, count($ids), '?'));
    $pStmt       = $pdo->prepare('SELECT * FROM ' . ALIEN_CHECKIN_PERSON_TABLE . ' WHERE DocID IN (' . $placeholder . ') ORDER BY DocID ASC, SeqNo ASC, PersonID ASC');
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

/**
 * เลขบัตร ปกส. ที่รายงานตัวมากกว่าหนึ่งครั้ง — ต้องนับจากข้อมูลทั้งหมด ไม่ใช่เฉพาะหน้าที่โหลด
 * keepPersonID = แถวของเอกสารฉบับล่าสุด ใช้เป็นตัวแทนเมื่อผู้ใช้เลือกยุบแถวซ้ำ
 */
$dupStmt = $pdo->query(
    "SELECT p.SsoCardNo,
            COUNT(*) AS cnt,
            CAST(SUBSTRING_INDEX(GROUP_CONCAT(p.PersonID ORDER BY d.DocDate DESC, d.DocID DESC), ',', 1) AS UNSIGNED) AS keepPersonID
     FROM " . ALIEN_CHECKIN_PERSON_TABLE . " p
     JOIN " . ALIEN_CHECKIN_DOC_TABLE . " d ON d.DocID = p.DocID
     WHERE p.SsoCardNo IS NOT NULL AND p.SsoCardNo <> ''
     GROUP BY p.SsoCardNo
     HAVING COUNT(*) > 1"
);

$duplicates = [];
foreach ($dupStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $duplicates[$row['SsoCardNo']] = [
        'count'        => (int) $row['cnt'],
        'keepPersonID' => (int) $row['keepPersonID'],
    ];
}

echo json_encode([
    'success'    => true,
    'data'       => $docs,
    'total'      => $total,
    'offset'     => $offset,
    'limit'      => $limit,
    'duplicates' => (object) $duplicates,
], JSON_UNESCAPED_UNICODE);
