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

$title = trim($_POST['title'] ?? '');
$release_year = (int)($_POST['release_year'] ?? date('Y'));
$age_rating = trim($_POST['age_rating'] ?? 'PG-13');
$genres = isset($_POST['genres']) && is_array($_POST['genres']) ? $_POST['genres'] : [];
$status = in_array($_POST['status'] ?? '', ['watchlist', 'watching', 'finished']) ? $_POST['status'] : 'watchlist';
$rating = !empty($_POST['rating']) ? max(1, min(5, (int)$_POST['rating'])) : null;
$notes = trim($_POST['notes'] ?? '');
$finished_date = !empty($_POST['finished_date']) ? $_POST['finished_date'] : null;

// Poster logic: Upload file vs URL
$poster_url = trim($_POST['poster_url'] ?? '');
if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
    $uploadedPath = handlePosterUpload($_FILES['poster_file']);
    if ($uploadedPath) {
        $poster_url = $uploadedPath;
    }
}

if (empty($title)) {
    $_SESSION['flash_error'] = 'Judul film wajib diisi.';
    header('Location: ../index.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $userId = currentUserId();

    $pdo->beginTransaction();

    // 1. Ngecek titlenya udah ada apa belom 
    $stmtCheckTitle = $pdo->prepare("SELECT id FROM `Title` WHERE LOWER(title) = LOWER(:title) AND release_year = :year LIMIT 1");
    $stmtCheckTitle->execute([
        ':title' => $title,
        ':year'  => $release_year
    ]);
    $existingTitle = $stmtCheckTitle->fetch();

    if ($existingTitle) {
        $titleId = (int)$existingTitle['id'];
        if (!empty($poster_url)) {
            $stmtUpdateTitle = $pdo->prepare("UPDATE `Title` SET `poster_url` = :poster, `age_rating` = :rating WHERE id = :id");
            $stmtUpdateTitle->execute([':poster' => $poster_url, ':rating' => $age_rating, ':id' => $titleId]);
        }
    } else {
        $stmtInsertTitle = $pdo->prepare("
            INSERT INTO `Title` (`title`, `release_year`, `age_rating`, `poster_url`)
            VALUES (:title, :release_year, :age_rating, :poster_url)
        ");
        $stmtInsertTitle->execute([
            ':title'        => $title,
            ':release_year' => $release_year,
            ':age_rating'   => $age_rating,
            ':poster_url'   => !empty($poster_url) ? $poster_url : null
        ]);
        $titleId = (int)$pdo->lastInsertId();
    }

    // 2. ngeLink Genres di Title_Genre
    if (!empty($genres)) {
        $stmtGenre = $pdo->prepare("INSERT IGNORE INTO `Title_Genre` (`title_id`, `genre_id`) VALUES (:title_id, :genre_id)");
        foreach ($genres as $genreId) {
            $genreId = (int)$genreId;
            if ($genreId > 0) {
                $stmtGenre->execute([':title_id' => $titleId, ':genre_id' => $genreId]);
            }
        }
    }

    // 3. Insert atau Update User_watchlist
    $stmtCheckWatchlist = $pdo->prepare("SELECT id FROM `User_watchlist` WHERE user_id = :user_id AND title_id = :title_id LIMIT 1");
    $stmtCheckWatchlist->execute([':user_id' => $userId, ':title_id' => $titleId]);
    $existingWatchlist = $stmtCheckWatchlist->fetch();

    if ($existingWatchlist) {
        $stmtUpdateWatchlist = $pdo->prepare("
            UPDATE `User_watchlist`
            SET `status` = :status, `rating` = :rating, `notes` = :notes, `finished_date` = :finished_date
            WHERE id = :id
        ");
        $stmtUpdateWatchlist->execute([
            ':status'        => $status,
            ':rating'        => $rating,
            ':notes'         => !empty($notes) ? $notes : null,
            ':finished_date' => $finished_date,
            ':id'            => $existingWatchlist['id']
        ]);
    } else {
        $stmtInsertWatchlist = $pdo->prepare("
            INSERT INTO `User_watchlist` (`user_id`, `title_id`, `status`, `rating`, `notes`, `finished_date`)
            VALUES (:user_id, :title_id, :status, :rating, :notes, :finished_date)
        ");
        $stmtInsertWatchlist->execute([
            ':user_id'       => $userId,
            ':title_id'      => $titleId,
            ':status'        => $status,
            ':rating'        => $rating,
            ':notes'         => !empty($notes) ? $notes : null,
            ':finished_date' => $finished_date
        ]);
    }

    $pdo->commit();
    $_SESSION['flash_success'] = 'Film "' . $title . '" berhasil ditambahkan ke watchlist!';
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

header('Location: ../index.php');
exit;
