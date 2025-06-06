<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns an array of the 5 most recent logs:
 *  [ ['message' => '...', 'timestamp' => '...'], … ]
 */

function getRecentLogs(): array
{
    $dbFile = __DIR__ . '/../config/users.sqlite';
    $pdo    = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        $stmt = $pdo->query("SELECT message, timestamp FROM logs ORDER BY timestamp DESC LIMIT 5");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [['message'   => 'No log table found.',
                 'timestamp' => '']];
    }
}
