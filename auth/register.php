<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $secret_pin = trim($_POST['secret_pin'] ?? '');

    if (empty($username) || empty($password) || empty($secret_pin)) {
        $error = 'Semua bidang wajib diisi.';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (!preg_match('/^[0-9]{6}$/', $secret_pin)) {
        $error = 'Secret PIN harus berupa 6 digit angka (contoh: 123456).';
    } else {
        $pdo = getDBConnection();

        // ngecheck usernamenya udah ada yg bikin blom 
        $stmt = $pdo->prepare("SELECT id FROM `User` WHERE `username` = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pinHash = password_hash($secret_pin, PASSWORD_DEFAULT);

            $stmtInsert = $pdo->prepare("INSERT INTO `User` (`username`, `password`, `secret_pin`) VALUES (:username, :password, :secret_pin)");
            if ($stmtInsert->execute([':username' => $username, ':password' => $hash, ':secret_pin' => $pinHash])) {
                $newUserId = (int)$pdo->lastInsertId();

                // Log user in
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                header('Location: ../index.php');
                exit;
            } else {
                $error = 'Gagal mendaftarkan akun. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register · Movie Tracker</title>
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
                <h1 class="auth-title">Buat Akun Baru</h1>
                <p class="auth-subtitle">Mulai bangun koleksi dan jurnal film pribadimu</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="notion-callout callout-error">
                    <span class="callout-icon">⚠️</span>
                    <div class="callout-text"><?= e($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="notion-form">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Pilih username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Ulangi password" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label" for="secret_pin">Secret PIN / PIN Keamanan (6 Angka)</label>
                    <input type="password" id="secret_pin" name="secret_pin" class="form-input" placeholder="Masukkan 6 digit angka (contoh: 123456)" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" value="<?= e($_POST['secret_pin'] ?? '') ?>">
                    <span class="form-help">Wajib 6 digit angka. Digunakan untuk pemulihan akun jika lupa password.</span>
                </div>

                <button type="submit" class="notion-btn notion-btn-primary btn-block">
                    Daftar Akun
                </button>
            </form>

            <div class="auth-footer">
                Sudah punya akun? <a href="login.php" class="notion-link">Log in</a>
            </div>
        </div>
    </div>
</body>
</html>
