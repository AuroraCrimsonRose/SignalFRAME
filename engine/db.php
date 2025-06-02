<?php
// /engine/db.php

function getDbConnection() {
    $dbPath = __DIR__ . '/../config/db.sqlite';
    if (!file_exists(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0775, true);
    }
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        api_token TEXT NOT NULL,
        role TEXT DEFAULT 'admin'
    )");

    return $pdo;
}

function getUserByToken($token) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ? LIMIT 1");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
