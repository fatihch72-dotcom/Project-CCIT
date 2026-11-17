<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$watchlistId = (int)($_POST['watchlist_id'] ?? 0);

if ($watchlistId <= 0) {
    $_SESSION['flash_error'] = 'ID watchlist tidak valid.';
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $userId = currentUserId();

    // ngambil movie title buat flash message
    $stmtFind = $pdo->prepare("
        SELECT w.title_id, t.title 
        FROM `User_watchlist` w 
        JOIN `Title` t ON w.title_id = t.id 
        WHERE w.id = :id AND w.user_id = :user_id 
        LIMIT 1
    ");
    $stmtFind->execute([':id' => $watchlistId, ':user_id' => $userId]);
    $item = $stmtFind->fetch();

    if (!$item) {
        throw new Exception("Film tidak ditemukan dalam daftar Anda.");
    }

    $titleId = (int)$item['title_id'];
    $movieTitle = $item['title'];

    // Delete from User_watchlist
    $stmtDelete = $pdo->prepare("DELETE FROM `User_watchlist` WHERE id = :id AND user_id = :user_id");
    $stmtDelete->execute([':id' => $watchlistId, ':user_id' => $userId]);

    // Cek apakah ada user lain yang nge-bookmark/nge-track judul film ini. 
    // Kalau gak ada (cuma user ini doang yang punya), judul film boleh dihapus dari database
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `User_watchlist` WHERE title_id = :title_id");
    $stmtCount->execute([':title_id' => $titleId]);
    if ($stmtCount->fetchColumn() == 0) {
        $stmtDeleteTitle = $pdo->prepare("DELETE FROM `Title` WHERE id = :title_id");
        $stmtDeleteTitle->execute([':title_id' => $titleId]);
    }

    $_SESSION['flash_success'] = 'Film "' . $movieTitle . '" berhasil dihapus dari watchlist.';
} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Gagal menghapus film: ' . $e->getMessage();
}

header('Location: ../index.php');
exit;
