<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

function getUserByToken($token) {
    $dbFile = __DIR__ . '/../config/users.sqlite';
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('
        SELECT users.id, users.username, users.role, users.station_id
        FROM users
        JOIN api_tokens ON users.id = api_tokens.user_id
        WHERE api_tokens.token = ? AND api_tokens.revoked = 0
        LIMIT 1
    ');
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getDbConnection() {
    // Match the same path used by setup.php
    $dbFile = __DIR__ . '/../config/users.sqlite';

    if (! file_exists($dbFile)) {
        throw new Exception("SQLite file not found at $dbFile");
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
