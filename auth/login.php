<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM `User` WHERE `username` = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ../index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in · Movie Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-icon">🎬</div>
                <h1 class="auth-title">Movie Tracker</h1>
                <p class="auth-subtitle">Minimalist cinema database for movie lovers</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="notion-callout callout-error">
                    <span class="callout-icon">⚠️</span>
                    <div class="callout-text"><?= e($error); ?></div>
                </div>
            <?php endif; ?>

            <?php 
            $flashSuccess = $_SESSION['flash_success'] ?? null;
            unset($_SESSION['flash_success']);
            if ($flashSuccess): ?>
                <div class="notion-callout callout-success">
                    <span class="callout-icon">✅</span>
                    <div class="callout-text"><?= e($flashSuccess); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="notion-form">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                        <a href="forgot_password.php" class="notion-link" style="font-size: 11px;">Lupa password?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="notion-btn notion-btn-primary btn-block">
                    Log in
                </button>
            </form>

            <div class="auth-footer">
                Belum punya akun? <a href="register.php" class="notion-link">Daftar sekarang</a>
            </div>
        </div>
    </div>
</body>
</html>
