<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * biar web aman dari serangan XSS (Cross-Site Scripting).
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * ngecek user kagi login ga
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ngambil id user yg lagi login 
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * ngambil username yg login
 */
function currentUsername(): string {
    return $_SESSION['username'] ?? 'Guest';
}

/**
 * Render star ratings (1 to 5 stars)
 */
function renderStars(?int $rating): string {
    if ($rating === null || $rating <= 0) {
        return '<span class="notion-stars-empty">No rating</span>';
    }

    $rating = max(1, min(5, (int)$rating));
    $html = '<span class="notion-stars" title="' . $rating . ' / 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<span class="star filled">★</span>';
        } else {
            $html .= '<span class="star empty">☆</span>';
        }
    }
    $html .= '</span>';
    return $html;
}

function getGenreBadgeStyle(string $genre): array {
    $palette = [
        'Action'      => ['bg' => '#fdebec', 'color' => '#d44c47'], // Red
        'Sci-Fi'      => ['bg' => '#e7f3f8', 'color' => '#2383e2'], // Blue
        'Drama'       => ['bg' => '#fbf3db', 'color' => '#cb912f'], // Yellow
        'Comedy'      => ['bg' => '#edf3ec', 'color' => '#448361'], // Green
        'Thriller'    => ['bg' => '#f4f0f7', 'color' => '#9065b0'], // Purple
        'Animation'   => ['bg' => '#faece2', 'color' => '#d9730d'], // Orange
        'Adventure'   => ['bg' => '#e6f7f2', 'color' => '#0f766e'], // Teal
        'Romance'     => ['bg' => '#fbf2f5', 'color' => '#c14c8a'], // Pink
        'Horror'      => ['bg' => '#f1f1ef', 'color' => '#787774'], // Gray
        'Fantasy'     => ['bg' => '#f0ebf8', 'color' => '#6b46c1'], // Indigo
        'Mystery'     => ['bg' => '#e2e8f0', 'color' => '#475569'], // Slate
        'Crime'       => ['bg' => '#fee2e2', 'color' => '#991b1b'], // Dark Red
    ];

    return $palette[$genre] ?? ['bg' => '#f1f1ef', 'color' => '#55534e'];
}

/**
 * Render Genre tag badge
 */
function renderGenreBadge(string $genre): string {
    $style = getGenreBadgeStyle($genre);
    return sprintf(
        '<span class="notion-tag" style="background-color: %s; color: %s;">%s</span>',
        $style['bg'],
        $style['color'],
        e($genre)
    );
}

/**
 * Render Status badge
 */
function renderStatusBadge(string $status): string {
    $statusMap = [
        'watchlist' => [
            'label' => 'Watchlist',
            'icon'  => '📥',
            'class' => 'status-watchlist'
        ],
        'watching' => [
            'label' => 'Watching',
            'icon'  => '⏳',
            'class' => 'status-watching'
        ],
        'finished' => [
            'label' => 'Finished',
            'icon'  => '✅',
            'class' => 'status-finished'
        ],
    ];

    $cfg = $statusMap[$status] ?? ['label' => ucfirst($status), 'icon' => '📌', 'class' => 'status-default'];
    return sprintf(
        '<span class="notion-status-badge %s"><span class="status-icon">%s</span> %s</span>',
        $cfg['class'],
        $cfg['icon'],
        e($cfg['label'])
    );
}

/**
 * Render Age Rating badge
 */
function renderAgeRatingBadge(string $ageRating): string {
    return sprintf('<span class="notion-age-rating">%s</span>', e($ageRating));
}

/**
 * Format finished_date 
 */
function formatFinishedDate(?string $dateStr): string {
    if (empty($dateStr)) return '-';
    $time = strtotime($dateStr);
    return $time ? date('d M Y', $time) : e($dateStr);
}

/**
 * Handle image upload 
 */
function handlePosterUpload(array $file): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedTypes)) {
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'poster_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/../assets/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $targetPath = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'assets/uploads/' . $filename;
    }

    return null;
}
