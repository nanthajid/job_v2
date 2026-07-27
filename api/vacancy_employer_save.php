<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE); exit; }
$keys=['EmployerID','EmployerName','BusinessType','EmployeeCount','EmployerPhone','IssuedBy','BuildingName','HouseNo','Moo','Soi','Road','Subdistrict','District','Province','PostalCode','ContactName','ContactPosition'];
$data=[]; foreach ($keys as $key) { $data[$key]=trim((string)($_POST[$key] ?? '')); }
if (strlen($data['PostalCode']) > 20) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'รหัสไปรษณีย์ต้องมีความยาวไม่เกิน 20 ตัวอักษร'], JSON_UNESCAPED_UNICODE); exit; }
if ($data['EmployerName']==='') { http_response_code(422); echo json_encode(['success'=>false,'message'=>'กรุณากรอกชื่อสถานประกอบการ'], JSON_UNESCAPED_UNICODE); exit; }
$pdo=getDB();
try {
  $pdo->beginTransaction();
  $where=$data['EmployerID']!=='' ? 'EmployerID=:EmployerID' : 'EmployerName=:EmployerName AND EmployerPhone=:EmployerPhone';
  $find=$pdo->prepare("SELECT EmployerNo FROM vacancy_employer WHERE $where ORDER BY EmployerNo LIMIT 1");
  if ($data['EmployerID'] !== '') { $find->execute(['EmployerID'=>$data['EmployerID']]); } else { $find->execute(['EmployerName'=>$data['EmployerName'],'EmployerPhone'=>$data['EmployerPhone']]); }
  $no=(int)$find->fetchColumn();
  if ($no) {
    // COALESCE keeps the existing value whenever this submission left the field blank,
    // so a later notice with fewer filled-in fields can't erase previously saved employer data.
    $set=[]; foreach ($keys as $key) { if ($key!=='EmployerID' && $key!=='EmployerName') $set[]="$key=COALESCE(:$key,$key)"; }
    $update=$pdo->prepare('UPDATE vacancy_employer SET '.implode(',', $set).' WHERE EmployerNo=:EmployerNo');
    $params=['BusinessType'=>$data['BusinessType'] ?: null,'EmployeeCount'=>$data['EmployeeCount']!==''?(int)$data['EmployeeCount']:null,'EmployerPhone'=>$data['EmployerPhone'] ?: null,'IssuedBy'=>$data['IssuedBy'] ?: null,'BuildingName'=>$data['BuildingName'] ?: null,'HouseNo'=>$data['HouseNo'] ?: null,'Moo'=>$data['Moo'] ?: null,'Soi'=>$data['Soi'] ?: null,'Road'=>$data['Road'] ?: null,'Subdistrict'=>$data['Subdistrict'] ?: null,'District'=>$data['District'] ?: null,'Province'=>$data['Province'] ?: null,'PostalCode'=>$data['PostalCode'] ?: null,'ContactName'=>$data['ContactName'] ?: null,'ContactPosition'=>$data['ContactPosition'] ?: null,'EmployerNo'=>$no]; $update->execute($params);
  } else {
    $cols=implode(',', $keys); $params=implode(',', array_map(fn($key)=>':'.$key,$keys));
    $insert=$pdo->prepare("INSERT INTO vacancy_employer ($cols) VALUES ($params)");
    $data['EmployeeCount']=$data['EmployeeCount']!==''?(int)$data['EmployeeCount']:null; $insert->execute($data); $no=(int)$pdo->lastInsertId();
  }
  $pdo->commit(); echo json_encode(['success'=>true,'EmployerNo'=>$no], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); http_response_code(422); echo json_encode(['success'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE); }