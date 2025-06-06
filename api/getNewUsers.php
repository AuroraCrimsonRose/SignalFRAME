<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

/**
 * Returns an array of the 5 most recently created users:
 *  [ ['username'=>'...','email'=>'...','created_at'=>'...'], … ]
 */

function getNewUsers(): array
{
    $dbFile = __DIR__ . '/../config/users.sqlite';
    $pdo    = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT username, email, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT 5
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
