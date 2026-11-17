<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$userId = currentUserId();

// 1. ngambil seluruh data genre dari database untuk dipakai di filter pencarian dan opsi centang (checkbox) pada modal
$stmtAllGenres = $pdo->query("SELECT id, genre FROM `Genre` ORDER BY genre ASC");
$allGenres = $stmtAllGenres->fetchAll();

// 2. ngambil film buat user
$stmtMovies = $pdo->prepare("
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
        t.poster_url,
        GROUP_CONCAT(g.genre ORDER BY g.genre SEPARATOR ', ') AS genre_names_str,
        GROUP_CONCAT(g.id ORDER BY g.id SEPARATOR ',') AS genre_ids_str
    FROM `User_watchlist` w
    JOIN `Title` t ON w.title_id = t.id
    LEFT JOIN `Title_Genre` tg ON t.id = tg.title_id
    LEFT JOIN `Genre` g ON tg.genre_id = g.id
    WHERE w.user_id = :user_id
    GROUP BY w.id
    ORDER BY w.id DESC
");
$stmtMovies->execute([':user_id' => $userId]);
$movies = $stmtMovies->fetchAll();

// 3. ngitung status film
$countAll = count($movies);
$countWatchlist = 0;
$countWatching = 0;
$countFinished = 0;

foreach ($movies as $m) {
    if ($m['status'] === 'watchlist') $countWatchlist++;
    elseif ($m['status'] === 'watching') $countWatching++;
    elseif ($m['status'] === 'finished') $countFinished++;
}

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Notion Page Banner Cover -->
<div class="page-cover"></div>

<div class="app-container">
    <!-- Notion Header Section -->
    <header class="page-header">
        <div class="page-icon" title="Movie Tracker Icon">🎬</div>
        <h1 class="page-title">MOVIE LIST</h1>
        <p class="page-description">
            Koleksi jurnal dan watchlist film pribadi Anda. Pantau status tontonan, beri rating, dan simpan review film favorit.
        </p>
    </header>

    <!-- Flash Notifications -->
    <?php if ($flashSuccess): ?>
        <div class="notion-callout callout-success">
            <span class="callout-icon">✅</span>
            <div class="callout-text"><?= e($flashSuccess) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="notion-callout callout-error">
            <span class="callout-icon">⚠️</span>
            <div class="callout-text"><?= e($flashError) ?></div>
        </div>
    <?php endif; ?>

    <!-- Database Toolbar & Controls -->
    <div class="db-controls-container">
        <!-- View Tabs -->
        <div class="view-tabs">
            <a href="#" class="view-tab active" data-status="all">
                <span>🎬 All Movies</span>
                <span class="tab-count"><?= $countAll ?></span>
            </a>
            <a href="#" class="view-tab" data-status="watchlist">
                <span>📥 Watchlist</span>
                <span class="tab-count"><?= $countWatchlist ?></span>
            </a>
            <a href="#" class="view-tab" data-status="watching">
                <span>⏳ Watching</span>
                <span class="tab-count"><?= $countWatching ?></span>
            </a>
            <a href="#" class="view-tab" data-status="finished">
                <span>✅ Finished</span>
                <span class="tab-count"><?= $countFinished ?></span>
            </a>
        </div>

        <!-- Filter, Search, Sort & Action Row -->
        <div class="toolbar-actions">
            <div class="toolbar-left">
                <!-- Search Input -->
                <div class="search-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchMovies" class="search-input" placeholder="Cari judul film...">
                </div>

                <!-- Genre Filter Dropdown -->
                <select id="genreFilter" class="notion-select">
                    <option value="">🎭 Semua Genre</option>
                    <?php foreach ($allGenres as $g): ?>
                        <option value="<?= e($g['genre']) ?>"><?= e($g['genre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Sort Dropdown -->
                <select id="sortSelect" class="notion-select">
                    <option value="default">↕️ Urutkan: Default (Terbaru)</option>
                    <option value="rating-desc">⭐ Rating: Tertinggi &rarr; Terendah</option>
                    <option value="rating-asc">⭐ Rating: Terendah &rarr; Tertinggi</option>
                    <option value="year-desc">📅 Tahun: Terbaru &rarr; Terlama</option>
                    <option value="year-asc">📅 Tahun: Terlama &rarr; Terbaru</option>
                    <option value="date-desc">🏁 Selesai: Baru Ditonton</option>
                    <option value="title-asc">🔤 Judul: A &rarr; Z</option>
                </select>
            </div>

            <div class="toolbar-right">
                <button type="button" id="btnNewMovie" class="notion-btn notion-btn-primary">
                    <span>+ New Movie</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Gallery View Grid -->
    <div class="gallery-grid" id="galleryGrid">
        <?php foreach ($movies as $movie): 
            $genreList = !empty($movie['genre_names_str']) ? explode(', ', $movie['genre_names_str']) : [];
        ?>
            <div class="movie-card" 
                 data-id="<?= $movie['watchlist_id'] ?>"
                 data-title="<?= e($movie['title']) ?>"
                 data-status="<?= e($movie['status']) ?>"
                 data-genres="<?= e($movie['genre_names_str'] ?? '') ?>"
                 data-rating="<?= $movie['rating'] ?? 0 ?>"
                 data-year="<?= $movie['release_year'] ?>"
                 data-finished="<?= e($movie['finished_date'] ?? '') ?>">
                
                <!-- Card Poster -->
                <div class="card-poster">
                    <?php if (!empty($movie['poster_url'])): ?>
                        <img src="<?= e($movie['poster_url']) ?>" alt="<?= e($movie['title']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'poster-fallback\'><span class=\'fallback-icon\'>🎬</span><span><?= e($movie['title']) ?></span></div>';">
                    <?php else: ?>
                        <div class="poster-fallback">
                            <span class="fallback-icon">🎬</span>
                            <span style="font-size: 11px; font-weight: 500;"><?= e($movie['title']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                    <div class="card-title-row">
                        <h3 class="card-title"><?= e($movie['title']) ?></h3>
                    </div>

                    <!-- Year & Age Rating -->
                    <div class="card-meta-row">
                        <span><?= e($movie['release_year']) ?></span>
                        <span>•</span>
                        <?= renderAgeRatingBadge($movie['age_rating']) ?>
                    </div>

                    <!-- Genre Pastel Tags -->
                    <div class="card-tags">
                        <?php foreach ($genreList as $gName): ?>
                            <?= renderGenreBadge($gName) ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Status Badge -->
                    <div>
                        <?= renderStatusBadge($movie['status']) ?>
                    </div>

                    <!-- Footer: Finished Date & Star Rating -->
                    <div class="card-footer">
                        <div>
                            <?= renderStars($movie['rating']) ?>
                        </div>
                        <?php if ($movie['status'] === 'finished' && !empty($movie['finished_date'])): ?>
                            <span class="card-finished-date" title="Tanggal selesai">
                                🏁 <?= formatFinishedDate($movie['finished_date']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Empty State (muncul kalo gak ada filmnya) -->
        <div class="empty-gallery" id="emptyGalleryState" style="<?= empty($movies) ? 'display:block;' : 'display:none;' ?>">
            <div class="empty-icon">🎬</div>
            <h4 class="empty-title">Belum ada film yang cocok</h4>
            <p class="empty-subtitle">Klik tombol "+ New Movie" di atas untuk menambahkan film baru ke dalam watchlist Anda.</p>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODAL 1: DETAIL FILM
     ============================================================== -->
<div class="notion-modal-overlay" id="detailModalOverlay">
    <div class="notion-modal" role="dialog" aria-modal="true">
        <!-- Top Poster Banner Preview -->
        <div class="modal-header-banner">
            <img id="detailBannerImg" src="" alt="Banner Preview">
            <div class="modal-top-actions">
                <button type="button" class="modal-action-btn" id="btnDetailEdit" title="Edit property film">
                    <span>✏️ Edit</span>
                </button>
                <button type="button" class="modal-action-btn" id="btnDetailDelete" style="color: var(--notion-red);" title="Hapus dari watchlist">
                    <span>🗑️ Hapus</span>
                </button>
                <button type="button" class="modal-action-btn btn-close btn-close-modal" title="Tutup">
                    <span>✕</span>
                </button>
            </div>
        </div>

        <div class="modal-content">
            <div class="modal-page-icon">🎬</div>
            <h2 class="modal-title" id="detailTitle">Judul Film</h2>

            <!-- Property Table List -->
            <div class="property-table">
                <div class="property-row">
                    <div class="property-label">🏷️ Status</div>
                    <div class="property-value" id="detailStatus">-</div>
                </div>

                <div class="property-row">
                    <div class="property-label">🎭 Genre</div>
                    <div class="property-value" id="detailGenres">-</div>
                </div>

                <div class="property-row">
                    <div class="property-label">📅 Release Year</div>
                    <div class="property-value" id="detailYear">-</div>
                </div>

                <div class="property-row">
                    <div class="property-label">🔞 Age Rating</div>
                    <div class="property-value" id="detailAge">-</div>
                </div>

                <div class="property-row">
                    <div class="property-label">⭐ Rating</div>
                    <div class="property-value" id="detailRating">-</div>
                </div>

                <div class="property-row">
                    <div class="property-label">🏁 Date Completed</div>
                    <div class="property-value" id="detailFinishedDate">-</div>
                </div>
            </div>

            <div class="notion-divider"></div>

            <!-- Notes & Review Section -->
            <div class="modal-notes-section">
                <div class="notes-heading">
                    <span>📝 Notes & Review</span>
                </div>
                <div id="detailNotes"></div>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODAL 2: ADD / EDIT MOVIE 
     ============================================================== -->
<div class="notion-modal-overlay" id="formModalOverlay">
    <div class="notion-modal" role="dialog" aria-modal="true">
        <div style="padding: 16px 24px; border-bottom: 1px solid var(--notion-border); display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">🎬</span>
                <h3 id="modalFormTitle" style="font-size: 16px; font-weight: 600; color: var(--notion-text);">Tambah Film Baru</h3>
            </div>
            <button type="button" class="modal-action-btn btn-close btn-close-modal" title="Tutup">✕</button>
        </div>

        <form id="movieForm" method="POST" action="actions/add_movie.php" enctype="multipart/form-data" class="notion-form" style="padding: 24px; overflow-y: auto;">
            <!-- Hidden Watchlist ID (used during edit) -->
            <input type="hidden" name="watchlist_id" id="formWatchlistId" value="">

            <div class="form-group">
                <label class="form-label" for="formTitle">Judul Film *</label>
                <input type="text" id="formTitle" name="title" class="form-input" placeholder="Contoh: Goodbye, Eri" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="formReleaseYear">Tahun Rilis *</label>
                    <input type="number" id="formReleaseYear" name="release_year" class="form-input" min="1888" max="2099" value="<?= date('Y') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="formAgeRating">Age Rating *</label>
                    <select id="formAgeRating" name="age_rating" class="form-select" required>
                        <option value="G">G (Semua Umur)</option>
                        <option value="PG">PG (Bimbingan Orang Tua)</option>
                        <option value="PG-13" selected>PG-13 (Remaja 13+)</option>
                        <option value="R">R (Dewasa 17+)</option>
                        <option value="NC-17">NC-17 (Hanya 18+)</option>
                        <option value="13+">13+</option>
                        <option value="17+">17+</option>
                        <option value="21+">21+</option>
                    </select>
                </div>
            </div>

            <!-- Genre Multi-Select -->
            <div class="form-group">
                <label class="form-label">Genre (Pilih satu atau lebih)</label>
                <div class="genre-selector">
                    <?php foreach ($allGenres as $g): ?>
                        <label class="genre-checkbox-label">
                            <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>">
                            <span><?= e($g['genre']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="formStatus">Status Tontonan *</label>
                    <select id="formStatus" name="status" class="form-select" required>
                        <option value="watchlist">📥 Watchlist (Ingin Ditonton)</option>
                        <option value="watching">⏳ Watching (Sedang Ditonton)</option>
                        <option value="finished">✅ Finished (Selesai Ditonton)</option>
                    </select>
                </div>

                <div class="form-group" id="formFinishedDateGroup">
                    <label class="form-label" for="formFinishedDate">Tanggal Selesai (Finished Date)</label>
                    <input type="date" id="formFinishedDate" name="finished_date" class="form-input">
                </div>
            </div>

            <!-- Star Rating (1 - 5) -->
            <div class="form-group">
                <label class="form-label">Rating Bintang (1 - 5 ⭐)</label>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="star-rating-select" id="formStarRating">
                        <span class="star-item" data-value="1">★</span>
                        <span class="star-item" data-value="2">★</span>
                        <span class="star-item" data-value="3">★</span>
                        <span class="star-item" data-value="4">★</span>
                        <span class="star-item" data-value="5">★</span>
                    </div>
                    <span style="font-size: 12px; color: var(--notion-text-light);">(Klik bintang untuk memilih/batalkan)</span>
                </div>
                <input type="hidden" name="rating" id="formRating" value="">
            </div>

            <!-- Notes & Review -->
            <div class="form-group">
                <label class="form-label" for="formNotes">Catatan / Review / Komentar Pribadi</label>
                <textarea id="formNotes" name="notes" class="form-textarea" placeholder="Tuliskan ulasan, pemikiran, kutipan favorit, atau kesanmu setelah menonton..."></textarea>
            </div>

            <!-- Poster Input: URL or Upload -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="formPosterUrl">Poster Image URL</label>
                    <input type="url" id="formPosterUrl" name="poster_url" class="form-input" placeholder="https://example.com/poster.jpg">
                    <span class="form-help">Bisa masukkan tautan gambar dari TMDB, IMDb, dsb.</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="formPosterFile">Atau Upload Gambar Poster</label>
                    <input type="file" id="formPosterFile" name="poster_file" class="form-input" accept="image/*">
                    <span class="form-help">Format: JPG, PNG, WEBP (Max 5MB)</span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--notion-border);">
                <button type="button" class="notion-btn notion-btn-default btn-close-modal">Batal</button>
                <button type="submit" class="notion-btn notion-btn-primary">Simpan Film</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteMovieForm" method="POST" action="actions/delete_movie.php" style="display: none;">
    <input type="hidden" name="watchlist_id" id="deleteWatchlistId" value="">
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
