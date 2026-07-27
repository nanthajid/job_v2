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

$stID    = trim($_POST['StID']    ?? '');
$titleNo = trim($_POST['TitleNo'] ?? '');
$stName  = trim($_POST['StName']  ?? '');
$sexNo   = trim($_POST['SexNo']   ?? '');
$stPost  = trim($_POST['StPost']  ?? '');
$depNo   = trim($_POST['DepNo']   ?? '');
$userName = trim($_POST['UserName'] ?? '');
$password = trim($_POST['Password'] ?? '');

// การจัดการรูปภาพ
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
        // ตรวจสอบขนาด (ไม่เกิน 2MB)
        if ($fileSize <= 2 * 1024 * 1024) {
            $uploadFileDir = __DIR__ . '/../uploads/staff/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imageName = $newFileName;
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
if ($password === '') {
    $errors[] = 'กรุณากรอกรหัสผ่าน';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' / ', $errors)], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDB();

// ตรวจสอบว่ารหัสเจ้าหน้าที่ซ้ำหรือไม่
$check = $pdo->prepare("SELECT 1 FROM staff WHERE StID = :id");
$check->execute([':id' => $stID]);
if ($check->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'รหัสเจ้าหน้าที่นี้มีอยู่ในระบบแล้ว'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ตรวจสอบว่าชื่อผู้ใช้ซ้ำหรือไม่
$checkUser = $pdo->prepare("SELECT 1 FROM users WHERE UserName = :u");
$checkUser->execute([':u' => $userName]);
if ($checkUser->fetchColumn()) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    // บันทึกลงตาราง staff
    $stmt = $pdo->prepare(
        "INSERT INTO staff (StID, TitleNo, StName, SexNo, StPost, DepNo, image)
         VALUES (:id, :title, :name, :sex, :post, :dep, :img)"
    );
    $stmt->execute([
        ':id'    => $stID,
        ':title' => $titleNo,
        ':name'  => $stName,
        ':sex'   => $sexNo,
        ':post'  => $stPost,
        ':dep'   => $depNo,
        ':img'   => $imageName
    ]);

    // บันทึกลงตาราง users
    $stmtUser = $pdo->prepare(
        "INSERT INTO users (UserName, Password, StID, level)
         VALUES (:u, :p, :id, '1')"
    );
    $stmtUser->execute([
        ':u'  => $userName,
        ':p'  => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $stID
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'เพิ่มเจ้าหน้าที่เรียบร้อย'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'บันทึกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
