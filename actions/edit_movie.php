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
$title = trim($_POST['title'] ?? '');
$release_year = (int)($_POST['release_year'] ?? date('Y'));
$age_rating = trim($_POST['age_rating'] ?? 'PG-13');
$genres = isset($_POST['genres']) && is_array($_POST['genres']) ? $_POST['genres'] : [];
$status = in_array($_POST['status'] ?? '', ['watchlist', 'watching', 'finished']) ? $_POST['status'] : 'watchlist';
$rating = !empty($_POST['rating']) ? max(1, min(5, (int)$_POST['rating'])) : null;
$notes = trim($_POST['notes'] ?? '');
$finished_date = !empty($_POST['finished_date']) ? $_POST['finished_date'] : null;
$poster_url = trim($_POST['poster_url'] ?? '');

if ($watchlistId <= 0 || empty($title)) {
    $_SESSION['flash_error'] = 'Data tidak lengkap atau ID tidak valid.';
    header('Location: ../index.php');
    exit;
}

// ngehandle poster file upload 
if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
    $uploadedPath = handlePosterUpload($_FILES['poster_file']);
    if ($uploadedPath) {
        $poster_url = $uploadedPath;
    }
}

try {
    $pdo = getDBConnection();
    $userId = currentUserId();

    $pdo->beginTransaction();

    // Verify watchlist entry belongs to user
    $stmtCheck = $pdo->prepare("SELECT title_id, poster_url FROM `User_watchlist` w JOIN `Title` t ON w.title_id = t.id WHERE w.id = :id AND w.user_id = :user_id LIMIT 1");
    $stmtCheck->execute([':id' => $watchlistId, ':user_id' => $userId]);
    $item = $stmtCheck->fetch();

    if (!$item) {
        throw new Exception("Data film tidak ditemukan atau Anda tidak memiliki akses.");
    }

    $titleId = (int)$item['title_id'];

    // tetap pakai poster yang lama kalau user enggak mengunggah file gambar baru ataupun enggak memasukkan URL gambar yang baru
    if (empty($poster_url) && !empty($item['poster_url'])) {
        $poster_url = $item['poster_url'];
    }

    // 1. Update Title table
    $stmtUpdateTitle = $pdo->prepare("
        UPDATE `Title`
        SET `title` = :title, `release_year` = :release_year, `age_rating` = :age_rating, `poster_url` = :poster_url
        WHERE id = :id
    ");
    $stmtUpdateTitle->execute([
        ':title'        => $title,
        ':release_year' => $release_year,
        ':age_rating'   => $age_rating,
        ':poster_url'   => !empty($poster_url) ? $poster_url : null,
        ':id'           => $titleId
    ]);

    // 2. Update Title_Genre 
    $stmtDeleteGenres = $pdo->prepare("DELETE FROM `Title_Genre` WHERE title_id = :title_id");
    $stmtDeleteGenres->execute([':title_id' => $titleId]);

    if (!empty($genres)) {
        $stmtInsertGenre = $pdo->prepare("INSERT IGNORE INTO `Title_Genre` (`title_id`, `genre_id`) VALUES (:title_id, :genre_id)");
        foreach ($genres as $genreId) {
            $genreId = (int)$genreId;
            if ($genreId > 0) {
                $stmtInsertGenre->execute([':title_id' => $titleId, ':genre_id' => $genreId]);
            }
        }
    }

    // 3. Update User_watchlist
    $stmtUpdateWatchlist = $pdo->prepare("
        UPDATE `User_watchlist`
        SET `status` = :status, `rating` = :rating, `notes` = :notes, `finished_date` = :finished_date
        WHERE id = :id AND user_id = :user_id
    ");
    $stmtUpdateWatchlist->execute([
        ':status'        => $status,
        ':rating'        => $rating,
        ':notes'         => !empty($notes) ? $notes : null,
        ':finished_date' => $finished_date,
        ':id'            => $watchlistId,
        ':user_id'       => $userId
    ]);

    $pdo->commit();
    $_SESSION['flash_success'] = 'Data film "' . $title . '" berhasil diperbarui!';
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Gagal memperbarui film: ' . $e->getMessage();
}

header('Location: ../index.php');
exit;
