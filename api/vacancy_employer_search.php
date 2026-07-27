<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode(['data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();
$like = '%' . $q . '%';
$stmt = $pdo->prepare('SELECT * FROM vacancy_employer WHERE EmployerID LIKE :q1 OR EmployerName LIKE :q2 ORDER BY EmployerName LIMIT 10');
$stmt->execute(['q1' => $like, 'q2' => $like]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['data' => $rows], JSON_UNESCAPED_UNICODE);
