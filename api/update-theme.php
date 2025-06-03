<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

session_start();
header('Content-Type: application/json');

function unauthorized() {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Use session user if available
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];  // <-- SESSION user, no token needed here
} else {
    // Otherwise require API token
    $token = null;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    } elseif (isset($_POST['api_token'])) {
        $token = $_POST['api_token'];
    }

    if (!$token) unauthorized();

    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../config/users.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('
            SELECT users.id, users.username, users.role 
            FROM users 
            JOIN api_tokens ON users.id = api_tokens.user_id 
            WHERE api_tokens.token = ? AND api_tokens.revoked = 0
        ');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) unauthorized();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
}


$station = $_GET['station'] ?? null;
if (!$station) {
    echo json_encode(['error' => 'Missing station parameter.']);
    exit;
}

$newTheme = $_POST['theme'] ?? '';
if (!$newTheme) {
    echo json_encode(['error' => 'Missing theme parameter.']);
    exit;
}

$stationPath = __DIR__ . "/../stations/$station";
$configFile = "$stationPath/config.json";

if (!file_exists($configFile)) {
    echo json_encode(['error' => 'Station config not found.']);
    exit;
}

$config = json_decode(file_get_contents($configFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Invalid station config JSON.']);
    exit;
}

$config['theme'] = $newTheme;

if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
    echo json_encode(['error' => 'Failed to save config.']);
    exit;
}

echo json_encode(['success' => true]);
