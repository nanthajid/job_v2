<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// ถ้า login อยู่แล้วให้ไปหน้า dashboard
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error    = '';
$username = '';

if (isset($_GET['disabled'])) {
    $error = 'บัญชีนี้ถูกปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT u.DocNo, u.UserName, u.Password, u.level, u.StID, u.StatusNo, s.StName, s.StPost, p.StPostName
             FROM users u
             LEFT JOIN staff s ON s.StID = u.StID
             LEFT JOIN position p ON p.StPost = s.StPost
             WHERE u.UserName = :u
             LIMIT 1"
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();

        if ($row && verifyPassword($password, $row['Password']) && (int)($row['StatusNo'] ?? 1) === 0) {
            $error = 'บัญชีนี้ถูกปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
        } elseif ($row && verifyPassword($password, $row['Password'])) {
            // Migrate legacy plain-text → bcrypt อัตโนมัติ
            if (!preg_match('/^\$2[aby]\$/', $row['Password'])) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET Password = :p WHERE DocNo = :d");
                $upd->execute([':p' => $newHash, ':d' => (int)$row['DocNo']]);
            }

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'DocNo'    => (int)$row['DocNo'],
                'UserName' => $row['UserName'],
                'level'    => $row['level'],
                'StID'     => $row['StID'],
                'StName'   => $row['StName'] ?? $row['UserName'],
                'StPost'   => $row['StPost'] ?? '',
                'StPostName' => $row['StPostName'] ?? '',
            ];

            header('Location: index.php');
            exit;
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบ | สจก. 2 ระบบจัดการผู้ว่างงาน</title>

  <!-- Fonts: Modern Thai GovTech Stack -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&family=IBM+Plex+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    :root {
      --gov-navy: #002D62;
      --gov-royal: #005EB8;
      --gov-gold: #D4AF37;
      --gov-bg: #F0F2F5;
      --gov-white: #FFFFFF;
      --gov-gray: #E9ECEF;
      --gov-text-dark: #1A1A1A;
      --gov-text-muted: #64748B;
      --gov-border: #DEE2E6;
      --gov-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    body {
      font-family: 'IBM Plex Sans Thai', 'Sarabun', sans-serif;
      background: linear-gradient(135deg, var(--gov-navy) 0%, var(--gov-royal) 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    h1, h2, h3, h4, h5, .brand-text, .btn {
      font-family: 'Prompt', sans-serif;
    }

    .login-page {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .login-box {
      width: 100%;
      max-width: 420px;
    }

    .login-card {
      border: 0;
      border-radius: 1.25rem;
      box-shadow: var(--gov-shadow);
      overflow: hidden;
      background-color: var(--gov-white);
    }

    .login-card .card-header {
      background-color: var(--gov-white);
      border-bottom: 1px solid var(--gov-gray);
      padding: 2.5rem 1.5rem 1.5rem;
      text-align: center;
      position: relative;
    }

    .login-card .card-header::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background-color: var(--gov-gold);
      border-radius: 2px;
    }

    .login-card .brand-icon-wrapper {
      margin-bottom: 1rem;
    }

    .login-card .brand-icon {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background-color: var(--gov-white);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      border: 2px solid var(--gov-gray);
      padding: 0;
      overflow: hidden;
    }

    .login-card .card-body {
      padding: 2rem 2.5rem;
    }

    .form-label {
      font-weight: 500;
      color: var(--gov-navy);
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    .input-group {
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid var(--gov-border);
      transition: all 0.2s;
    }

    .input-group:focus-within {
      border-color: var(--gov-royal);
      box-shadow: 0 0 0 3px rgba(0, 94, 184, 0.15);
    }

    .input-group-prepend .input-group-text,
    .input-group-append .btn {
      background-color: #F8FAFC;
      border: none;
      color: var(--gov-text-muted);
      padding: 0 1rem;
    }

    .form-control {
      border: none;
      height: 48px;
      padding: 0.75rem 0.5rem;
      font-size: 1rem;
      background-color: #F8FAFC;
    }

    .form-control:focus {
      box-shadow: none;
      background-color: #fff;
    }

    .btn-login {
      height: 50px;
      border-radius: 10px;
      background-color: var(--gov-navy);
      color: white;
      border: 1px solid var(--gov-navy);
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.2s;
      margin-top: 1.5rem;
    }

    .btn-login:hover {
      background-color: var(--gov-royal);
      color: white;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 45, 98, 0.2);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .login-footer {
      color: var(--gov-navy);
      text-align: center;
      padding: 2rem;
      font-size: 0.85rem;
      font-weight: 500;
    }

    .text-navy {
      color: var(--gov-navy) !important;
    }

    .alert-gov {
      border-radius: 10px;
      border: none;
      background-color: #FEF2F2;
      color: #991B1B;
      padding: 0.75rem 1.25rem;
      font-size: 0.9rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
    }

    /* Custom scrollbar for better look */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: var(--gov-bg); }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
  </style>
</head>
<body>

<div class="login-page">
  <div class="login-box">
    <div class="card login-card">

      <div class="card-header">
        <div class="brand-icon-wrapper">
          <div class="brand-icon">
          <img src="assets/images/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        </div>
        <h4 class="mb-1 font-weight-bold text-navy">ระบบจัดการคนว่างงาน</h4>
        <p class="text-navy mb-0" style="font-size: 0.95rem; font-weight: 800;">สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</p>
      </div>

      <div class="card-body">
        <?php if ($error !== ''): ?>
          <div class="alert alert-gov" role="alert">
            <i class="fas fa-circle-exclamation mr-2 fa-lg"></i>
            <div><?= htmlspecialchars($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" novalidate>
          <div class="form-group mb-4">
            <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
              </div>
              <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                value="<?= htmlspecialchars($username) ?>"
                placeholder="ระบุชื่อผู้ใช้งาน"
                required
                autofocus>
            </div>
          </div>

          <div class="form-group mb-4">
            <label for="password" class="form-label">รหัสผ่าน</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
              </div>
              <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                placeholder="ระบุรหัสผ่าน"
                required>
              <div class="input-group-append">
                <button type="button" class="btn btn-light" id="togglePw" tabindex="-1">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-login btn-block shadow-sm">
            <i class="fas fa-right-to-bracket mr-2"></i> เข้าสู่ระบบใช้งาน
          </button>
        </form>
      </div>
    </div>

    <div class="login-footer">
      <div class="mb-1 font-weight-bold">Developed By Nanthajid Sawasri </div>
      <div>&copy; <?= 2569 ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
  $(function() {
    $('#togglePw').on('click', function () {
      var $pw = $('#password');
      var isPw = $pw.attr('type') === 'password';
      $pw.attr('type', isPw ? 'text' : 'password');
      $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // Simple animation on focus
    $('.form-control').on('focus', function() {
      $(this).parent('.input-group').addClass('shadow-sm');
    }).on('blur', function() {
      $(this).parent('.input-group').removeClass('shadow-sm');
    });
  });
</script>
</body>
</html>
