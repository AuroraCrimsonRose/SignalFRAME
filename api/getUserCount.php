<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns the total number of users in the “users” table.
 */

function getUserCount(): int
{
    $dbFile = __DIR__ . '/../config/users.sqlite';
    $pdo    = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt   = $pdo->query("SELECT COUNT(*) AS cnt FROM users");
    $row    = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($row['cnt']);
}
