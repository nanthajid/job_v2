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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT u.DocNo, u.UserName, u.Password, u.StID, s.StName, s.StPost
             FROM users u
             LEFT JOIN staft s ON s.StID = u.StID
             WHERE u.UserName = :u
             LIMIT 1"
        );
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();

        if ($row && verifyPassword($password, $row['Password'])) {
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
                'StID'     => $row['StID'],
                'StName'   => $row['StName'] ?? $row['UserName'],
                'StPost'   => $row['StPost'] ?? '',
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
  <title>เข้าสู่ระบบ | สำนักงานจัดหางาน กทม. พื้นที่ 2</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/css/custom.css">

  <style>
    body {
      font-family: 'Sarabun', sans-serif;
      background: linear-gradient(135deg, #007bff 0%, #20c997 100%);
      min-height: 100vh;
    }
    .login-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .login-box {
      width: 100%;
      max-width: 420px;
    }
    .login-card {
      border: 0;
      border-radius: .75rem;
      box-shadow: 0 1rem 2rem rgba(0,0,0,.18);
      overflow: hidden;
    }
    .login-card .card-header {
      background: #fff;
      border-bottom: 1px solid #eef0f2;
      padding: 1.5rem 1.5rem 1rem;
      text-align: center;
    }
    .login-card .brand-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: linear-gradient(135deg, #007bff, #20c997);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: .5rem;
    }
    .login-card .card-body { padding: 1.5rem; }
    .login-card .form-control { padding: .65rem .9rem; }
    .login-card .input-group-text {
      background: #fff;
      border-right: 0;
      color: #6c757d;
    }
    .login-card .form-control:focus { box-shadow: 0 0 0 .2rem rgba(0,123,255,.15); }
    .login-card .btn-login {
      padding: .65rem;
      font-weight: 600;
      letter-spacing: .02em;
      background: linear-gradient(135deg, #007bff, #20c997);
      border: 0;
      color: #fff;
    }
    .login-card .btn-login:hover { filter: brightness(.95); color: #fff; }
    .login-footer { color: rgba(255,255,255,.85); text-align: center; margin-top: 1rem; font-size: .85rem; }
  </style>
</head>
<body>

<div class="login-page">
  <div class="login-box">
    <div class="card login-card">

      <div class="card-header">
        <div class="brand-icon"><i class="fas fa-briefcase"></i></div>
        <h5 class="mb-0 font-weight-bold">ระบบจัดการผู้ว่างงาน</h5>
        <small class="text-muted">สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2</small>
      </div>

      <div class="card-body">
        <p class="text-center text-muted mb-3" style="font-size:.9rem;">
          <i class="fas fa-lock mr-1"></i>กรุณาเข้าสู่ระบบเพื่อใช้งาน
        </p>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger py-2 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i><?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" novalidate>
          <div class="form-group">
            <label for="username" class="mb-1 small">
              <i class="fas fa-user mr-1 text-primary"></i>ชื่อผู้ใช้
            </label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
              </div>
              <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                value="<?= htmlspecialchars($username) ?>"
                placeholder="ชื่อผู้ใช้"
                required
                autofocus>
            </div>
          </div>

          <div class="form-group">
            <label for="password" class="mb-1 small">
              <i class="fas fa-key mr-1 text-primary"></i>รหัสผ่าน
            </label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
              </div>
              <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                placeholder="รหัสผ่าน"
                required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary" id="togglePw" tabindex="-1">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-login btn-block">
            <i class="fas fa-sign-in-alt mr-1"></i>เข้าสู่ระบบ
          </button>
        </form>
      </div>
    </div>

    <div class="login-footer">
      &copy; <?= date('Y') + 543 ?> สำนักงานจัดหางานกรุงเทพมหานครพื้นที่ 2
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
  $('#togglePw').on('click', function () {
    var $pw = $('#password');
    var isPw = $pw.attr('type') === 'password';
    $pw.attr('type', isPw ? 'text' : 'password');
    $(this).find('i').toggleClass('fa-eye fa-eye-slash');
  });
</script>
</body>
</html>
