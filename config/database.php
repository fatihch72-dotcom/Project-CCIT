<?php

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'movie_tracker');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_PORT')) define('DB_PORT', '3306');

/**
 * mengecek dan menghubungkan database menggunakan enkripsi karakter UTF-8.
 * mengembalikan instance koneksi database PDO yang dipakai bersama
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // ngecek struktur tabel user di database secara otomatis, dan langsung nambahin kolom secret_pin kalau ternyata kolom tersebut belum ada
            try {
                $checkCol = $pdo->query("SHOW COLUMNS FROM `User` LIKE 'secret_pin'")->fetch();
                if (!$checkCol) {
                    $pdo->exec("ALTER TABLE `User` ADD COLUMN `secret_pin` VARCHAR(255) NULL AFTER `password`");
                }
            } catch (Exception $colEx) {
                // Ignore kalo tabelnya gak ada
            }

        } catch (PDOException $e) {
            // ngecek error gak karena database gak ada
            if ($e->getCode() == 1049) {
                // nyoba bikin database terus nge run setup otomatis
                try {
                    $initPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
                    $schemaFile = __DIR__ . '/../database/schema.sql';
                    if (file_exists($schemaFile)) {
                        $sql = file_get_contents($schemaFile);
                        $initPdo->exec($sql);
                        // Reconnect ke database yg baru dibuat
                        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                        return $pdo;
                    }
                } catch (Exception $initEx) {
                    die("Database initialization error: " . htmlspecialchars($initEx->getMessage()));
                }
            }

            die("Database connection failed: " . htmlspecialchars($e->getMessage()) . "<br>Please ensure MySQL is running and check your credentials in <code>config/database.php</code>.");
        }
    }

    return $pdo;
}

/**
 * mengembalikan instance koneksi database MySQLi yang dipakai bersama
 * 
 * @return mysqli
 */
function getMySQLiConnection(): mysqli {
    static $mysqli = null;

    if ($mysqli === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
            $mysqli->set_charset("utf8mb4");

            try {
                $check = $mysqli->query("SHOW COLUMNS FROM `User` LIKE 'secret_pin'");
                if ($check && $check->num_rows === 0) {
                    $mysqli->query("ALTER TABLE `User` ADD COLUMN `secret_pin` VARCHAR(255) NULL AFTER `password`");
                }
            } catch (Throwable $t) {
                try {
                    $check2 = $mysqli->query("SHOW COLUMNS FROM `users` LIKE 'secret_pin'");
                    if ($check2 && $check2->num_rows === 0) {
                        $mysqli->query("ALTER TABLE `users` ADD COLUMN `secret_pin` VARCHAR(255) NULL AFTER `password`");
                    }
                } catch (Throwable $t2) {
                }
            }
        } catch (mysqli_sql_exception $e) {
            die("MySQLi connection failed: " . htmlspecialchars($e->getMessage()) . "<br>Please ensure MySQL is running.");
        }
    }

    return $mysqli;
}
