<?php
/**
 * SignalFrame by CATALYSTS LABS
 * Copyright © 2025 CATALYSTS LABS
 * Licensed under LICENSE.txt / LICENSE_COMMERCIAL.txt
 */

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
