<?php
/**
 * Authentication Middleware Check
 */
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
} else {
    require_once __DIR__ . '/../config/database.php';
    try {
        $pdo = getDBConnection();
        $stmtUser = $pdo->prepare("SELECT id FROM `User` WHERE id = :id LIMIT 1");
        $stmtUser->execute([':id' => currentUserId()]);
        if (!$stmtUser->fetch()) {
            $_SESSION = [];
            header('Location: auth/register.php');
            exit;
        }
    } catch (Exception $e) {
        // Fallback
    }
}
