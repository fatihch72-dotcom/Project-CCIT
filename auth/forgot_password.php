<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// Panggil koneksi di awal agar kolom secret_pin otomatis dibuat saat halaman dimuat
$mysqli = getMySQLiConnection();

// Master key reset sebagai opsi darurat pemulihan akun
define('MASTER_RESET_KEY', 'reset123');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $secret_pin       = trim($_POST['secret_pin'] ?? '');
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($secret_pin) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua bidang formulir wajib diisi.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } else {
        try {
            // Menggunakan Prepared Statements MySQLi
            $mysqli = getMySQLiConnection();

            // Memastikan kolom secret_pin ada di database
            try {
                $checkCol = $mysqli->query("SHOW COLUMNS FROM `User` LIKE 'secret_pin'");
                if ($checkCol && $checkCol->num_rows === 0) {
                    $mysqli->query("ALTER TABLE `User` ADD COLUMN `secret_pin` VARCHAR(255) NULL AFTER `password`");
                }
            } catch (Throwable $ignored) {}

            // 1. Cek apakah username ada di database
            $stmt = $mysqli->prepare("SELECT id, username, password, secret_pin FROM `User` WHERE `username` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if (!$user) {
                    $error = 'Username tidak ditemukan dalam sistem.';
                } else {
                    // 2. Validasi Secret PIN atau Master Key
                    $isPinValid = false;

                    // Cek kecocokan dengan Master Key Reset 
                    if ($secret_pin === MASTER_RESET_KEY) {
                        $isPinValid = true;
                    }
                    // Cek kecocokan dengan Secret PIN yang tersimpan (hashed)
                    elseif (!empty($user['secret_pin']) && password_verify($secret_pin, $user['secret_pin'])) {
                        $isPinValid = true;
                    }
                    // Cek fallback jika tersimpan dalam bentuk plain text
                    elseif (!empty($user['secret_pin']) && $user['secret_pin'] === $secret_pin) {
                        $isPinValid = true;
                    }

                    if (!$isPinValid) {
                        $error = 'Secret PIN atau Master Key tidak cocok / salah.';
                    } else {
                        // 3. Hash password baru
                        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

                        // 4. Update password di database menggunakan MySQLi Prepared Statement
                        $updateStmt = $mysqli->prepare("UPDATE `User` SET `password` = ? WHERE `id` = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                            if ($updateStmt->execute()) {
                                $updateStmt->close();

                                // Redirect ke login.php dengan notifikasi sukses
                                $_SESSION['flash_success'] = 'Password akun "' . e($username) . '" berhasil direset! Silakan login dengan password baru.';
                                header('Location: login.php?reset=success');
                                exit;
                            } else {
                                $error = 'Gagal memperbarui password di database: ' . $updateStmt->error;
                                $updateStmt->close();
                            }
                        } else {
                            $error = 'Gagal menyiapkan query update password: ' . $mysqli->error;
                        }
                    }
                }
            } else {
                $error = 'Gagal menyiapkan query pencarian user: ' . $mysqli->error;
            }
        } catch (mysqli_sql_exception $ex) {
            $error = 'Terjadi kesalahan database: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password · Movie Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <div class="auth-icon">🔐</div>
                <h1 class="auth-title">Reset Password</h1>
                <p class="auth-subtitle">Pulihkan akses akun Anda dengan Secret PIN</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="notion-callout callout-error">
                    <span class="callout-icon">⚠️</span>
                    <div class="callout-text"><?= e($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot_password.php" class="notion-form">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username Anda" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="secret_pin">Secret PIN / Master Key Reset</label>
                    <input type="password" id="secret_pin" name="secret_pin" class="form-input" placeholder="6 digit PIN atau Master Key" required>
                    <span class="form-help">Gunakan 6 digit PIN akun Anda atau Master Key default (<code>reset123</code>).</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Minimal 6 karakter" required minlength="6">
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Ulangi password baru" required minlength="6">
                </div>

                <button type="submit" class="notion-btn notion-btn-primary btn-block">
                    Simpan Password Baru
                </button>
            </form>

            <div class="auth-footer">
                Ingat password akun? <a href="login.php" class="notion-link">Kembali ke Log in</a>
            </div>
        </div>
    </div>
</body>
</html>
