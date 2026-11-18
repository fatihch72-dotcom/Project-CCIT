🎬 Movie Tracker 

Ya buat ngetrack film yang ditonton, apa lagi

## Fitur Utama

1. Desain simple gak lebay
2. Filter & Sort View Cepat
   - Filter instan berdasarkan Status (All, Watchlist, Watching, Finished).
   - Filter dropdown berdasarkan Genre.
   - Pengurutan dinamis berdasarkan Rating, Tahun Rilis, Tanggal Selesai, dan Judul Film (A-Z).
   - Pencarian instan berdasarkan judul film.
3. Operasi CRUD Lengkap 
   - Create: Tambah film baru dengan input judul, tahun rilis, age rating, multi-select genre, status, rating 1-5 bintang, tanggal selesai, review notes, serta dukungan Poster URL atau Upload File gambar.
   - Read: Tampilan Gallery Grid dan Modal Peek Detail.
   - Update: Edit data properti film dan review kapan saja melalui modal edit.
   - Delete: Hapus film dari watchlist user.

5. Database Relasional MySQL :
   - `User`
   - `Title`
   - `Genre`
   - `Title_Genre` (Many-to-Many Title & Genre)
   - `User_watchlist` (Relasi user & title beserta status, rating, notes, finished_date)

## Struktur Folder Project
Movie Tracker/
├── index.php                      # Dashboard utama (Gallery View, Tabs, Filter, Modal)
├── config/
│   └── database.php               # Konfigurasi PDO MySQL & helper koneksi
├── database/
│   ├── schema.sql                 # DDL (5 tabel relasional) & DML (sample data lengkap)
│   └── setup.php                  # Script installer & migrasi database sekali klik
├── includes/
│   ├── auth_check.php             # Middleware pengecekan session user
│   ├── functions.php              # Helper fungsi (Notion badge pastel, bintang, sanitizer)
│   ├── header.php                 # Header & Navbar minimalis Notion
│   └── footer.php                 # Footer & skrip penutup
├── auth/
│   ├── login.php                  # Halaman Login Notion-style
│   ├── register.php               # Halaman Pendaftaran akun baru (dengan Secret PIN)
│   ├── forgot_password.php        # Halaman Reset Password dengan Secret PIN / Master Key
│   └── logout.php                 # Logout session handler
├── actions/
│   ├── add_movie.php              # Handler Create (tambah film baru)
│   ├── edit_movie.php             # Handler Update (edit film & watchlist)
│   ├── delete_movie.php           # Handler Delete (hapus film dari watchlist)
│   └── get_movie.php              # Endpoint API JSON untuk data modal detail
├── assets/
│   ├── css/
│   │   └── style.css              # Styling lengkap bertema Notion
│   ├── js/
│   │   └── app.js                 # Logika interaktif modal, filter, sort, dan rating
│   └── uploads/                   # Direktori penyimpanan poster hasil upload
└── README.md                      # Dokumentasi 

## Skema Database (MySQL)

Aplikasi menggunakan 5 tabel relasional:

1. user
   - `id` (INT, Primary Key, Auto Increment)
   - `username` (VARCHAR 50, Unique)
   - `password` (VARCHAR 255, Bcrypt Hash)
   - `secret_pin` (VARCHAR 255, Hashed PIN untuk pemulihan akun)
   - `created_at` (TIMESTAMP)

2. Title
   - `id` (INT, Primary Key, Auto Increment)
   - `title` (VARCHAR 255)
   - `release_year` (INT 4)
   - `age_rating` (VARCHAR 20: G, PG, PG-13, R, NC-17, dll.)
   - `poster_url` (TEXT)

3. Genre
   - `id` (INT, Primary Key, Auto Increment)
   - `genre` (VARCHAR 50, Unique: Action, Sci-Fi, Drama, dll.)

4. Title_Genre (Many-to-Many)
   - `title_id` (INT, FK ke Title.id, ON DELETE CASCADE)
   - `genre_id` (INT, FK ke Genre.id, ON DELETE CASCADE)
   - Primary Key: `(title_id, genre_id)`

5. User_watchlist
   - `id` (INT, Primary Key, Auto Increment)
   - `user_id` (INT, FK ke User.id, ON DELETE CASCADE)
   - `title_id` (INT, FK ke Title.id, ON DELETE CASCADE)
   - `status` (ENUM: `'watchlist'`, `'watching'`, `'finished'`)
   - `rating` (TINYINT: Skala 1 - 5 bintang)
   - `notes` (TEXT: Ulasan / komentar / review)
   - `finished_date` (DATE: Tanggal selesai menonton)
   - `created_at` (TIMESTAMP)
