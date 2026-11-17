document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------
    // 1. Modals Element References
    // -------------------------------------------------------------
    const detailModalOverlay = document.getElementById('detailModalOverlay');
    const formModalOverlay = document.getElementById('formModalOverlay');
    const deleteForm = document.getElementById('deleteMovieForm');
    const deleteWatchlistIdInput = document.getElementById('deleteWatchlistId');

    // Detail Modal Elements
    const detailBannerImg = document.getElementById('detailBannerImg');
    const detailTitle = document.getElementById('detailTitle');
    const detailStatus = document.getElementById('detailStatus');
    const detailGenres = document.getElementById('detailGenres');
    const detailYear = document.getElementById('detailYear');
    const detailAge = document.getElementById('detailAge');
    const detailRating = document.getElementById('detailRating');
    const detailFinishedDate = document.getElementById('detailFinishedDate');
    const detailNotes = document.getElementById('detailNotes');
    const btnDetailEdit = document.getElementById('btnDetailEdit');
    const btnDetailDelete = document.getElementById('btnDetailDelete');

    // Add / Edit Modal Form Elements
    const movieForm = document.getElementById('movieForm');
    const modalFormTitle = document.getElementById('modalFormTitle');
    const formWatchlistId = document.getElementById('formWatchlistId');
    const formTitle = document.getElementById('formTitle');
    const formReleaseYear = document.getElementById('formReleaseYear');
    const formAgeRating = document.getElementById('formAgeRating');
    const formStatus = document.getElementById('formStatus');
    const formRating = document.getElementById('formRating');
    const formFinishedDate = document.getElementById('formFinishedDate');
    const formFinishedDateGroup = document.getElementById('formFinishedDateGroup');
    const formNotes = document.getElementById('formNotes');
    const formPosterUrl = document.getElementById('formPosterUrl');
    const formPosterFile = document.getElementById('formPosterFile');

    let currentMovieData = null;

    // -------------------------------------------------------------
    // 2. Open / Close Modal Helpers
    // -------------------------------------------------------------
    function openModal(overlay) {
        if (!overlay) return;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(overlay) {
        if (!overlay) return;
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function closeAllModals() {
        closeModal(detailModalOverlay);
        closeModal(formModalOverlay);
    }

    // Close on backdrop click or close buttons
    document.querySelectorAll('.notion-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // -------------------------------------------------------------
    // 3. Star Rating Logic for Forms
    // -------------------------------------------------------------
    function setupStarRating(containerId, hiddenInput) {
        const container = document.getElementById(containerId);
        if (!container || !hiddenInput) return;

        const stars = container.querySelectorAll('.star-item');

        function updateStarsVisual(value) {
            stars.forEach(star => {
                const starVal = parseInt(star.getAttribute('data-value'));
                if (starVal <= value) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        stars.forEach(star => {
            star.addEventListener('mouseenter', () => {
                const val = parseInt(star.getAttribute('data-value'));
                stars.forEach(s => {
                    const sVal = parseInt(s.getAttribute('data-value'));
                    if (sVal <= val) {
                        s.classList.add('hovered');
                    } else {
                        s.classList.remove('hovered');
                    }
                });
            });

            star.addEventListener('mouseleave', () => {
                stars.forEach(s => s.classList.remove('hovered'));
            });

            star.addEventListener('click', () => {
                const val = parseInt(star.getAttribute('data-value'));
                const currentVal = parseInt(hiddenInput.value) || 0;
                if (currentVal === val) {
                    // Click again to unselect
                    hiddenInput.value = '';
                    updateStarsVisual(0);
                } else {
                    hiddenInput.value = val;
                    updateStarsVisual(val);
                }
            });
        });

        // Initialize with input's value
        updateStarsVisual(parseInt(hiddenInput.value) || 0);
    }

    setupStarRating('formStarRating', formRating);

    // -------------------------------------------------------------
    // 4. Status Change & Finished Date Auto-Fill
    // -------------------------------------------------------------
    if (formStatus && formFinishedDate) {
        formStatus.addEventListener('change', () => {
            if (formStatus.value === 'finished') {
                if (!formFinishedDate.value) {
                    const today = new Date().toISOString().split('T')[0];
                    formFinishedDate.value = today;
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 5. Open Detail Modal on Card Click
    // -------------------------------------------------------------
    document.querySelectorAll('.movie-card').forEach(card => {
        card.addEventListener('click', async (e) => {
            const watchlistId = card.getAttribute('data-id');
            if (!watchlistId) return;

            try {
                // Fetch latest details
                const res = await fetch(`actions/get_movie.php?id=${watchlistId}`);
                const json = await res.json();

                if (!json.success || !json.data) {
                    alert('Gagal mengambil data film: ' + (json.error || 'Unknown error'));
                    return;
                }

                const data = json.data;
                currentMovieData = data;

                // Populate Detail Modal
                detailTitle.textContent = data.title;
                detailYear.textContent = data.release_year;
                detailAge.innerHTML = data.age_badge_html;
                detailStatus.innerHTML = data.status_badge_html;
                detailGenres.innerHTML = data.genre_badges_html || '<span class="text-muted">-</span>';
                detailRating.innerHTML = data.stars_html;
                detailFinishedDate.textContent = data.formatted_finished_date || '-';
                
                if (data.notes && data.notes.trim() !== '') {
                    detailNotes.innerHTML = `<div class="notes-body">${escapeHtml(data.notes)}</div>`;
                } else {
                    detailNotes.innerHTML = `<div class="notes-empty">Belum ada catatan atau review untuk film ini.</div>`;
                }

                // Poster/Banner
                if (data.poster_url) {
                    detailBannerImg.src = data.poster_url;
                    detailBannerImg.style.display = 'block';
                } else {
                    detailBannerImg.style.display = 'none';
                }

                openModal(detailModalOverlay);
            } catch (err) {
                console.error('Error fetching movie details:', err);
            }
        });
    });

    // -------------------------------------------------------------
    // 6. "+ New" Button -> Open Add Modal
    // -------------------------------------------------------------
    const btnNewMovie = document.getElementById('btnNewMovie');
    if (btnNewMovie) {
        btnNewMovie.addEventListener('click', () => {
            resetMovieForm();
            modalFormTitle.textContent = 'Tambah Film Baru';
            movieForm.action = 'actions/add_movie.php';
            openModal(formModalOverlay);
        });
    }

    // -------------------------------------------------------------
    // 7. Edit Button -> Open Edit Modal
    // -------------------------------------------------------------
    if (btnDetailEdit) {
        btnDetailEdit.addEventListener('click', () => {
            if (!currentMovieData) return;
            closeModal(detailModalOverlay);
            populateEditForm(currentMovieData);
            openModal(formModalOverlay);
        });
    }

    function resetMovieForm() {
        movieForm.reset();
        formWatchlistId.value = '';
        formRating.value = '';
        formStatus.value = 'watchlist';
        formReleaseYear.value = new Date().getFullYear();
        formAgeRating.value = 'PG-13';

        // Uncheck all genres
        document.querySelectorAll('.genre-checkbox-label input').forEach(cb => {
            cb.checked = false;
            cb.closest('.genre-checkbox-label').classList.remove('checked');
        });

        // Reset visual stars
        const stars = document.querySelectorAll('#formStarRating .star-item');
        stars.forEach(s => s.classList.remove('active', 'hovered'));
    }

    function populateEditForm(data) {
        resetMovieForm();
        modalFormTitle.textContent = 'Edit Data Film';
        movieForm.action = 'actions/edit_movie.php';

        formWatchlistId.value = data.watchlist_id;
        formTitle.value = data.title;
        formReleaseYear.value = data.release_year;
        formAgeRating.value = data.age_rating;
        formStatus.value = data.status;
        formRating.value = data.rating || '';
        formFinishedDate.value = data.finished_date || '';
        formNotes.value = data.notes || '';
        formPosterUrl.value = data.poster_url || '';

        // Check genre checkboxes
        const genreIds = data.genre_ids || [];
        document.querySelectorAll('.genre-checkbox-label input').forEach(cb => {
            if (genreIds.includes(parseInt(cb.value))) {
                cb.checked = true;
                cb.closest('.genre-checkbox-label').classList.add('checked');
            } else {
                cb.checked = false;
                cb.closest('.genre-checkbox-label').classList.remove('checked');
            }
        });

        // Visual stars
        const ratingVal = parseInt(data.rating) || 0;
        const stars = document.querySelectorAll('#formStarRating .star-item');
        stars.forEach(s => {
            if (parseInt(s.getAttribute('data-value')) <= ratingVal) {
                s.classList.add('active');
            }
        });
    }

    // Toggle genre pill visual state on checkbox change
    document.querySelectorAll('.genre-checkbox-label input').forEach(cb => {
        cb.addEventListener('change', () => {
            const label = cb.closest('.genre-checkbox-label');
            if (cb.checked) {
                label.classList.add('checked');
            } else {
                label.classList.remove('checked');
            }
        });
    });

    // -------------------------------------------------------------
    // 8. Delete Movie Action
    // -------------------------------------------------------------
    if (btnDetailDelete) {
        btnDetailDelete.addEventListener('click', () => {
            if (!currentMovieData) return;
            const confirmMsg = `Apakah Anda yakin ingin menghapus "${currentMovieData.title}" dari watchlist?`;
            if (confirm(confirmMsg)) {
                deleteWatchlistIdInput.value = currentMovieData.watchlist_id;
                deleteForm.submit();
            }
        });
    }

    // -------------------------------------------------------------
    // 9. Instant Gallery Filter & Sort
    // -------------------------------------------------------------
    const searchInput = document.getElementById('searchMovies');
    const genreFilter = document.getElementById('genreFilter');
    const sortSelect = document.getElementById('sortSelect');
    const galleryGrid = document.getElementById('galleryGrid');
    const emptyState = document.getElementById('emptyGalleryState');
    const viewTabs = document.querySelectorAll('.view-tab');

    let activeStatusTab = 'all';

    // Status Tab clicks
    viewTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            viewTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeStatusTab = tab.getAttribute('data-status') || 'all';
            filterAndSortGallery();
        });
    });

    if (searchInput) searchInput.addEventListener('input', filterAndSortGallery);
    if (genreFilter) genreFilter.addEventListener('change', filterAndSortGallery);
    if (sortSelect) sortSelect.addEventListener('change', filterAndSortGallery);

    function filterAndSortGallery() {
        if (!galleryGrid) return;

        const cards = Array.from(galleryGrid.querySelectorAll('.movie-card'));
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedGenre = genreFilter ? genreFilter.value : '';
        const sortMode = sortSelect ? sortSelect.value : 'default';

        let visibleCount = 0;

        // 1. Filter
        cards.forEach(card => {
            const cardStatus = card.getAttribute('data-status') || '';
            const cardTitle = (card.getAttribute('data-title') || '').toLowerCase();
            const cardGenres = (card.getAttribute('data-genres') || '').split(',');

            const matchStatus = (activeStatusTab === 'all' || cardStatus === activeStatusTab);
            const matchSearch = (query === '' || cardTitle.includes(query));
            const matchGenre = (selectedGenre === '' || cardGenres.includes(selectedGenre));

            if (matchStatus && matchSearch && matchGenre) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // 2. Sort visible cards
        cards.sort((a, b) => {
            if (sortMode === 'rating-desc') {
                return (parseFloat(b.getAttribute('data-rating')) || 0) - (parseFloat(a.getAttribute('data-rating')) || 0);
            } else if (sortMode === 'rating-asc') {
                return (parseFloat(a.getAttribute('data-rating')) || 0) - (parseFloat(b.getAttribute('data-rating')) || 0);
            } else if (sortMode === 'year-desc') {
                return (parseInt(b.getAttribute('data-year')) || 0) - (parseInt(a.getAttribute('data-year')) || 0);
            } else if (sortMode === 'year-asc') {
                return (parseInt(a.getAttribute('data-year')) || 0) - (parseInt(b.getAttribute('data-year')) || 0);
            } else if (sortMode === 'date-desc') {
                const dateA = a.getAttribute('data-finished') || '';
                const dateB = b.getAttribute('data-finished') || '';
                return dateB.localeCompare(dateA);
            } else if (sortMode === 'title-asc') {
                return (a.getAttribute('data-title') || '').localeCompare(b.getAttribute('data-title') || '');
            }
            return 0;
        });

        // Re-append sorted cards
        cards.forEach(card => galleryGrid.appendChild(card));

        // Show/hide empty state
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
