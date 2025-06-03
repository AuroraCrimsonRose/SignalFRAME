<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// /api/update-theme.php

require_once __DIR__ . '/../engine/auth.php';

$user = requireAuth(); // Validate API token

$station = $_GET['station'] ?? null;
$newTheme = $_POST['theme'] ?? null;

if (!$station || !$newTheme) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing station or theme.']);
    exit;
}

$stationPath = __DIR__ . "/../stations/" . basename($station);
$configPath = "$stationPath/config.json";

if (!file_exists($configPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Station config not found.']);
    exit;
}

$config = json_decode(file_get_contents($configPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid config.json format.']);
    exit;
}

$config['theme'] = $newTheme;
file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

http_response_code(200);
echo json_encode(['success' => true, 'theme' => $newTheme]);
