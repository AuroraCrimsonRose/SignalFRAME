<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns the number of API tokens (rows in api_tokens).
 */

function getTokenCount(): int
{
    $dbFile = __DIR__ . '/../config/users.sqlite';
    $pdo    = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM api_tokens");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($row['cnt']);
    } catch (Exception $e) {
        return 0;
    }
}
