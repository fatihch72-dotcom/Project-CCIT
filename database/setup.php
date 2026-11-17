<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$isCli = (php_sapi_name() === 'cli');

function outputMsg($msg, $type = 'info') {
    global $isCli;
    if ($isCli) {
        echo "[$type] " . strip_tags($msg) . PHP_EOL;
    } else {
        $color = $type === 'success' ? '#0f766e' : ($type === 'error' ? '#b91c1c' : '#37352f');
        $bg = $type === 'success' ? '#f0fdfa' : ($type === 'error' ? '#fef2f2' : '#f7f6f3');
        echo "<div style='padding:12px 16px; margin-bottom:10px; border-radius:6px; background:{$bg}; color:{$color}; font-family:sans-serif; font-size:14px; border:1px solid rgba(0,0,0,0.06);'>{$msg}</div>";
    }
}

try {
    // 1. Buat Connect ke MySQL Server 
    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    $dbname = DB_NAME;

    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // 2. buat ngebaca schema.sql
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("schema.sql file not found at " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);

    // 3. nge Execute SQL statements
    $pdo->exec($sql);

    outputMsg("Database `{$dbname}` and all 5 tables created successfully!", "success");
    outputMsg("Sample users, genres, movies, and watchlists loaded!", "success");
    outputMsg("Default Login: <strong>demo</strong> | Password: <strong>password123</strong> | Secret PIN: <strong>123456</strong> | Master Key: <strong>reset123</strong>", "info");

    if (!$isCli) {
        echo "<p style='font-family:sans-serif; margin-top:20px;'><a href='../index.php' style='display:inline-block; padding:10px 18px; background:#2383e2; color:#fff; text-decoration:none; border-radius:6px; font-weight:500;'>Go to Movie Tracker &rarr;</a></p>";
    }

} catch (PDOException $e) {
    outputMsg("Database Setup Error: " . $e->getMessage(), "error");
} catch (Exception $e) {
    outputMsg("Error: " . $e->getMessage(), "error");
}
