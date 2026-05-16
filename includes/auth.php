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
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
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
