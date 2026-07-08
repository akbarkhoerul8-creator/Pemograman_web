<?php
// pages/login.php
require_once __DIR__ . '/../config/database.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: " . BASE_URL . "/index.php?page=home");
        exit();
    } else {
        $error_message = 'Username atau Password salah!';
    }
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Admin — FleetHub</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css" />
</head>

<body>
  <div class="login-wrapper">
    <div class="login-background">
      <div class="bg-shape bg-shape-1"></div>
      <div class="bg-shape bg-shape-2"></div>
    </div>

    <div class="login-card">
      <div class="login-header">
        <div class="logo-mark">// FLEET</div>
        <h1>FleetHub</h1>
        <p>Akses Portal Administrasi Pengelolaan Armada</p>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="login-error">
          <span class="error-icon">⚠️</span>
          <span class="error-text"><?= htmlspecialchars($error_message) ?></span>
        </div>
      <?php endif; ?>

      <form action="" method="POST" class="login-form">
        <div class="form-group-login">
          <label for="username">Username</label>
          <div class="input-wrapper">
            <span class="input-icon">👤</span>
            <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required autofocus />
          </div>
        </div>

        <div class="form-group-login">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <span class="input-icon">🔒</span>
            <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required />
          </div>
        </div>

        <button type="submit" class="btn btn-login">Masuk Aplikasi</button>
      </form>

      <div class="login-footer">
        <p>&copy; <?= date('Y') ?> FleetHub. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>

</html>