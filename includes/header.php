<?php
require_once __DIR__ . '/functions.php';
$username = currentUsername();
$initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie List · Movie Tracker</title>
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Minimalist Top Navbar -->
    <nav class="notion-navbar">
        <div class="nav-left"></div>
        <div class="nav-right">
            <div class="user-badge" title="Logged in as <?= e($username) ?>">
                <div class="user-avatar"><?= e($initial) ?></div>
                <span><?= e($username) ?></span>
            </div>
            <a href="auth/logout.php" class="notion-btn notion-btn-default" style="font-size: 12px; padding: 4px 10px;">
                Log out
            </a>
        </div>
    </nav>
