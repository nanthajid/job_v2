<?php
require_once __DIR__ . '/../includes/auth.php'; requireLoginApi();
header('Content-Type: application/json; charset=utf-8'); require_once __DIR__ . '/../config/database.php';
$pdo=getDB();
$rows=$pdo->query('SELECT p.PositionID,n.NoticeID,n.EmployerName,n.ContactName,n.CreatedAt,m.PositionName,p.Headcount,p.Wage,p.Gender,p.AgeRange,p.Education,p.Conditions,p.WorkSchedule,p.MilitaryStatus,p.ExpireDate,p.Remark FROM vacancy_notice_position p JOIN vacancy_notice n ON n.NoticeID=p.NoticeID JOIN vacancy_position_master m ON m.PositionMasterID=p.PositionMasterID ORDER BY n.NoticeID DESC, p.PositionID ASC')->fetchAll();
echo json_encode(['data'=>$rows], JSON_UNESCAPED_UNICODE);
