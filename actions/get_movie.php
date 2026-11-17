<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$watchlistId = (int)($_GET['id'] ?? 0);
if ($watchlistId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    $userId = currentUserId();

    $stmt = $pdo->prepare("
        SELECT 
            w.id AS watchlist_id,
            w.user_id,
            w.status,
            w.rating,
            w.notes,
            w.finished_date,
            w.created_at AS watchlist_created_at,
            t.id AS title_id,
            t.title,
            t.release_year,
            t.age_rating,
            t.poster_url
        FROM `User_watchlist` w
        JOIN `Title` t ON w.title_id = t.id
        WHERE w.id = :id AND w.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':id' => $watchlistId, ':user_id' => $userId]);
    $movie = $stmt->fetch();

    if (!$movie) {
        http_response_code(404);
        echo json_encode(['error' => 'Movie not found']);
        exit;
    }

    // Ngambil Genres buat title ini
    $stmtGenres = $pdo->prepare("
        SELECT g.id, g.genre
        FROM `Genre` g
        JOIN `Title_Genre` tg ON g.id = tg.genre_id
        WHERE tg.title_id = :title_id
        ORDER BY g.genre ASC
    ");
    $stmtGenres->execute([':title_id' => $movie['title_id']]);
    $genres = $stmtGenres->fetchAll();

    $movie['genres'] = $genres;
    $movie['genre_ids'] = array_map(function($g) { return (int)$g['id']; }, $genres);
    $movie['genre_names'] = array_map(function($g) { return $g['genre']; }, $genres);
    $movie['formatted_finished_date'] = formatFinishedDate($movie['finished_date']);
    $movie['stars_html'] = renderStars($movie['rating']);
    $movie['status_badge_html'] = renderStatusBadge($movie['status']);
    $movie['age_badge_html'] = renderAgeRatingBadge($movie['age_rating']);

    // Genre badges HTML
    $badgesHtml = '';
    foreach ($genres as $g) {
        $badgesHtml .= renderGenreBadge($g['genre']) . ' ';
    }
    $movie['genre_badges_html'] = trim($badgesHtml);

    echo json_encode(['success' => true, 'data' => $movie]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
