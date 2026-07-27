<?php
require_once __DIR__ . '/../includes/auth.php'; requireLoginApi(); header('Content-Type: application/json; charset=utf-8'); require_once __DIR__ . '/../config/database.php';
$id=(int)($_POST['NoticeID'] ?? 0); if ($id<1) { http_response_code(422); echo json_encode(['success'=>false,'message'=>'ไม่พบรายการ']); exit; }
$s=getDB()->prepare('DELETE FROM vacancy_notice WHERE NoticeID=:id'); $s->execute(['id'=>$id]); echo json_encode(['success'=>$s->rowCount()>0,'message'=>$s->rowCount()>0?'ลบรายการเรียบร้อย':'ไม่พบรายการ'], JSON_UNESCAPED_UNICODE);
