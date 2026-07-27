<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$results = [];

if (strlen($q) >= 1) {
    $pdo = getDB();
    $search = '%' . $q . '%';

    $stmt = $pdo->prepare("SELECT EmpID, EmpName, Titles, SexNo, Phone FROM employee WHERE EmpID LIKE ? OR EmpName LIKE ? LIMIT 15");
    $stmt->execute([$search, $search]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $empId = $row['EmpID'];

        // Get title name
        $title = '';
        if ($row['Titles']) {
            try {
                $t = $pdo->query("SELECT Title FROM titles WHERE TitleNo = " . intval($row['Titles']));
                $t_row = $t->fetch(PDO::FETCH_ASSOC);
                $title = $t_row['Title'] ?? '';
            } catch (Exception $e) {}
        }

        // Get sex name
        $sexName = '';
        if ($row['SexNo']) {
            try {
                $s = $pdo->query("SELECT SexName FROM sex WHERE SexNo = " . intval($row['SexNo']));
                $s_row = $s->fetch(PDO::FETCH_ASSOC);
                $sexName = $s_row['SexName'] ?? '';
            } catch (Exception $e) {}
        }

        // Get latest address from job_placement
        $address = '';
        try {
            $addr = $pdo->query("SELECT Address FROM job_placement WHERE EmpID = '" . $pdo->quote($empId) . "' ORDER BY JPNo DESC LIMIT 1");
            $addr_row = $addr->fetch(PDO::FETCH_ASSOC);
            $address = $addr_row['Address'] ?? '';
        } catch (Exception $e) {}

        // Get latest education from register
        $eqName = '';
        try {
            $eq = $pdo->query("SELECT eq.EqName FROM register r LEFT JOIN educational_qualification eq ON eq.EqNo = r.EqNo WHERE r.EmpID = '" . $pdo->quote($empId) . "' ORDER BY r.RDate DESC LIMIT 1");
            $eq_row = $eq->fetch(PDO::FETCH_ASSOC);
            $eqName = $eq_row['EqName'] ?? '';
        } catch (Exception $e) {}

        $results[] = [
            'id' => $empId,
            'text' => $empId . ' - ' . $row['EmpName'],
            'Title' => $title,
            'SexName' => $sexName,
            'Phone' => $row['Phone'] ?? '',
            'Address' => $address,
            'EqName' => $eqName
        ];
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);
