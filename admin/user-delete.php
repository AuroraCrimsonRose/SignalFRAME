<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$usersDbFile = __DIR__ . '/../config/users.sqlite';

try {
    $pdo = new PDO('sqlite:' . $usersDbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = (int)($_GET['id'] ?? 0);
    $loggedInUserId = $_SESSION['user']['id'];

    if ($userId === 0) {
        $_SESSION['flash_message'] = 'Invalid user ID.';
        header('Location: user-manager.php');
        exit;
    }

    if ($userId === $loggedInUserId) {
        $_SESSION['flash_message'] = 'You cannot delete your own account.';
        header('Location: user-manager.php');
        exit;
    }

    // Delete user
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$userId]);

    // Delete api tokens for that user
    $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE user_id = ?');
    $stmt->execute([$userId]);

    $_SESSION['flash_message'] = 'User deleted successfully.';
    header('Location: user-manager.php');
    exit;

} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Error deleting user: ' . htmlspecialchars($e->getMessage());
    header('Location: user-manager.php');
    exit;
}
