<?php
require_once __DIR__ . '/../includes/auth.php';
requireLoginApi();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะ POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$oldStID = trim($_POST['old_StID'] ?? '');
$stID    = trim($_POST['StID']     ?? '');
$titleNo = trim($_POST['TitleNo']  ?? '');
$stName  = trim($_POST['StName']   ?? '');
$sexNo   = trim($_POST['SexNo']    ?? '');
$stPost  = trim($_POST['StPost']   ?? '');
$depNo   = trim($_POST['DepNo']    ?? '');
$userName = trim($_POST['UserName'] ?? '');
$password = trim($_POST['Password'] ?? '');
$statusNo = trim($_POST['StatusNo'] ?? '1');
$statusNo = ($statusNo === '0') ? 0 : 1; // normalize เป็น 0/1

$pdo  = getDB();
$user = currentUser();

// กันไม่ให้ปิดสถานะบัญชีของตนเอง (กันล็อกเอาต์ตัวเอง)
if ($statusNo === 0 && (string) ($user['StID'] ?? '') === (string) $oldStID) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถปิดใช้งานบัญชีของตนเองได้'], JSON_UNESCAPED_UNICODE);
    exit;
}

// การจัดการรูปภาพ (เฉพาะเมื่อมีการอัปโหลดไฟล์ใหม่)
$imageName = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = $_FILES['image']['name'];
    $fileSize = $_FILES['image']['size'];
    $fileType = $_FILES['image']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำ
    $newFileName = $stID . '_' . time() . '.' . $fileExtension;

    // ตรวจสอบนามสกุลไฟล์
    $allowedfileExtensions = array('jpg', 'jpeg', 'png');
    if (in_array($fileExtension, $allowedfileExtensions)) {
        if ($fileSize <= 2 * 1024 * 1024) {
            $uploadFileDir = __DIR__ . '/../uploads/staff/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imageName = $newFileName;

                // ลบรูปเก่า (ถ้ามี)
                $oldImgStmt = $pdo->prepare("SELECT image FROM staff WHERE StID = :id");
                $oldImgStmt->execute([':id' => $oldStID]);
                $oldImg = $oldImgStmt->fetchColumn();
                if ($oldImg && file_exists($uploadFileDir . $oldImg)) {
                    unlink($uploadFileDir . $oldImg);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการย้ายไฟล์'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ขนาดไฟล์เกิน 2MB'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'รองรับเฉพาะไฟล์ JPG และ PNG'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$errors = [];
if ($oldStID === '') {
    $errors[] = 'ไม่พบรหัสเจ้าหน้าที่เดิม';
}
if ($stID === '') {
    $errors[] = 'กรุณากรอกรหัสเจ้าหน้าที่';
}
if ($titleNo === '') {
    $errors[] = 'กรุณาเลือกคำนำหน้า';
}
if ($stName === '') {
    $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
}
if ($sexNo === '') {
    $errors[] = 'กรุณาเลือกเพศ';
}
if ($stPost === '') {
    $errors[] = 'กรุณาเลือกตำแหน่ง';
}
if ($depNo === '') {
    $errors[] = 'กรุณาเลือกฝ่าย';
}
if ($userName === '') {
    $errors[] = 'กรุณากรอกชื่อผู้ใช้';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

// ถ้ามีการเปลี่ยน StID ให้เช็คว่าซ้ำกับคนอื่นไหม
if ($stID !== $oldStID) {
    $check = $pdo->prepare("SELECT 1 FROM staff WHERE StID = :id");
    $check->execute([':id' => $stID]);
    if ($check->fetchColumn()) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'รหัสเจ้าหน้าที่ใหม่นี้มีอยู่ในระบบแล้ว'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ตรวจสอบชื่อผู้ใช้ซ้ำ (ยกเว้นของตัวเอง)
$checkUser = $pdo->prepare("SELECT 1 FROM users WHERE UserName = :u AND StID != :old_id");
$checkUser->execute([':u' => $userName, ':old_id' => $oldStID]);
if ($checkUser->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้โดยเจ้าหน้าที่คนอื่นแล้ว'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    $sqlParams = [
        ':new_id' => $stID,
        ':title'  => $titleNo,
        ':name'   => $stName,
        ':sex'    => $sexNo,
        ':post'   => $stPost,
        ':dep'    => $depNo,
        ':old_id' => $oldStID
    ];

    $imageSql = "";
    if ($imageName) {
        $imageSql = ", image = :img";
        $sqlParams[':img'] = $imageName;
    }

    $stmt = $pdo->prepare(
        "UPDATE staff 
         SET StID = :new_id, TitleNo = :title, StName = :name, SexNo = :sex, StPost = :post, DepNo = :dep $imageSql
         WHERE StID = :old_id"
    );
    $stmt->execute($sqlParams);

    // อัปเดตตาราง users
    $pwSql = "";
    $userParams = [
        ':u'      => $userName,
        ':new_id' => $stID,
        ':status' => $statusNo,
        ':old_id' => $oldStID
    ];
    if ($password !== '') {
        $pwSql = ", Password = :p";
        $userParams[':p'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $updUser = $pdo->prepare(
        "UPDATE users SET UserName = :u, StID = :new_id, StatusNo = :status $pwSql WHERE StID = :old_id"
    );
    $updUser->execute($userParams);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'อัปเดตข้อมูลเรียบร้อย'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'อัปเดตไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
