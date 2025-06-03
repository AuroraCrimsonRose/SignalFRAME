<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

// /engine/auth.php
require_once __DIR__ . '/db.php';

function requireAuth() {
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? null;

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing API token.']);
        exit;
    }

    $user = getUserByToken($token);
    if (!$user) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid API token.']);
        exit;
    }

    return $user;
}
