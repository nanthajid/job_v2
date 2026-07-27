<?php
// Session + auth helpers — include at top of every protected page/API
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function requireLogin(string $loginPath = 'login.php'): void
{
    if (empty($_SESSION['user'])) {
        if (isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'  => false,
                'message'  => 'กรุณาเข้าสู่ระบบ',
                'redirect' => $loginPath,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header('Location: ' . $loginPath);
        }
        exit;
    }
    enforceActiveStatus($loginPath, false);
}

function requireLoginApi(string $loginPath = '../login.php'): void
{
    if (empty($_SESSION['user'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => false,
            'message'  => 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่',
            'redirect' => $loginPath,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    enforceActiveStatus($loginPath, true);
}

/**
 * เช็คสถานะบัญชีทุก request — หากถูกปิดใช้งาน (หรือบัญชีถูกลบ) ให้เตะออกทันที
 * fail-open หากเชื่อมต่อฐานข้อมูลไม่ได้ เพื่อไม่ให้ล็อกเอาต์ทั้งระบบจากปัญหาชั่วคราว
 */
function enforceActiveStatus(string $loginPath, bool $isApi): void
{
    $docNo = (int) ($_SESSION['user']['DocNo'] ?? 0);
    if ($docNo <= 0) {
        return;
    }

    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT StatusNo FROM users WHERE DocNo = :d LIMIT 1");
        $stmt->execute([':d' => $docNo]);
        $status = $stmt->fetchColumn();
    } catch (Throwable $e) {
        return; // ข้ามการเช็คเมื่อ DB มีปัญหา (fail-open)
    }

    // บัญชีถูกลบ ($status === false) หรือถูกปิดใช้งาน (0) → เตะออก
    if ($status === false || (int) $status === 0) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        if ($isApi || isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'  => false,
                'message'  => 'บัญชีนี้ถูกปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                'redirect' => $loginPath,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header('Location: ' . $loginPath . '?disabled=1');
        }
        exit;
    }
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** ผู้ดูแลระบบคือผู้ใช้ level = 1 (เมนูกลุ่ม "ระบบ" ใน sidebar ใช้เกณฑ์เดียวกัน) */
function isAdmin(): bool
{
    $user = currentUser();
    return isset($user['level']) && (int) $user['level'] === 1;
}

/** หน้าเว็บเฉพาะผู้ดูแลระบบ — ต้องเรียกหลัง requireLogin() */
function requireAdmin(string $redirect = 'index.php'): void
{
    if (!isAdmin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/** API เฉพาะผู้ดูแลระบบ — ต้องเรียกหลัง requireLoginApi() */
function requireAdminApi(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่แก้ไขส่วนนี้ได้',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Verify password — supports legacy plain-text + bcrypt
 */
function verifyPassword(string $input, string $stored): bool
{
    if (preg_match('/^\$2[aby]\$/', $stored)) {
        return password_verify($input, $stored);
    }
    return hash_equals($stored, $input);
}
